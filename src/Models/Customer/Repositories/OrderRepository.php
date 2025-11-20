<?php
namespace App\Models\Customer\Repositories;

use App\Core\DB;
use PDO;

class OrderRepository
{
    /**
     * Lấy danh sách đơn hàng của khách hàng
     */
    public function getOrders(int $customerId): array
    {
        $sql = "SELECT 
                o.id,
                o.code,
                o.status,
                o.grand_total,
                o.loyalty_points_earned,
                o.created_at,
                o.note,
                o.payment_method,
                o.payment_status,
                o.order_type,
                ua.receiver_name,
                ua.receiver_phone,
                ua.line1 AS delivery_address,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
            FROM orders o
            LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC, o.id DESC";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$customerId]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data
        foreach ($orders as &$order) {
            $order['status_label'] = $this->getStatusLabel($order['status']);
            $order['payment_status_label'] = $this->getPaymentStatusLabel($order['payment_status']);
            $order['payment_method_label'] = $this->getPaymentMethodLabel($order['payment_method']);
        }

        return $orders;
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderById(int $orderId, int $customerId): ?array
    {
        $sql = "SELECT 
                o.id,
                o.code,
                o.status,
                o.grand_total,
                o.subtotal,
                o.discount_amount,
                o.tax_amount,
                o.shipping_fee,
                o.loyalty_points_earned,
                o.loyalty_points_used,
                o.created_at,
                o.note,
                o.payment_method,
                o.payment_status,
                o.order_type,
                ua.receiver_name,
                ua.receiver_phone,
                ua.line1 AS delivery_address,
                ua.line2,
                ua.city,
                ua.district,
                ua.ward
            FROM orders o
            LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
            WHERE o.id = ? AND o.user_id = ?";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$orderId, $customerId]);

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Format labels
        $order['status_label'] = $this->getStatusLabel($order['status']);
        $order['payment_status_label'] = $this->getPaymentStatusLabel($order['payment_status']);
        $order['payment_method_label'] = $this->getPaymentMethodLabel($order['payment_method']);

        // Lấy items
        $order['items'] = $this->getOrderItems($orderId);

        return $order;
    }

    /**
     * Lấy chi tiết đơn hàng đầy đủ (bao gồm thông tin staff)
     */
    public function getOrderDetail(int $orderId, int $customerId): ?array
    {
        $sql = "SELECT 
                o.id,
                o.code,
                o.status,
                o.grand_total,
                o.subtotal,
                o.discount_total AS discount_amount,
                o.shipping_fee,
                o.loyalty_points_earned,
                o.loyalty_points_used,
                o.created_at,
                o.note,
                o.payment_method,
                o.payment_status,
                o.order_type,
                o.created_by,
                s.full_name AS staff_name,
                ua.receiver_name,
                ua.receiver_phone,
                ua.line1 AS delivery_address,
                ua.commune_code,
                ua.province_code
            FROM orders o
            LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
            LEFT JOIN users s ON s.id = o.created_by
            WHERE o.id = ? AND o.user_id = ?";

        try {
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute([$orderId, $customerId]);

            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return null;
            }

            // Ensure required fields have default values
            $order['status'] = $order['status'] ?? 'pending';
            $order['payment_status'] = $order['payment_status'] ?? 'pending';
            $order['payment_method'] = $order['payment_method'] ?? 'cod';
            $order['subtotal'] = $order['subtotal'] ?? 0;
            $order['discount_amount'] = $order['discount_amount'] ?? 0;
            $order['grand_total'] = $order['grand_total'] ?? 0;

            // Format labels
            $order['status_label'] = $this->getStatusLabel($order['status']);
            $order['payment_status_label'] = $this->getPaymentStatusLabel($order['payment_status']);
            $order['payment_method_label'] = $this->getPaymentMethodLabel($order['payment_method']);

            // Lấy items với SKU và ảnh
            $itemsSql = "SELECT 
                    oi.id,
                    oi.product_id,
                    oi.qty AS quantity,
                    oi.unit_price,
                    oi.discount,
                    oi.tax,
                    oi.line_total AS total,
                    oi.is_gift,
                    p.name AS product_name,
                    p.sku AS product_sku,
                    pi.image_url AS product_image
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";

            $itemsStmt = DB::pdo()->prepare($itemsSql);
            $itemsStmt->execute([$orderId]);
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (\PDOException $e) {
            error_log("OrderRepository::getOrderDetail SQL Error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: orderId=$orderId, customerId=$customerId");
            throw $e;
        }
    }

    /**
     * Lấy danh sách items của đơn hàng
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT 
                oi.id,
                oi.product_id,
                oi.qty AS quantity,
                oi.unit_price,
                oi.discount,
                oi.tax,
                oi.line_total AS total,
                oi.is_gift,
                p.name AS product_name,
                p.sku,
                p.image_url AS product_image
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số đơn hàng của khách hàng
     */
    public function countOrders(int $customerId): int
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$customerId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Lấy nhãn trạng thái đơn hàng
     */
    private function getStatusLabel(string $status): string
    {
        // Handle both Vietnamese (database) and English values
        $labels = [
            // Vietnamese (from database enum)
            'Chờ xử lý' => 'Chờ xử lý',
            'Đang xử lý' => 'Đang xử lý',
            'Hoàn tất' => 'Hoàn tất',
            'Đã hủy' => 'Đã hủy',
            // English (for compatibility)
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Lấy nhãn trạng thái thanh toán
     */
    private function getPaymentStatusLabel(string $status): string
    {
        // Handle both Vietnamese (database) and English values
        $labels = [
            // Vietnamese (from database enum)
            'Chưa thanh toán' => 'Chưa thanh toán',
            'Đã thanh toán' => 'Đã thanh toán',
            'Hoàn tiền' => 'Hoàn tiền',
            // English (for compatibility)
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại',
            'refunded' => 'Đã hoàn tiền'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Lấy nhãn phương thức thanh toán
     */
    private function getPaymentMethodLabel(string $method): string
    {
        // Handle both Vietnamese (database) and English values
        $labels = [
            // Vietnamese (from database enum)
            'Tiền mặt' => 'Tiền mặt',
            'Chuyển khoản' => 'Chuyển khoản',
            'Quẹt thẻ' => 'Quẹt thẻ',
            'PayPal' => 'PayPal',
            'Thanh toán khi nhận hàng (COD)' => 'Thanh toán khi nhận hàng',
            // English (for compatibility)
            'cod' => 'Thanh toán khi nhận hàng',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'vnpay' => 'VNPay'
        ];

        return $labels[$method] ?? $method;
    }
}
