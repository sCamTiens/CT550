<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\DB;
use PDO;

class ChatController extends Controller
{
    private $pdo = null;

    public function __construct()
    {
        // Set timezone to Asia/Ho_Chi_Minh (GMT+7)
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->getDB(); // Initialize DB connection
    }

    /**
     * Get database connection (lazy loading)
     */
    private function getDB()
    {
        if ($this->pdo === null) {
            $this->pdo = DB::getConnection();
        }
        return $this->pdo;
    }

    /**
     * Khởi tạo phiên chat mới hoặc lấy phiên hiện tại
     */
    public function init(Request $request, Response $response)
    {
        // Get user from JWT token (set by middleware)
        $userId = $request->user['id'] ?? null;

        if (!$userId) {
            $response->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để sử dụng chat'
            ], 401);
        }

        try {
            // Kiểm tra phiên chat đang active
            $stmt = $this->pdo->prepare("
                SELECT * FROM chat_sessions 
                WHERE user_id = ? AND status = 'active'
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                // Tạo phiên mới
                $sessionToken = bin2hex(random_bytes(32));
                $isAIMode = !$this->isWorkingHours();

                $stmt = $this->pdo->prepare("
                    INSERT INTO chat_sessions (user_id, session_token, is_ai_mode)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $sessionToken, $isAIMode]);
                $sessionId = $this->pdo->lastInsertId();

                // Nếu trong giờ làm việc, tìm nhân viên
                if (!$isAIMode) {
                    $this->assignStaff($sessionId);
                }

                $session = [
                    'id' => $sessionId,
                    'session_token' => $sessionToken,
                    'is_ai_mode' => $isAIMode
                ];
            }

            // Lấy lịch sử chat
            $stmt = $this->pdo->prepare("
                SELECT * FROM chat_messages 
                WHERE session_id = ? 
                ORDER BY created_at ASC
                LIMIT 50
            ");
            $stmt->execute([$session['id']]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'session_id' => $session['id'],
                'is_ai_mode' => (bool)$session['is_ai_mode'],
                'messages' => $messages,
                'user' => [
                    'id' => $userId,
                    'name' => $request->user['full_name'] ?? $request->user['username'] ?? 'User',
                    'avatar' => isset($request->user['avatar']) && $request->user['avatar']
                        ? '/storage/avatars/' . $request->user['avatar']
                        : null
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Chat init error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Không thể khởi tạo chat'
            ], 500);
        }
    }

    /**
     * Gửi tin nhắn
     */
    public function send(Request $request, Response $response)
    {
        // Get user from JWT token (set by middleware)
        $userId = $request->user['id'] ?? null;

        if (!$userId) {
            return $response->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ], 401);
        }

        $data = $request->getBody();
        $sessionId = $data['session_id'] ?? null;
        $message = trim($data['message'] ?? '');

        if (!$sessionId || !$message) {
            return $response->json([
                'success' => false,
                'message' => 'Thiếu thông tin'
            ], 400);
        }

        try {
            // Lưu tin nhắn của khách hàng
            $stmt = $this->pdo->prepare("
                INSERT INTO chat_messages (session_id, sender_type, sender_id, message)
                VALUES (?, 'customer', ?, ?)
            ");
            $stmt->execute([$sessionId, $userId, $message]);

            // Cập nhật session
            $stmt = $this->pdo->prepare("
                UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$sessionId]);

            // Kiểm tra chế độ AI hay nhân viên
            $stmt = $this->pdo->prepare("
                SELECT is_ai_mode, assigned_staff_id FROM chat_sessions WHERE id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            // *** AUTO-SWITCH AI MODE BASED ON WORKING HOURS ***
            $currentAIMode = (bool)$session['is_ai_mode'];
            $newAIMode = $this->checkAndSwitchAIMode($sessionId, $currentAIMode);
            $session['is_ai_mode'] = $newAIMode; // Update local variable

            error_log("[ChatController.send] SessionID: $sessionId, Message: '$message', CurrentAIMode: " . ($currentAIMode ? 'YES' : 'NO') . ", NewAIMode: " . ($newAIMode ? 'YES' : 'NO'));

            if ($session['is_ai_mode']) {
                error_log("[ChatController.send] Processing with AI for message: " . substr($message, 0, 50));

                // Xử lý bằng AI
                $aiResponse = $this->processWithAI($message, $userId);

                error_log("[ChatController.send] AI Response received: " . json_encode($aiResponse));

                if (empty($aiResponse['message'])) {
                    error_log("[ChatController.send] ERROR: AI response message is EMPTY!");
                    return $response->json([
                        'success' => false,
                        'message' => 'AI không thể tạo phản hồi'
                    ], 500);
                }

                // Lưu phản hồi AI
                $stmt = $this->pdo->prepare("
                    INSERT INTO chat_messages (session_id, sender_type, message, metadata)
                    VALUES (?, 'ai', ?, ?)
                ");
                $stmt->execute([
                    $sessionId,
                    $aiResponse['message'],
                    json_encode($aiResponse['metadata'] ?? null)
                ]);

                // Get the created_at timestamp
                $messageId = $this->pdo->lastInsertId();
                error_log("[ChatController.send] AI message saved with ID: $messageId");

                $stmt = $this->pdo->prepare("SELECT created_at FROM chat_messages WHERE id = ?");
                $stmt->execute([$messageId]);
                $timestamp = $stmt->fetchColumn();

                return $response->json([
                    'success' => true,
                    'response' => $aiResponse['message'],
                    'sender_type' => 'ai',
                    'metadata' => $aiResponse['metadata'] ?? null,
                    'created_at' => $timestamp
                ]);
            } else {
                error_log("[ChatController.send] Staff mode - No automatic reply");
                // Staff mode - No automatic reply
                return $response->json([
                    'success' => true
                ]);
            }
        } catch (\Exception $e) {
            error_log("Chat send error: " . $e->getMessage());
            return $response->json([
                'success' => false,
                'message' => 'Không thể gửi tin nhắn'
            ], 500);
        }
    }

    /**
     * Lấy tin nhắn mới
     */
    public function getMessages(Request $request, Response $response, $sessionId)
    {
        // Get user from JWT token (set by middleware)
        $userId = $request->user['id'] ?? null;

        if (!$userId) {
            return $response->json(['success' => false], 401);
        }

        try {
            // Lấy tin nhắn chưa đọc
            $stmt = $this->pdo->prepare("
                SELECT cm.* FROM chat_messages cm
                JOIN chat_sessions cs ON cm.session_id = cs.id
                WHERE cs.id = ? AND cs.user_id = ? 
                AND cm.sender_type != 'customer' AND cm.is_read = 0
                ORDER BY cm.created_at ASC
            ");
            $stmt->execute([$sessionId, $userId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Đánh dấu đã đọc
            if (!empty($messages)) {
                $messageIds = array_column($messages, 'id');
                $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
                $stmt = $this->pdo->prepare("
                    UPDATE chat_messages SET is_read = 1 
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute($messageIds);
            }

            return $response->json([
                'success' => true,
                'new_messages' => $messages
            ]);
        } catch (\Exception $e) {
            error_log("Get messages error: " . $e->getMessage());
            return $response->json(['success' => false], 500);
        }
    }

    /**
     * Xử lý tin nhắn bằng AI (tích hợp Rasa)
     */
    private function processWithAI($message, $userId)
    {
        // Thử kết nối với Rasa trước
        try {
            $rasaResponse = $this->callRasaAPI($message, $userId);
            error_log("[processWithAI] Rasa response: " . json_encode($rasaResponse));

            if ($rasaResponse && !empty($rasaResponse['text'])) {
                $metadata = $rasaResponse['metadata'] ?? $rasaResponse['custom'] ?? null;
                error_log("[processWithAI] Extracted metadata: " . json_encode($metadata));

                return [
                    'message' => $rasaResponse['text'],
                    'metadata' => $metadata
                ];
            }
        } catch (\Exception $e) {
            error_log("Rasa API error: " . $e->getMessage());
        }

        // Fallback: Sử dụng AI đơn giản dựa trên rule-based
        return $this->simpleAIFallback($message, $userId);
    }

    /**
     * Gọi Rasa API
     */
    private function callRasaAPI($message, $userId)
    {
        // Try 127.0.0.1 instead of localhost to avoid DNS issues
        $url = 'http://127.0.0.1:5005/webhooks/rest/webhook';

        $data = [
            'sender' => (string)$userId,
            'message' => $message
        ];

        error_log("Rasa API Request: " . json_encode(['url' => $url, 'data' => $data]));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduced to 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Reduced to 2 seconds

        // Critical: Close session to prevent locking other requests if Rasa hangs
        if (session_id()) {
            session_write_close();
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        error_log("Rasa API Response: HTTP $httpCode, Error: " . ($error ?: 'none') . ", Response: " . substr($response, 0, 200));

        if ($error) {
            error_log("Rasa cURL error details: " . json_encode($curlInfo));
            throw new \Exception("Rasa connection error: " . $error);
        }

        if ($httpCode === 200) {
            $responses = json_decode($response, true);
            error_log("Rasa parsed response: " . json_encode($responses));
            return $responses[0] ?? null;
        }

        error_log("Rasa API failed with HTTP code: $httpCode");
        return null;
    }

    /**
     * AI đơn giản dựa trên rule-based (fallback khi Rasa không khả dụng)
     */
    private function simpleAIFallback($message, $userId)
    {
        error_log("[simpleAIFallback] Called with message: '$message', userId: $userId");
        $message = strtolower($message);

        // Phát hiện intent
        if (strpos($message, 'đơn hàng') !== false || strpos($message, 'order') !== false) {
            error_log("[simpleAIFallback] Detected ORDER intent");
            return $this->handleOrderIntent($userId);
        } elseif (strpos($message, 'sản phẩm') !== false || strpos($message, 'product') !== false) {
            error_log("[simpleAIFallback] Detected PRODUCT intent");
            return $this->handleProductIntent($message);
        } elseif (strpos($message, 'giá') !== false || strpos($message, 'price') !== false) {
            error_log("[simpleAIFallback] Detected PRICE intent");
            return $this->handlePriceIntent($message);
        } elseif (strpos($message, 'nhân viên') !== false || strpos($message, 'staff') !== false) {
            error_log("[simpleAIFallback] Detected STAFF intent");
            return $this->handleStaffRequestIntent();
        } else {
            error_log("[simpleAIFallback] No intent matched - returning default response");
            return [
                'message' => 'Tôi có thể giúp bạn về: đơn hàng, sản phẩm, giá cả. Bạn cần hỗ trợ gì?'
            ];
        }
    }

    private function handleOrderIntent($userId)
    {
        error_log("[handleOrderIntent] Called for userId: $userId");
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, order_number, total_price, status, created_at
                FROM orders
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("[handleOrderIntent] Found " . count($orders) . " orders for user $userId");

            if (empty($orders)) {
                error_log("[handleOrderIntent] No orders found - returning empty message");
                return [
                    'message' => 'Bạn chưa có đơn hàng nào. Hãy mua sắm ngay!'
                ];
            }

            $message = "Đây là các đơn hàng gần đây của bạn:\n\n";
            foreach ($orders as $order) {
                $statusText = $this->getOrderStatusText($order['status']);
                $message .= "📦 Đơn #{$order['order_number']}\n";
                $message .= "   Trạng thái: {$statusText}\n";
                $message .= "   Tổng tiền: " . number_format($order['total_price'], 0, ',', '.') . "₫\n";
                $message .= "   Ngày đặt: " . date('d/m/Y', strtotime($order['created_at'])) . "\n\n";
            }

            error_log("[handleOrderIntent] Generated message: " . substr($message, 0, 100));

            return [
                'message' => $message,
                'metadata' => ['order_id' => $orders[0]['id']]
            ];
        } catch (\Exception $e) {
            error_log("[handleOrderIntent] Exception: " . $e->getMessage());
            return [
                'message' => 'Không thể lấy thông tin đơn hàng. Vui lòng thử lại sau.'
            ];
        }
    }

    private function handleProductIntent($message)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, sale_price 
                FROM products 
                WHERE is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = "Đây là một số sản phẩm nổi bật:\n\n";
            foreach ($products as $product) {
                $response .= "🛒 {$product['name']}\n";
                $response .= "   Giá: " . number_format($product['sale_price'], 0, ',', '.') . "₫\n\n";
            }

            return ['message' => $response];
        } catch (\Exception $e) {
            return [
                'message' => 'Không thể lấy danh sách sản phẩm. Vui lòng thử lại sau.'
            ];
        }
    }

    private function handlePriceIntent($message)
    {
        return [
            'message' => 'Bạn muốn biết giá của sản phẩm nào? Vui lòng cho tôi biết tên sản phẩm.'
        ];
    }

    private function handleStaffRequestIntent()
    {
        return [
            'message' => 'Tôi sẽ chuyển bạn đến nhân viên hỗ trợ. Vui lòng đợi trong giây lát...'
        ];
    }

    private function getOrderStatusText($status)
    {
        $statusMap = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy'
        ];

        return $statusMap[$status] ?? 'Không xác định';
    }

    /**
     * Kiểm tra giờ làm việc của nhân viên
     * Nhân viên: 6:00 - 22:00
     * Bot (AI): 22:00 - 6:00
     */
    private function isWorkingHours()
    {
        $currentHour = (int)date('H');

        // Giờ làm việc nhân viên: 6h - 22h (mỗi ngày)
        // Ngoài giờ này, Bot AI sẽ xử lý
        $isStaffHours = $currentHour >= 6 && $currentHour < 22;

        error_log("[Working Hours] Current hour: $currentHour, Staff hours: " . ($isStaffHours ? 'YES' : 'NO (Bot mode)'));

        return $isStaffHours;
    }

    /**
     * Gán nhân viên hỗ trợ
     */
    private function assignStaff($sessionId)
    {
        try {
            $this->getDB(); // Ensure DB is connected
            $stmt = $this->pdo->prepare("
                SELECT staff_id FROM staff_online_status
                WHERE is_online = 1 AND current_chat_count < max_concurrent_chats
                ORDER BY current_chat_count ASC
                LIMIT 1
            ");
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff) {
                // Gán nhân viên
                $stmt = $this->pdo->prepare("
                    UPDATE chat_sessions 
                    SET assigned_staff_id = ?, is_ai_mode = 0
                    WHERE id = ?
                ");
                $stmt->execute([$staff['staff_id'], $sessionId]);

                // Tăng số lượng chat của nhân viên
                $stmt = $this->pdo->prepare("
                    UPDATE staff_online_status 
                    SET current_chat_count = current_chat_count + 1
                    WHERE staff_id = ?
                ");
                $stmt->execute([$staff['staff_id']]);
            }
        } catch (\Exception $e) {
            error_log("Assign staff error: " . $e->getMessage());
        }
    }

    /**
     * Kiểm tra và tự động chuyển đổi AI mode dựa trên giờ
     * Gọi khi init session hoặc send message
     */
    private function checkAndSwitchAIMode(int $sessionId, bool $currentAIMode): bool
    {
        $shouldBeAIMode = !$this->isWorkingHours();

        // Nếu mode hiện tại khác với mode mong muốn → chuyển đổi
        if ($currentAIMode != $shouldBeAIMode) {
            error_log("[Auto-Switch] Session $sessionId: Switching AI mode from " .
                ($currentAIMode ? 'ON' : 'OFF') . " to " .
                ($shouldBeAIMode ? 'ON' : 'OFF'));

            $stmt = $this->pdo->prepare("
                UPDATE chat_sessions 
                SET is_ai_mode = ?
                WHERE id = ?
            ");
            $stmt->execute([$shouldBeAIMode, $sessionId]);

            // Nếu chuyển sang AI mode, bỏ assign staff
            if ($shouldBeAIMode) {
                $stmt = $this->pdo->prepare("
                    UPDATE chat_sessions 
                    SET assigned_staff_id = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$sessionId]);
            }
            // Nếu chuyển sang staff mode, assign nhân viên
            else {
                $this->assignStaff($sessionId);
            }

            return $shouldBeAIMode;
        }

        return $currentAIMode;
    }
}
