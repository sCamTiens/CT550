<?php
namespace App\Controllers;

use App\Models\Repositories\AttendanceRepository;
use App\Models\Repositories\WorkShiftRepository;
use App\Core\Controller;

class EmployeeAttendanceController extends Controller
{
    protected $repo;
    protected $shiftRepo;

    public function __construct()
    {
        $this->repo = new AttendanceRepository();
        $this->shiftRepo = new WorkShiftRepository();
    }

    /**
     * Trang chấm công cho nhân viên
     * GET /employee/attendance
     */
    public function index()
    {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        return $this->view('employee/attendance', [
            'user' => $_SESSION['user']
        ]);
    }

    /**
     * API: Lấy danh sách ca làm việc
     * GET /employee/api/work-shifts
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
     * API: Lấy chấm công hôm nay
     * GET /employee/api/attendance/today
     */
    public function apiToday()
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                return $this->json(['error' => 'Chưa đăng nhập'], 401);
            }

            $attendances = $this->repo->getTodayAttendances($userId);
            $this->json(['attendances' => $attendances]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Chấm công vào ca
     * POST /employee/api/attendance/check-in
     */
    public function checkIn()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            return $this->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $shiftId = $data['shift_id'] ?? null;
        $date = date('Y-m-d');
        
        if (!$shiftId) {
            return $this->json(['error' => 'Thiếu thông tin ca làm việc'], 400);
        }

        try {
            $result = $this->repo->checkIn($userId, $shiftId, $date);
            
            if ($result === false) {
                return $this->json(['error' => 'Bạn đã chấm công ca này rồi'], 400);
            }
            
            $this->json(['message' => 'Chấm công thành công', 'data' => $result]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Chấm công ra ca
     * POST /employee/api/attendance/check-out
     */
    public function checkOut()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            return $this->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $attendanceId = $data['attendance_id'] ?? null;
        
        if (!$attendanceId) {
            return $this->json(['error' => 'Thiếu thông tin'], 400);
        }

        try {
            $result = $this->repo->checkOut($attendanceId);
            $this->json(['message' => 'Chấm công ra ca thành công', 'data' => $result]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy lịch sử chấm công của nhân viên
     * GET /employee/api/attendance/history?month=X&year=Y
     */
    public function apiHistory()
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                return $this->json(['error' => 'Chưa đăng nhập'], 401);
            }

            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            
            $items = $this->repo->getByUserAndMonth($userId, (int)$month, (int)$year);
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
