<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\WorkShiftRepository;

class WorkShiftController extends BaseAdminController
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new WorkShiftRepository();
    }

    /**
     * Giao diện quản lý ca làm việc
     * GET /admin/work-shifts
     */
    public function index()
    {
        return $this->view('admin/work-shifts/index');
    }

    /**
     * API: Danh sách ca làm việc
     * GET /admin/api/work-shifts
     */
    public function apiIndex()
    {
        try {
            $items = $this->repo->all();
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Tạo mới ca làm việc
     * POST /admin/api/work-shifts
     */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate
        if (empty($data['name'])) {
            return $this->json(['error' => 'Tên ca không được để trống'], 400);
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            return $this->json(['error' => 'Giờ bắt đầu và kết thúc không được để trống'], 400);
        }

        try {
            $data['created_by'] = $this->currentUserId();
            $result = $this->repo->create($data);
            $this->json(['message' => 'Tạo ca làm việc thành công', 'data' => $result]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Cập nhật ca làm việc
     * PUT /admin/api/work-shifts/{id}
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate
        if (empty($data['name'])) {
            return $this->json(['error' => 'Tên ca không được để trống'], 400);
        }

        try {
            $data['updated_by'] = $this->currentUserId();
            $result = $this->repo->update($id, $data);
            $this->json(['message' => 'Cập nhật thành công', 'data' => $result]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xóa ca làm việc
     * DELETE /admin/api/work-shifts/{id}
     */
    public function delete($id)
    {
        try {
            $this->repo->delete($id);
            $this->json(['message' => 'Xóa thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
