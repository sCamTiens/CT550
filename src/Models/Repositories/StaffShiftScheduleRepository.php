<?php

namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\StaffShiftSchedule;
use PDO;

/**
 * Repository: Quản lý lịch làm việc nhân viên
 */
class StaffShiftScheduleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::pdo();
    }

    /**
     * Lấy danh sách lịch làm việc với filter
     * 
     * @param array $filters ['staff_id' => ?, 'shift_id' => ?, 'start_date' => ?, 'end_date' => ?, 'status' => ?]
     * @return array<StaffShiftSchedule>
     */
    public function getSchedules(array $filters = []): array
    {
        $sql = "SELECT 
                    s.*,
                    u.full_name as staff_name,
                    ws.name as shift_name,
                    ws.start_time,
                    ws.end_time
                FROM staff_shift_schedule s
                JOIN users u ON s.staff_id = u.id
                JOIN work_shifts ws ON s.shift_id = ws.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['staff_id'])) {
            $sql .= " AND s.staff_id = :staff_id";
            $params['staff_id'] = $filters['staff_id'];
        }

        if (!empty($filters['shift_id'])) {
            $sql .= " AND s.shift_id = :shift_id";
            $params['shift_id'] = $filters['shift_id'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND s.work_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND s.work_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY s.work_date DESC, s.shift_id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_CLASS, StaffShiftSchedule::class);
    }

    /**
     * Lấy lịch làm việc theo ngày
     * 
     * @param string $date Ngày (Y-m-d)
     * @return array<StaffShiftSchedule>
     */
    public function getByDate(string $date): array
    {
        $sql = "SELECT 
                    s.*,
                    u.full_name as staff_name,
                    ws.name as shift_name,
                    ws.start_time,
                    ws.end_time
                FROM staff_shift_schedule s
                JOIN users u ON s.staff_id = u.id
                JOIN work_shifts ws ON s.shift_id = ws.id
                WHERE s.work_date = :date
                ORDER BY s.shift_id, u.full_name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['date' => $date]);

        return $stmt->fetchAll(PDO::FETCH_CLASS, StaffShiftSchedule::class);
    }

    /**
     * Lấy lịch làm việc của nhân viên trong khoảng thời gian
     * 
     * @param int $staffId
     * @param string $startDate
     * @param string $endDate
     * @return array<StaffShiftSchedule>
     */
    public function getStaffSchedule(int $staffId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    s.*,
                    u.full_name as staff_name,
                    ws.name as shift_name,
                    ws.start_time,
                    ws.end_time
                FROM staff_shift_schedule s
                JOIN users u ON s.staff_id = u.id
                JOIN work_shifts ws ON s.shift_id = ws.id
                WHERE s.staff_id = :staff_id 
                AND s.work_date BETWEEN :start_date AND :end_date
                ORDER BY s.work_date, s.shift_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'staff_id' => $staffId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_CLASS, StaffShiftSchedule::class);
    }

    /**
     * Tạo lịch làm việc
     * 
     * @param int $staffId
     * @param int $shiftId
     * @param string $workDate
     * @param string $status
     * @param string|null $note
     * @param int $createdBy
     * @return int ID của schedule vừa tạo
     */
    public function create(
        int $staffId,
        int $shiftId,
        string $workDate,
        string $status = 'Làm việc',
        ?string $note = null,
        ?int $createdBy = null
    ): int {
        $sql = "INSERT INTO staff_shift_schedule 
                (staff_id, shift_id, work_date, status, note, created_by, updated_by) 
                VALUES 
                (:staff_id, :shift_id, :work_date, :status, :note, :created_by, :updated_by)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'staff_id' => $staffId,
            'shift_id' => $shiftId,
            'work_date' => $workDate,
            'status' => $status,
            'note' => $note,
            'created_by' => $createdBy,
            'updated_by' => $createdBy
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Cập nhật lịch làm việc
     * 
     * @param int $id
     * @param array $data ['status' => ?, 'note' => ?, 'updated_by' => ?]
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = ['id' => $id];

        if (isset($data['status'])) {
            $updates[] = "status = :status";
            $params['status'] = $data['status'];
        }

        if (isset($data['note'])) {
            $updates[] = "note = :note";
            $params['note'] = $data['note'];
        }

        if (isset($data['updated_by'])) {
            $updates[] = "updated_by = :updated_by";
            $params['updated_by'] = $data['updated_by'];
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE staff_shift_schedule SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Xóa lịch làm việc
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM staff_shift_schedule WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Sao chép lịch làm việc từ tuần này sang tuần khác
     * 
     * @param string $fromDate Ngày bắt đầu tuần nguồn
     * @param string $toDate Ngày bắt đầu tuần đích
     * @param int|null $createdBy
     * @return int Số lịch đã sao chép
     */
    public function copyWeekSchedule(string $fromDate, string $toDate, ?int $createdBy): int
    {
        // Lấy lịch tuần nguồn
        $fromEndDate = date('Y-m-d', strtotime($fromDate . ' +6 days'));
        $sourceSchedules = $this->getSchedules([
            'start_date' => $fromDate,
            'end_date' => $fromEndDate
        ]);

        $count = 0;
        foreach ($sourceSchedules as $schedule) {
            $daysDiff = (strtotime($schedule->work_date) - strtotime($fromDate)) / 86400;
            $newWorkDate = date('Y-m-d', strtotime($toDate . ' +' . $daysDiff . ' days'));

            try {
                $this->create(
                    $schedule->staff_id,
                    $schedule->shift_id,
                    $newWorkDate,
                    'Làm việc',
                    'Sao chép từ ' . $schedule->work_date,
                    $createdBy
                );
                $count++;
            } catch (\Exception $e) {
                // Skip nếu đã tồn tại
                continue;
            }
        }

        return $count;
    }

    /**
     * Tạo lịch làm việc hàng loạt
     * 
     * @param array $schedules [['staff_id' => ?, 'shift_id' => ?, 'work_date' => ?, 'status' => ?, 'note' => ?], ...]
     * @param int|null $createdBy
     * @return int Số lịch đã tạo
     */
    public function bulkCreate(array $schedules, ?int $createdBy): int
    {
        error_log('===== REPOSITORY bulkCreate =====');
        error_log('Schedules array count: ' . count($schedules));
        error_log('Created by user ID: ' . ($createdBy ?? 'NULL'));
        
        $count = 0;
        foreach ($schedules as $index => $schedule) {
            error_log("Processing schedule #$index: " . print_r($schedule, true));
            
            try {
                $id = $this->create(
                    $schedule['staff_id'],
                    $schedule['shift_id'],
                    $schedule['work_date'],
                    $schedule['status'] ?? 'Làm việc',
                    $schedule['note'] ?? null,
                    $createdBy
                );
                error_log("Successfully created schedule ID: $id");
                $count++;
            } catch (\Exception $e) {
                error_log("Failed to create schedule #$index: " . $e->getMessage());
                // Skip duplicates
                continue;
            }
        }
        
        error_log("Total created: $count");
        return $count;
    }

    /**
     * Lấy thống kê lịch làm việc theo nhân viên trong tháng
     * 
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMonthlyStats(int $month, int $year): array
    {
        $sql = "SELECT 
                    s.staff_id,
                    u.full_name as staff_name,
                    COUNT(CASE WHEN s.status = 'Làm việc' THEN 1 END) as scheduled_shifts,
                    COUNT(CASE WHEN s.status = 'Nghỉ' THEN 1 END) as day_off,
                    COUNT(CASE WHEN s.status = 'Có phép' THEN 1 END) as approved_leave,
                    COUNT(CASE WHEN s.status = 'Không phép' THEN 1 END) as unauthorized_leave
                FROM staff_shift_schedule s
                JOIN users u ON s.staff_id = u.id
                WHERE MONTH(s.work_date) = :month 
                AND YEAR(s.work_date) = :year
                GROUP BY s.staff_id, u.full_name
                ORDER BY u.full_name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['month' => $month, 'year' => $year]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
