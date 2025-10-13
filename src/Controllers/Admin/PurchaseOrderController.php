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
        http_response_code(200);
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $_SESSION['user']['id'] ?? null;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            $id = $this->repo->createReceipt($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            // Không expose chi tiết lỗi ra ngoài để an toàn
            echo json_encode(['error' => 'Có lỗi xảy ra khi tạo phiếu nhập']);
            exit;
        }
    }

    // API: GET /admin/api/suppliers
    public function apiSuppliers()
    {
        $items = $this->supplierRepo->all();

        // Nếu repo trả về array thuần (FETCH_ASSOC) thì không cần map nữa
        if (!empty($items) && is_object($items[0] ?? null)) {
            $items = array_map(function ($s) {
                return method_exists($s, 'toArray') ? $s->toArray() : (array) $s;
            }, $items);
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
