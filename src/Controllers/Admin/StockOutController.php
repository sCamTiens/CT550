<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\StockOutRepository;
use App\Controllers\Admin\AuthController;

class StockOutController extends BaseAdminController
{
    private $stockOutRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->stockOutRepo = new StockOutRepository();
    }

    /** GET /admin/stock-outs (trả về view) */
    public function index()
    {
        return $this->view('admin/stock-outs/stock-out');
    }

    /** GET /admin/api/stock-outs (list) */
    public function apiIndex()
    {
        $items = $this->stockOutRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/stock-outs/next-code */
    public function nextCode()
    {
        $code = $this->stockOutRepo->generateCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stock-outs (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $id = $this->stockOutRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->stockOutRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo phiếu xuất kho: ' . $e->getMessage()]);
            exit;
        }
    }

    /** PUT /admin/api/stock-outs/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $this->stockOutRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->stockOutRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật phiếu xuất kho: ' . $e->getMessage()]);
            exit;
        }
    }

    /** DELETE /admin/api/stock-outs/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->stockOutRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** GET /admin/api/stock-outs/pending */
    public function pending()
    {
        $items = $this->stockOutRepo->pending();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stock-outs/{id}/approve */
    public function approve($id)
    {
        $currentUser = $this->currentUserId();
        try {
            $this->stockOutRepo->approve($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /admin/api/stock-outs/{id}/complete */
    public function complete($id)
    {
        $currentUser = $this->currentUserId();
        try {
            $this->stockOutRepo->complete($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
