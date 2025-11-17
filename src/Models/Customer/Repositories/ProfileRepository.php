<?php
namespace App\Models\Customer\Repositories;

use App\Core\DB;

class ProfileRepository
{
    /**
     * Lấy thông tin customer theo ID
     */
    public function findById(int $customerId): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, full_name, email, phone, gender, date_of_birth, avatar_url, loyalty_points, created_at
            FROM users
            WHERE id = ? AND role_id = 1
        ");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $customer ?: null;
    }

    /**
     * Kiểm tra email có tồn tại không (trừ customer hiện tại)
     */
    public function emailExists(string $email, int $excludeId): bool
    {
        $stmt = DB::pdo()->prepare("
            SELECT id FROM users 
            WHERE email = ? AND id != ? AND role_id = 1
        ");
        $stmt->execute([$email, $excludeId]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Cập nhật thông tin customer
     */
    public function updateProfile(int $customerId, array $data): bool
    {
        $stmt = DB::pdo()->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, gender = ?, date_of_birth = ?, updated_at = NOW()
            WHERE id = ? AND role_id = 1
        ");
        
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['gender'],
            $data['date_of_birth'],
            $customerId
        ]);
    }

    /**
     * Lấy mật khẩu hiện tại của customer
     */
    public function getPassword(int $customerId): ?string
    {
        $stmt = DB::pdo()->prepare("
            SELECT password_hash FROM users 
            WHERE id = ? AND role_id = 1
        ");
        $stmt->execute([$customerId]);
        $result = $stmt->fetchColumn();
        
        return $result ?: null;
    }

    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword(int $customerId, string $hashedPassword): bool
    {
        $stmt = DB::pdo()->prepare("
            UPDATE users 
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ? AND role_id = 1
        ");
        
        return $stmt->execute([$hashedPassword, $customerId]);
    }

    /**
     * Lấy avatar URL hiện tại
     */
    public function getAvatarUrl(int $customerId): ?string
    {
        $stmt = DB::pdo()->prepare("
            SELECT avatar_url FROM users 
            WHERE id = ? AND role_id = 1
        ");
        $stmt->execute([$customerId]);
        $result = $stmt->fetchColumn();
        
        return $result ?: null;
    }

    /**
     * Cập nhật avatar
     */
    public function updateAvatar(int $customerId, string $filename): bool
    {
        $stmt = DB::pdo()->prepare("
            UPDATE users 
            SET avatar_url = ?, updated_at = NOW()
            WHERE id = ? AND role_id = 1
        ");
        
        return $stmt->execute([$filename, $customerId]);
    }

    /**
     * Validate dữ liệu profile
     */
    public function validateProfileData(array $data): array
    {
        $errors = [];

        // Họ và tên
        if (empty(trim($data['full_name'] ?? ''))) {
            $errors['full_name'] = 'Họ và tên không được bỏ trống';
        }

        // Email
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'Email không được bỏ trống';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        }

        // Số điện thoại
        $phone = trim($data['phone'] ?? '');
        if (empty($phone)) {
            $errors['phone'] = 'Số điện thoại không được bỏ trống';
        } elseif (!preg_match('/^0\d{9}$/', $phone)) {
            $errors['phone'] = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0';
        }

        // Giới tính
        if (empty($data['gender'] ?? '')) {
            $errors['gender'] = 'Vui lòng chọn giới tính';
        }

        // Ngày sinh
        if (empty($data['date_of_birth'] ?? '')) {
            $errors['date_of_birth'] = 'Vui lòng chọn ngày sinh';
        }

        return $errors;
    }

    /**
     * Convert date từ dd/mm/yyyy sang yyyy-mm-dd
     */
    public function convertDateFormat(string $date): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        return null;
    }

    /**
     * Validate password
     */
    public function validatePassword(string $oldPassword, string $newPassword, string $confirmPassword): array
    {
        $errors = [];

        if (empty($oldPassword)) {
            $errors['old_password'] = 'Vui lòng nhập mật khẩu hiện tại';
        }

        if (empty($newPassword)) {
            $errors['new_password'] = 'Vui lòng nhập mật khẩu mới';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }

        if (empty($confirmPassword)) {
            $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
        }

        if (empty($errors) && $oldPassword === $newPassword) {
            $errors['new_password'] = 'Mật khẩu mới không được trùng với mật khẩu hiện tại';
        }

        return $errors;
    }

    /**
     * Validate uploaded avatar file
     */
    public function validateAvatarFile(array $file): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Vui lòng chọn file ảnh';
            return $errors;
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)';
        }

        // Max 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Kích thước file không được vượt quá 5MB';
        }

        return $errors;
    }

    /**
     * Generate unique avatar filename
     */
    public function generateAvatarFilename(int $customerId, string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        return 'customer_' . $customerId . '_' . time() . '.' . $extension;
    }

    /**
     * Delete old avatar file
     */
    public function deleteOldAvatar(string $avatarUrl): bool
    {
        $uploadDir = __DIR__ . '/../../../../public/assets/images/avatar/';
        $filePath = $uploadDir . $avatarUrl;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
}
