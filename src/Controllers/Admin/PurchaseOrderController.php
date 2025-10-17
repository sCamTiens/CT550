<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PurchaseOrderRepository;
use App\Models\Repositories\SupplierRepository;
use App\Models\Repositories\ProductRepository;
use App\Models\Repositories\ExpenseVoucherRepository;

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

    /**
     * GET /admin/api/purchase-orders/{id}
     * Lấy chi tiết phiếu nhập kèm các dòng sản phẩm
     */
    public function show($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $details = $this->repo->getDetailsWithLines($id);
        
        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($details, JSON_UNESCAPED_UNICODE);
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
            // Expose lỗi để debug
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi tạo phiếu nhập',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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

    public function update($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $_SESSION['user']['id'] ?? null;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            // Kiểm tra trạng thái thanh toán
            $po = $this->repo->findById($id);
            if (!$po) {
                http_response_code(404);
                echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
                exit;
            }

            // Không cho sửa nếu đã thanh toán một phần hoặc hết
            if ($po['payment_status'] == '0' || $po['payment_status'] == '2') {
                http_response_code(403);
                echo json_encode(['error' => 'Không thể sửa phiếu nhập đã thanh toán']);
                exit;
            }

            $this->repo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi cập nhật phiếu nhập',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function destroy($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $currentUser = $_SESSION['user']['id'] ?? null;
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            // Kiểm tra trạng thái thanh toán
            $po = $this->repo->findById($id);
            if (!$po) {
                http_response_code(404);
                echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
                exit;
            }

            // Không cho xóa nếu đã thanh toán một phần hoặc hết
            if ($po['payment_status'] == '0' || $po['payment_status'] == '2') {
                http_response_code(403);
                echo json_encode(['error' => 'Không thể xóa phiếu nhập đã thanh toán']);
                exit;
            }

            $this->repo->delete($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi xóa phiếu nhập',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET /admin/api/purchase_orders/unpaid
     * Trả về danh sách phiếu nhập chưa thanh toán hoặc thanh toán một phần
     */
    public function unpaid()
    {
        $repo = new ExpenseVoucherRepository();
        $items = $repo->getUnpaidPurchaseOrders();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
