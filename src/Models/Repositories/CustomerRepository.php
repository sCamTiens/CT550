<?php
namespace App\Models\Repositories;

use App\Core\DB;
use PDO;
use PDOException;

class CustomerRepository
{
    protected string $userTable = 'users';

    /**
     * Lấy danh sách khách hàng
     */
    public function all(): array
    {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.gender,
                    u.date_of_birth,
                    u.is_active,
                    u.created_at,
                    u.updated_at,
                    cu.full_name AS created_by_name,
                    uu.full_name AS updated_by_name
                FROM {$this->userTable} u
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.role_id = 1
                ORDER BY u.created_at DESC, u.id DESC";

        return DB::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm khách hàng theo ID
     *
     * @return array|false
     */
    public function find(int|string $id): array|false
    {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.gender,
                    u.date_of_birth,
                    u.is_active,
                    u.created_at,
                    u.updated_at,
                    cu.full_name AS created_by_name,
                    uu.full_name AS updated_by_name
                FROM {$this->userTable} u
                LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
                LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
                WHERE u.id = ? AND u.role_id = 1";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: false;
    }

    /**
     * Tạo khách hàng mới
     *
     * @return array|false
     */
    public function create(array $data): array|false
    {
        try {
            $pdo = DB::pdo();
            $pdo->beginTransaction();

            $sql = "INSERT INTO {$this->userTable}
                        (username, password_hash, full_name, email, phone, gender, date_of_birth, is_active, role_id, created_by, force_change_password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 0)";

            $stmt = $pdo->prepare($sql);
            $passwordRaw = trim($data['password'] ?? '') ?: '123456';
            $passwordHash = password_hash($passwordRaw, PASSWORD_BCRYPT);

            $stmt->execute([
                trim($data['username'] ?? ''),
                $passwordHash,
                trim($data['full_name'] ?? ''),
                ($data['email'] ?? '') !== '' ? $data['email'] : null,
                ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                ($data['gender'] ?? '') !== '' ? $data['gender'] : null,
                ($data['date_of_birth'] ?? '') !== '' ? $data['date_of_birth'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['created_by'] ?? null
            ]);

            $id = (int)$pdo->lastInsertId();
            $pdo->commit();

            $created = $this->find($id);

            return is_array($created) ? $created : false;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin khách hàng
     *
     * @return array|false
     */
    public function update(int|string $id, array $data): array|false
    {
        $sql = "UPDATE {$this->userTable}
                SET username = ?,
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    gender = ?,
                    date_of_birth = ?,
                    is_active = ?,
                    updated_by = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND role_id = 1";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            trim($data['username'] ?? ''),
            trim($data['full_name'] ?? ''),
            ($data['email'] ?? '') !== '' ? $data['email'] : null,
            ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            ($data['gender'] ?? '') !== '' ? $data['gender'] : null,
            ($data['date_of_birth'] ?? '') !== '' ? $data['date_of_birth'] : null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['updated_by'] ?? null,
            $id
        ]);

    $updated = $this->find($id);

    return is_array($updated) ? $updated : false;
    }

    /**
     * Xoá khách hàng
     */
    public function delete(int|string $id): bool
    {
        $stmt = DB::pdo()->prepare("DELETE FROM {$this->userTable} WHERE id = ? AND role_id = 1");
        return $stmt->execute([$id]);
    }

    /**
     * Đổi mật khẩu khách hàng
     */
    public function changePassword(int|string $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = DB::pdo()->prepare("UPDATE {$this->userTable} SET password_hash = ?, force_change_password = 0 WHERE id = ? AND role_id = 1");
        return $stmt->execute([$hash, $id]);
    }
}
