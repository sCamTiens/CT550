<?php

/**
 * JWT-based Chat API V3.0
 * - JWT authentication
 * - Save messages to DB
 * - User avatar support
 * - Auto cleanup on logout
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Support\EnvHelper;
use App\Core\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

EnvHelper::load(__DIR__ . '/../.env');
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$action = $_GET['action'] ?? 'init';

// Get user from JWT or session
$userId = null;
$userInfo = null;

// Check JWT first
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    try {
        $jwt = $matches[1];
        $secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        $userId = $decoded->user_id ?? $decoded->sub ?? null;

        // Get user info from DB
        if ($userId) {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare("SELECT id, full_name, email, avatar FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("JWT decode error: " . $e->getMessage());
    }
}

// Fallback to session
if (!$userId && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

$isLoggedIn = !empty($userId) && !empty($userInfo);

// Helper: Call Rasa
function callRasa($message, $userId)
{
    $url = 'http://127.0.0.1:5005/webhooks/rest/webhook';

    $data = json_encode([
        'sender' => (string)$userId,
        'message' => $message,
        'metadata' => ['user_id' => $userId]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $responses = json_decode($response, true);
        return $responses[0] ?? ['text' => 'Bot không phản hồi'];
    }

    return ['error' => 'Rasa error'];
}

try {
    if (!$isLoggedIn) {
        // Guest mode
        echo json_encode([
            'success' => false,
            'require_login' => true,
            'message' => 'Vui lòng đăng nhập để sử dụng chat'
        ]);
        exit;
    }

    $pdo = DB::getConnection();

    switch ($action) {
        case 'init':
            // Get or create session for this user
            $stmt = $pdo->prepare("
                SELECT id FROM chat_sessions 
                WHERE user_id = ? AND status = 'active'
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                // Create new session
                $stmt = $pdo->prepare("
                    INSERT INTO chat_sessions (user_id, is_ai_mode, status)
                    VALUES (?, 1, 'active')
                ");
                $stmt->execute([$userId]);
                $sessionId = (int)$pdo->lastInsertId();

                // Welcome message
                $stmt = $pdo->prepare("
                    INSERT INTO chat_messages (session_id, sender_type, message)
                    VALUES (?, 'ai', ?)
                ");
                $stmt->execute([
                    $sessionId,
                    'Xin chào ' . ($userInfo['full_name'] ?? 'bạn') . '! Tôi là trợ lý ảo của MiniGo. Tôi có thể giúp gì cho bạn? 😊'
                ]);

                error_log("✅ [Init] Created new session: $sessionId for user: $userId");
            } else {
                $sessionId = (int)$session['id'];
                error_log("✅ [Init] Using existing session: $sessionId for user: $userId");
            }

            // Load messages
            $stmt = $pdo->prepare("
                SELECT sender_type, message, metadata, created_at
                FROM chat_messages
                WHERE session_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$sessionId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'session_id' => $sessionId,
                'messages' => $messages,
                'user' => [
                    'id' => $userInfo['id'],
                    'name' => $userInfo['full_name'],
                    'email' => $userInfo['email'],
                    'avatar' => $userInfo['avatar'] ? '/storage/avatars/' . $userInfo['avatar'] : null
                ],
                'mode' => 'logged_in'
            ]);
            break;

        case 'send':
            $input = json_decode(file_get_contents('php://input'), true);
            $message = trim($input['message'] ?? '');
            $sessionId = (int)($input['session_id'] ?? 0);

            error_log("📨 [Send] sessionId=$sessionId, userId=$userId, message=" . substr($message, 0, 50));

            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Message required']);
                exit;
            }

            if (!$sessionId) {
                error_log("❌ [Send] No sessionId!");
                echo json_encode(['success' => false, 'error' => 'Session not initialized']);
                exit;
            }

            // Verify session belongs to user
            $stmt = $pdo->prepare("SELECT user_id FROM chat_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $sessionOwner = $stmt->fetchColumn();

            if ($sessionOwner != $userId) {
                error_log("❌ [Send] Session $sessionId does not belong to user $userId");
                echo json_encode(['success' => false, 'error' => 'Invalid session']);
                exit;
            }

            // Save customer message
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO chat_messages (session_id, sender_type, message, created_at)
                    VALUES (?, 'customer', ?, NOW())
                ");
                $result = $stmt->execute([$sessionId, $message]);
                error_log("✅ [Send] Customer message saved: " . ($result ? 'yes' : 'no') . " (ID: " . $pdo->lastInsertId() . ")");
            } catch (PDOException $e) {
                error_log("❌ [Send] DB Error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Database error']);
                exit;
            }

            // Call Rasa
            $rasaResponse = callRasa($message, $userId);

            $aiMessage = $rasaResponse['text'] ?? 'Xin lỗi, tôi không hiểu.';
            $metadata = isset($rasaResponse['metadata']) ? json_encode($rasaResponse['metadata']) : null;

            // Save AI response
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO chat_messages (session_id, sender_type, message, metadata, created_at)
                    VALUES (?, 'ai', ?, ?, NOW())
                ");
                $result = $stmt->execute([$sessionId, $aiMessage, $metadata]);
                error_log("✅ [Send] AI message saved: " . ($result ? 'yes' : 'no') . " (ID: " . $pdo->lastInsertId() . ")");
            } catch (PDOException $e) {
                error_log("❌ [Send] DB Error (AI): " . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'response' => $aiMessage,
                'metadata' => $rasaResponse['metadata'] ?? null,
                'sender_type' => 'ai'
            ]);
            break;

        case 'clear':
            // Clear all sessions for user (on logout)
            $stmt = $pdo->prepare("UPDATE chat_sessions SET status = 'closed' WHERE user_id = ?");
            $stmt->execute([$userId]);

            echo json_encode(['success' => true, 'message' => 'Chat cleared']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("❌ [Chat API] Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
