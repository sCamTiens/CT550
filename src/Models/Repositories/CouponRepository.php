<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;

class CouponRepository
{
    use Auditable;
    /**
     * Lấy toàn bộ danh sách mã giảm giá
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT c.*, 
                cu.full_name AS created_by_name,
                uu.full_name AS updated_by_name
            FROM coupons c
            LEFT JOIN users cu ON cu.id = c.created_by
            LEFT JOIN users uu ON uu.id = c.updated_by
            ORDER BY c.id DESC
        ";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Tìm mã giảm giá theo ID
     */
    public function findOne(int $id): ?array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT c.*, 
                cu.full_name AS created_by_name,
                uu.full_name AS updated_by_name
            FROM coupons c
            LEFT JOIN users cu ON cu.id = c.created_by
            LEFT JOIN users uu ON uu.id = c.updated_by
            WHERE c.id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Chuyển đổi ngày từ d/m/Y sang Y-m-d
     */
    private function convertDate(?string $date): ?string
    {
        if (!$date) return null;
        
        // Nếu đã đúng định dạng Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) {
            return substr($date, 0, 10);
        }
        
        // Nếu là định dạng d/m/Y
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        
        return null;
    }

    /**
     * Tạo mã giảm giá mới
     */
    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO coupons (
                code, description, discount_type, discount_value,
                min_order_value, max_discount, max_uses, used_count,
                starts_at, ends_at, is_active,
                created_by, updated_by, created_at, updated_at
            ) VALUES (
                :code, :description, :discount_type, :discount_value,
                :min_order_value, :max_discount, :max_uses, 0,
                :starts_at, :ends_at, :is_active,
                :created_by, :updated_by, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':code' => strtoupper($data['code']),
            ':description' => $data['description'] ?? null,
            ':discount_type' => $data['discount_type'] ?? 'percentage',
            ':discount_value' => $data['discount_value'] ?? 0,
            ':min_order_value' => $data['min_order_value'] ?? 0,
            ':max_discount' => $data['max_discount'] ?? 0,
            ':max_uses' => !empty($data['max_uses']) ? $data['max_uses'] : null,
            ':starts_at' => $this->convertDate($data['starts_at'] ?? null),
            ':ends_at' => $this->convertDate($data['ends_at'] ?? null),
            ':is_active' => $data['is_active'] ?? 1,
            ':created_by' => $currentUser,
            ':updated_by' => $currentUser,
        ]);

        $id = (int) $pdo->lastInsertId();
        
        // Log audit
        $this->logCreate('coupons', $id, $data, $currentUser);
        
        return $id;
    }

    /**
     * Cập nhật mã giảm giá
     */
    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        
        // Lấy dữ liệu trước khi update
        $beforeData = $this->findOne($id);
        
        $stmt = $pdo->prepare("
            UPDATE coupons SET
                code = :code,
                description = :description,
                discount_type = :discount_type,
                discount_value = :discount_value,
                min_order_value = :min_order_value,
                max_discount = :max_discount,
                max_uses = :max_uses,
                starts_at = :starts_at,
                ends_at = :ends_at,
                is_active = :is_active,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':code' => strtoupper($data['code']),
            ':description' => $data['description'] ?? null,
            ':discount_type' => $data['discount_type'] ?? 'percentage',
            ':discount_value' => $data['discount_value'] ?? 0,
            ':min_order_value' => $data['min_order_value'] ?? 0,
            ':max_discount' => $data['max_discount'] ?? 0,
            ':max_uses' => !empty($data['max_uses']) ? $data['max_uses'] : null,
            ':starts_at' => $this->convertDate($data['starts_at'] ?? null),
            ':ends_at' => $this->convertDate($data['ends_at'] ?? null),
            ':is_active' => $data['is_active'] ?? 1,
            ':updated_by' => $currentUser,
            ':id' => $id,
        ]);
        
        // Log audit
        if ($beforeData) {
            $this->logUpdate('coupons', $id, $beforeData, $data, $currentUser);
        }
    }

    /**
     * Xóa mã giảm giá
     */
    public function delete(int $id, ?int $currentUser = null): void
    {
        $pdo = DB::pdo();
        
        // Lấy dữ liệu trước khi xóa
        $beforeData = $this->findOne($id);
        
        // Kiểm tra xem mã đã được sử dụng chưa
        $stmt = $pdo->prepare("SELECT used_count FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $usedCount = $stmt->fetchColumn();

        if ($usedCount > 0) {
            throw new \Exception('Không thể xóa mã giảm giá đã được sử dụng');
        }

        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
        
        // Log audit
        if ($beforeData) {
            $this->logDelete('coupons', $id, $beforeData, $currentUser);
        }
    }
}
