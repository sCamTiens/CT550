<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\OrderRepository;
use App\Controllers\Admin\AuthController;

class OrderController extends BaseAdminController
{
    private $orderRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->orderRepo = new OrderRepository();
    }

    /** GET /admin/orders (trả về view) */
    public function index()
    {
        return $this->view('admin/orders/order');
    }

    /** GET /admin/api/orders (list) */
    public function apiIndex()
    {
        $items = $this->orderRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/orders/next-code */
    public function nextCode()
    {
        $code = $this->orderRepo->generateCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/orders (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $id = $this->orderRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->orderRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo đơn hàng: ' . $e->getMessage()]);
            exit;
        }
    }

    /** PUT /admin/orders/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $this->orderRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->orderRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật đơn hàng: ' . $e->getMessage()]);
            exit;
        }
    }

    /** DELETE /admin/orders/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->orderRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** GET /admin/api/orders/unpaid */
    public function unpaid()
    {
        $items = $this->orderRepo->unpaid();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
