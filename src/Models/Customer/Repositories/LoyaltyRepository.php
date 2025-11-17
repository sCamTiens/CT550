<?php
namespace App\Models\Customer\Repositories;

use App\Core\DB;

class LoyaltyRepository
{
    /**
     * Lấy thông tin customer
     */
    public function getCustomerInfo(int $customerId): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, full_name, email, loyalty_points, avatar_url
            FROM users
            WHERE id = ? AND role_id = 1
        ");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $customer ?: null;
    }

    /**
     * Lấy danh sách giao dịch điểm tích lũy
     */
    public function getTransactions(int $customerId): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT 
                lt.id,
                lt.order_id,
                lt.points,
                lt.transaction_type,
                lt.description,
                lt.created_at,
                lt.balance_before,
                lt.balance_after,
                o.code as order_code,
                o.grand_total as total_amount,
                o.payment_status
            FROM loyalty_transactions lt
            LEFT JOIN orders o ON lt.order_id = o.id
            WHERE lt.user_id = ?
            ORDER BY lt.created_at DESC
        ");
        $stmt->execute([$customerId]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Format dữ liệu
        foreach ($transactions as &$trans) {
            $trans['points_change'] = (int)$trans['points']; // Đổi tên để frontend dùng
            $trans['total_amount'] = (float)($trans['total_amount'] ?? 0);
            $trans['order_code'] = $trans['order_code'] ?? '—';
            $trans['payment_status'] = $trans['payment_status'] ?? '—';
        }
        
        return $transactions;
    }

    /**
     * Lấy tổng điểm đã tích lũy (all time)
     */
    public function getTotalEarned(int $customerId): int
    {
        $stmt = DB::pdo()->prepare("
            SELECT COALESCE(SUM(points), 0) as total
            FROM loyalty_transactions
            WHERE user_id = ? AND points > 0
        ");
        $stmt->execute([$customerId]);
        $result = $stmt->fetchColumn();
        
        return (int)$result;
    }

    /**
     * Lấy tổng điểm đã sử dụng
     */
    public function getTotalRedeemed(int $customerId): int
    {
        // Lấy điểm hiện tại
        $customer = $this->getCustomerInfo($customerId);
        $currentPoints = $customer['loyalty_points'] ?? 0;
        
        // Lấy tổng đã tích
        $totalEarned = $this->getTotalEarned($customerId);
        
        // Đã sử dụng = Tổng tích - Hiện tại
        return max(0, $totalEarned - $currentPoints);
    }

    /**
     * Lấy số đơn hàng đã được cộng điểm
     */
    public function getOrderCount(int $customerId): int
    {
        $stmt = DB::pdo()->prepare("
            SELECT COUNT(DISTINCT order_id)
            FROM loyalty_transactions
            WHERE user_id = ? AND order_id IS NOT NULL AND points > 0
        ");
        $stmt->execute([$customerId]);
        $result = $stmt->fetchColumn();
        
        return (int)$result;
    }
}
