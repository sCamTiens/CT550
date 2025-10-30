<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ImportHistoryRepository;

class ImportHistoryController extends BaseAdminController
{
    private $importHistoryRepo;

    public function __construct()
    {
        parent::__construct();
        $this->importHistoryRepo = new ImportHistoryRepository();
    }

    /** GET /admin/import-history (view) */
    public function index()
    {
        return $this->view('admin/import-history/index');
    }

    /** GET /admin/api/import-history (list JSON) */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        $rows = $this->importHistoryRepo->all();
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/import-history/{id} (detail) */
    public function apiDetail($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $item = $this->importHistoryRepo->find($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy lịch sử'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** DELETE /admin/api/import-history/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->importHistoryRepo->delete($id);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Không thể xóa lịch sử'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
