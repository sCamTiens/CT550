<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\UserRepository;

class AdminForgotPasswordController extends Controller
{
    /**
     * Forgot Password - Send OTP to admin email
     */
    public function sendOTP()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            $this->json(['success' => false, 'message' => 'Email không được để trống']);
            return;
        }

        // Check if email exists and belongs to an admin
        $user = UserRepository::findByEmail($email);

        if (!$user || $user->role_id != 2) {
            $this->json(['success' => false, 'message' => 'Email không tồn tại trong hệ thống quản trị']);
            return;
        }

        // Use PasswordResetRepository to send OTP
        $passwordResetRepo = new \App\Models\Repositories\PasswordResetRepository();
        $result = $passwordResetRepo->createAndSendOTP($email);

        if ($result['success']) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $this->json([
                'success' => true,
                'message' => 'Mã xác nhận đã được gửi đến email của bạn',
                'expires_at' => $expiresAt,
                'expires_in_seconds' => 600
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => $result['message'] ?? 'Có lỗi xảy ra khi gửi email'
            ]);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOTP()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $otp = trim($data['otp'] ?? '');

        if (empty($email) || empty($otp)) {
            $this->json(['success' => false, 'message' => 'Email và OTP không được để trống']);
            return;
        }

        $passwordResetRepo = new \App\Models\Repositories\PasswordResetRepository();
        $result = $passwordResetRepo->verifyOTP($email, $otp);

        $this->json($result);
    }

    /**
     * Reset Password with verified OTP
     */
    public function resetPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $newPassword = $data['password'] ?? '';

        if (empty($email) || empty($newPassword)) {
            $this->json(['success' => false, 'message' => 'Email và mật khẩu không được để trống']);
            return;
        }

        // Validate password strength
        $errors = [];
        if (strlen($newPassword) < 8) {
            $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự';
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'Mật khẩu phải có ít nhất 1 chữ HOA';
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'Mật khẩu phải có ít nhất 1 chữ thường';
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'Mật khẩu phải có ít nhất 1 chữ số';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\\\|`~]/', $newPassword)) {
            $errors[] = 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt';
        }

        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => implode('. ', $errors)]);
            return;
        }

        $passwordResetRepo = new \App\Models\Repositories\PasswordResetRepository();
        $result = $passwordResetRepo->resetPassword($email, $newPassword);

        $this->json($result);
    }
}
