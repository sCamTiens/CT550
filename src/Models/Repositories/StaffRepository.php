<?php
namespace App\Models\Repositories;

use \App\Core\DB;
use PDO;
use PDOException;

class StaffRepository
{
    /** Đổi mật khẩu cho nhân viên */
    public function changePassword(int|string $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->userTable} SET password_hash = ? WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$hash, $userId]);
    }
    protected $userTable = 'users';
    protected $staffTable = 'staff_profiles';

    /** Lấy toàn bộ danh sách nhân viên */
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
                    s.note
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.role_id = 2
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
                    s.note
                FROM {$this->userTable} u
                INNER JOIN {$this->staffTable} s ON u.id = s.user_id
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.id = ? AND u.role_id = 2";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Thêm nhân viên mới */
    public function create(array $data): array|false
    {
        try {
            DB::pdo()->beginTransaction();

            // Chuẩn bị dữ liệu người dùng
            $username = trim($data['username'] ?? '');
            $fullName = trim($data['full_name'] ?? '');
            $email = $data['email'] ?? null;
            $phone = $data['phone'] ?? null;

            // Mặc định mật khẩu 123456 nếu không truyền
            $passwordHash = password_hash('123456', PASSWORD_BCRYPT);

            // Insert vào bảng users (set force_change_password = 1)
            // Ghi created_by nếu controller truyền vào
            $sqlUser = "INSERT INTO {$this->userTable}
                        (username, full_name, email, phone, password_hash, role_id, is_active, created_by, force_change_password)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtUser = DB::pdo()->prepare($sqlUser);
            $stmtUser->execute([
                $username,
                $fullName,
                $email,
                $phone,
                $passwordHash,
                2,  // role_id = 2 (Nhân viên)
                $data['is_active'] ?? 1,  // is_active (use provided value or default to 1)
                $data['created_by'] ?? null,
                1   // force_change_password = true
            ]);
            $userId = DB::pdo()->lastInsertId();

            // Insert vào staff_profiles
            $sqlStaff = "INSERT INTO {$this->staffTable}
                         (user_id, staff_role, hired_at, note)
                         VALUES (?, ?, ?, ?)";
            $stmtStaff = DB::pdo()->prepare($sqlStaff);

            // Chuẩn hóa giá trị ngày vào làm
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

            return $this->find($userId);
        } catch (PDOException $e) {
            DB::pdo()->rollBack();
            error_log("StaffRepository::create error: " . $e->getMessage());
            return false;
        }
    }

    /** Cập nhật nhân viên */
    public function update(int|string $id, array $data): array|false
    {
        try {
            /** @var \PDO $pdo */
            DB::pdo()->beginTransaction();

            $sqlUser = "UPDATE {$this->userTable}
                        SET username=?, full_name=?, email=?, phone=?, is_active=?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id=? AND role_id = 2";
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
            if (is_array($result) || $result === false) {
                return $result;
            }
            return false;
        } catch (PDOException $e) {
            DB::pdo()->rollBack();
            error_log("StaffRepository::update error: " . $e->getMessage());
            return false;
        }
    }

    /** Xóa nhân viên (user → staff_profiles bị xóa theo cascade) */
    public function delete(int|string $id): bool
    {
    $stmt = DB::pdo()->prepare("DELETE FROM {$this->userTable} WHERE id = ? AND role_id = 2");
        return $stmt->execute([$id]);
    }
}
