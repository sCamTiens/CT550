<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;
use PDO;
use PDOException;

class CustomerRepository
{
    use Auditable;

    protected string $userTable = 'users';

    /**
     * Lấy danh sách khách hàng
     */
    public function all(): array
    {
        $sql = $this->getBaseSelect() . " ORDER BY u.created_at DESC, u.id DESC";

        return DB::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm khách hàng theo ID
     *
     * @return array|false
     */
    public function find(int|string $id): array|false
    {
        $sql = $this->getBaseSelect() . " AND u.id = ?";

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
    public function create(array $data): array|string|false
    {
        try {
            $pdo = DB::pdo();
            $pdo->beginTransaction();

            $sql = "INSERT INTO {$this->userTable}
                    (username, password_hash, full_name, email, phone, gender, date_of_birth, is_active, role_id, created_by, updated_by, force_change_password, is_deleted)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 1, 0)";

            $stmt = $pdo->prepare($sql);
            $passwordRaw = trim($data['password'] ?? '') ?: '123456';
            $passwordHash = password_hash($passwordRaw, PASSWORD_BCRYPT);

            // Xử lý date_of_birth: nếu là chuỗi rỗng hoặc null thì gán null, không để chuỗi rỗng
            $dateOfBirth = trim($data['date_of_birth'] ?? '');
            $dateOfBirth = $dateOfBirth !== '' ? $dateOfBirth : null;

            $stmt->execute([
                trim($data['username'] ?? ''),
                $passwordHash,
                trim($data['full_name'] ?? ''),
                ($data['email'] ?? '') !== '' ? $data['email'] : null,
                ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                ($data['gender'] ?? '') !== '' ? $data['gender'] : null,
                $dateOfBirth,
                isset($data['is_active']) ? (int) $data['is_active'] : 1,
                $data['created_by'] ?? null,
                $data['updated_by'] ?? null,
            ]);

            $id = (int) $pdo->lastInsertId();
            $pdo->commit();

            $created = $this->find($id);
            
            // Log audit
            if (is_array($created)) {
                $this->logCreate('customers', $id, [
                    'username' => $created['username'] ?? null,
                    'full_name' => $created['full_name'] ?? null,
                    'email' => $created['email'] ?? null,
                    'phone' => $created['phone'] ?? null,
                    'is_active' => $created['is_active'] ?? null
                ]);
            }

            return is_array($created) ? $created : false;
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $msg = strtolower($e->getMessage());
            if (preg_match('/(username|users\.username)/', $msg)) {
                return 'Tên tài khoản đã tồn tại trong hệ thống';
            }
            if (preg_match('/(email|users\.email)/', $msg)) {
                return 'Email đã tồn tại trong hệ thống';
            }

            throw $e; // nếu không phải lỗi username/email thì ném tiếp
        }

    }

    /**
     * Cập nhật thông tin khách hàng
     *
     * @return array|false
     */
    public function update(int|string $id, array $data): array|string|false
    {
        // Get before data
        $beforeData = $this->find($id);
        $beforeArray = null;
        if (is_array($beforeData)) {
            $beforeArray = [
                'full_name' => $beforeData['full_name'] ?? null,
                'email' => $beforeData['email'] ?? null,
                'phone' => $beforeData['phone'] ?? null,
                'gender' => $beforeData['gender'] ?? null,
                'date_of_birth' => $beforeData['date_of_birth'] ?? null,
                'is_active' => $beforeData['is_active'] ?? null
            ];
        }
        
        try {
            $sql = "UPDATE {$this->userTable}
                SET full_name = ?,
                    email = ?,
                    phone = ?,
                    gender = ?,
                    date_of_birth = ?,
                    is_active = ?,
                    updated_by = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND role_id = 1 AND is_deleted = 0";

            $stmt = DB::pdo()->prepare($sql);
            
            // Xử lý date_of_birth: nếu là chuỗi rỗng hoặc null thì gán null, không để chuỗi rỗng
            $dateOfBirth = trim($data['date_of_birth'] ?? '');
            $dateOfBirth = $dateOfBirth !== '' ? $dateOfBirth : null;
            
            $stmt->execute([
                trim($data['full_name'] ?? ''),
                ($data['email'] ?? '') !== '' ? $data['email'] : null,
                ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                ($data['gender'] ?? '') !== '' ? $data['gender'] : null,
                $dateOfBirth,
                isset($data['is_active']) ? (int) $data['is_active'] : 1,
                $data['updated_by'] ?? null,
                $id
            ]);

            $result = $this->find($id);
            
            // Log audit
            if (is_array($result) && $beforeArray) {
                $afterArray = [
                    'full_name' => $result['full_name'] ?? null,
                    'email' => $result['email'] ?? null,
                    'phone' => $result['phone'] ?? null,
                    'gender' => $result['gender'] ?? null,
                    'date_of_birth' => $result['date_of_birth'] ?? null,
                    'is_active' => $result['is_active'] ?? null
                ];
                $this->logUpdate('customers', (int)$id, $beforeArray, $afterArray);
            }
            
            return is_array($result) ? $result : false;
        } catch (PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (preg_match('/(username|users\.username)/', $msg)) {
                return 'Tên tài khoản đã tồn tại trong hệ thống';
            }
            if (preg_match('/(email|users\.email)/', $msg)) {
                return 'Email đã tồn tại trong hệ thống';
            }
            throw $e;
        }
    }

    /**
     * Xoá khách hàng
     */
    public function delete(int|string $id): bool
    {
        // Get before data
        $beforeData = $this->find($id);
        $beforeArray = null;
        if (is_array($beforeData)) {
            $beforeArray = [
                'username' => $beforeData['username'] ?? null,
                'full_name' => $beforeData['full_name'] ?? null,
                'email' => $beforeData['email'] ?? null,
                'phone' => $beforeData['phone'] ?? null
            ];
        }
        
        $stmt = DB::pdo()->prepare("UPDATE {$this->userTable} SET is_deleted = 1 WHERE id = ? AND role_id = 1");
        $result = $stmt->execute([$id]);
        
        // Log audit (soft delete)
        if ($result && $beforeArray) {
            $this->logDelete('customers', (int)$id, $beforeArray);
        }
        
        return $result;
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

    /**
     * Lấy danh sách địa chỉ của khách hàng
     *
     * @return array
     */
    public function getAddresses(int|string $userId): array
    {
        $sql = "SELECT 
                ua.id,
                ua.label,
                ua.recipient_name,
                ua.phone,
                ua.address_line,
                ua.province_code,
                ua.district_code,
                ua.commune_code,
                ua.is_default,
                CONCAT_WS(', ',
                    ua.address_line,
                    COALESCE(c.name, ''),
                    COALESCE(d.name, ''),
                    COALESCE(p.name, '')
                ) AS full_address
            FROM user_addresses ua
            LEFT JOIN provinces p ON p.code = ua.province_code
            LEFT JOIN districts d ON d.code = ua.district_code
            LEFT JOIN communes c ON c.code = ua.commune_code
            WHERE ua.user_id = ?
            ORDER BY ua.is_default DESC, ua.id ASC";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy câu lệnh SELECT cơ bản
     */
    private function getBaseSelect(): string
    {
        return "SELECT 
                u.id,
                u.username,
                u.full_name,
                u.email,
                u.phone,
                u.gender,
                u.date_of_birth,
                u.is_active,
                u.avatar_url,
                u.created_at,
                u.updated_at,
                cu.full_name AS created_by_name,
                uu.full_name AS updated_by_name
            FROM {$this->userTable} u
            LEFT JOIN {$this->userTable} cu ON cu.id = u.created_by
            LEFT JOIN {$this->userTable} uu ON uu.id = u.updated_by
            WHERE u.role_id = 1 AND u.is_deleted = 0";
    }
}
