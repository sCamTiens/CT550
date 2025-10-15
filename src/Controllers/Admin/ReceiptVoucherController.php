<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ReceiptVoucherRepository;
use App\Models\Entities\ReceiptVoucher;

class ReceiptVoucherController extends BaseAdminController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new ReceiptVoucherRepository();
    }

    /** GET /admin/receipt_vouchers (trả về view) */
    public function index()
    {
        return $this->view('admin/receipts/receipt');
    }

    /** GET /admin/api/receipt_vouchers (list) */
    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/receipt_vouchers (create) */
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
            echo json_encode([
                'error' => 'Lỗi máy chủ khi tạo phiếu thu'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/receipt_vouchers/{id} */
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
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi máy chủ khi cập nhật phiếu thu'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/receipt_vouchers/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->delete($id);
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

    /**
     * GET /admin/api/receipt_vouchers/next-code
     * Trả về mã phiếu thu tiếp theo
     */
    public function nextCode()
    {
        $code = $this->repo->getNextCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
