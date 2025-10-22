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
        $items = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        // Convert discount_type từ tiếng Việt sang tiếng Anh cho frontend
        foreach ($items as &$item) {
            $item['description'] = $item['name'];  // Thêm field description
            $item['discount_type'] = ($item['discount_type'] === 'Phần trăm') ? 'percentage' : 'fixed';
        }
        
        return $items;
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
        
        if ($row) {
            // Convert discount_type từ tiếng Việt sang tiếng Anh cho frontend
            $row['description'] = $row['name'];
            $row['discount_type'] = ($row['discount_type'] === 'Phần trăm') ? 'percentage' : 'fixed';
        }
        
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
        
        // Convert dates
        $startsAt = $this->convertDate($data['starts_at'] ?? null);
        $endsAt = $this->convertDate($data['ends_at'] ?? null);
        
        // Convert discount_type từ tiếng Anh sang tiếng Việt (khớp với ENUM trong database)
        $discountTypeMap = [
            'percentage' => 'Phần trăm',
            'fixed' => 'Số tiền'
        ];
        $discountType = $discountTypeMap[$data['discount_type'] ?? 'percentage'] ?? 'Phần trăm';
        
        $stmt = $pdo->prepare("
            INSERT INTO coupons (
                code, name, discount_type, discount_value,
                min_order_value, max_discount, max_uses, used_count,
                starts_at, ends_at, is_active,
                created_by, updated_by, created_at, updated_at
            ) VALUES (
                :code, :name, :discount_type, :discount_value,
                :min_order_value, :max_discount, :max_uses, 0,
                :starts_at, :ends_at, :is_active,
                :created_by, :updated_by, NOW(), NOW()
            )
        ");

        $params = [
            ':code' => strtoupper($data['code']),
            ':name' => $data['description'] ?? null,
            ':discount_type' => $discountType,
            ':discount_value' => $data['discount_value'] ?? 0,
            ':min_order_value' => $data['min_order_value'] ?? 0,
            ':max_discount' => $data['max_discount'] ?? 0,  // Thêm max_discount
            ':max_uses' => !empty($data['max_uses']) ? $data['max_uses'] : 0,
            ':starts_at' => $startsAt,
            ':ends_at' => $endsAt,
            ':is_active' => $data['is_active'] ?? 1,
            ':created_by' => $currentUser,
            ':updated_by' => $currentUser,
        ];
        
        $stmt->execute($params);

        $id = (int) $pdo->lastInsertId();
        
        // Log audit (with error handling)
        try {
            $this->logCreate('coupons', $id, $data, $currentUser);
        } catch (\Exception $e) {
            // Silent fail - không để lỗi audit log chặn việc tạo coupon
            error_log("Audit log failed: " . $e->getMessage());
        }
        
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
        
        // Convert discount_type từ tiếng Anh sang tiếng Việt
        $discountTypeMap = [
            'percentage' => 'Phần trăm',
            'fixed' => 'Số tiền'
        ];
        $discountType = $discountTypeMap[$data['discount_type'] ?? 'percentage'] ?? 'Phần trăm';
        
        $stmt = $pdo->prepare("
            UPDATE coupons SET
                code = :code,
                name = :name,
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
            ':name' => $data['description'] ?? null,
            ':discount_type' => $discountType,
            ':discount_value' => $data['discount_value'] ?? 0,
            ':min_order_value' => $data['min_order_value'] ?? 0,
            ':max_discount' => $data['max_discount'] ?? 0,  // Thêm max_discount
            ':max_uses' => !empty($data['max_uses']) ? $data['max_uses'] : 0,
            ':starts_at' => $this->convertDate($data['starts_at'] ?? null),
            ':ends_at' => $this->convertDate($data['ends_at'] ?? null),
            ':is_active' => $data['is_active'] ?? 1,
            ':updated_by' => $currentUser,
            ':id' => $id,
        ]);
        
        // Log audit (with error handling)
        if ($beforeData) {
            try {
                $this->logUpdate('coupons', $id, $beforeData, $data, $currentUser);
            } catch (\Exception $e) {
                error_log("Audit log failed: " . $e->getMessage());
            }
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
        
        // Log audit (with error handling)
        if ($beforeData) {
            try {
                $this->logDelete('coupons', $id, $beforeData, $currentUser);
            } catch (\Exception $e) {
                error_log("Audit log failed: " . $e->getMessage());
            }
        }
    }
}
