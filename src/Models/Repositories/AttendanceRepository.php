<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\EnvHelper;
use PDO;

class AttendanceRepository
{
    protected $table = 'attendances';

    /**
     * Lấy ca làm việc hôm nay của nhân viên (từ staff_shift_schedule)
     */
    public function getTodayScheduleForUser(int $userId, string $date): array
    {
        $sql = "SELECT 
                    sss.id as schedule_id,
                    sss.staff_id,
                    sss.shift_id,
                    sss.work_date,
                    sss.status as schedule_status,
                    ws.name as shift_name,
                    ws.start_time,
                    ws.end_time,
                    a.id as attendance_id,
                    a.check_in_time,
                    a.check_out_time,
                    a.check_in_status,
                    a.check_out_status,
                    a.status as attendance_status,
                    a.work_hours
                FROM staff_shift_schedule sss
                INNER JOIN work_shifts ws ON sss.shift_id = ws.id
                LEFT JOIN {$this->table} a ON a.user_id = sss.staff_id 
                    AND a.shift_id = sss.shift_id 
                    AND a.attendance_date = sss.work_date
                WHERE sss.staff_id = ? 
                    AND sss.work_date = ?
                    AND sss.status = 'Làm việc'
                    AND ws.is_active = 1
                ORDER BY ws.start_time";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Chấm công vào ca (phiên bản mới)
     */
    public function checkIn(int $userId, int $shiftId, string $date): array|false
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        // Lấy IP để lưu vào database (nhưng không kiểm tra)
        $clientIP = EnvHelper::getClientIP();

        // Kiểm tra lịch làm việc
        $sql = "SELECT sss.*, ws.start_time, ws.end_time, ws.name as shift_name
                FROM staff_shift_schedule sss
                INNER JOIN work_shifts ws ON sss.shift_id = ws.id
                WHERE sss.staff_id = ? 
                    AND sss.shift_id = ?
                    AND sss.work_date = ?
                    AND sss.status = 'Làm việc'";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $shiftId, $date]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            throw new \Exception('Bạn không có lịch làm việc ca này hôm nay');
        }

        // Xác định trạng thái check-in (cho phép check-in bất cứ lúc nào)
        $shiftStart = strtotime($schedule['start_time']);
        $shiftEnd = strtotime($schedule['end_time']);
        $currentTimestamp = strtotime($currentTime);
        
        // Chỉ kiểm tra xem ca đã kết thúc chưa (quá muộn)
        // Cho phép check-in đến 30 phút sau khi ca kết thúc
        $maxLateTime = $shiftEnd + 1800; // +30 phút
        if ($currentTimestamp > $maxLateTime) {
            throw new \Exception('Ca làm việc đã kết thúc lúc ' . $schedule['end_time'] . '. Không thể chấm công sau 30 phút.');
        }

        // Xác định trạng thái check-in
        // Cho phép muộn tối đa 15 phút
        $lateThreshold = $shiftStart + 900; // +15 phút
        
        if ($currentTimestamp > $lateThreshold) {
            $checkInStatus = 'Muộn';
        } else {
            $checkInStatus = 'Đúng giờ';
        }

        // Kiểm tra đã check-in chưa
        $sql = "SELECT id, check_in_time FROM {$this->table} 
                WHERE user_id = ? 
                    AND shift_id = ?
                    AND attendance_date = ?";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $shiftId, $date]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attendance && $attendance['check_in_time']) {
            throw new \Exception('Bạn đã check-in lúc ' . date('H:i', strtotime($attendance['check_in_time'])));
        }

        // Tạo hoặc cập nhật bản ghi chấm công
        if ($attendance) {
            // Cập nhật check-in
            $sql = "UPDATE {$this->table} 
                    SET check_in_time = NOW(),
                        check_in_status = ?,
                        check_in_ip = ?,
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute([
                $checkInStatus,
                $clientIP,
                $checkInStatus === 'Muộn' ? 'Đi muộn' : 'Có mặt',
                $attendance['id']
            ]);
            
            return $this->find($attendance['id']);
        } else {
            // Tạo mới
            $sql = "INSERT INTO {$this->table} 
                    (user_id, shift_id, attendance_date, check_in_time, check_in_status, check_in_ip, status, created_at)
                    VALUES 
                    (?, ?, ?, NOW(), ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute([
                $userId,
                $shiftId,
                $date,
                $checkInStatus,
                $clientIP,
                $checkInStatus === 'Muộn' ? 'Đi muộn' : 'Có mặt'
            ]);
            
            $id = DB::pdo()->lastInsertId();
            return $this->find($id);
        }
    }

    /**
     * Chấm công ra ca (phiên bản mới)
     */
    public function checkOutByShift(int $userId, int $shiftId, string $date): array|false
    {
        $currentTime = date('H:i:s');
        
        // Lấy IP để lưu vào database (nhưng không kiểm tra)
        $clientIP = EnvHelper::getClientIP();

        // Lấy thông tin ca và chấm công
        $sql = "SELECT a.*, ws.end_time, ws.start_time, ws.name as shift_name
                FROM {$this->table} a
                INNER JOIN work_shifts ws ON a.shift_id = ws.id
                WHERE a.user_id = ? 
                    AND a.shift_id = ?
                    AND a.attendance_date = ?";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $shiftId, $date]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attendance) {
            throw new \Exception('Bạn chưa check-in ca này');
        }

        if (!$attendance['check_in_time']) {
            throw new \Exception('Bạn chưa check-in ca này');
        }

        if ($attendance['check_out_time']) {
            throw new \Exception('Bạn đã check-out lúc ' . date('H:i', strtotime($attendance['check_out_time'])));
        }

        // Kiểm tra thời gian check-out (cho phép check-out bất cứ lúc nào sau khi check-in)
        $shiftStart = strtotime($attendance['start_time']);
        $shiftEnd = strtotime($attendance['end_time']);
        $currentTimestamp = strtotime($currentTime);

        // Cho phép check-out từ sau khi check-in đến cuối ca + 30 phút
        $allowedLate = $shiftEnd + 1800; // +30 phút sau ca
        if ($currentTimestamp > $allowedLate) {
            throw new \Exception('Đã quá muộn để check-out ca này (ca kết thúc lúc ' . $attendance['end_time'] . ')');
        }

        // Xác định trạng thái check-out
        if ($currentTimestamp < $shiftEnd) {
            $checkOutStatus = 'Sớm';
        } else {
            $checkOutStatus = 'Đúng giờ';
        }

        // Lấy thời gian hiện tại để check-out
        $now = new \DateTime();
        
        // Tính số giờ làm việc
        $checkInTime = new \DateTime($attendance['check_in_time']);
        $checkOutTime = $now;
        $diff = $checkInTime->diff($checkOutTime);
        $workHours = $diff->h + ($diff->i / 60); // Giờ + phút quy đổi
        $workHours = round($workHours, 2);
        
        // Đảm bảo work_hours không âm
        if ($workHours < 0 || $diff->invert) {
            $workHours = 0;
        }

        // Xác định trạng thái tổng thể
        $finalStatus = 'Có mặt';
        if ($attendance['check_in_status'] === 'Muộn' && $checkOutStatus === 'Sớm') {
            $finalStatus = 'Đi muộn';
        } elseif ($attendance['check_in_status'] === 'Muộn') {
            $finalStatus = 'Đi muộn';
        } elseif ($checkOutStatus === 'Sớm') {
            $finalStatus = 'Về sớm';
        }

        // Cập nhật check-out
        $sql = "UPDATE {$this->table} 
                SET check_out_time = NOW(),
                    check_out_status = ?,
                    check_out_ip = ?,
                    work_hours = ?,
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            $checkOutStatus,
            $clientIP,
            $workHours,
            $finalStatus,
            $attendance['id']
        ]);

        return $this->find($attendance['id']);
    }

    /**
     * Lấy tất cả chấm công theo tháng/năm
     */
    public function getByMonth(int $month, int $year): array
    {
        $sql = "SELECT 
                    a.*,
                    u.full_name,
                    u.username,
                    s.name as shift_name,
                    s.start_time,
                    s.end_time
                FROM {$this->table} a
                INNER JOIN users u ON a.user_id = u.id
                INNER JOIN work_shifts s ON a.shift_id = s.id
                WHERE MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
                ORDER BY a.attendance_date DESC, a.user_id, s.start_time";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chấm công của 1 nhân viên theo tháng/năm
     */
    public function getByUserAndMonth(int $userId, int $month, int $year): array
    {
        $sql = "SELECT 
                    a.*,
                    s.name as shift_name,
                    s.start_time,
                    s.end_time
                FROM {$this->table} a
                INNER JOIN work_shifts s ON a.shift_id = s.id
                WHERE a.user_id = ? 
                AND MONTH(a.attendance_date) = ? 
                AND YEAR(a.attendance_date) = ?
                ORDER BY a.attendance_date DESC, s.start_time";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra đã chấm công chưa
     */
    public function hasCheckedIn(int $userId, int $shiftId, string $date): bool
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE user_id = ? AND shift_id = ? AND attendance_date = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $shiftId, $date]);
        return $stmt->fetch() !== false;
    }

    /**
     * Chấm công ra ca (method cũ - dùng cho admin)
     */
    public function checkOut(int $attendanceId): array|false
    {
        $sql = "UPDATE {$this->table} SET check_out_time = NOW() WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$attendanceId]);
        
        // Fetch the updated attendance row and return it as an associative array
        $sql = "SELECT 
                    a.*,
                    s.name as shift_name,
                    s.start_time,
                    s.end_time
                FROM {$this->table} a
                INNER JOIN work_shifts s ON a.shift_id = s.id
                WHERE a.id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$attendanceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm attendance theo ID
     */
    public function find(int $id): array|false
    {
        $sql = "SELECT 
                    a.*,
                    s.name as shift_name,
                    s.start_time,
                    s.end_time
                FROM {$this->table} a
                INNER JOIN work_shifts s ON a.shift_id = s.id
                WHERE a.id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy attendance hôm nay của user
     */
    public function getTodayAttendances(int $userId): array
    {
        $sql = "SELECT 
                    a.*,
                    s.name as shift_name,
                    s.start_time,
                    s.end_time
                FROM {$this->table} a
                INNER JOIN work_shifts s ON a.shift_id = s.id
                WHERE a.user_id = ? AND a.attendance_date = CURDATE()
                ORDER BY s.start_time";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm số ca làm của nhân viên trong tháng
     */
    public function countShiftsByUserAndMonth(int $userId, int $month, int $year): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE user_id = ? 
                AND MONTH(attendance_date) = ? 
                AND YEAR(attendance_date) = ?
                AND check_in_time IS NOT NULL";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $month, $year]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Cập nhật trạng thái phê duyệt
     */
    public function approve(int $id, int $approvedBy): bool
    {
        $sql = "UPDATE {$this->table} 
                SET is_approved = 1, approved_by = ?, approved_at = NOW() 
                WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$approvedBy, $id]);
    }

    /**
     * Xóa chấm công
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Tạo bản ghi chấm công thủ công (dành cho Admin)
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, shift_id, attendance_date, check_in_time, check_out_time, 
                 check_in_status, check_out_status, work_hours, status, notes, created_at)
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['shift_id'] ?? null,
            $data['attendance_date'] ?? date('Y-m-d'),
            $data['check_in_time'] ?? null,
            $data['check_out_time'] ?? null,
            $data['check_in_status'] ?? 'Chưa chấm',
            $data['check_out_status'] ?? 'Chưa chấm',
            $data['work_hours'] ?? null,
            $data['status'] ?? 'Có mặt',
            $data['notes'] ?? null
        ]);
        
        return (int)DB::pdo()->lastInsertId();
    }

    /**
     * Cập nhật bản ghi chấm công (dành cho Admin)
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET check_in_time = ?,
                    check_out_time = ?,
                    check_in_status = ?,
                    check_out_status = ?,
                    work_hours = ?,
                    status = ?,
                    notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([
            $data['check_in_time'] ?? null,
            $data['check_out_time'] ?? null,
            $data['check_in_status'] ?? 'Chưa chấm',
            $data['check_out_status'] ?? 'Chưa chấm',
            $data['work_hours'] ?? null,
            $data['status'] ?? 'Có mặt',
            $data['notes'] ?? null,
            $id
        ]);
    }
}
