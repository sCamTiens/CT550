<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PromotionRepository;
use App\Controllers\Admin\AuthController;

class PromotionController extends BaseAdminController
{
    private $promotionRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->promotionRepo = new PromotionRepository();
    }

    /** GET /admin/promotions (trả về view) */
    public function index()
    {
        return $this->view('admin/promotions/promotion');
    }

    /** GET /admin/api/promotions (list) */
    public function apiIndex()
    {
        $items = $this->promotionRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/promotions (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        // Validate user
        if ($currentUser === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Không xác định được người dùng. Vui lòng đăng nhập lại.',
                'debug' => [
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'session_user' => $_SESSION['user'] ?? 'not set',
                    'session_admin_user' => $_SESSION['admin_user'] ?? 'not set'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra user có tồn tại không
        $pdo = \App\Core\DB::pdo();
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $stmt->execute([$currentUser]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'ID người dùng không tồn tại trong hệ thống',
                'debug' => [
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'all_users_ids' => $pdo->query("SELECT id FROM users LIMIT 10")->fetchAll(\PDO::FETCH_COLUMN)
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $id = $this->promotionRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();

            // Debug: Log lỗi chi tiết
            error_log("PDO Error Code: " . $errorCode);
            error_log("PDO Error Message: " . $errorMsg);
            error_log("Current User ID: " . $currentUser);

            // Phân tích lỗi cụ thể
            $responseError = 'Lỗi máy chủ khi tạo chương trình khuyến mãi';
            $httpCode = 500;

            if ($errorCode === '23000') {
                // Kiểm tra chi tiết lỗi constraint
                if (stripos($errorMsg, 'Duplicate entry') !== false && stripos($errorMsg, "'name'") !== false) {
                    $responseError = 'Tên chương trình khuyến mãi đã tồn tại';
                    $httpCode = 409;
                } elseif (stripos($errorMsg, 'foreign key constraint') !== false) {
                    if (stripos($errorMsg, 'created_by') !== false || stripos($errorMsg, 'updated_by') !== false) {
                        $responseError = 'ID người dùng không hợp lệ. Vui lòng đăng nhập lại.';
                        $httpCode = 401;
                    } else {
                        $responseError = 'Sản phẩm hoặc danh mục không tồn tại';
                        $httpCode = 400;
                    }
                } elseif (stripos($errorMsg, 'cannot be null') !== false) {
                    $responseError = 'Thiếu thông tin bắt buộc';
                    $httpCode = 400;
                } else {
                    // Lỗi constraint khác
                    $responseError = 'Dữ liệu không hợp lệ: ' . $errorMsg;
                    $httpCode = 400;
                }
            }

            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $responseError,
                'debug' => [
                    'code' => $errorCode,
                    'message' => $errorMsg,
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'session_user' => $_SESSION['user'] ?? 'not set',
                    'session_admin_user' => $_SESSION['admin_user'] ?? 'not set'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi không xác định',
                'debug' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** PUT /admin/promotions/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        // Validate user
        if ($currentUser === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Không xác định được người dùng. Vui lòng đăng nhập lại.',
                'debug' => 'currentUser is null'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $this->promotionRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();

            error_log("PDO Update Error Code: " . $errorCode);
            error_log("PDO Update Error Message: " . $errorMsg);

            $responseError = 'Lỗi máy chủ khi cập nhật chương trình khuyến mãi';
            $httpCode = 500;

            if ($errorCode === '23000') {
                if (stripos($errorMsg, 'Duplicate entry') !== false && stripos($errorMsg, "'name'") !== false) {
                    $responseError = 'Tên chương trình khuyến mãi đã tồn tại';
                    $httpCode = 409;
                } elseif (stripos($errorMsg, 'foreign key constraint') !== false) {
                    $responseError = 'Sản phẩm hoặc danh mục không tồn tại';
                    $httpCode = 400;
                } else {
                    $responseError = 'Dữ liệu không hợp lệ: ' . $errorMsg;
                    $httpCode = 400;
                }
            }

            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $responseError,
                'debug' => [
                    'code' => $errorCode,
                    'message' => $errorMsg
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** DELETE /admin/promotions/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->promotionRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        // Debug session
        error_log("Session data: " . json_encode($_SESSION));
        
        $userId = $_SESSION['user']['id'] ?? null;
        
        if ($userId === null) {
            error_log("User ID is null - Session user: " . json_encode($_SESSION['user'] ?? 'not set'));
            // Nếu không có user trong session, thử lấy từ admin_user
            $userId = $_SESSION['admin_user']['id'] ?? null;
            if ($userId === null) {
                error_log("Admin user ID also null - Session admin_user: " . json_encode($_SESSION['admin_user'] ?? 'not set'));
            }
        }
        
        return $userId;
    }
}