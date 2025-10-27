<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\AuditLogRepository;
use App\Controllers\Admin\AuthController;

class AuditLogController extends BaseAdminController
{
    private $auditRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->requireAdmin(false); // Chỉ admin mới được truy cập (false = không phải API)
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Kiểm tra quyền admin (role_id = 2 VÀ staff_role = 'Admin')
     * Nếu chưa đăng nhập hoặc không phải admin -> chuyển về trang login
     */
    private function requireAdmin(bool $forApi = false): void
    {
        if (!isset($_SESSION['user'])) {
            if ($forApi) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            } else {
                header('Location: /admin/login');
                exit;
            }
        }

        $user = $_SESSION['user'];
        $isAdmin = (
            isset($user['role_id'], $user['staff_role']) &&
            $user['role_id'] == 2 &&
            $user['staff_role'] === 'Admin'
        );

        if (!$isAdmin) {
            if ($forApi) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            } else {
                header('Location: /admin/login');
                exit;
            }
        }
    }

    /** GET /admin/audit-logs (view) */
    public function index()
    {
        echo $this->view('admin/audit-logs/audit-log');
    }

    /** GET /admin/api/audit-logs (list with filters) */
    public function apiIndex()
    {
        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'actor_type' => $_GET['actor_type'] ?? null,
                'entity_type' => $_GET['entity_type'] ?? null,
                'action' => $_GET['action'] ?? null,
                'from_date' => $_GET['from_date'] ?? null,
                'to_date' => $_GET['to_date'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];

            // Convert date format from dd/mm/yyyy to yyyy-mm-dd
            if (!empty($filters['from_date']) && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $filters['from_date'], $m)) {
                $filters['from_date'] = "{$m[3]}-{$m[2]}-{$m[1]}";
            }
            if (!empty($filters['to_date']) && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $filters['to_date'], $m)) {
                $filters['to_date'] = "{$m[3]}-{$m[2]}-{$m[1]}";
            }

            // Lọc bỏ các filter null
            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

            $items = $this->auditRepo->all($filters);

            // Parse JSON data để hiển thị
            foreach ($items as &$item) {
                $item['before_data'] = $item['before_data'] ? json_decode($item['before_data'], true) : null;
                $item['after_data'] = $item['after_data'] ? json_decode($item['after_data'], true) : null;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'items' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** GET /admin/api/audit-logs/entity/{type}/{id} */
    public function apiGetByEntity(string $type, int $id)
    {
        $items = $this->auditRepo->getByEntity($type, $id);

        // Parse JSON data
        foreach ($items as &$item) {
            $item['before_data'] = $item['before_data'] ? json_decode($item['before_data'], true) : null;
            $item['after_data'] = $item['after_data'] ? json_decode($item['after_data'], true) : null;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/audit-logs/stats/action */
    public function apiStatsByAction()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $stats = $this->auditRepo->statsByAction($fromDate, $toDate);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/audit-logs/stats/entity */
    public function apiStatsByEntity()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $stats = $this->auditRepo->statsByEntity($fromDate, $toDate);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/audit-logs/stats/staff */
    public function apiStatsByStaff()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        $stats = $this->auditRepo->statsByStaff($fromDate, $toDate, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/audit-logs/stats/customer */
    public function apiStatsByCustomer()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        $stats = $this->auditRepo->statsByCustomer($fromDate, $toDate, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/audit-logs/staff-list */
    public function apiGetStaffList()
    {
        $staff = $this->auditRepo->getActiveStaff();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['staff' => $staff], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
