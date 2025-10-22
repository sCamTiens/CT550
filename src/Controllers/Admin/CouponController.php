<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\CouponRepository;
use App\Controllers\Admin\AuthController;

class CouponController extends BaseAdminController
{
    private $couponRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->couponRepo = new CouponRepository();
    }

    /** GET /admin/coupons (view) */
    public function index()
    {
        $items = $this->couponRepo->all();
        return $this->view('admin/coupons/coupon', ['items' => $items]);
    }

    /** GET /admin/api/coupons (list) */
    public function apiIndex()
    {
        $items = $this->couponRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/coupons (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $id = $this->couponRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->couponRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            
            // Log chi tiết để debug
            error_log("Coupon create error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo json_encode([
                'error' => 'Lỗi máy chủ khi tạo mã giảm giá: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** PUT /admin/coupons/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $this->couponRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->couponRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật mã giảm giá: ' . $e->getMessage()]);
            exit;
        }
    }

    /** DELETE /admin/coupons/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->couponRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['admin_user']['id'] ?? $_SESSION['user']['id'] ?? null;
    }
}
