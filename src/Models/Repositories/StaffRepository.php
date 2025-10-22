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

    /** Đổi mật khẩu cho nhân viên */
    public function changePassword(int|string $userId, string $newPassword): bool
    {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $sql = "UPDATE {$this->userTable} SET password_hash = ? WHERE id = ?";
    $stmt = DB::pdo()->prepare($sql);
    return $stmt->execute([$hash, $userId]);
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
                    u.avatar_url
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.role_id = 2 AND u.is_deleted = 0
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
                    u.avatar_url
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.id = ? AND u.role_id = 2 AND u.is_deleted = 0";
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

            $passwordHash = password_hash('123456', PASSWORD_BCRYPT);

            $sqlUser = "INSERT INTO {$this->userTable}
                    (username, full_name, email, phone, password_hash, role_id, is_active, created_by, force_change_password, is_deleted)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                0    // is_deleted = false
            ]);
            $userId = DB::pdo()->lastInsertId();

            $sqlStaff = "INSERT INTO {$this->staffTable}
                     (user_id, staff_role, hired_at, note)
                     VALUES (?, ?, ?, ?)";
            $stmtStaff = DB::pdo()->prepare($sqlStaff);

            $hiredAt = trim($data['hired_at'] ?? '');
            if ($hiredAt === '' || strtolower($hiredAt) === 'null') {
                $hiredAt = null;
            }
            $stmtStaff->execute([
                $userId,
                $data['staff_role'] ?? 'Kho',
                $hiredAt,
                $data['note'] ?? null
            ]);

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

    /** Cập nhật nhân viên */
    public function update(int|string $id, array $data): array|string|false
    {
        // Get before data
        $beforeData = $this->find($id);
        $beforeArray = null;
        if (is_array($beforeData)) {
            $beforeArray = [
                'username' => $beforeData['username'] ?? null,
                'full_name' => $beforeData['full_name'] ?? null,
                'email' => $beforeData['email'] ?? null,
                'phone' => $beforeData['phone'] ?? null,
                'staff_role' => $beforeData['staff_role'] ?? null,
                'is_active' => $beforeData['is_active'] ?? null
            ];
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
                    SET username=?, full_name=?, email=?, phone=?, is_active=?, updated_by=?, updated_at=CURRENT_TIMESTAMP
                    WHERE id=? AND role_id=2 AND is_deleted=0";
            $stmtUser = DB::pdo()->prepare($sqlUser);
            $stmtUser->execute([
                $data['username'] ?? null,
                $data['full_name'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['is_active'] ?? 1,
                $data['updated_by'] ?? null,
                $id
            ]);

            $sqlStaff = "UPDATE {$this->staffTable}
                     SET staff_role=?, hired_at=?, note=?
                     WHERE user_id=?";
            $stmtStaff = DB::pdo()->prepare($sqlStaff);
            $stmtStaff->execute([
                $data['staff_role'] ?? 'Kho',
                $data['hired_at'] ?? null,
                $data['note'] ?? null,
                $id
            ]);

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
