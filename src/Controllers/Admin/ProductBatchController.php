<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ProductBatchRepository;
use App\Controllers\Admin\AuthController;
use App\Models\Repositories\ProductRepository;

class ProductBatchController extends BaseAdminController
{
    private ProductBatchRepository $repo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new ProductBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    // GET /admin/product-batches (view)
    public function index()
    {
        $items = $this->repo->all();
        $products = $this->productRepo->all();
        return $this->view('admin/product-batches/product-batches', ['items' => $items, 'products' => $products]);
    }

    // GET /admin/api/product-batches
    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST /admin/api/product-batches
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        try {
            $id = $this->repo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->repo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi khi tạo lô sản phẩm']);
            exit;
        }
    }

    // PUT /admin/api/product-batches/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        try {
            $this->repo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->repo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi khi cập nhật lô']);
            exit;
        }
    }

    // DELETE /admin/api/product-batches/{id}
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->delete($id); // soft-delete (archive)
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // POST /admin/api/product-batches/{id}/restore
    public function restore($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->restore($id);
            echo json_encode(['ok' => true, 'id' => $id]);
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
