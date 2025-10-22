<?php
/**
 * Script kiểm tra tồn kho thấp và tạo thông báo hàng ngày
 * 
 * Script này nên được chạy tự động lúc 7h sáng mỗi ngày bằng Windows Task Scheduler
 * 
 * === HƯỚNG DẪN CÀI ĐẶT WINDOWS TASK SCHEDULER ===
 * 
 * 1. Mở Task Scheduler:
 *    - Nhấn Win + R, gõ: taskschd.msc
 *    - Hoặc tìm "Task Scheduler" trong Start Menu
 * 
 * 2. Tạo Task mới:
 *    - Click "Create Basic Task..." ở bên phải
 *    - Name: "Daily Stock Alert - 7AM"
 *    - Description: "Kiểm tra tồn kho thấp và tạo thông báo mỗi ngày lúc 7h sáng"
 * 
 * 3. Trigger (Khi nào chạy):
 *    - Chọn: "Daily" (Hàng ngày)
 *    - Start date: Hôm nay
 *    - Recur every: 1 days (Mỗi ngày)
 *    - Time: 07:00:00 (7 giờ sáng)
 * 
 * 4. Action (Làm gì):
 *    - Chọn: "Start a program"
 *    - Program/script: C:\path\to\php.exe
 *      (Ví dụ: C:\xampp\php\php.exe hoặc C:\php\php.exe)
 *    - Add arguments: "C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.php"
 *    - Start in: C:\Users\Dell\OneDrive\Documents\Course\CT550
 * 
 * 5. Settings (Cài đặt bổ sung):
 *    - ✓ Run task as soon as possible after a scheduled start is missed
 *    - ✓ If the task fails, restart every: 10 minutes
 *    - Attempt to restart up to: 3 times
 * 
 * === CÁCH CHẠY THỬ THỦ CÔNG ===
 * 
 * Mở CMD tại thư mục dự án và chạy:
 *   php daily_stock_check.php
 * 
 * Hoặc double-click file: daily_stock_check.bat
 * 
 * === LOG FILE ===
 * 
 * Kết quả sẽ được ghi vào: logs/daily_stock_check.log
 */

// Chuyển đến thư mục gốc dự án
chdir(__DIR__);

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
Dotenv::createImmutable(__DIR__)->safeLoad();

use App\Services\DailyStockAlertService;

// Đảm bảo có thư mục logs
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Tạo log file
$logFile = __DIR__ . '/logs/daily_stock_check.log';
$logContent = "";

function logMessage($message) {
    global $logContent;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}\n";
    echo $line;
    $logContent .= $line;
}

logMessage("========================================");
logMessage("DAILY STOCK ALERT CHECK STARTED");
logMessage("========================================");

try {
    // Chạy kiểm tra tồn kho hàng ngày
    logMessage("Running daily stock check...");
    $result = DailyStockAlertService::runDailyCheck();
    
    logMessage("");
    logMessage("--- RESULTS ---");
    logMessage("Deleted old notifications: {$result['deleted_old_notifications']}");
    logMessage("Out of stock products: {$result['out_of_stock_products']}");
    logMessage("Low stock products: {$result['low_stock_products']}");
    logMessage("Total notifications created: {$result['notifications_created']}");
    logMessage("Old notifications cleaned (>30 days): " . ($result['old_notifications_cleaned'] ?? 0));
    logMessage("Timestamp: {$result['timestamp']}");
    
    // Lấy thống kê tổng quan
    logMessage("");
    logMessage("--- CURRENT STATISTICS ---");
    $stats = DailyStockAlertService::getStockStats();
    logMessage("Active products: {$stats['active_products']}");
    logMessage("Out of stock: {$stats['out_of_stock']}");
    logMessage("Low stock: {$stats['low_stock']}");
    logMessage("Critical (< 50% safety): {$stats['critical']}");
    logMessage("Total issues: {$stats['total_issues']}");
    
    // Dọn dẹp thông báo cũ (chỉ chạy vào chủ nhật)
    $dayOfWeek = (int)date('w'); // 0 = Sunday
    if ($dayOfWeek === 0) {
        logMessage("");
        logMessage("--- WEEKLY CLEANUP (Sunday) ---");
        $deleted = DailyStockAlertService::cleanupOldNotifications();
        logMessage("Deleted old notifications: {$deleted}");
    }
    
    logMessage("");
    logMessage("========================================");
    logMessage("DAILY STOCK ALERT CHECK COMPLETED");
    logMessage("Status: SUCCESS");
    logMessage("========================================");
    
    // Ghi log ra file
    file_put_contents($logFile, $logContent, FILE_APPEND);
    
    exit(0);
    
} catch (\Exception $e) {
    logMessage("");
    logMessage("========================================");
    logMessage("ERROR OCCURRED");
    logMessage("========================================");
    logMessage("Error: " . $e->getMessage());
    logMessage("File: " . $e->getFile());
    logMessage("Line: " . $e->getLine());
    logMessage("");
    logMessage("Stack Trace:");
    logMessage($e->getTraceAsString());
    logMessage("");
    logMessage("========================================");
    logMessage("DAILY STOCK ALERT CHECK FAILED");
    logMessage("Status: FAILED");
    logMessage("========================================");
    
    // Ghi log ra file
    file_put_contents($logFile, $logContent, FILE_APPEND);
    
    exit(1);
}
