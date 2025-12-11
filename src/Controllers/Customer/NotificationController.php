<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\DB;

class NotificationController extends Controller
{
    public function apiIndex()
    {
        header('Content-Type: application/json');
        $userId = $_SESSION['customer']['id'] ?? null;

        if (!$userId) {
            echo json_encode(['notifications' => [], 'unread_count' => 0]);
            exit;
        }

        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
            $stmtCount->execute([$userId]);
            $unread = (int)$stmtCount->fetchColumn();

            echo json_encode([
                'success' => true,
                'notifications' => $items,
                'unread_count' => $unread
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function markRead()
    {
        header('Content-Type: application/json');
        $userId = $_SESSION['customer']['id'] ?? null;

        if ($userId) {
            \App\Support\NotificationHelper::markAllAsRead($userId);
        }

        echo json_encode(['success' => true]);
        exit;
    }
}
