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
            $wagePerShift = $salaryInfo['wage_per_shift'] ?? 0;

            // Đếm số ca làm việc trong tháng
            $attRepo = new AttendanceRepository();
            $totalShiftsWorked = $attRepo->countShiftsByUserAndMonth($userId, $month, $year);

            // Tính lương thực tế
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

            // Kiểm tra xem đã có bảng lương chưa
            $existing = $this->getByUserAndMonth($userId, $month, $year);
            
            if ($existing) {
                // Cập nhật
                $sql = "UPDATE {$this->table} SET
                        total_shifts_worked = ?,
                        required_shifts = ?,
                        base_salary = ?,
                        actual_salary = ?,
                        total_salary = actual_salary + bonus - deduction,
                        status = 'Nháp',
                        updated_by = ?
                        WHERE user_id = ? AND month = ? AND year = ?";
                $stmt = DB::pdo()->prepare($sql);
                $stmt->execute([
                    $totalShiftsWorked,
                    $requiredShifts,
                    $baseSalary,
                    $actualSalary,
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
                         base_salary, actual_salary, total_salary, created_by, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Nháp')";
                $stmt = DB::pdo()->prepare($sql);
                $stmt->execute([
                    $userId,
                    $month,
                    $year,
                    $totalShiftsWorked,
                    $requiredShifts,
                    $baseSalary,
                    $actualSalary,
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
     * Cập nhật thưởng/phạt
     */
    public function updateBonusDeduction(int $id, float $bonus, float $deduction, int $updatedBy): bool
    {
        $sql = "UPDATE {$this->table} SET
                bonus = ?,
                deduction = ?,
                total_salary = actual_salary + ? - ?,
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
