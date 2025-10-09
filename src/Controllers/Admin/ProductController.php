<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ProductRepository;

use App\Controllers\Admin\AuthController;
class ProductController extends BaseAdminController
{
    private $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->productRepo = new ProductRepository();
    }
    /** GET /admin/products (trả về view) */
    public function index()
    {
        return $this->view('admin/products/product');
    }

    /** GET /admin/api/products (list) */
    public function apiIndex()
    {
        $items = $this->productRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $id = $this->productRepo->create($data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi tạo sản phẩm'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/products/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $this->productRepo->update($id, $data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi cập nhật sản phẩm'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/products/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->productRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // findOne now in ProductRepository

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-') ?: uniqid('sp-');
    }
}
