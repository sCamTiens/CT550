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
        // Load items để truyền vào view
        $items = $this->orderRepo->all();
        return $this->view('admin/orders/order', ['items' => $items]);
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
        // Log request data để debug
        $rawInput = file_get_contents('php://input');
        error_log("=== ORDER CREATE REQUEST ===");
        error_log("Raw input: " . $rawInput);

        $data = json_decode($rawInput, true) ?? [];
        error_log("Decoded data: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $currentUser = $this->currentUserId();
        error_log("Current user ID: " . $currentUser);

        try {
            $id = $this->orderRepo->create($data, $currentUser);
            error_log("Order created successfully with ID: " . $id);

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->orderRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            error_log("=== PDO EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi cơ sở dữ liệu',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            error_log("=== GENERAL EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật đơn hàng: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
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

    /** GET /admin/api/orders/{id}/items */
    public function getItems($id)
    {
        $items = $this->orderRepo->getOrderItems($id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/orders/{id}/print - In hóa đơn */
    public function print($id)
    {
        $orderObj = $this->orderRepo->findOne($id);
        if (!$orderObj) {
            http_response_code(404);
            echo "Đơn hàng không tồn tại";
            exit;
        }

        // Convert object sang array để sử dụng trong view
        $order = json_decode(json_encode($orderObj), true);
        $items = $this->orderRepo->getOrderItems($id);
        
        return $this->view('admin/orders/invoice-template', [
            'order' => $order,
            'items' => $items
        ]);
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}