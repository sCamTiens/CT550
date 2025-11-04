<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PayrollRepository;
use App\Models\Repositories\StaffRepository;

class PayrollController extends BaseAdminController
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new PayrollRepository();
    }

    /**
     * Giao diện quản lý bảng lương
     * GET /admin/payroll
     */
    public function index()
    {
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        
        return $this->view('admin/payroll/payroll', [
            'month' => $month,
            'year' => $year
        ]);
    }

    /**
     * API: Lấy danh sách bảng lương theo tháng
     * GET /admin/api/payroll?month=X&year=Y
     */
    public function apiIndex()
    {
        try {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            
            $items = $this->repo->getByMonth((int)$month, (int)$year);
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Tính lương cho tất cả nhân viên trong tháng
     * POST /admin/api/payroll/calculate
     */
    public function calculate()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $month = $data['month'] ?? date('n');
        $year = $data['year'] ?? date('Y');
        $createdBy = $this->currentUserId();

        try {
            $staffRepo = new StaffRepository();
            $staffs = $staffRepo->all();
            
            $results = [];
            foreach ($staffs as $staff) {
                $payroll = $this->repo->calculatePayroll(
                    $staff['user_id'], 
                    (int)$month, 
                    (int)$year, 
                    $createdBy
                );
                $results[] = $payroll;
            }
            
            $this->json([
                'message' => 'Tính lương thành công cho ' . count($results) . ' nhân viên',
                'data' => $results
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Tính lương cho một nhân viên
     * POST /admin/api/payroll/calculate/{userId}
     */
    public function calculateOne($userId)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $month = $data['month'] ?? date('n');
        $year = $data['year'] ?? date('Y');
        $createdBy = $this->currentUserId();

        try {
            $payroll = $this->repo->calculatePayroll(
                (int)$userId, 
                (int)$month, 
                (int)$year, 
                $createdBy
            );
            
            $this->json([
                'message' => 'Tính lương thành công',
                'data' => $payroll
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Cập nhật thưởng/phạt
     * PUT /admin/api/payroll/{id}/bonus-deduction
     */
    public function updateBonusDeduction($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $bonus = $data['bonus'] ?? 0;
        $deduction = $data['deduction'] ?? 0;
        $updatedBy = $this->currentUserId();

        try {
            $this->repo->updateBonusDeduction($id, $bonus, $deduction, $updatedBy);
            $this->json(['message' => 'Cập nhật thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Phê duyệt bảng lương
     * POST /admin/api/payroll/{id}/approve
     */
    public function approve($id)
    {
        try {
            $approvedBy = $this->currentUserId();
            $this->repo->approve($id, $approvedBy);
            $this->json(['message' => 'Phê duyệt thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Đánh dấu đã trả lương
     * POST /admin/api/payroll/{id}/mark-paid
     */
    public function markAsPaid($id)
    {
        try {
            $this->repo->markAsPaid($id);
            $this->json(['message' => 'Đã đánh dấu đã trả lương']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xóa bảng lương
     * DELETE /admin/api/payroll/{id}
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
