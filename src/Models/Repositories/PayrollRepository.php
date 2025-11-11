<?php
namespace App\Models\Repositories;

use App\Core\DB;
use PDO;

class PayrollRepository
{
    protected $table = 'payrolls';

    /**
     * Lấy tất cả bảng lương theo tháng/năm
     */
    public function getByMonth(int $month, int $year): array
    {
        $sql = "SELECT 
                    p.*,
                    u.full_name,
                    u.username,
                    sp.staff_role,
                    cb.full_name as created_by_name,
                    ab.full_name as approved_by_name
                FROM {$this->table} p
                INNER JOIN users u ON p.user_id = u.id
                LEFT JOIN staff_profiles sp ON p.user_id = sp.user_id
                LEFT JOIN users cb ON p.created_by = cb.id
                LEFT JOIN users ab ON p.approved_by = ab.id
                WHERE p.month = ? AND p.year = ?
                ORDER BY p.total_salary DESC";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy bảng lương của 1 nhân viên
     */
    public function getByUserAndMonth(int $userId, int $month, int $year): array|false
    {
        $sql = "SELECT 
                    p.*,
                    u.full_name,
                    u.username
                FROM {$this->table} p
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ? AND p.month = ? AND p.year = ?";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $month, $year]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm theo ID
     */
    public function find(int $id): array|false
    {
        $sql = "SELECT 
                    p.*,
                    u.full_name,
                    u.username,
                    sp.staff_role,
                    sp.base_salary as staff_base_salary,
                    sp.salary_type,
                    sp.required_shifts_per_month
                FROM {$this->table} p
                INNER JOIN users u ON p.user_id = u.id
                LEFT JOIN staff_profiles sp ON p.user_id = sp.user_id
                WHERE p.id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tính lương cho nhân viên theo tháng
     */
    public function calculatePayroll(int $userId, int $month, int $year, int $createdBy): array
    {
        DB::pdo()->beginTransaction();
        
        try {
            // Lấy thông tin nhân viên
            $staffRepo = new StaffRepository();
            $staff = $staffRepo->find($userId);
            
            if (!$staff) {
                throw new \Exception('Không tìm thấy nhân viên');
            }

            // Lấy thông tin lương từ staff_profiles
            $sql = "SELECT base_salary, salary_type, required_shifts_per_month, wage_per_shift 
                    FROM staff_profiles WHERE user_id = ?";
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute([$userId]);
            $salaryInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $baseSalary = $salaryInfo['base_salary'] ?? 0;
            $salaryType = $salaryInfo['salary_type'] ?? 'Theo ca';
            $requiredShifts = $salaryInfo['required_shifts_per_month'] ?? 28;
            
            // Tự động tính wage_per_shift nếu là "Theo ca"
            if ($salaryType === 'Theo ca') {
                $wagePerShift = $requiredShifts > 0 ? ($baseSalary / $requiredShifts) : 0;
            } else {
                $wagePerShift = $salaryInfo['wage_per_shift'] ?? 0;
            }

            // Đếm số ca làm việc trong tháng
            $attRepo = new AttendanceRepository();
            $totalShiftsWorked = $attRepo->countShiftsByUserAndMonth($userId, $month, $year);

            // Tính lương thực tế ban đầu
            $actualSalary = 0;
            
            if ($salaryType === 'Theo tháng') {
                // Lương theo tháng: tính theo tỷ lệ số ca làm / số ca yêu cầu
                if ($totalShiftsWorked >= $requiredShifts) {
                    $actualSalary = $baseSalary;
                } else {
                    $actualSalary = ($baseSalary / $requiredShifts) * $totalShiftsWorked;
                }
            } else {
                // Lương theo ca: lấy wage_per_shift của nhân viên × số ca làm
                $actualSalary = $wagePerShift * $totalShiftsWorked;
            }

            // ==================== TÍNH PHẠT ĐI TRỄ/VỀ SỚM ====================
            $lateDeduction = $this->calculateLateDeduction($userId, $month, $year, $wagePerShift, $baseSalary, $salaryType);
            $actualSalary -= $lateDeduction; // Trừ lương do đi trễ/về sớm

            // Kiểm tra xem đã có bảng lương chưa
            $existing = $this->getByUserAndMonth($userId, $month, $year);
            
            if ($existing) {
                // Cập nhật
                $sql = "UPDATE {$this->table} SET
                        total_shifts_worked = ?,
                        required_shifts = ?,
                        base_salary = ?,
                        actual_salary = ?,
                        late_deduction = ?,
                        total_salary = actual_salary + bonus - deduction - late_deduction,
                        status = 'Nháp',
                        updated_by = ?
                        WHERE user_id = ? AND month = ? AND year = ?";
                $stmt = DB::pdo()->prepare($sql);
                $stmt->execute([
                    $totalShiftsWorked,
                    $requiredShifts,
                    $baseSalary,
                    $actualSalary,
                    $lateDeduction,
                    $createdBy,
                    $userId,
                    $month,
                    $year
                ]);
                
                $payroll = $this->getByUserAndMonth($userId, $month, $year);
            } else {
                // Tạo mới
                $totalSalary = $actualSalary;
                
                $sql = "INSERT INTO {$this->table} 
                        (user_id, month, year, total_shifts_worked, required_shifts, 
                         base_salary, actual_salary, late_deduction, total_salary, created_by, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Nháp')";
                $stmt = DB::pdo()->prepare($sql);
                $stmt->execute([
                    $userId,
                    $month,
                    $year,
                    $totalShiftsWorked,
                    $requiredShifts,
                    $baseSalary,
                    $actualSalary,
                    $lateDeduction,
                    $totalSalary,
                    $createdBy
                ]);
                
                $payroll = $this->getByUserAndMonth($userId, $month, $year);
            }

            DB::pdo()->commit();
            return $payroll;
            
        } catch (\Exception $e) {
            DB::pdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Tính phạt đi trễ/về sớm theo quy định
     * 
     * Quy định:
     * - 1-15 phút: Không trừ lương
     * - 16-30 phút: Trừ theo phút (lương 1 giờ / 60 × số phút trễ)
     * - >30 phút hoặc tổng giờ làm <7.5h: Trừ 50% lương ngày
     * - Liên tục 3 ngày trễ: Cảnh cáo và trừ thêm 10% lương ngày
     */
    private function calculateLateDeduction(int $userId, int $month, int $year, float $wagePerShift, float $baseSalary, string $salaryType): float
    {
        $totalDeduction = 0;
        
        // Lấy lương 1 giờ dựa vào loại lương
        $hourlyWage = 0;
        if ($salaryType === 'Theo ca') {
            $hourlyWage = $wagePerShift / 8; // 1 ca = 8 giờ
        } else {
            // Theo tháng: giả sử 1 tháng = 28 ca × 8h = 224h
            $hourlyWage = $baseSalary / 224;
        }
        
        // Lương 1 ngày (1 ca)
        $dailyWage = $hourlyWage * 8;

        // Lấy tất cả bản ghi chấm công trong tháng
        $sql = "SELECT 
                    a.*,
                    ws.start_time,
                    ws.end_time
                FROM attendances a
                INNER JOIN work_shifts ws ON a.shift_id = ws.id
                WHERE a.user_id = ?
                AND MONTH(a.attendance_date) = ?
                AND YEAR(a.attendance_date) = ?
                AND a.check_in_time IS NOT NULL
                ORDER BY a.attendance_date";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId, $month, $year]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $consecutiveLate = 0; // Đếm số ngày trễ liên tục
        $lastDate = null;

        foreach ($attendances as $att) {
            $checkInTime = new \DateTime($att['check_in_time']);
            $checkOutTime = $att['check_out_time'] ? new \DateTime($att['check_out_time']) : null;
            
            // Giờ bắt đầu và kết thúc ca theo quy định
            $shiftStart = new \DateTime($att['attendance_date'] . ' ' . $att['start_time']);
            $shiftEnd = new \DateTime($att['attendance_date'] . ' ' . $att['end_time']);
            
            // Tính số phút trễ khi check-in
            $lateMinutes = 0;
            if ($checkInTime > $shiftStart) {
                $lateMinutes = ($checkInTime->getTimestamp() - $shiftStart->getTimestamp()) / 60;
            }
            
            // Tính số phút về sớm khi check-out
            $earlyMinutes = 0;
            if ($checkOutTime && $checkOutTime < $shiftEnd) {
                $earlyMinutes = ($shiftEnd->getTimestamp() - $checkOutTime->getTimestamp()) / 60;
            }
            
            // Tổng số phút vi phạm
            $totalViolationMinutes = $lateMinutes + $earlyMinutes;
            
            // Tính tổng giờ làm việc thực tế
            $actualWorkHours = 0;
            if ($checkOutTime) {
                $actualWorkHours = ($checkOutTime->getTimestamp() - $checkInTime->getTimestamp()) / 3600;
            }
            
            $dayDeduction = 0;
            $isLateToday = false;

            // Quy tắc 1: >30 phút hoặc tổng giờ làm <7.5h → trừ 50% lương ngày
            if ($totalViolationMinutes > 30 || ($actualWorkHours > 0 && $actualWorkHours < 7.5)) {
                $dayDeduction = $dailyWage * 0.5;
                $isLateToday = true;
            }
            // Quy tắc 2: 16-30 phút → trừ theo phút
            elseif ($totalViolationMinutes >= 16 && $totalViolationMinutes <= 30) {
                $dayDeduction = ($hourlyWage / 60) * $totalViolationMinutes;
                $isLateToday = true;
            }
            // Quy tắc 3: 1-15 phút → không trừ (nhưng vẫn tính là trễ cho quy tắc liên tục)
            elseif ($totalViolationMinutes >= 1 && $totalViolationMinutes < 16) {
                $dayDeduction = 0;
                $isLateToday = true;
            }
            
            $totalDeduction += $dayDeduction;

            // Kiểm tra ngày trễ liên tục
            if ($isLateToday) {
                $currentDate = new \DateTime($att['attendance_date']);
                
                // Nếu là ngày liên tiếp (hoặc ngày đầu tiên)
                if ($lastDate === null || $currentDate->diff($lastDate)->days == 1) {
                    $consecutiveLate++;
                } else {
                    $consecutiveLate = 1; // Reset về 1
                }
                
                // Quy tắc 4: Liên tục 3 ngày trễ → cảnh cáo và trừ thêm 10% lương ngày
                if ($consecutiveLate == 3) {
                    $totalDeduction += $dailyWage * 0.1;
                    // Log cảnh cáo (có thể thêm vào bảng notifications hoặc logs)
                }
                
                $lastDate = $currentDate;
            } else {
                $consecutiveLate = 0;
                $lastDate = null;
            }
        }

        return round($totalDeduction, 2);
    }

    /**
     * Cập nhật thưởng/phạt
     */
    public function updateBonusDeduction(int $id, float $bonus, float $deduction, int $updatedBy): bool
    {
        $sql = "UPDATE {$this->table} SET
                bonus = ?,
                deduction = ?,
                total_salary = actual_salary + ? - ? - COALESCE(late_deduction, 0),
                updated_by = ?
                WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$bonus, $deduction, $bonus, $deduction, $updatedBy, $id]);
    }

    /**
     * Phê duyệt bảng lương
     */
    public function approve(int $id, int $approvedBy): bool
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'Đã duyệt', approved_by = ?, approved_at = NOW() 
                WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$approvedBy, $id]);
    }

    /**
     * Đánh dấu đã trả lương
     */
    public function markAsPaid(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'Đã trả', paid_at = NOW() WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Xóa bảng lương
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Lấy lịch sử thay đổi lương của nhân viên
     */
    public function getSalaryHistory(int $userId): array
    {
        $sql = "SELECT 
                    sh.id,
                    sh.user_id,
                    sh.salary,
                    sh.from_date,
                    sh.to_date,
                    sh.note,
                    sh.created_at,
                    u.full_name,
                    u.username
                FROM salary_history sh
                INNER JOIN users u ON sh.user_id = u.id
                WHERE sh.user_id = ?
                ORDER BY sh.from_date DESC";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả bảng lương
     */
    public function all(): array
    {
        $sql = "SELECT 
                    p.*,
                    u.full_name,
                    u.username,
                    sp.staff_role
                FROM {$this->table} p
                INNER JOIN users u ON p.user_id = u.id
                LEFT JOIN staff_profiles sp ON p.user_id = sp.user_id
                ORDER BY p.year DESC, p.month DESC, p.total_salary DESC";
        
        return DB::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
