<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\StaffShiftScheduleRepository;
use App\Models\Repositories\StaffRepository;
use App\Models\Repositories\WorkShiftRepository;

class ScheduleController extends Controller
{
    private StaffShiftScheduleRepository $scheduleRepo;
    private StaffRepository $staffRepo;
    private WorkShiftRepository $shiftRepo;

    public function __construct()
    {
        $this->scheduleRepo = new StaffShiftScheduleRepository();
        $this->staffRepo = new StaffRepository();
        $this->shiftRepo = new WorkShiftRepository();
    }

    protected function currentUserId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function index()
    {
        return $this->view('admin/schedules/index');
    }

    public function apiList()
    {
        try {
            $filters = [
                'staff_id' => $_GET['staff_id'] ?? null,
                'shift_id' => $_GET['shift_id'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'status' => $_GET['status'] ?? null,
            ];

            $schedules = $this->scheduleRepo->getSchedules(array_filter($filters));
            
            // Convert objects to array để tránh lỗi JSON
            $data = array_map(function($schedule) {
                return (array) $schedule;
            }, $schedules);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['schedules' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function apiByDate()
    {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $schedules = $this->scheduleRepo->getByDate($date);
            
            $data = array_map(function($schedule) {
                return (array) $schedule;
            }, $schedules);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['schedules' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function apiStaffList()
    {
        try {
            $staff = $this->staffRepo->all();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['staff' => $staff], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function apiShiftList()
    {
        try {
            $shifts = $this->shiftRepo->all();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['shifts' => $shifts], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function create()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $id = $this->scheduleRepo->create(
                (int) $input['staff_id'],
                (int) $input['shift_id'],
                $input['work_date'],
                $input['status'] ?? 'Làm việc',
                $input['note'] ?? null,
                $this->currentUserId()
            );

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['id' => $id, 'message' => 'Tạo lịch thành công'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function bulkCreate()
    {
        $rawInput = file_get_contents('php://input');
        
        try {
            error_log('===== BULK CREATE DEBUG =====');
            error_log('Raw input: ' . $rawInput);
            
            $input = json_decode($rawInput, true);
            error_log('Decoded input: ' . print_r($input, true));
            
            $schedules = $input['schedules'] ?? [];
            error_log('Schedules count: ' . count($schedules));
            error_log('First schedule: ' . print_r($schedules[0] ?? null, true));
            
            $count = $this->scheduleRepo->bulkCreate($schedules, $this->currentUserId());
            error_log('Created count: ' . $count);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['created' => $count, 'message' => "Đã tạo $count lịch"], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            error_log('EXCEPTION in bulkCreate: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function copyWeek()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $count = $this->scheduleRepo->copyWeekSchedule(
                $input['from_date'],
                $input['to_date'],
                $this->currentUserId()
            );

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['copied' => $count, 'message' => "Đã sao chép $count lịch"], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function update($id)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $ok = $this->scheduleRepo->update((int) $id, [
                'status' => $input['status'] ?? null,
                'note' => $input['note'] ?? null,
                'updated_by' => $this->currentUserId()
            ]);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $ok, 'message' => 'Cập nhật thành công'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function delete($id)
    {
        try {
            $ok = $this->scheduleRepo->delete((int) $id);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $ok, 'message' => 'Xóa thành công'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function monthlyStats()
    {
        try {
            $month = (int) ($_GET['month'] ?? date('n'));
            $year = (int) ($_GET['year'] ?? date('Y'));
            
            $stats = $this->scheduleRepo->getMonthlyStats($month, $year);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}