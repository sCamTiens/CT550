<?php

/**
 * GHN Webhook Handler
 * Nhận cập nhật trạng thái từ GHN và tự động update vào database
 */

require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\DB;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Get webhook data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log webhook for debugging
$logFile = __DIR__ . '/../../storage/logs/ghn_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);

// Validate webhook
if (!$data || !isset($data['OrderCode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook data']);
    exit;
}

try {
    $pdo = DB::pdo();

    $orderCode = $data['OrderCode'];
    $ghnStatus = $data['Status'] ?? '';
    $ghnDescription = $data['Description'] ?? '';

    // Map GHN status to our status
    $statusMap = [
        'ready_to_pick' => ['Đang giao hàng', 'Chờ lấy hàng'],
        'picking' => ['Đang giao hàng', 'Đang lấy hàng'],
        'cancel' => ['Đã hủy', 'GHN hủy'],
        'money_collect_picking' => ['Đang giao hàng', 'Đang lấy hàng'],
        'picked' => ['Đang giao hàng', 'Đã lấy hàng'],
        'storing' => ['Đang giao hàng', 'Đang lưu kho'],
        'transporting' => ['Đang giao hàng', 'Đang vận chuyển'],
        'sorting' => ['Đang giao hàng', 'Đang phân loại'],
        'delivering' => ['Đang giao hàng', 'Đang giao'],
        'money_collect_delivering' => ['Đang giao hàng', 'Đang giao'],
        'delivered' => ['Đã giao', 'Giao thành công'],
        'delivery_fail' => ['Đang giao hàng', 'Giao thất bại'],
        'waiting_to_return' => ['Đang giao hàng', 'Chờ chuyển hoàn'],
        'return' => ['Đã hủy', 'Đang chuyển hoàn'],
        'return_transporting' => ['Đã hủy', 'Đang hoàn về'],
        'return_sorting' => ['Đã hủy', 'Đang phân loại hoàn'],
        'returning' => ['Đã hủy', 'Đang hoàn về'],
        'return_fail' => ['Đã hủy', 'Hoàn thất bại'],
        'returned' => ['Đã hủy', 'Đã hoàn'],
        'exception' => ['Đã hủy', 'Ngoại lệ'],
        'damage' => ['Đã hủy', 'Hư hỏng'],
        'lost' => ['Đã hủy', 'Thất lạc'],
    ];

    [$status, $ghnStatusVi] = $statusMap[$ghnStatus] ?? ['Đang giao hàng', $ghnStatus];

    // Find order
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE ghn_order_code = ?");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?,
            ghn_status = ?,
            ghn_tracking_data = JSON_SET(
                COALESCE(ghn_tracking_data, '{}'),
                '$.last_update', NOW(),
                '$.status', ?,
                '$.description', ?
            ),
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $ghnStatusVi,
        $ghnStatus,
        $ghnDescription,
        $order['id']
    ]);

    // Log status change
    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, old_status, new_status, note, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $order['id'],
        '', // We don't track old status here
        $status,
        "GHN Update: $ghnStatusVi - $ghnDescription"
    ]);

    // Send success response to GHN
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'order_id' => $order['id'],
        'status' => $status
    ]);
} catch (\Exception $e) {
    // Log error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " ERROR: " . $e->getMessage() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
