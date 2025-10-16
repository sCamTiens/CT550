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

    /** GET /admin/promotions (view) */
    public function index()
    {
        $items = $this->promotionRepo->all();
        return $this->view('admin/promotions/promotion', ['items' => $items]);
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

        try {
            $id = $this->promotionRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo chương trình khuyến mãi: ' . $e->getMessage()]);
            exit;
        }
    }

    /** PUT /admin/promotions/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $this->promotionRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật chương trình khuyến mãi: ' . $e->getMessage()]);
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
        return $_SESSION['user']['id'] ?? null;
    }
}
