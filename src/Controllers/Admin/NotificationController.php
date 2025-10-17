<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\NotificationRepository;

class NotificationController extends BaseAdminController
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }

    /**
     * Lấy danh sách thông báo của user hiện tại
     * GET /admin/api/notifications
     */
    public function index()
    {
        $userId = $_SESSION['admin_user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $notifications = NotificationRepository::getByUser($userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($notifications, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Đếm số thông báo chưa đọc
     * GET /admin/api/notifications/unread-count
     */
    public function unreadCount()
    {
        $userId = $_SESSION['admin_user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $count = NotificationRepository::countUnread($userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['count' => $count]);
        exit;
    }

    /**
     * Đánh dấu 1 thông báo đã đọc
     * POST /admin/api/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $userId = $_SESSION['admin_user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $success = NotificationRepository::markAsRead($id, $userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success]);
        exit;
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     * POST /admin/api/notifications/read-all
     */
    public function markAllAsRead()
    {
        $userId = $_SESSION['admin_user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $success = NotificationRepository::markAllAsRead($userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success]);
        exit;
    }

    /**
     * Xóa thông báo
     * DELETE /admin/api/notifications/{id}
     */
    public function delete($id)
    {
        $userId = $_SESSION['admin_user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $success = NotificationRepository::delete($id, $userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success]);
        exit;
    }
}
