<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ChatRepository;
use App\Core\DB;
use PDO;

class ChatSupportController extends BaseAdminController
{
    private ChatRepository $chatRepo;

    public function __construct()
    {
        parent::__construct(); // IMPORTANT: Call parent to check admin session
        $this->chatRepo = new ChatRepository();
    }

    /**
     * Trang chính - Danh sách phiên chat
     */
    public function index()
    {
        // No need to check permission here - BaseAdminController already checked

        return $this->view('admin.chat-support.index', [
            'title' => 'Hỗ Trợ Trực Tuyến',
            'pageTitle' => 'Chat Support Dashboard'
        ]);
    }

    /**
     * API: Lấy danh sách phiên chat
     */
    public function apiGetSessions(Request $request, Response $response)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        try {
            $status = $_GET['status'] ?? 'active';
            $staffRole = $_SESSION['user']['staff_role'] ?? null;
            $staffId = null;

            // Nếu không phải Admin, chỉ xem chat của mình
            if ($staffRole !== 'Admin') {
                $staffId = $_SESSION['user']['id'] ?? null;
            }

            error_log("Chat sessions query - Status: $status, StaffId: " . ($staffId ?? 'NULL'));

            $sessions = $this->chatRepo->getSessions($status, $staffId);

            error_log("Sessions count: " . count($sessions));

            $response->json([
                'success' => true,
                'sessions' => $sessions
            ]);
        } catch (\Exception $e) {
            error_log("Get sessions error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy tin nhắn của một phiên
     */
    public function apiGetMessages(Request $request, Response $response, $sessionId)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false], 403);
            return;
        }

        try {
            $sessionId = (int)$sessionId;

            // Verify quyền truy cập session
            if (!$this->canAccessSession($sessionId)) {
                $response->json(['success' => false, 'message' => 'No access'], 403);
                return;
            }

            $messages = $this->chatRepo->getMessagesBySession($sessionId);

            // Mark as delivered when staff fetches
            $this->chatRepo->markMessagesAsDelivered($sessionId);

            // Mark as read when staff views
            $this->chatRepo->markCustomerMessagesAsRead($sessionId);

            $response->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            error_log("Get messages error: " . $e->getMessage());
            $response->json(['success' => false], 500);
        }
    }

    /**
     * API: Gửi tin nhắn (Staff reply)
     */
    public function apiSendMessage(Request $request, Response $response)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false], 403);
            return;
        }

        try {
            $data = $request->getBody();
            $sessionId = (int)($data['session_id'] ?? 0);
            $message = trim($data['message'] ?? '');
            $staffId = $_SESSION['user']['id'] ?? null;

            if (!$sessionId || !$message) {
                $response->json(['success' => false, 'message' => 'Missing data'], 400);
            }

            // Verify quyền
            if (!$this->canAccessSession($sessionId)) {
                $response->json(['success' => false], 403);
            }

            // Command Parsing for Rich Messages
            $metadata = null;

            // Check for order command: !order [CODE] or #order [CODE]
            if (preg_match('/^(\!|\#)order\s+([A-Za-z0-9\-_]+)$/i', $message, $matches)) {
                $orderCode = $matches[2];
                $order = $this->findOrder($orderCode);

                if ($order) {
                    // Create Rich Message
                    $metadata = json_encode([
                        'type' => 'order_card',
                        'order_id' => $order['id'], // ID for link
                        'order_number' => $order['code'],
                        'total_price' => $order['grand_total'],
                        'status' => $order['status'],
                        'items_count' => 0, // Should be fetched if possible, keeping simple for now
                        'created_at' => $order['created_at']
                    ]);
                    // Optional: Format the text message nicely too
                    $message = "Thông tin đơn hàng #{$order['code']}:";
                } else {
                    // Notify staff (hidden from user? No, staff sees this as their message)
                    // Just append error note
                    $message .= " (Không tìm thấy đơn hàng: $orderCode)";
                }
            }
            // Check for product search command: !product [keyword] or #product [keyword]
            elseif (preg_match('/^(\!|\#)product\s+(.+)$/i', $message, $matches)) {
                $keyword = trim($matches[2]);
                error_log("🔍 Product search command detected - Keyword: '$keyword'");

                $products = $this->findProducts($keyword);
                error_log("🔍 Product search results: " . count($products) . " products found");

                if (!empty($products)) {
                    $metadata = json_encode([
                        'type' => 'product_list',
                        'keyword' => $keyword,
                        'products' => $products
                    ]);

                    if ($metadata === false) {
                        error_log("❌ JSON encode failed: " . json_last_error_msg());
                        $message .= " (Lỗi khi mã hóa dữ liệu sản phẩm)";
                    } else {
                        error_log("✅ Metadata JSON created: " . strlen($metadata) . " bytes");
                        $message = "Tìm thấy " . count($products) . " sản phẩm cho từ khóa '{$keyword}':";
                    }
                } else {
                    error_log("❌ No products found for keyword: '$keyword'");
                    $message .= " (Không tìm thấy sản phẩm nào với từ khóa: $keyword)";
                }
            }

            // Lưu tin nhắn
            $messageId = $this->chatRepo->createMessage($sessionId, 'staff', $staffId, $message, $metadata);

            // Update session timestamp
            $this->chatRepo->updateSessionTimestamp($sessionId);

            $response->json([
                'success' => true,
                'message_id' => $messageId
            ]);
        } catch (\Exception $e) {
            error_log("Send message error: " . $e->getMessage());
            $response->json(['success' => false], 500);
        }
    }

    /**
     * API: Assign staff to session
     */
    public function apiAssignStaff(Request $request, Response $response)
    {
        // Only admin can assign
        if (($_SESSION['user']['staff_role'] ?? '') !== 'Admin') {
            $response->json(['success' => false], 403);
            return;
        }

        try {
            $data = $request->getBody();
            $sessionId = (int)($data['session_id'] ?? 0);
            $staffId = (int)($data['staff_id'] ?? 0);

            $this->chatRepo->assignStaffToSession($sessionId, $staffId);

            $response->json(['success' => true]);
        } catch (\Exception $e) {
            $response->json(['success' => false], 500);
        }
    }

    /**
     * API: Đóng phiên chat
     */
    public function apiCloseSession(Request $request, Response $response)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false], 403);
        }

        try {
            $data = $request->getBody();
            $sessionId = (int)($data['session_id'] ?? 0);

            if (!$this->canAccessSession($sessionId)) {
                $response->json(['success' => false], 403);
            }

            $this->chatRepo->closeSession($sessionId);

            $response->json(['success' => true]);
        } catch (\Exception $e) {
            $response->json(['success' => false], 500);
        }
    }

    /**
     * API: Thống kê
     */
    public function apiGetStats(Request $request, Response $response)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false], 403);
            return;
        }

        try {
            $staffRole = $_SESSION['user']['staff_role'] ?? null;
            $staffId = null;

            // Nếu không phải Admin, chỉ xem stats của mình
            if ($staffRole !== 'Admin') {
                $staffId = $_SESSION['user']['id'] ?? null;
            }

            $stats = $this->chatRepo->getStats($staffId);

            $response->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            $response->json(['success' => false], 500);
        }
    }

    /**
     * API: Lấy quick replies
     */
    public function apiGetQuickReplies(Request $request, Response $response)
    {
        if (!$this->hasPermission()) {
            $response->json(['success' => false], 403);
        }

        try {
            $quickReplies = $this->chatRepo->getQuickReplies();

            $response->json([
                'success' => true,
                'quick_replies' => $quickReplies
            ]);
        } catch (\Exception $e) {
            $response->json(['success' => false], 500);
        }
    }

    /**
     * Kiểm tra quyền truy cập trang chat support
     */
    private function hasPermission(): bool
    {
        // Admin staff đều có quyền truy cập
        return !empty($_SESSION['admin_user']);
    }

    /**
     * Kiểm tra quyền truy cập session cụ thể
     */
    private function canAccessSession(int $sessionId): bool
    {
        $staffRole = $_SESSION['user']['staff_role'] ?? '';

        // Admin có thể truy cập tất cả
        if ($staffRole === 'Admin') {
            return true;
        }

        // Staff khác chỉ truy cập chat được assign
        $staffId = $_SESSION['user']['id'] ?? null;
        if (!$staffId) {
            return false;
        }
        return $this->chatRepo->canStaffAccessSession($sessionId, $staffId);
    }
    /**
     * Helper: Find order by code or ID
     */
    private function findOrder($orderCode)
    {
        try {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, code, grand_total, status, created_at 
                FROM orders 
                WHERE code = ? OR id = ? 
                LIMIT 1
            ");
            $stmt->execute([$orderCode, $orderCode]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Find products by keyword
     */
    private function findProducts($keyword, $limit = 6)
    {
        try {
            $pdo = DB::getConnection();
            $searchTerm = '%' . $keyword . '%';

            // Note: LIMIT cannot be a bound parameter in MySQL/MariaDB
            // Using literal value instead
            $stmt = $pdo->prepare("
                SELECT p.id, p.slug, p.name, p.sale_price, 
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
                FROM products p
                WHERE (p.name LIKE ? COLLATE utf8mb4_unicode_ci 
                       OR p.description LIKE ? COLLATE utf8mb4_unicode_ci)
                ORDER BY p.created_at DESC
                LIMIT " . (int)$limit . "
            ");
            $stmt->execute([$searchTerm, $searchTerm]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug log
            error_log("Product search - Keyword: '$keyword', Search term: '$searchTerm', Found: " . count($products));

            return $products;
        } catch (\Exception $e) {
            error_log("Error finding products: " . $e->getMessage());
            return [];
        }
    }
}
