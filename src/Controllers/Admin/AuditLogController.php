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
        $this->requireAdmin(); // Chỉ admin mới được truy cập
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Kiểm tra quyền admin (role_id = 2 VÀ staff_role = 'Admin')
     */
    private function requireAdmin(): void
    {
        $isAdmin = false;
        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 2) {
            if (isset($_SESSION['user']['staff_role']) && $_SESSION['user']['staff_role'] === 'Admin') {
                $isAdmin = true;
            }
        }

        if (!$isAdmin) {
            http_response_code(403);
            echo '403 Forbidden - Chỉ Admin mới được truy cập trang này';
            exit;
        }
    }

    /** GET /admin/audit-logs (view) */
    public function index()
    {
        return $this->view('admin/audit-logs/audit-log');
    }

    /** GET /admin/api/audit-logs (list with filters) */
    public function apiIndex()
    {
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'action' => $_GET['action'] ?? null,
            'from_date' => $_GET['from_date'] ?? null,
            'to_date' => $_GET['to_date'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

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

    /** GET /admin/api/audit-logs/stats/user */
    public function apiStatsByUser()
    {
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        $stats = $this->auditRepo->statsByUser($fromDate, $toDate, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
