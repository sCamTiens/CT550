<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PurchaseOrderRepository;
use App\Models\Repositories\SupplierRepository;
use App\Models\Repositories\ProductRepository;

class PurchaseOrderController extends BaseAdminController
{
    private PurchaseOrderRepository $repo;
    private SupplierRepository $supplierRepo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new PurchaseOrderRepository();
        $this->supplierRepo = new SupplierRepository();
        $this->productRepo = new ProductRepository();
    }

    public function index()
    {
        return $this->view('admin/purchase-orders/purchase-orders');
    }

    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $_SESSION['user']['id'] ?? null;
        try {
            $id = $this->repo->createReceipt($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
