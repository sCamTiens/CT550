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
     * Parse JWT token optionally (for routes without JWT middleware)
     * Allows both guest and logged-in users
     */
    private function parseOptionalJWT(Request $request): void
    {
        // Try to get token from session first
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = null;

        // Priority 1: Session
        if (!empty($_SESSION['customer']['access_token'])) {
            $token = $_SESSION['customer']['access_token'];
        } else {
            // Priority 2: Authorization header
            $authHeader = null;

            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            }

            if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            }

            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if ($token) {
            // Validate and decode token
            $userData = \App\Support\JWTHelper::getUserFromToken($token);
            if ($userData) {
                $request->user = $userData;
                error_log("[ChatController.parseJWT] User authenticated: ID=" . ($userData['id'] ?? 'N/A'));
            } else {
                error_log("[ChatController.parseJWT] Token invalid");
            }
        } else {
            error_log("[ChatController.parseJWT] No token found - treating as guest");
        }
    }

    /**
     * Khởi tạo phiên chat mới hoặc lấy phiên hiện tại
     */
    public function init(Request $request, Response $response)
    {
        // Parse JWT token manually (no middleware on this route)
        $this->parseOptionalJWT($request);

        // Get user from JWT token
        $userId = $request->user['id'] ?? null;
        $isGuest = !$userId;

        // Guest users: NO database storage, chat trực tiếp
        if ($isGuest) {
            $response->json([
                'success' => true,
                'session_id' => 'guest_temp',
                'is_ai_mode' => true,
                'is_guest' => true,
                'messages' => [],
                'user' => [
                    'id' => 'guest',
                    'name' => 'Khách',
                    'avatar' => null
                ]
            ]);
            return;
        }

        try {
            // Kiểm tra phiên chat đang active (chỉ cho user đã đăng nhập)
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
            } else {
                // Session đã tồn tại - check và update AI mode theo giờ hiện tại
                $currentAIMode = (bool)$session['is_ai_mode'];
                $newAIMode = $this->checkAndSwitchAIMode($session['id'], $currentAIMode);
                $session['is_ai_mode'] = $newAIMode; // Update local variable

                error_log("[Chat Init] SessionID: {$session['id']}, Old AI Mode: " . ($currentAIMode ? 'YES' : 'NO') . ", New AI Mode: " . ($newAIMode ? 'YES' : 'NO'));
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
                'is_guest' => false,
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
        // Parse JWT token manually (no middleware on this route)
        $this->parseOptionalJWT($request);

        // Get user from JWT token
        $userId = $request->user['id'] ?? null;
        $isGuest = !$userId;

        // DEBUG: Log user info
        error_log("[ChatController.send] UserId: " . ($userId ?? 'NULL') . ", IsGuest: " . ($isGuest ? 'YES' : 'NO'));
        error_log("[ChatController.send] Request->user: " . json_encode($request->user ?? 'NULL'));

        $data = $request->getBody();
        $sessionId = $data['session_id'] ?? null;
        $message = trim($data['message'] ?? '');

        if (!$message) {
            return $response->json([
                'success' => false,
                'message' => 'Thiếu thông tin'
            ], 400);
        }

        // Guest: Chat trực tiếp với AI, không lưu DB
        if ($isGuest) {
            try {
                error_log("[ChatController.send] GUEST user sending message: $message");
                $aiResponse = $this->processWithAI($message, 'guest');

                if (empty($aiResponse['message'])) {
                    return $response->json([
                        'success' => false,
                        'message' => 'AI không thể tạo phản hồi'
                    ], 500);
                }

                return $response->json([
                    'success' => true,
                    'response' => $aiResponse['message'],
                    'sender_type' => 'ai',
                    'metadata' => $aiResponse['metadata'] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                error_log("Guest chat error: " . $e->getMessage());
                return $response->json([
                    'success' => false,
                    'message' => 'Không thể gửi tin nhắn'
                ], 500);
            }
        }

        // Logged-in user: Lưu vào DB
        if (!$sessionId) {
            return $response->json([
                'success' => false,
                'message' => 'Thiếu session ID'
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
        $isGuest = ($userId === 'guest');
        $messageLower = strtolower($message);

        // BLOCK GUEST từ hỏi về thông tin nhạy cảm TRƯỚC KHI gọi Rasa
        if ($isGuest) {
            // Check các từ khóa bị cấm cho guest
            $blockedKeywords = ['đơn hàng', 'order', 'mã đơn', 'giỏ hàng', 'cart', 'tài khoản', 'profile', 'ORD', 'DH'];

            foreach ($blockedKeywords as $keyword) {
                if (strpos($messageLower, strtolower($keyword)) !== false) {
                    error_log("[processWithAI] BLOCKED guest query with keyword: $keyword");

                    // Trả về message phù hợp
                    if (strpos($messageLower, 'đơn') !== false || strpos($messageLower, 'order') !== false || strpos($messageLower, 'ord') !== false || strpos($messageLower, 'dh') !== false) {
                        return [
                            'message' => 'Bạn cần đăng nhập để xem thông tin đơn hàng. Hãy đăng ký/đăng nhập để mua sắm và theo dõi đơn hàng nhé!'
                        ];
                    } elseif (strpos($messageLower, 'giỏ') !== false || strpos($messageLower, 'cart') !== false) {
                        return [
                            'message' => 'Bạn cần đăng nhập để sử dụng giỏ hàng. Vui lòng đăng ký/đăng nhập để mua sắm!'
                        ];
                    } else {
                        return [
                            'message' => 'Bạn cần đăng nhập để truy cập tính năng này. Hãy đăng ký ngay để trải nghiệm đầy đủ!'
                        ];
                    }
                }
            }
        }

        // TEMPORARY: Skip Rasa, use PHP fallback directly
        /* Commented out for quick testing
        // Thử kết nối với Rasa (chỉ cho câu hỏi hợp lệ)
        try {
            $rasaResponse = $this->callRasaAPI($message, $userId);
            error_log("[processWithAI] Rasa response: " . json_encode($rasaResponse));

            if ($rasaResponse) {
                // Rasa có thể trả về nhiều format khác nhau
                $responseText = null;
                $metadata = null;

                // Format 1: {text: "...", ...}
                if (isset($rasaResponse['text']) && !empty($rasaResponse['text'])) {
                    $responseText = $rasaResponse['text'];
                    $metadata = $rasaResponse['metadata'] ?? $rasaResponse['custom'] ?? null;
                }
                // Format 2: {recipient_id: "...", text: "..."}
                elseif (isset($rasaResponse['recipient_id']) && isset($rasaResponse['text'])) {
                    $responseText = $rasaResponse['text'];
                }
                // Format 3: array with first element
                elseif (is_array($rasaResponse) && isset($rasaResponse[0]['text'])) {
                    $responseText = $rasaResponse[0]['text'];
                    $metadata = $rasaResponse[0]['metadata'] ?? $rasaResponse[0]['custom'] ?? null;
                }

                if ($responseText) {
                    error_log("[processWithAI] Extracted text: $responseText");
                    return [
                        'message' => $responseText,
                        'metadata' => $metadata
                    ];
                }

                error_log("[processWithAI] Rasa response has no valid text field");
            }
        } catch (\Exception $e) {
            error_log("Rasa API error: " . $e->getMessage());
        }
        */

        // Fallback: Sử dụng AI đơn giản dựa trên rule-based
        error_log("[processWithAI] Falling back to simple AI");
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
        $isGuest = ($userId === 'guest');

        // Phát hiện intent
        if (strpos($message, 'đơn hàng') !== false || strpos($message, 'order') !== false || strpos($message, 'mã đơn') !== false) {
            error_log("[simpleAIFallback] Detected ORDER intent");

            // Guest không được xem đơn hàng
            if ($isGuest) {
                return [
                    'message' => 'Bạn cần đăng nhập để xem đơn hàng. Hãy đăng ký/đăng nhập để mua sắm và theo dõi đơn hàng nhé!'
                ];
            }

            return $this->handleOrderIntent($userId);
        } elseif (strpos($message, 'giỏ hàng') !== false || strpos($message, 'cart') !== false) {
            // Guest không có giỏ hàng
            if ($isGuest) {
                return [
                    'message' => 'Bạn cần đăng nhập để sử dụng giỏ hàng. Vui lòng đăng ký/đăng nhập để mua sắm!'
                ];
            }

            return [
                'message' => 'Để xem giỏ hàng, vui lòng truy cập trang giỏ hàng hoặc click vào biểu tượng giỏ hàng ở góc trên.'
            ];
        } elseif (strpos($message, 'tài khoản') !== false || strpos($message, 'profile') !== false) {
            // Guest không có tài khoản
            if ($isGuest) {
                return [
                    'message' => 'Bạn cần đăng nhập để quản lý tài khoản. Hãy đăng ký ngay để trải nghiệm đầy đủ tính năng!'
                ];
            }

            return [
                'message' => 'Bạn có thể quản lý thông tin tài khoản tại trang Hồ sơ cá nhân.'
            ];
        } elseif (strpos($message, 'khuyến mãi') !== false || strpos($message, 'giảm giá') !== false || strpos($message, 'promotion') !== false || strpos($message, 'ưu đãi') !== false) {
            return $this->handlePromotionIntent();
        } elseif (strpos($message, 'địa chỉ') !== false || strpos($message, 'shop ở đâu') !== false || strpos($message, 'cửa hàng') !== false || strpos($message, 'ở đâu') !== false) {
            return [
                'message' => '📍 Cửa hàng MiniGo tại Khu II, Trường Đại học Cần Thơ, Đường 3/2, Phường Xuân Khánh, Quận Ninh Kiều, TP. Cần Thơ. Tọa độ: 10.03289, 105.769082'
            ];
        } elseif (strpos($message, 'giao hàng') !== false || strpos($message, 'ship') !== false || strpos($message, 'delivery') !== false) {
            return [
                'message' => '🚚 Chúng tôi giao hàng trong bán kính 10km từ cửa hàng (Quận Ninh Kiều, Cái Răng, Bình Thủy, Ô Môn). Phí ship: 20,000đ - 30,000đ tùy khoảng cách.'
            ];
        } elseif (strpos($message, 'mở cửa') !== false || strpos($message, 'giờ') !== false) {
            return [
                'message' => '🕒 Giờ làm việc: Thứ 2 - Chủ nhật, 6:00 - 22:00. Chúng tôi luôn sẵn sàng phục vụ bạn!'
            ];
        } elseif (strpos($message, 'sản phẩm') !== false || strpos($message, 'product') !== false || strpos($message, 'tìm') !== false || strpos($message, 'search') !== false) {
            error_log("[simpleAIFallback] Detected PRODUCT intent");
            return $this->handleProductIntent($message);
        } elseif (strpos($message, 'giá') !== false || strpos($message, 'price') !== false) {
            error_log("[simpleAIFallback] Detected PRICE intent");
            return $this->handlePriceIntent($message);
        } elseif (strpos($message, 'nhân viên') !== false || strpos($message, 'staff') !== false) {
            error_log("[simpleAIFallback] Detected STAFF intent");

            if ($isGuest) {
                return [
                    'message' => 'Bạn có thể đăng ký/đăng nhập để được hỗ trợ trực tiếp từ nhân viên trong giờ làm việc (6h - 22h).'
                ];
            }

            return $this->handleStaffRequestIntent();
        } else {
            error_log("[simpleAIFallback] No specific intent - treating as product search");

            // Nếu là từ đơn (1-2 từ) → Tìm sản phẩm
            $wordCount = str_word_count($message);
            if ($wordCount <= 3 && $wordCount > 0) {
                error_log("[simpleAIFallback] Single/short query - searching products");
                return $this->handleProductIntent($message);
            }

            // Default message
            if ($isGuest) {
                return [
                    'message' => 'Xin chào! Tôi là trợ lý ảo của MiniGo. Tôi có thể giúp bạn tìm hiểu về: 🛍️ Sản phẩm 💰 Giá cả 🎁 Khuyến mãi. nĐể xem đơn hàng và sử dụng đầy đủ tính năng, vui lòng đăng nhập!'
                ];
            }

            return [
                'message' => 'Tôi có thể giúp bạn về: đơn hàng, sản phẩm, giá cả, khuyến mãi. Bạn cần hỗ trợ gì?'
            ];
        }
    }

    private function handleOrderIntent($userId)
    {
        error_log("[handleOrderIntent] Called for userId: $userId");

        // Guest users không có đơn hàng
        if ($userId === 'guest') {
            return [
                'message' => 'Bạn cần đăng nhập để xem đơn hàng. Hãy đăng ký/đăng nhập để mua sắm và theo dõi đơn hàng nhé!'
            ];
        }

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
                $message .= "Đơn #{$order['order_number']}\n";
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
            // Extract product keyword from message
            $keyword = str_replace(['sản phẩm', 'product', 'tìm', 'search', 'danh sách', 'list', 'xem', 'có', 'gì'], '', strtolower($message));
            $keyword = trim($keyword);

            // If no specific keyword, ask user
            if (empty($keyword) || strlen($keyword) < 2) {
                return [
                    'message' => 'Bạn cần tìm sản phẩm gì? Vui lòng cho tôi biết tên hoặc loại sản phẩm bạn đang tìm kiếm (ví dụ: cá, thịt, sữa, bánh...).'
                ];
            }

            $stmt = $this->pdo->prepare("
                SELECT p.id, p.name, p.sale_price, p.slug, s.qty as stock_qty, pi.image_url
                FROM products p
                LEFT JOIN stocks s ON p.id = s.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_active = 1 
                AND (p.name LIKE ? OR p.slug LIKE ?)
                ORDER BY p.created_at DESC 
                LIMIT 5
            ");

            $searchTerm = "%$keyword%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($products)) {
                return [
                    'message' => "Không tìm thấy sản phẩm nào" . ($keyword ? " với từ khóa \"$keyword\"" : "") . ". Vui lòng thử từ khóa khác!"
                ];
            }

            // Build product list for metadata
            $productList = [];
            foreach ($products as $product) {
                $productList[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'sale_price' => $product['sale_price'],
                    'stock_qty' => $product['stock_qty'] ?? 0,
                    'image_url' => $product['image_url'] ? $product['image_url'] : '/assets/images/no-image.png'
                ];
            }

            $message = "Tìm thấy " . count($products) . " sản phẩm" . ($keyword ? " liên quan đến \"$keyword\"" : "") . ":";

            return [
                'message' => $message,
                'metadata' => [
                    'type' => 'product_list',
                    'products' => $productList
                ]
            ];
        } catch (\Exception $e) {
            error_log("[handleProductIntent] Exception: " . $e->getMessage());
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

    private function handlePromotionIntent()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, promo_type, discount_type, discount_value, starts_at, ends_at 
                FROM promotions 
                WHERE is_active = 1 
                AND starts_at <= NOW() 
                AND ends_at >= NOW()
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($promotions)) {
                // TEMPORARY: Return test data if no DB promotions
                $promotions = [
                    [
                        'id' => 1,
                        'name' => 'Giảm giá 50% toàn bộ sản phẩm',
                        'type' => 'Giảm giá %',
                        'discount_value' => 50,
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+7 days'))
                    ],
                    [
                        'id' => 2,
                        'name' => 'Mua 2 tặng 1',
                        'type' => 'Combo',
                        'discount_value' => 0,
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+30 days'))
                    ]
                ];
            }

            if (empty($promotions)) {
                return [
                    'message' => 'Hiện tại chưa có chương trình khuyến mãi nào. Vui lòng quay lại sau!'
                ];
            }

            // Build promotion list for metadata
            $promotionList = [];
            foreach ($promotions as $promo) {
                // Format promo_type
                $typeText = '';
                switch ($promo['promo_type']) {
                    case 'discount':
                        if (isset($promo['discount_value'])) {
                            if (isset($promo['discount_type']) && $promo['discount_type'] === 'percentage') {
                                $typeText = "Giảm {$promo['discount_value']}%";
                            } else {
                                $typeText = "Giảm " . number_format($promo['discount_value'], 0, ',', '.') . "₫";
                            }
                        } else {
                            $typeText = "Giảm giá";
                        }
                        break;
                    case 'bundle':
                        $typeText = "Mua kèm";
                        break;
                    case 'gift':
                        $typeText = "Tặng quà";
                        break;
                    case 'combo':
                        $typeText = "Combo ưu đãi";
                        break;
                    default:
                        $typeText = $promo['promo_type'];
                }

                $promotionList[] = [
                    'id' => $promo['id'],
                    'name' => $promo['name'],
                    'type' => $promo['promo_type'],
                    'type_text' => $typeText,
                    'discount_value' => $promo['discount_value'] ?? 0,
                    'start_date' => date('d/m/Y', strtotime($promo['starts_at'])),
                    'end_date' => date('d/m/Y', strtotime($promo['ends_at'])),
                    'description' => ''
                ];
            }

            $message = "Tìm thấy " . count($promotions) . " chương trình khuyến mãi đang diễn ra:";

            return [
                'message' => $message,
                'metadata' => [
                    'type' => 'promotion_list',
                    'promotions' => $promotionList
                ]
            ];
        } catch (\Exception $e) {
            error_log("[handlePromotionIntent] Exception: " . $e->getMessage());
            return [
                'message' => 'Chúng tôi có nhiều chương trình khuyến mãi hấp dẫn! Vui lòng truy cập trang chủ để xem chi tiết.'
            ];
        }
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
        $fullTime = date('Y-m-d H:i:s');
        $timezone = date_default_timezone_get();

        // Giờ làm việc nhân viên: 6h - 22h (mỗi ngày)
        // Ngoài giờ này, Bot AI sẽ xử lý
        $isStaffHours = $currentHour >= 6 && $currentHour < 22;

        error_log("[Working Hours] Timezone: $timezone, Full time: $fullTime, Current hour: $currentHour, Staff hours: " . ($isStaffHours ? 'YES' : 'NO (Bot mode)'));

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
     * GUEST USERS luôn dùng AI mode, không giới hạn giờ
     */
    private function checkAndSwitchAIMode(int $sessionId, bool $currentAIMode): bool
    {
        // Lấy thông tin session để check guest
        $stmt = $this->pdo->prepare("SELECT user_id FROM chat_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        // Guest users luôn dùng AI mode, không bị ảnh hưởng bởi working hours
        if ($session && strpos($session['user_id'], 'guest_') === 0) {
            error_log("[Auto-Switch] Session $sessionId is GUEST - keeping AI mode ON");
            return true; // Guest luôn AI mode
        }

        // Chỉ apply working hours cho user đã đăng nhập
        $shouldBeAIMode = !$this->isWorkingHours();

        error_log("[Auto-Switch] Session $sessionId: CurrentAIMode=$currentAIMode, ShouldBeAIMode=$shouldBeAIMode");

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

        error_log("[Auto-Switch] Session $sessionId: No change needed, keeping mode " . ($currentAIMode ? 'ON' : 'OFF'));
        return $currentAIMode;
    }
    /**
     * Get full promotion details with products
     */
    public function getPromotionDetails(Request $request, Response $response, $id)
    {
        try {
            if (!$id) {
                return $response->json(['success' => false, 'message' => 'Missing promotion ID'], 400);
            }

            // Get promotion
            $stmt = $this->pdo->prepare("
            SELECT id, name, promo_type, discount_type, discount_value, combo_price, starts_at, ends_at, description
            FROM promotions
            WHERE id = ? AND is_active = 1
        ");
            $stmt->execute([$id]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$promo) {
                return $response->json(['success' => false, 'message' => 'Promotion not found'], 404);
            }

            // Fetch products based on type
            $products = [];

            if ($promo['promo_type'] === 'combo') {
                $stmt = $this->pdo->prepare("
                SELECT p.id as product_id, p.name, p.sale_price, pci.required_qty, pi.image_url
                FROM promotion_combo_items pci
                JOIN products p ON pci.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE pci.promotion_id = ?
            ");
                $stmt->execute([$id]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($promo['promo_type'] === 'bundle') {
                $stmt = $this->pdo->prepare("
                SELECT p.id as product_id, p.name, p.sale_price, pbr.required_qty, pbr.bundle_price, pi.image_url
                FROM promotion_bundle_rules pbr
                JOIN products p ON pbr.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE pbr.promotion_id = ?
            ");
                $stmt->execute([$id]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($promo['promo_type'] === 'gift') {
                $stmt = $this->pdo->prepare("
                SELECT p.id as product_id, p.name, p.sale_price, pgr.required_qty,
                       gp.id as gift_product_id, gp.name as gift_name, pgr.gift_qty, 
                       gpi.image_url as gift_image_url, pi.image_url
                FROM promotion_gift_rules pgr
                JOIN products p ON pgr.trigger_product_id = p.id
                JOIN products gp ON pgr.gift_product_id = gp.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_images gpi ON gp.id = gpi.product_id AND gpi.is_primary = 1
                WHERE pgr.promotion_id = ?
            ");
                $stmt->execute([$id]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Discount
                $stmt = $this->pdo->prepare("
                SELECT p.id as product_id, p.name, p.sale_price, pi.image_url
                FROM promotion_products pp
                JOIN products p ON pp.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE pp.promotion_id = ?
                LIMIT 10
            ");
                $stmt->execute([$id]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Format image URLs
            foreach ($products as &$product) {
                $product['image_url'] = $product['image_url'] ?? '/assets/images/no-image.png';
                if (isset($product['gift_image_url'])) {
                    $product['gift_image_url'] = $product['gift_image_url'] ?? '/assets/images/no-image.png';
                }
            }

            return $response->json([
                'success' => true,
                'promotion' => $promo,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            error_log("Get promotion details error: " . $e->getMessage());
            return $response->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
