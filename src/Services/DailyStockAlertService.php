<?php
namespace App\Services;

use App\Core\DB;
use App\Models\Repositories\NotificationRepository;

/**
 * Service kiểm tra tồn kho thấp và tạo thông báo hàng ngày
 * Chạy vào 7h sáng mỗi ngày để cảnh báo sản phẩm tồn kho thấp
 */
class DailyStockAlertService
{
    /**
     * Reset và tạo lại thông báo tồn kho cho ngày mới (Chạy lúc 7h sáng)
     * - XÓA HOÀN TOÀN tất cả thông báo tồn kho cũ (cả đã đọc và chưa đọc)
     * - Tạo thông báo mới CHỈ cho sản phẩm ĐANG CÒN tồn kho thấp
     * - Nếu sản phẩm đã được nhập đủ hàng → KHÔNG tạo thông báo nữa
     * - Chỉ cảnh báo sản phẩm đang bán (is_active = 1)
     * 
     * @return array Kết quả thực hiện
     */
    public static function runDailyCheck(): array
    {
        $pdo = DB::pdo();
        $result = [
            'deleted_old_notifications' => 0,
            'low_stock_products' => 0,
            'out_of_stock_products' => 0,
            'notifications_created' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Bước 1: XÓA HOÀN TOÀN tất cả thông báo tồn kho cũ (reset mỗi ngày)
            $sqlDelete = "DELETE FROM notifications 
                         WHERE title LIKE '%tồn kho%' OR title LIKE '%hết hàng%'";
            $stmtDelete = $pdo->query($sqlDelete);
            $result['deleted_old_notifications'] = $stmtDelete->rowCount();
            
            // Bước 2: Lấy CHỈ những sản phẩm ĐANG CÒN tồn kho thấp (chỉ sản phẩm đang bán)
            // Nếu sản phẩm đã được nhập hàng đủ → KHÔNG nằm trong list này → KHÔNG tạo thông báo
            $sql = "SELECT 
                        s.product_id, 
                        s.qty, 
                        s.safety_stock, 
                        p.name, 
                        p.sku
                    FROM stocks s 
                    JOIN products p ON p.id = s.product_id 
                    WHERE s.qty <= s.safety_stock 
                      AND p.is_active = 1
                    ORDER BY s.qty ASC, p.name ASC";
            
            $stmt = $pdo->query($sql);
            $lowStockProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Ghi log để debug
            error_log("Daily Stock Check at " . date('Y-m-d H:i:s') . ": Found " . count($lowStockProducts) . " products with low stock");
            
            // Đếm sản phẩm hết hàng và tồn kho thấp
            foreach ($lowStockProducts as $product) {
                if ($product['qty'] == 0) {
                    $result['out_of_stock_products']++;
                } else {
                    $result['low_stock_products']++;
                }
            }
            
            // Bước 3: Tạo thông báo mới CHỈ cho sản phẩm còn tồn kho thấp
            foreach ($lowStockProducts as $product) {
                $created = self::createNotificationForProduct(
                    $product['product_id'],
                    $product['name'],
                    $product['sku'],
                    $product['qty'],
                    $product['safety_stock']
                );
                
                if ($created) {
                    $result['notifications_created']++;
                }
            }
            
            // Bước 4: Tự động dọn dẹp thông báo cũ hơn 30 ngày (tránh database phình to)
            $result['old_notifications_cleaned'] = self::cleanupOldNotifications();
            
            // Ghi log kết quả
            error_log("Daily Stock Check completed: " . json_encode($result));
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Error in daily stock alert check: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Tạo thông báo cho một sản phẩm cụ thể
     * Gửi đến tất cả admin, quản lý và nhân viên kho
     * 
     * @return bool True nếu đã tạo thông báo thành công
     */
    private static function createNotificationForProduct(
        int $productId,
        string $productName,
        string $productSku,
        int $currentQty,
        int $safetyStock
    ): bool {
        $pdo = DB::pdo();
        
        // Lấy danh sách user cần nhận thông báo (admin, quản lý, nhân viên kho)
        $sqlUsers = "SELECT DISTINCT u.id 
                     FROM users u 
                     LEFT JOIN staff_profiles sp ON sp.user_id = u.id 
                     WHERE u.role_id IN (2, 3, 4) 
                        OR sp.staff_role IN ('Kho', 'Admin')";
        $stmt = $pdo->query($sqlUsers);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $created = false;
        
        foreach ($userIds as $userId) {
            // Xác định loại và nội dung thông báo
            if ($currentQty == 0) {
                // Sản phẩm hết hàng
                $type = 'error';
                $title = 'Sản phẩm hết hàng';
                $message = "Sản phẩm '{$productName}' (SKU: {$productSku}) đã hết hàng (mức an toàn: {$safetyStock})";
            } else {
                // Tồn kho thấp
                $type = 'warning';
                $title = 'Cảnh báo tồn kho thấp';
                $message = "Sản phẩm '{$productName}' (SKU: {$productSku}) chỉ còn {$currentQty} (mức an toàn: {$safetyStock})";
            }
            
            // Tạo thông báo
            try {
                NotificationRepository::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'link' => '/admin/stocks'
                ]);
                $created = true;
            } catch (\Exception $e) {
                error_log("Error creating notification for user {$userId}: " . $e->getMessage());
            }
        }
        
        return $created;
    }
    
    /**
     * Lấy thống kê chi tiết về tồn kho
     * 
     * @return array Thống kê
     */
    public static function getStockStats(): array
    {
        $pdo = DB::pdo();
        
        // Sản phẩm hết hàng (chỉ tính sản phẩm đang bán)
        $sqlOutOfStock = "SELECT COUNT(*) 
                          FROM stocks s 
                          JOIN products p ON p.id = s.product_id 
                          WHERE s.qty = 0 AND p.is_active = 1";
        $outOfStock = (int)$pdo->query($sqlOutOfStock)->fetchColumn();
        
        // Sản phẩm tồn kho thấp (không bao gồm hết hàng)
        $sqlLowStock = "SELECT COUNT(*) 
                        FROM stocks s 
                        JOIN products p ON p.id = s.product_id 
                        WHERE s.qty > 0 AND s.qty <= s.safety_stock AND p.is_active = 1";
        $lowStock = (int)$pdo->query($sqlLowStock)->fetchColumn();
        
        // Sản phẩm tồn kho rất thấp (< 50% mức an toàn)
        $sqlCritical = "SELECT COUNT(*) 
                        FROM stocks s 
                        JOIN products p ON p.id = s.product_id 
                        WHERE s.qty > 0 AND s.qty <= (s.safety_stock * 0.5) AND p.is_active = 1";
        $critical = (int)$pdo->query($sqlCritical)->fetchColumn();
        
        // Tổng số sản phẩm đang bán
        $sqlActive = "SELECT COUNT(*) FROM products WHERE is_active = 1";
        $activeProducts = (int)$pdo->query($sqlActive)->fetchColumn();
        
        return [
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'critical' => $critical,
            'total_issues' => $outOfStock + $lowStock,
            'active_products' => $activeProducts,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Dọn dẹp thông báo tồn kho cũ (> 30 ngày) đã đọc
     * 
     * @return int Số thông báo đã xóa
     */
    public static function cleanupOldNotifications(): int
    {
        $pdo = DB::pdo();
        
        $sql = "DELETE FROM notifications 
                WHERE (title LIKE '%tồn kho%' OR title LIKE '%hết hàng%')
                  AND is_read = 1 
                  AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $pdo->query($sql);
        return $stmt->rowCount();
    }
}
