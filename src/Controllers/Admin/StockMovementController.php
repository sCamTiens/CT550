<?php

namespace App\Controllers\Admin;

use App\Models\Repositories\StockMovementRepository;

class StockMovementController extends BaseAdminController
{
    private StockMovementRepository $repo;

    public function __construct()
    {
        $this->repo = new StockMovementRepository();
    }

    /**
     * GET /admin/stock-movements
     * Hiển thị trang danh sách lịch sử thay đổi tồn kho
     */
    public function index()
    {
        $this->requireAdmin();

        $items = $this->repo->all(1000);

        require_once __DIR__ . '/../../views/admin/stock-movements/stock-movements.php';
    }

    /**
     * GET /admin/api/stock-movements
     * API lấy danh sách movements
     */
    public function apiIndex()
    {
        $this->requireAdmin();

        $items = $this->repo->all(1000);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /admin/api/stock-movements/{id}
     * API lấy chi tiết một movement
     */
    public function show($id)
    {
        $this->requireAdmin();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID']);
            exit;
        }

        $item = $this->repo->findById($id);

        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
