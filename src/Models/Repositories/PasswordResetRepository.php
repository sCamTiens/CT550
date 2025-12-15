<?php

namespace App\Models\Repositories;

use App\Core\DB;
use PDO;

class PasswordResetRepository
{
    /**
     * Tạo mã OTP mới cho email
     */
    public function createOTP($email)
    {
        // Tạo mã OTP 6 số
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Xóa các OTP cũ chưa sử dụng của email này
        $this->deleteUnusedOTPs($email);

        // Lưu OTP mới - sử dụng DATE_ADD với CURRENT_TIMESTAMP để tránh timezone mismatch
        $stmt = DB::pdo()->prepare("
            INSERT INTO password_resets (email, otp_code, expires_at, created_at)
            VALUES (?, ?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE), CURRENT_TIMESTAMP)
        ");

        $stmt->execute([$email, $otpCode]);

        // Debug log
        error_log("[Password Reset] OTP Created - Email: $email, Code: $otpCode");

        return $otpCode;
    }

    /**
     * Xác thực mã OTP
     */
    public function verifyOTP($email, $otpCode)
    {
        // Debug đơn giản hơn
        $debugStmt = DB::pdo()->prepare("
            SELECT 
                email,
                otp_code,
                is_used,
                expires_at,
                CURRENT_TIMESTAMP as now_time
            FROM password_resets
            WHERE email = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $debugStmt->execute([$email]);
        $debug = $debugStmt->fetch(PDO::FETCH_ASSOC);

        if ($debug) {
            error_log("[Password Reset] Debug - Found OTP: " . json_encode($debug, JSON_UNESCAPED_UNICODE));
        } else {
            error_log("[Password Reset] Debug - No OTP found for email: $email");
        }

        // Xác thực OTP chính thức
        $stmt = DB::pdo()->prepare("
            SELECT * FROM password_resets
            WHERE email = ? 
            AND otp_code = ? 
            AND is_used = FALSE
            AND expires_at > CURRENT_TIMESTAMP
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$email, $otpCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("[Password Reset] OTP Verification SUCCESS - Email: $email, Code: $otpCode");
            return [
                'success' => true,
                'message' => 'Mã OTP hợp lệ'
            ];
        } else {
            error_log("[Password Reset] OTP Verification FAILED - Email: $email, Code: $otpCode");
            return [
                'success' => false,
                'message' => 'Mã OTP không đúng hoặc đã hết hạn'
            ];
        }
    }

    /**
     * Đánh dấu OTP đã sử dụng
     */
    public function markOTPAsUsed($email, $otpCode)
    {
        $stmt = DB::pdo()->prepare("
            UPDATE password_resets
            SET is_used = TRUE
            WHERE email = ? AND otp_code = ?
        ");

        $result = $stmt->execute([$email, $otpCode]);

        if ($result) {
            error_log("[Password Reset] OTP marked as used - Email: $email, Code: $otpCode");
        }

        return $result;
    }

    /**
     * Xóa các OTP cũ chưa sử dụng
     */
    private function deleteUnusedOTPs($email)
    {
        $stmt = DB::pdo()->prepare("
            DELETE FROM password_resets
            WHERE email = ? AND is_used = FALSE
        ");

        $stmt->execute([$email]);
    }

    /**
     * Xóa các OTP đã hết hạn (cleanup)
     */
    public function cleanupExpiredOTPs()
    {
        $stmt = DB::pdo()->prepare("
            DELETE FROM password_resets
            WHERE expires_at < CURRENT_TIMESTAMP
        ");

        return $stmt->execute();
    }

    /**
     * Tạo OTP và gửi email
     * 
     * @param string $email Email của người dùng
     * @return array ['success' => bool, 'message' => string]
     */
    public function createAndSendOTP($email)
    {
        try {
            // Tạo mã OTP
            $otpCode = $this->createOTP($email);

            // Lấy thông tin người dùng để có tên đầy đủ
            $user = UserRepository::findByEmail($email);

            $fullName = $user ? ($user->full_name ?? '') : '';

            // Gửi email OTP
            $emailService = new \App\Services\EmailService();
            $result = $emailService->sendOTPEmail($email, $otpCode, $fullName);

            if ($result['success']) {
                error_log("[Password Reset] OTP sent successfully to: $email");
                return [
                    'success' => true,
                    'message' => 'Mã xác nhận đã được gửi đến email của bạn'
                ];
            } else {
                error_log("[Password Reset] Failed to send OTP email: " . ($result['message'] ?? 'Unknown error'));
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể gửi email'
                ];
            }
        } catch (\Exception $e) {
            error_log("[Password Reset] Error in createAndSendOTP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi mã xác nhận'
            ];
        }
    }

    /**
     * Đặt lại mật khẩu sau khi xác thực OTP
     * 
     * @param string $email Email của người dùng
     * @param string $newPassword Mật khẩu mới
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword($email, $newPassword)
    {
        try {
            // Kiểm tra xem có OTP hợp lệ đã được xác thực chưa
            $stmt = DB::pdo()->prepare("
                SELECT * FROM password_resets
                WHERE email = ? 
                AND is_used = FALSE
                AND expires_at > CURRENT_TIMESTAMP
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([$email]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRecord) {
                return [
                    'success' => false,
                    'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu mã mới'
                ];
            }

            // Cập nhật mật khẩu người dùng
            $user = UserRepository::findByEmail($email);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ];
            }

            // Hash mật khẩu mới
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu trong database
            $updateStmt = DB::pdo()->prepare("
                UPDATE users 
                SET password_hash = ? 
                WHERE email = ?
            ");

            if ($updateStmt->execute([$hashedPassword, $email])) {
                // Đánh dấu OTP đã sử dụng
                $this->markOTPAsUsed($email, $otpRecord['otp_code']);

                error_log("[Password Reset] Password reset successfully for: $email");
                return [
                    'success' => true,
                    'message' => 'Mật khẩu đã được đặt lại thành công'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật mật khẩu'
                ];
            }
        } catch (\Exception $e) {
            error_log("[Password Reset] Error in resetPassword: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt lại mật khẩu'
            ];
        }
    }
}
