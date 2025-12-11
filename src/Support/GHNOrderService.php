<?php

namespace App\Support;

use App\Core\DB;

/**
 * GHN Order Processing Service
 * Handles order processing and shipping with GHN
 */
class GHNOrderService
{
    private GHNService $ghn;
    private \PDO $pdo;

    public function __construct()
    {
        $this->ghn = new GHNService();
        $this->pdo = DB::pdo();
    }

    /**
     * Process order: Change status from "Chờ xử lý" to "Đang xử lý"
     */
    public function processOrder(int $orderId, int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \RuntimeException('Đơn hàng không tồn tại');
        }

        if ($order['status'] !== 'Chờ xử lý') {
            throw new \RuntimeException('Chỉ có thể xử lý đơn hàng ở trạng thái "Chờ xử lý"');
        }

        // Update status
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'Đang xử lý',
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$userId, $orderId]);

        // Log status change
        $this->logStatusChange($orderId, 'Chờ xử lý', 'Đang xử lý', 'Admin bắt đầu xử lý đơn');

        return ['success' => true, 'message' => 'Đơn hàng đã chuyển sang trạng thái "Đang xử lý"'];
    }

    /**
     * Ship with GHN: Create GHN order and update status to "Đang giao hàng"
     */
    public function shipWithGHN(int $orderId, int $userId): array
    {
        // Get order details
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.full_name, u.phone, u.email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \RuntimeException('Đơn hàng không tồn tại');
        }

        if ($order['status'] !== 'Đang xử lý') {
            throw new \RuntimeException('Chỉ có thể giao hàng khi đơn đang ở trạng thái "Đang xử lý"');
        }

        if ($order['ghn_order_code']) {
            throw new \RuntimeException('Đơn hàng đã được giao cho GHN rồi');
        }

        // Get order items for weight calculation
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate total weight (assume 500g per item if not specified)
        $totalWeight = 0;
        $itemsForGHN = [];
        foreach ($items as $item) {
            $weight = 500; // Default weight in grams
            $totalWeight += $weight * ($item['qty'] ?? $item['quantity'] ?? 1);

            $itemsForGHN[] = [
                'name' => $item['product_name'],
                'quantity' => (int)($item['qty'] ?? $item['quantity'] ?? 1),
                'price' => (int)$item['unit_price'],
            ];
        }

        // Auto-fetch district_id if missing but ward_code exists
        if (empty($order['shipping_district_id']) && !empty($order['shipping_ward_code'])) {
            try {
                $districtInfo = GHNWardCache::getDistrictByWardCode(
                    $order['shipping_ward_code'],
                    $order['shipping_province_id']
                );

                if ($districtInfo) {
                    // Update order with district_id
                    $stmt = $this->pdo->prepare("
                        UPDATE orders 
                        SET shipping_district_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$districtInfo['district_id'], $orderId]);

                    // Reload order data
                    $order['shipping_district_id'] = $districtInfo['district_id'];
                }
            } catch (\Exception $e) {
                error_log("Failed to auto-fetch district_id: " . $e->getMessage());
            }
        }

        // Validate required shipping info
        $missingFields = [];
        if (empty($order['delivery_name']) && empty($order['full_name'])) {
            $missingFields[] = 'Tên người nhận';
        }
        if (empty($order['delivery_phone']) && empty($order['phone'])) {
            $missingFields[] = 'Số điện thoại';
        }
        if (empty($order['delivery_address']) && empty($order['shipping_address'])) {
            $missingFields[] = 'Địa chỉ giao hàng';
        }
        // Temporarily disabled - let GHN API validate
        /*
        if (empty($order['shipping_district_id'])) {
            $missingFields[] = 'Quận/Huyện';
        }
        */
        if (empty($order['shipping_ward_code'])) {
            $missingFields[] = 'Phường/Xã';
        }

        if (!empty($missingFields)) {
            throw new \RuntimeException(
                'Đơn hàng thiếu thông tin giao hàng: ' . implode(', ', $missingFields) .
                    '. Vui lòng cập nhật đầy đủ địa chỉ trước khi giao GHN.'
            );
        }

        // Create GHN order
        try {
            $ghnOrder = $this->ghn->createOrder([
                'to_name' => $order['delivery_name'] ?? $order['full_name'] ?? 'Khách hàng',
                'to_phone' => $order['delivery_phone'] ?? $order['phone'] ?? '0000000000',
                'to_address' => $order['delivery_address'] ?? $order['shipping_address'] ?? '',
                'to_ward_code' => $order['shipping_ward_code'] ?? '',
                'to_district_id' => (int)($order['shipping_district_id'] ?? 0),
                'weight' => max($totalWeight, 200), // Min 200g
                'cod_amount' => ($order['payment_method'] === 'COD') ? (int)$order['total_amount'] : 0,
                'content' => 'Đơn hàng #' . $order['code'],
                'note' => $order['note'] ?? '',
                'required_note' => 'KHONGCHOXEMHANG', // Mandatory field

                // Sender Info - Load from environment variables
                'from_name' => $_ENV['STORE_NAME'] ?? '',
                'from_phone' => $_ENV['STORE_PHONE'] ?? '',
                'from_address' => $_ENV['STORE_ADDRESS'] ?? '',
                'from_ward_code' => $_ENV['GHN_FROM_WARD_CODE'] ?? '',
                'from_district_id' => (int)($_ENV['GHN_FROM_DISTRICT_ID'] ?? 0),

                'items' => $itemsForGHN,
                'service_type_id' => 2, // Standard
                'payment_type_id' => 2, // Customer pays
            ]);

            // Update order with GHN info
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET status = 'Đang giao hàng',
                    ghn_order_code = ?,
                    ghn_status = 'Chờ lấy hàng',
                    expected_delivery_time = ?,
                    ghn_tracking_data = ?,
                   updated_at = NOW(),
                    updated_by = ?
                WHERE id = ?
            ");

            $trackingData = json_encode($ghnOrder);
            $expectedDelivery = $ghnOrder['expected_delivery_time'] ?? null;

            $stmt->execute([
                $ghnOrder['order_code'],
                $expectedDelivery,
                $trackingData,
                $userId,
                $orderId
            ]);

            // Log status change
            $this->logStatusChange(
                $orderId,
                'Đang xử lý',
                'Đang giao hàng',
                'Đã tạo vận đơn GHN: ' . $ghnOrder['order_code']
            );

            return [
                'success' => true,
                'message' => 'Đã tạo vận đơn GHN thành công',
                'ghn_order_code' => $ghnOrder['order_code'],
                'expected_delivery_time' => $expectedDelivery,
                'label_url' => $ghnOrder['label_url'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Lỗi khi tạo vận đơn GHN: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, int $userId, string $reason = ''): array
    {
        $stmt = $this->pdo->prepare("SELECT status, ghn_order_code FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            throw new \RuntimeException('Đơn hàng không tồn tại');
        }

        if ($order['status'] === 'Đã giao') {
            throw new \RuntimeException('Không thể hủy đơn hàng đã giao');
        }

        // If GHN order exists, cancel it
        if ($order['ghn_order_code']) {
            try {
                $this->ghn->cancelOrder($order['ghn_order_code']);
            } catch (\Exception $e) {
                // Log but continue
                error_log('Failed to cancel GHN order: ' . $e->getMessage());
            }
        }

        // Update order
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'Đã hủy',
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$userId, $orderId]);

        // Log status change
        $this->logStatusChange($orderId, $order['status'], 'Đã hủy', 'Hủy đơn: ' . $reason);

        return ['success' => true, 'message' => 'Đã hủy đơn hàng'];
    }

    /**
     * Get GHN tracking info
     */
    public function getTrackingInfo(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT ghn_order_code, status, payment_method FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order || !$order['ghn_order_code']) {
            throw new \RuntimeException('Đơn hàng chưa được giao cho GHN');
        }

        $ghnData = $this->ghn->getOrderDetail($order['ghn_order_code']);

        // Sync logic: Update local DB based on GHN status
        if (isset($ghnData['status'])) {
            $ghnStatus = $ghnData['status'];
            $newLocalStatus = null;
            $note = "Cập nhật từ GHN: $ghnStatus";

            // Map GHN status to local status
            switch ($ghnStatus) {
                case 'delivered':
                    $newLocalStatus = 'Hoàn thành';
                    break;
                case 'cancel':
                    $newLocalStatus = 'Đã hủy';
                    break;
                case 'returned':
                    $newLocalStatus = 'Đã hoàn trả';
                    break;
                    // Add more cases if needed
            }

            // Update ghn_status column
            $stmt = $this->pdo->prepare("UPDATE orders SET ghn_status = ? WHERE id = ?");
            $stmt->execute([$ghnStatus, $orderId]);

            // Update main status if mapped
            if ($newLocalStatus && $newLocalStatus !== $order['status']) {
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET status = ?, ghn_status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newLocalStatus, $ghnStatus, $orderId]);

                $this->logStatusChange($orderId, $order['status'], $newLocalStatus, $note);

                // If Delivered, maybe update payment status to Paid (if COD)?
                // Doing this safely requires business logic check
                if ($newLocalStatus === 'Hoàn thành' && $order['payment_method'] === 'COD') {
                    $stmt = $this->pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán' WHERE id = ?");
                    $stmt->execute([$orderId]);
                }
            }
        }

        return $ghnData;
    }

    /**
     * Log status change
     */
    private function logStatusChange(int $orderId, string $oldStatus, string $newStatus, string $note): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, note, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$orderId, $oldStatus, $newStatus, $note]);
    }
}
