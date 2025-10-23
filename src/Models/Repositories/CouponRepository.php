<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;
use App\Models\Entities\Coupon;
use PDO;

class CouponRepository
{
    use Auditable;

    /**
     * Lấy toàn bộ danh sách mã giảm giá
     * @return Coupon[]
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
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $coupons = [];
        foreach ($rows as $row) {
            // Convert discount_type sang chuẩn Anh - Việt
            $row['description'] = $row['name'];
            $row['discount_type'] = ($row['discount_type'] === 'Phần trăm') ? 'percentage' : 'fixed';
            $coupons[] = new Coupon($row);
        }

        return $coupons;
    }

    /**
     * Tìm mã giảm giá theo ID
     */
    public function findOne(int $id): ?Coupon
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
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['description'] = $row['name'];
        $row['discount_type'] = ($row['discount_type'] === 'Phần trăm') ? 'percentage' : 'fixed';

        return new Coupon($row);
    }

    /**
     * Chuyển đổi ngày từ d/m/Y sang Y-m-d
     */
    private function convertDate(?string $date): ?string
    {
        if (!$date) return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) {
            return substr($date, 0, 10);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        return null;
    }

    /**
     * Tạo mã giảm giá mới
     */
    public function create(Coupon $coupon, int $currentUser): int
    {
        $pdo = DB::pdo();

        $startsAt = $this->convertDate($coupon->starts_at);
        $endsAt = $this->convertDate($coupon->ends_at);

        $discountTypeMap = [
            'percentage' => 'Phần trăm',
            'fixed' => 'Số tiền'
        ];
        $discountType = $discountTypeMap[$coupon->discount_type ?? 'percentage'] ?? 'Phần trăm';

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

        $stmt->execute([
            ':code' => strtoupper($coupon->code),
            ':name' => $coupon->description,
            ':discount_type' => $discountType,
            ':discount_value' => $coupon->discount_value ?? 0,
            ':min_order_value' => $coupon->min_order_value ?? 0,
            ':max_discount' => $coupon->max_discount ?? 0,
            ':max_uses' => $coupon->max_uses ?? 0,
            ':starts_at' => $startsAt,
            ':ends_at' => $endsAt,
            ':is_active' => $coupon->is_active ?? 1,
            ':created_by' => $currentUser,
            ':updated_by' => $currentUser,
        ]);

        $id = (int) $pdo->lastInsertId();

        try {
            $this->logCreate('coupons', $id, (array)$coupon, $currentUser);
        } catch (\Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }

        return $id;
    }

    /**
     * Cập nhật mã giảm giá
     */
    public function update(int $id, Coupon $coupon, int $currentUser): void
    {
        $pdo = DB::pdo();
        $beforeData = $this->findOne($id);

        $discountTypeMap = [
            'percentage' => 'Phần trăm',
            'fixed' => 'Số tiền'
        ];
        $discountType = $discountTypeMap[$coupon->discount_type ?? 'percentage'] ?? 'Phần trăm';

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
            ':code' => strtoupper($coupon->code),
            ':name' => $coupon->description,
            ':discount_type' => $discountType,
            ':discount_value' => $coupon->discount_value ?? 0,
            ':min_order_value' => $coupon->min_order_value ?? 0,
            ':max_discount' => $coupon->max_discount ?? 0,
            ':max_uses' => $coupon->max_uses ?? 0,
            ':starts_at' => $this->convertDate($coupon->starts_at),
            ':ends_at' => $this->convertDate($coupon->ends_at),
            ':is_active' => $coupon->is_active ?? 1,
            ':updated_by' => $currentUser,
            ':id' => $id,
        ]);

        if ($beforeData) {
            try {
                $this->logUpdate('coupons', $id, (array)$beforeData, (array)$coupon, $currentUser);
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

        $beforeData = $this->findOne($id);
        $stmt = $pdo->prepare("SELECT used_count FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $usedCount = $stmt->fetchColumn();

        if ($usedCount > 0) {
            throw new \Exception('Không thể xóa mã giảm giá đã được sử dụng');
        }

        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);

        if ($beforeData) {
            try {
                $this->logDelete('coupons', $id, (array)$beforeData, $currentUser);
            } catch (\Exception $e) {
                error_log("Audit log failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Validate mã giảm giá khi áp dụng vào đơn hàng
     */
    public function validateCoupon(string $code, float $orderAmount): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            SELECT * FROM coupons WHERE UPPER(code) = UPPER(:code)
        ");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new \Exception('Mã giảm giá không tồn tại');
        $coupon = new Coupon($row);

        if (!$coupon->is_active) throw new \Exception('Mã giảm giá không còn hiệu lực');

        $now = date('Y-m-d');
        $startsAt = $coupon->starts_at ? date('Y-m-d', strtotime($coupon->starts_at)) : null;
        $endsAt = $coupon->ends_at ? date('Y-m-d', strtotime($coupon->ends_at)) : null;

        if ($startsAt && $now < $startsAt)
            throw new \Exception('Mã giảm giá chưa đến ngày áp dụng');
        if ($endsAt && $now > $endsAt)
            throw new \Exception('Mã giảm giá đã hết hạn');
        if ($coupon->max_uses > 0 && $coupon->used_count >= $coupon->max_uses)
            throw new \Exception('Mã giảm giá đã hết lượt sử dụng');
        if ($coupon->min_order_value > 0 && $orderAmount < $coupon->min_order_value) {
            $minFormatted = number_format($coupon->min_order_value, 0, ',', '.');
            throw new \Exception("Đơn hàng phải có giá trị tối thiểu {$minFormatted} để áp dụng mã này");
        }

        $discountAmount = 0;
        if ($coupon->discount_type === 'Phần trăm') {
            $discountAmount = $orderAmount * ($coupon->discount_value / 100);
            if ($coupon->max_discount > 0 && $discountAmount > $coupon->max_discount)
                $discountAmount = $coupon->max_discount;
        } else {
            $discountAmount = $coupon->discount_value;
        }

        if ($discountAmount > $orderAmount)
            $discountAmount = $orderAmount;

        return [
            'valid' => true,
            'discount_amount' => $discountAmount,
            'message' => 'Áp dụng mã giảm giá thành công',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_name' => $coupon->name
        ];
    }
}