<?php
namespace App\Models\Repositories;

use \App\Core\DB;
use App\Support\Auditable;
use PDO;
use PDOException;

class StaffRepository
{
    use Auditable;

    protected $userTable = 'users';
    protected $staffTable = 'staff_profiles';

    /** Chuyển đổi ngày từ định dạng d/m/Y sang Y-m-d */
    private function convertDateToMysql(?string $date): ?string
    {
        if (empty($date) || strtolower($date) === 'null') {
            return null;
        }
        
        // Nếu đã đúng định dạng Y-m-d hoặc Y-m-d H:i:s
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) {
            return $date;
        }
        
        // Chuyển từ d/m/Y sang Y-m-d
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        
        return null;
    }

    /** Đổi mật khẩu cho nhân viên */
    public function changePassword(int|string $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->userTable} SET password_hash = ? WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$hash, $userId]);
    }

    /** Tìm nhân viên theo username */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.username,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.is_active,
                    s.staff_role,
                    s.hired_at,
                    s.note,
                    u.gender,
                    u.date_of_birth
                FROM {$this->userTable} u
                LEFT JOIN {$this->staffTable} s ON s.user_id = u.id
                WHERE u.username = ? AND u.is_deleted = 0
                LIMIT 1";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /** Lấy toàn bộ danh sách nhân viên (chỉ nhân viên chưa bị xóa) */
    public function all(): array
    {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.username,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.is_active,
                    u.created_at,
                    u.updated_at,
                    cu.full_name AS created_by_name,
                    uu.full_name AS updated_by_name,
                    s.staff_role,
                    s.hired_at,
                    s.note,
                    s.base_salary,
                    u.avatar_url,
                    u.gender,
                    u.date_of_birth
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.role_id = 2 
                  AND u.is_deleted = 0 
                  AND u.username != 'admin'
                ORDER BY u.id DESC";
        return DB::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tìm nhân viên theo ID */
    public function find(int|string $id): array|false
    {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.username,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.is_active,
                    u.created_at,
                    u.updated_at,
                    cu.full_name AS created_by_name,
                    uu.full_name AS updated_by_name,
                    s.staff_role,
                    s.hired_at,
                    s.note,
                    s.base_salary,
                    u.avatar_url,
                    u.gender,
                    u.date_of_birth
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.id = ? 
                  AND u.role_id = 2 
                  AND u.is_deleted = 0
                  AND u.username != 'admin'";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Xử lý lỗi duplicate constraint → trả về thông báo tiếng Việt */
    private function mapDuplicateError(\PDOException $e): string|false
    {
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'username') || str_contains($msg, 'users.username')) {
            return 'Tên tài khoản đã tồn tại trong hệ thống';
        }
        if (str_contains($msg, 'email') || str_contains($msg, 'users.email')) {
            return 'Email đã tồn tại trong hệ thống';
        }
        if (str_contains($msg, 'phone') || str_contains($msg, 'users.phone')) {
            return 'Số điện thoại đã tồn tại trong hệ thống';
        }
        return false;
    }
    
    /** Kiểm tra email hoặc phone đã tồn tại chưa (trừ user hiện tại) */
    private function checkDuplicateContact(string|null $email, string|null $phone, int|string|null $excludeUserId = null): string|false
    {
        // Kiểm tra email
        if (!empty($email)) {
            $sql = "SELECT id FROM {$this->userTable} WHERE email = ? AND is_deleted = 0";
            $params = [$email];
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                return 'Email đã tồn tại trong hệ thống';
            }
        }
        
        // Kiểm tra phone
        if (!empty($phone)) {
            $sql = "SELECT id FROM {$this->userTable} WHERE phone = ? AND is_deleted = 0";
            $params = [$phone];
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                return 'Số điện thoại đã tồn tại trong hệ thống';
            }
        }
        
        return false;
    }

    /** Thêm nhân viên mới */
    public function create(array $data): array|string|false
    {
        try {
            DB::pdo()->beginTransaction();

            $username = trim($data['username'] ?? '');
            $fullName = trim($data['full_name'] ?? '');
            $email = $data['email'] ?? null;
            $phone = $data['phone'] ?? null;
            
            // Kiểm tra trùng email/phone trước khi insert
            if ($err = $this->checkDuplicateContact($email, $phone)) {
                DB::pdo()->rollBack();
                return $err;
            }

            // Use provided password or default
            if (!empty($data['password'])) {
                // If password is already hashed (starts with $2y$), use it directly
                if (strpos($data['password'], '$2y$') === 0) {
                    $passwordHash = $data['password'];
                } else {
                    // Otherwise, hash it
                    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
                }
            } else {
                $passwordHash = password_hash('123456', PASSWORD_BCRYPT);
            }

            $sqlUser = "INSERT INTO {$this->userTable}
                    (username, full_name, email, phone, password_hash, role_id, is_active, created_by, force_change_password, is_deleted, gender, date_of_birth)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtUser = DB::pdo()->prepare($sqlUser);
            $stmtUser->execute([
                $username,
                $fullName,
                $email,
                $phone,
                $passwordHash,
                2,
                $data['is_active'] ?? 1,
                $data['created_by'] ?? null,
                1,   // force_change_password = true
                0,    // is_deleted = false
                $data['gender'] ?? null,
                $data['date_of_birth'] ?? null
            ]);
            $userId = DB::pdo()->lastInsertId();

            $sqlStaff = "INSERT INTO {$this->staffTable}
                     (user_id, staff_role, hired_at, note, base_salary)
                     VALUES (?, ?, ?, ?, ?)";
            $stmtStaff = DB::pdo()->prepare($sqlStaff);

            $hiredAt = $this->convertDateToMysql($data['hired_at'] ?? null);
            $baseSalary = floatval($data['base_salary'] ?? 0);
            
            $stmtStaff->execute([
                $userId,
                $data['staff_role'] ?? null,
                $hiredAt,
                $data['note'] ?? null,
                $baseSalary
            ]);

            // Lưu lịch sử lương khởi điểm nếu có lương > 0
            if ($baseSalary > 0) {
                $this->saveSalaryHistory(
                    (int)$userId,
                    $baseSalary,
                    $hiredAt ?: date('Y-m-d'),
                    $data['created_by'] ?? null,
                    true  // isNew = true (đang tạo nhân viên mới)
                );
            }

            DB::pdo()->commit();
            
            $created = $this->find($userId);
            
            // Log audit
            if (is_array($created)) {
                $this->logCreate('staff', (int)$userId, [
                    'username' => $created['username'] ?? null,
                    'full_name' => $created['full_name'] ?? null,
                    'email' => $created['email'] ?? null,
                    'phone' => $created['phone'] ?? null,
                    'staff_role' => $created['staff_role'] ?? null,
                    'is_active' => $created['is_active'] ?? null
                ]);
            }
            
            return $created;
        } catch (PDOException $e) {
            DB::pdo()->rollBack();
            if ($err = $this->mapDuplicateError($e)) {
                return $err;
            }
            throw $e;
        }
    }

    /**
     * Lưu lịch sử thay đổi lương
     * @param int $userId ID nhân viên
     * @param float $newSalary Mức lương mới
     * @param string|null $fromDate Ngày áp dụng (chỉ dùng khi tạo mới nhân viên)
     * @param int|null $createdBy ID người thực hiện thay đổi
     * @param bool $isNew True nếu đang tạo nhân viên mới
     * @return bool
     */
    private function saveSalaryHistory(int $userId, float $newSalary, ?string $fromDate, ?int $createdBy, bool $isNew = false): bool
    {
        // Lấy lịch sử lương gần nhất của nhân viên
        $sqlLatest = "SELECT id, salary, to_date FROM salary_history 
                      WHERE user_id = ? 
                      ORDER BY from_date DESC, id DESC 
                      LIMIT 1";
        $stmtLatest = DB::pdo()->prepare($sqlLatest);
        $stmtLatest->execute([$userId]);
        $latestHistory = $stmtLatest->fetch(PDO::FETCH_ASSOC);

        // Nếu lương mới giống lương hiện tại, không cần lưu
        if ($latestHistory && floatval($latestHistory['salary']) == floatval($newSalary)) {
            return false; // Không có thay đổi
        }

        // Nếu có lịch sử cũ và đang mở (to_date NULL), cập nhật to_date = hôm nay
        if ($latestHistory && $latestHistory['to_date'] === null) {
            $sqlUpdate = "UPDATE salary_history 
                         SET to_date = CURDATE() 
                         WHERE id = ?";
            $stmtUpdate = DB::pdo()->prepare($sqlUpdate);
            $stmtUpdate->execute([$latestHistory['id']]);
        }

        // Xác định from_date:
        // - Nếu là nhân viên mới (isNew = true): dùng hired_at
        // - Nếu là cập nhật lương: dùng ngày hiện tại
        $effectiveFromDate = $isNew ? ($fromDate ?: date('Y-m-d')) : date('Y-m-d');
        
        $sqlInsert = "INSERT INTO salary_history (user_id, salary, from_date, to_date, note, created_by) 
                      VALUES (?, ?, ?, NULL, ?, ?)";
        $stmtInsert = DB::pdo()->prepare($sqlInsert);
        $note = $latestHistory ? 'Điều chỉnh lương' : 'Lương khởi điểm';
        
        return $stmtInsert->execute([
            $userId,
            $newSalary,
            $effectiveFromDate,
            $note,
            $createdBy
        ]);
    }

    /** Cập nhật nhân viên */
    public function update(int|string $id, array $data): array|string|false
    {
        // Get before data
        $beforeData = $this->find($id);
        $beforeArray = null;
        $oldSalary = null;
        
        if (is_array($beforeData)) {
            $beforeArray = [
                'username' => $beforeData['username'] ?? null,
                'full_name' => $beforeData['full_name'] ?? null,
                'email' => $beforeData['email'] ?? null,
                'phone' => $beforeData['phone'] ?? null,
                'staff_role' => $beforeData['staff_role'] ?? null,
                'is_active' => $beforeData['is_active'] ?? null
            ];
            $oldSalary = floatval($beforeData['base_salary'] ?? 0);
        }
        
        // Kiểm tra trùng email/phone (loại trừ user hiện tại)
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        if ($err = $this->checkDuplicateContact($email, $phone, $id)) {
            return $err;
        }
        
        try {
            DB::pdo()->beginTransaction();

            $sqlUser = "UPDATE {$this->userTable}
                    SET username=?, full_name=?, email=?, phone=?, is_active=?, updated_by=?, updated_at=CURRENT_TIMESTAMP, gender=?, date_of_birth=?
                    WHERE id=? AND role_id=2 AND is_deleted=0";
            $stmtUser = DB::pdo()->prepare($sqlUser);
            $stmtUser->execute([
                $data['username'] ?? null,
                $data['full_name'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['is_active'] ?? 1,
                $data['updated_by'] ?? null,
                $data['gender'] ?? null,
                $data['date_of_birth'] ?? null,
                $id
            ]);

            $sqlStaff = "UPDATE {$this->staffTable}
                     SET staff_role=?, hired_at=?, note=?, base_salary=?
                     WHERE user_id=?";
            $stmtStaff = DB::pdo()->prepare($sqlStaff);
            
            $hiredAt = $this->convertDateToMysql($data['hired_at'] ?? null);
            $newSalary = floatval($data['base_salary'] ?? 0);
            
            $stmtStaff->execute([
                $data['staff_role'] ?? null,
                $hiredAt,
                $data['note'] ?? null,
                $newSalary,
                $id
            ]);

            // Lưu lịch sử lương nếu có thay đổi
            if ($oldSalary !== null && $newSalary != $oldSalary) {
                $this->saveSalaryHistory(
                    (int)$id,
                    $newSalary,
                    null,  // không cần truyền hired_at khi update
                    $data['updated_by'] ?? null,
                    false  // isNew = false (đang cập nhật)
                );
            }

            DB::pdo()->commit();
            $result = $this->find($id);
            
            // Log audit
            if (is_array($result) && $beforeArray) {
                $afterArray = [
                    'username' => $result['username'] ?? null,
                    'full_name' => $result['full_name'] ?? null,
                    'email' => $result['email'] ?? null,
                    'phone' => $result['phone'] ?? null,
                    'staff_role' => $result['staff_role'] ?? null,
                    'is_active' => $result['is_active'] ?? null
                ];
                $this->logUpdate('staff', (int)$id, $beforeArray, $afterArray);
            }
            return is_array($result) ? $result : false;
        } catch (PDOException $e) {
            DB::pdo()->rollBack();
            if ($err = $this->mapDuplicateError($e)) {
                return $err;
            }
            throw $e;
        }
    }

    /** Xóa mềm nhân viên (chỉ đánh dấu is_deleted=1) */
    public function delete(int|string $id): bool
    {
        // Get before data
        $beforeData = $this->find($id);
        $beforeArray = null;
        if (is_array($beforeData)) {
            $beforeArray = [
                'username' => $beforeData['username'] ?? null,
                'full_name' => $beforeData['full_name'] ?? null,
                'staff_role' => $beforeData['staff_role'] ?? null
            ];
        }
        
        $sql = "UPDATE {$this->userTable} SET is_deleted = 1, updated_at=CURRENT_TIMESTAMP WHERE id=? AND role_id=2 AND is_deleted=0";
        $stmt = DB::pdo()->prepare($sql);
        $result = $stmt->execute([$id]);
        
        // Log audit
        if ($result && $beforeArray) {
            $this->logDelete('staff', (int)$id, $beforeArray);
        }
        
        return $result;
    }
}
