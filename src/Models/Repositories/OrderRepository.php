<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Order;

class OrderRepository
{
    /**
     * Lấy toàn bộ danh sách đơn hàng
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT o.id, o.code, o.user_id AS customer_id, o.order_type, o.status,
                o.subtotal, 
                o.discount_total AS discount_amount, 
                o.shipping_fee, 
                o.cod_amount, 
                o.grand_total AS total_amount,
                o.coupon_code,
                o.shipping_address_id,
                o.note,
                o.created_at, o.updated_at,
                o.created_by, cu.full_name AS created_by_name,
                o.updated_by, uu.full_name AS updated_by_name,
                u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                p.id AS payment_id, p.method AS payment_method, 
                o.payment_status
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            ORDER BY o.id DESC
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Order($row), $rows);
    }

    /**
     * Tìm đơn hàng theo ID
     */
    public function findOne(int $id): ?Order
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT o.id, o.code, o.user_id AS customer_id, o.order_type, o.status,
                o.subtotal, 
                o.discount_total AS discount_amount, 
                o.shipping_fee, 
                o.cod_amount, 
                o.grand_total AS total_amount,
                o.coupon_code,
                o.shipping_address_id,
                o.note,
                o.created_at, o.updated_at,
                o.created_by, cu.full_name AS created_by_name,
                o.updated_by, uu.full_name AS updated_by_name,
                u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                p.id AS payment_id, p.method AS payment_method, 
                o.payment_status
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            WHERE o.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Order($row) : null;
    }

    /**
     * Tạo mã đơn hàng tự động
     */
    public function generateCode(): string
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM orders");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'DH' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo đơn hàng mới
     */
    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Tạo payment nếu cần — giữ logic cũ (bạn có thể thay giá trị status phù hợp)
            $paymentId = null;
            if (!empty($data['payment_method'])) {
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (method, created_at)
                    VALUES (:method, NOW())
                ");
                $stmtPay->execute([
                    ':method' => $data['payment_method'],
                ]);
                $paymentId = (int) $pdo->lastInsertId();
            }

            // Tạo đơn hàng — dùng tên cột theo schema
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (code, user_id, order_type, status, payment_id, payment_method, payment_status,
                subtotal, discount_total, shipping_fee, cod_amount, grand_total,
                coupon_code,
                shipping_address_id, note,
                created_by, updated_by, created_at, updated_at)
                VALUES
                (:code, :user_id, :order_type, :status, :payment_id, :payment_method, :payment_status,
                :subtotal, :discount_total, :shipping_fee, :cod_amount, :grand_total,
                :coupon_code,
                :shipping_address_id, :note,
                :created_by, :updated_by, NOW(), NOW())
            ");
            // Xử lý customer_id - cho phép null (khách vãng lai)
            $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
            
            // Map payment method
            $paymentMethodMap = [
                'cash' => 'Tiền mặt',
                'credit_card' => 'Quẹt thẻ',
                'bank_transfer' => 'Chuyển khoản'
            ];
            $paymentMethod = $paymentMethodMap[$data['payment_method'] ?? 'cash'] ?? 'Tiền mặt';
            
            $stmt->execute([
                ':code' => $data['code'],
                ':user_id' => $customerId,
                ':order_type' => 'Offline',
                ':status' => 'Hoàn tất',
                ':payment_id' => $paymentId,
                ':payment_method' => $paymentMethod,
                ':payment_status' => 'Đã thanh toán',
                ':subtotal' => $data['subtotal'] ?? 0,
                ':discount_total' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => 0,
                ':cod_amount' => 0,
                ':grand_total' => $data['total_amount'] ?? 0,
                ':coupon_code' => $data['coupon_code'] ?? null,
                ':shipping_address_id' => null,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

            // Lưu order items và trừ tồn kho
            if (!empty($data['items']) && is_array($data['items'])) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, qty, unit_price, discount, tax, line_total)
                    VALUES (:order_id, :product_id, :qty, :unit_price, :discount, :tax, :line_total)
                ");
                
                $stmtUpdateStock = $pdo->prepare("
                    UPDATE products SET stock = stock - :qty WHERE id = :product_id
                ");
                
                foreach ($data['items'] as $item) {
                    $qty = (int) ($item['qty'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $discount = (float) ($item['discount'] ?? 0);
                    $tax = (float) ($item['tax'] ?? 0);
                    $lineTotal = ($qty * $unitPrice) - $discount + $tax;
                    
                    $stmtItem->execute([
                        ':order_id' => $id,
                        ':product_id' => $item['product_id'],
                        ':qty' => $qty,
                        ':unit_price' => $unitPrice,
                        ':discount' => $discount,
                        ':tax' => $tax,
                        ':line_total' => $lineTotal,
                    ]);
                    
                    // Trừ tồn kho
                    $stmtUpdateStock->execute([
                        ':qty' => $qty,
                        ':product_id' => $item['product_id']
                    ]);
                }
            }

            // Tự động tạo phiếu thu
            $receiptCode = $this->generateReceiptCode($pdo);
            $stmtReceipt = $pdo->prepare("
                INSERT INTO receipt_vouchers
                (code, customer_id, order_id, method, txn_ref, received_at, amount, received_by, note, bank_time, is_active, created_by, updated_by, created_at, updated_at)
                VALUES
                (:code, :customer_id, :order_id, :method, :txn_ref, NOW(), :amount, :received_by, :note, NULL, 1, :created_by, :updated_by, NOW(), NOW())
            ");
            $stmtReceipt->execute([
                ':code' => $receiptCode,
                ':customer_id' => $customerId,
                ':order_id' => $id,
                ':method' => $paymentMethod,
                ':txn_ref' => $data['code'],
                ':amount' => $data['total_amount'] ?? 0,
                ':received_by' => $currentUser,
                ':note' => 'Phiếu thu tự động từ đơn hàng ' . $data['code'],
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            // Tự động tạo phiếu xuất kho
            $stockOutCode = $this->generateStockOutCode($pdo);
            $stmtStockOut = $pdo->prepare("
                INSERT INTO stock_outs
                (code, type, order_id, status, out_date, total_amount, note, created_by, updated_by, created_at, updated_at)
                VALUES
                (:code, 'sale', :order_id, 'completed', NOW(), :total_amount, :note, :created_by, :updated_by, NOW(), NOW())
            ");
            $stmtStockOut->execute([
                ':code' => $stockOutCode,
                ':order_id' => $id,
                ':total_amount' => $data['total_amount'] ?? 0,
                ':note' => 'Phiếu xuất kho tự động từ đơn hàng ' . $data['code'],
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $pdo->commit();
            return $id;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật đơn hàng
     */
    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Cập nhật payment nếu có
            if (isset($data['payment_id']) && $data['payment_id']) {
                $stmtPay = $pdo->prepare("
                    UPDATE payments SET 
                        method = :method
                    WHERE id = :id
                ");
                $stmtPay->execute([
                    ':id' => $data['payment_id'],
                    ':method' => $data['payment_method'] ?? null,
                ]);
            }

            // Cập nhật đơn hàng (tên cột theo schema)
            $stmt = $pdo->prepare("
                UPDATE orders SET 
                    user_id = :user_id,
                    order_type = :order_type,
                    status = :status,
                    payment_method = :payment_method,
                    payment_status = :payment_status,
                    subtotal = :subtotal,
                    discount_total = :discount_total,
                    shipping_fee = :shipping_fee,
                    cod_amount = :cod_amount,
                    grand_total = :grand_total,
                    coupon_code = :coupon_code,
                    shipping_address_id = :shipping_address_id,
                    note = :note,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            // Xử lý customer_id - cho phép null (khách vãng lai)
            $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
            
            // Map payment method
            $paymentMethodMap = [
                'cash' => 'Tiền mặt',
                'credit_card' => 'Quẹt thẻ',
                'bank_transfer' => 'Chuyển khoản'
            ];
            $paymentMethod = $paymentMethodMap[$data['payment_method'] ?? 'cash'] ?? 'Tiền mặt';
            
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $customerId,
                ':order_type' => 'Offline',
                ':status' => 'Hoàn tất',
                ':payment_method' => $paymentMethod,
                ':payment_status' => 'Đã thanh toán',
                ':subtotal' => $data['subtotal'] ?? 0,
                ':discount_total' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => 0,
                ':cod_amount' => 0,
                ':grand_total' => $data['total_amount'] ?? 0,
                ':coupon_code' => $data['coupon_code'] ?? null,
                ':shipping_address_id' => null,
                ':note' => $data['note'] ?? null,
                ':updated_by' => $currentUser,
            ]);

            // Cập nhật order items - hoàn lại tồn kho cũ, xóa items cũ, thêm items mới và trừ tồn kho mới
            if (isset($data['items']) && is_array($data['items'])) {
                // Lấy các items cũ để hoàn lại tồn kho
                $stmtOldItems = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
                $stmtOldItems->execute([$id]);
                $oldItems = $stmtOldItems->fetchAll(\PDO::FETCH_ASSOC);
                
                // Hoàn lại tồn kho cho các sản phẩm cũ
                $stmtRestoreStock = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :product_id");
                foreach ($oldItems as $oldItem) {
                    $stmtRestoreStock->execute([
                        ':qty' => $oldItem['qty'],
                        ':product_id' => $oldItem['product_id']
                    ]);
                }
                
                // Xóa các items cũ
                $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
                
                // Thêm các items mới và trừ tồn kho
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, qty, unit_price, discount, tax, line_total)
                    VALUES (:order_id, :product_id, :qty, :unit_price, :discount, :tax, :line_total)
                ");
                
                $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :product_id");
                
                foreach ($data['items'] as $item) {
                    $qty = (int) ($item['qty'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $discount = (float) ($item['discount'] ?? 0);
                    $tax = (float) ($item['tax'] ?? 0);
                    $lineTotal = ($qty * $unitPrice) - $discount + $tax;
                    
                    $stmtItem->execute([
                        ':order_id' => $id,
                        ':product_id' => $item['product_id'],
                        ':qty' => $qty,
                        ':unit_price' => $unitPrice,
                        ':discount' => $discount,
                        ':tax' => $tax,
                        ':line_total' => $lineTotal,
                    ]);
                    
                    // Trừ tồn kho mới
                    $stmtUpdateStock->execute([
                        ':qty' => $qty,
                        ':product_id' => $item['product_id']
                    ]);
                }
                
                // Cập nhật phiếu thu nếu có
                $stmtUpdateReceipt = $pdo->prepare("
                    UPDATE receipt_vouchers 
                    SET customer_id = :customer_id, 
                        method = :method, 
                        amount = :amount,
                        note = :note,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE order_id = :order_id
                ");
                $stmtUpdateReceipt->execute([
                    ':customer_id' => $customerId,
                    ':method' => $paymentMethod,
                    ':amount' => $data['total_amount'] ?? 0,
                    ':note' => 'Phiếu thu từ đơn hàng ' . ($data['code'] ?? ''),
                    ':updated_by' => $currentUser,
                    ':order_id' => $id
                ]);
                
                // Cập nhật phiếu xuất kho nếu có
                $stmtUpdateStockOut = $pdo->prepare("
                    UPDATE stock_outs 
                    SET total_amount = :total_amount,
                        note = :note,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE order_id = :order_id
                ");
                $stmtUpdateStockOut->execute([
                    ':total_amount' => $data['total_amount'] ?? 0,
                    ':note' => 'Phiếu xuất kho từ đơn hàng ' . ($data['code'] ?? ''),
                    ':updated_by' => $currentUser,
                    ':order_id' => $id
                ]);
            }

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa đơn hàng
     */
    public function delete(int $id): void
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Lấy payment_id trước khi xóa
            $stmt = $pdo->prepare("SELECT payment_id FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $paymentId = $stmt->fetchColumn();

            // Lấy các items để hoàn lại tồn kho
            $stmtItems = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);
            
            // Hoàn lại tồn kho
            $stmtRestoreStock = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :product_id");
            foreach ($items as $item) {
                $stmtRestoreStock->execute([
                    ':qty' => $item['qty'],
                    ':product_id' => $item['product_id']
                ]);
            }

            // Xóa phiếu thu liên quan
            $pdo->prepare("DELETE FROM receipt_vouchers WHERE order_id = ?")->execute([$id]);
            
            // Xóa phiếu xuất kho liên quan
            $pdo->prepare("DELETE FROM stock_outs WHERE order_id = ?")->execute([$id]);

            // Xóa order items
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);

            // Xóa đơn hàng
            $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);

            // Xóa payment nếu có
            if ($paymentId) {
                $pdo->prepare("DELETE FROM payments WHERE id = ?")->execute([$paymentId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lấy danh sách đơn hàng chưa thanh toán
     */
    public function unpaid(): array
    {
            $pdo = DB::pdo();
            $sql = "
            SELECT o.id, o.code, o.user_id, u.full_name AS customer_name, o.grand_total
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN payments p ON p.id = o.payment_id
            WHERE (o.payment_status IS NULL) OR (o.payment_status != 'Đã thanh toán')
            ORDER BY o.id DESC
        ";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function getOrderItems(int $orderId): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT oi.*, p.name as product_name, p.sku as product_sku
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Tạo mã phiếu thu tự động
     */
    private function generateReceiptCode($pdo): string
    {
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM receipt_vouchers");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'PT-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo mã phiếu xuất kho tự động
     */
    private function generateStockOutCode($pdo): string
    {
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM stock_outs");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'XK' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

}
