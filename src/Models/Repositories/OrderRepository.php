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
            SELECT o.id, o.code, o.customer_id, o.status, 
                   o.subtotal, o.discount_amount, o.shipping_fee, o.tax_amount, o.total_amount,
                   o.shipping_address, o.province_code, o.commune_code, o.note,
                   o.created_at, o.updated_at,
                   o.created_by, cu.full_name AS created_by_name,
                   o.updated_by, uu.full_name AS updated_by_name,
                   u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                   p.id AS payment_id, p.method AS payment_method, p.status AS payment_status,
                   pr.name AS province_name, co.name AS commune_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.customer_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            LEFT JOIN provinces pr ON pr.code = o.province_code
            LEFT JOIN communes co ON co.code = o.commune_code
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
            SELECT o.id, o.code, o.customer_id, o.status, 
                   o.subtotal, o.discount_amount, o.shipping_fee, o.tax_amount, o.total_amount,
                   o.shipping_address, o.province_code, o.commune_code, o.note,
                   o.created_at, o.updated_at,
                   o.created_by, cu.full_name AS created_by_name,
                   o.updated_by, uu.full_name AS updated_by_name,
                   u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                   p.id AS payment_id, p.method AS payment_method, p.status AS payment_status,
                   pr.name AS province_name, co.name AS commune_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.customer_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            LEFT JOIN provinces pr ON pr.code = o.province_code
            LEFT JOIN communes co ON co.code = o.commune_code
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

            // Tạo payment nếu có thông tin thanh toán
            $paymentId = null;
            if (!empty($data['payment_method'])) {
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (method, status, created_at)
                    VALUES (:method, :status, NOW())
                ");
                $stmtPay->execute([
                    ':method' => $data['payment_method'],
                    ':status' => $data['payment_status'] ?? 'pending'
                ]);
                $paymentId = (int) $pdo->lastInsertId();
            }

            // Tạo đơn hàng
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (code, customer_id, status, payment_id, 
                 subtotal, discount_amount, shipping_fee, tax_amount, total_amount,
                 shipping_address, province_code, commune_code, note,
                 created_by, updated_by, created_at, updated_at)
                VALUES
                (:code, :customer_id, :status, :payment_id,
                 :subtotal, :discount_amount, :shipping_fee, :tax_amount, :total_amount,
                 :shipping_address, :province_code, :commune_code, :note,
                 :created_by, :updated_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':code' => $data['code'],
                ':customer_id' => $data['customer_id'] ?: null,
                ':status' => $data['status'] ?? 'pending',
                ':payment_id' => $paymentId,
                ':subtotal' => $data['subtotal'] ?? 0,
                ':discount_amount' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => $data['shipping_fee'] ?? 0,
                ':tax_amount' => $data['tax_amount'] ?? 0,
                ':total_amount' => $data['total_amount'] ?? 0,
                ':shipping_address' => $data['shipping_address'] ?? null,
                ':province_code' => $data['province_code'] ?? null,
                ':commune_code' => $data['commune_code'] ?? null,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

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
                        method = :method,
                        status = :status
                    WHERE id = :id
                ");
                $stmtPay->execute([
                    ':id' => $data['payment_id'],
                    ':method' => $data['payment_method'] ?? null,
                    ':status' => $data['payment_status'] ?? 'pending'
                ]);
            }

            // Cập nhật đơn hàng
            $stmt = $pdo->prepare("
                UPDATE orders SET 
                    customer_id = :customer_id,
                    status = :status,
                    subtotal = :subtotal,
                    discount_amount = :discount_amount,
                    shipping_fee = :shipping_fee,
                    tax_amount = :tax_amount,
                    total_amount = :total_amount,
                    shipping_address = :shipping_address,
                    province_code = :province_code,
                    commune_code = :commune_code,
                    note = :note,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'] ?: null,
                ':status' => $data['status'] ?? 'pending',
                ':subtotal' => $data['subtotal'] ?? 0,
                ':discount_amount' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => $data['shipping_fee'] ?? 0,
                ':tax_amount' => $data['tax_amount'] ?? 0,
                ':total_amount' => $data['total_amount'] ?? 0,
                ':shipping_address' => $data['shipping_address'] ?? null,
                ':province_code' => $data['province_code'] ?? null,
                ':commune_code' => $data['commune_code'] ?? null,
                ':note' => $data['note'] ?? null,
                ':updated_by' => $currentUser,
            ]);

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
            SELECT o.id, o.code, o.customer_id, u.full_name AS customer_name, o.total_amount
            FROM orders o
            LEFT JOIN users u ON u.id = o.customer_id
            LEFT JOIN payments p ON p.id = o.payment_id
            WHERE p.status != 'paid' OR p.status IS NULL
            ORDER BY o.id DESC
        ";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
