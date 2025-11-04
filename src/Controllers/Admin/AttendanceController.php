<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\AttendanceRepository;
use App\Models\Repositories\WorkShiftRepository;
use App\Models\Repositories\StaffRepository;

class AttendanceController extends BaseAdminController
{
    protected $repo;
    protected $shiftRepo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new AttendanceRepository();
        $this->shiftRepo = new WorkShiftRepository();
    }

    /**
     * Giao diện quản lý chấm công
     * GET /admin/attendance
     */
    public function index()
    {
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        
        return $this->view('admin/attendance/attendance', [
            'month' => $month,
            'year' => $year
        ]);
    }

    /**
     * API: Lấy danh sách chấm công theo tháng
     * GET /admin/api/attendance?month=X&year=Y
     */
    public function apiList()
    {
        try {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            
            $items = $this->repo->getByMonth((int)$month, (int)$year);
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            // Log chi tiết lỗi để debug
            error_log('AttendanceController::apiList Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'items' => []
            ], 500);
        }
    }

    /**
     * API: Tạo bản ghi chấm công thủ công (cho Admin)
     * POST /admin/api/attendance
     */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $id = $this->repo->create($data);
            $this->json([
                'success' => true,
                'message' => 'Tạo bản ghi chấm công thành công',
                'id' => $id
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Cập nhật bản ghi chấm công
     * PUT /admin/api/attendance/{id}
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $this->repo->update($id, $data);
            $this->json([
                'success' => true,
                'message' => 'Cập nhật thành công'
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy danh sách ca làm việc
     * GET /admin/api/work-shifts
     */
    public function apiShifts()
    {
        try {
            $shifts = $this->shiftRepo->all();
            $this->json(['shifts' => $shifts]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy ca làm việc hôm nay của nhân viên hiện tại
     * GET /admin/api/attendance/today-shift
     */
    public function getTodayShift()
    {
        try {
            $userId = $this->currentUserId();
            if (!$userId) {
                return $this->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
            }

            $today = date('Y-m-d');
            
            // Lấy ca làm việc hôm nay từ bảng staff_shift_schedule
            $schedules = $this->repo->getTodayScheduleForUser($userId, $today);
            
            $this->json([
                'success' => true,
                'data' => $schedules,
                'current_time' => date('H:i:s')
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Chấm công vào ca
     * POST /admin/api/attendance/check-in
     */
    public function checkIn()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $userId = $this->currentUserId();
        $shiftId = $data['shift_id'] ?? null;
        
        if (!$userId) {
            return $this->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
        }
        
        if (!$shiftId) {
            return $this->json(['success' => false, 'message' => 'Thiếu thông tin ca làm việc'], 400);
        }

        try {
            $result = $this->repo->checkIn($userId, $shiftId, date('Y-m-d'));
            
            if ($result === false) {
                return $this->json(['success' => false, 'message' => 'Đã chấm công ca này rồi'], 400);
            }
            
            $this->json([
                'success' => true,
                'message' => 'Bạn đã chấm công lúc ' . date('H:i', strtotime($result['check_in_time'])) . ' – ' . ($result['shift_name'] ?? 'Ca làm việc'),
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Chấm công ra ca
     * POST /admin/api/attendance/check-out
     */
    public function checkOut()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $userId = $this->currentUserId();
        $shiftId = $data['shift_id'] ?? null;
        
        if (!$userId) {
            return $this->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
        }
        
        if (!$shiftId) {
            return $this->json(['success' => false, 'message' => 'Thiếu thông tin'], 400);
        }

        try {
            $result = $this->repo->checkOutByShift($userId, $shiftId, date('Y-m-d'));
            
            $this->json([
                'success' => true,
                'message' => 'Bạn đã check-out lúc ' . date('H:i', strtotime($result['check_out_time'])) . ' – ' . ($result['shift_name'] ?? 'Ca làm việc'),
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xóa chấm công
     * DELETE /admin/api/attendance/{id}
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

    /**
     * API: Phê duyệt chấm công
     * POST /admin/api/attendance/{id}/approve
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

    private function currentUserId(): ?int
    {
        // Thử lấy từ cả 2 session keys
        return $_SESSION['user']['id'] ?? $_SESSION['admin_user']['id'] ?? null;
    }
}
