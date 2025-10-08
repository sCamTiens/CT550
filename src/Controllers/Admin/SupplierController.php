<?php
namespace App\Controllers\Admin;


use App\Models\Repositories\SupplierRepository;

class SupplierController extends BaseAdminController
{
    private $supplierRepo;

    public function __construct()
    {
        parent::__construct();
        $this->supplierRepo = new SupplierRepository();
    }
    public function index()
    {
        return $this->view('admin/suppliers/supplier');
    }

    /** GET /admin/api/suppliers */
    public function apiIndex()
    {
        $items = $this->supplierRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/suppliers */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số']);
            exit;
        }
        $id = $this->supplierRepo->create($data, $currentUser);
        echo json_encode($this->supplierRepo->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** PUT /admin/suppliers/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số']);
            exit;
        }
        $this->supplierRepo->update($id, $data, $currentUser);
        echo json_encode($this->supplierRepo->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** DELETE /admin/suppliers/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->supplierRepo->delete($id);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi xoá', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // findOne now in SupplierRepository

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
