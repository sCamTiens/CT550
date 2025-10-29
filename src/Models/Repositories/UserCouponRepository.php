<?php
namespace App\Models\Repositories;

use App\Core\DB;
use PDO;

class UserCouponRepository
{
    /**
     * Ghi nhận việc sử dụng mã giảm giá của user
     * 
     * @param int $userId ID người dùng
     * @param int $couponId ID mã giảm giá
     * @param int|null $orderId ID đơn hàng
     * @param int|null $createdBy ID người tạo (admin/user)
     * @return int ID của bản ghi mới
     */
    public function recordUsage(int $userId, int $couponId, ?int $orderId = null, ?int $createdBy = null): int
    {
        error_log("=== UserCouponRepository::recordUsage ===");
        error_log("UserId: {$userId}, CouponId: {$couponId}, OrderId: {$orderId}, CreatedBy: {$createdBy}");
        
        try {
            $pdo = DB::pdo();
            
            $stmt = $pdo->prepare("
                INSERT INTO user_coupons (
                    user_id, 
                    coupon_id, 
                    order_id,
                    status, 
                    assigned_at, 
                    used_at,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :coupon_id,
                    :order_id,
                    'Đã sử dụng',
                    NOW(),
                    NOW(),
                    :created_by,
                    :updated_by,
                    NOW(),
                    NOW()
                )
            ");
            
            $params = [
                ':user_id' => $userId,
                ':coupon_id' => $couponId,
                ':order_id' => $orderId,
                ':created_by' => $createdBy ?? $userId,
                ':updated_by' => $createdBy ?? $userId
            ];
            
            error_log("Executing INSERT with params: " . json_encode($params));
            
            $stmt->execute($params);
            
            $lastId = (int) $pdo->lastInsertId();
            error_log("Successfully inserted record with ID: {$lastId}");
            
            return $lastId;
        } catch (\Exception $e) {
            error_log("ERROR in recordUsage: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Đếm số lần user đã sử dụng một mã cụ thể
     * 
     * @param int $userId
     * @param int $couponId
     * @return int Số lần đã sử dụng
     */
    public function countUserUsage(int $userId, int $couponId): int
    {
        $pdo = DB::pdo();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM user_coupons
            WHERE user_id = :user_id 
            AND coupon_id = :coupon_id
            AND status = 'Đã sử dụng'
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':coupon_id' => $couponId
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }
    
    /**
     * Lấy danh sách các lần sử dụng mã của user
     * 
     * @param int $userId
     * @param int|null $couponId Nếu null thì lấy tất cả
     * @return array
     */
    public function getUserUsageHistory(int $userId, ?int $couponId = null): array
    {
        $pdo = DB::pdo();
        
        $sql = "
            SELECT 
                uc.*,
                c.code as coupon_code,
                c.name as coupon_name,
                o.code as order_code,
                o.grand_total as order_total
            FROM user_coupons uc
            INNER JOIN coupons c ON c.id = uc.coupon_id
            LEFT JOIN orders o ON o.id = uc.order_id
            WHERE uc.user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        
        if ($couponId) {
            $sql .= " AND uc.coupon_id = :coupon_id";
            $params[':coupon_id'] = $couponId;
        }
        
        $sql .= " ORDER BY uc.used_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra xem user còn được sử dụng mã này không
     * (dựa trên max_uses_per_customer của coupon)
     * 
     * @param int $userId
     * @param int $couponId
     * @param int $maxUsesPerCustomer Giới hạn từ bảng coupons
     * @return bool true nếu còn được dùng
     */
    public function canUserUseCoupon(int $userId, int $couponId, int $maxUsesPerCustomer): bool
    {
        // Nếu max = 0 thì không giới hạn
        if ($maxUsesPerCustomer == 0) {
            return true;
        }
        
        $usedCount = $this->countUserUsage($userId, $couponId);
        return $usedCount < $maxUsesPerCustomer;
    }
    
    /**
     * Lấy thống kê sử dụng coupon theo user
     * 
     * @param int $couponId
     * @return array Danh sách users và số lần họ đã dùng
     */
    public function getCouponUsageStatsByUser(int $couponId): array
    {
        $pdo = DB::pdo();
        
        $stmt = $pdo->prepare("
            SELECT 
                u.id as user_id,
                u.full_name,
                u.email,
                COUNT(uc.id) as usage_count,
                MAX(uc.used_at) as last_used_at,
                SUM(o.grand_total) as total_order_value
            FROM user_coupons uc
            INNER JOIN users u ON u.id = uc.user_id
            LEFT JOIN orders o ON o.id = uc.order_id
            WHERE uc.coupon_id = :coupon_id
            AND uc.status = 'Đã sử dụng'
            GROUP BY u.id, u.full_name, u.email
            ORDER BY usage_count DESC
        ");
        
        $stmt->execute([':coupon_id' => $couponId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
