<!-- Forgot Password Modal for Admin -->
<div id="adminForgotPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminForgotModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-sky-500 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-key text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Quên mật khẩu</h2>
            <p class="text-gray-600 mt-2">Nhập email để nhận mã xác nhận</p>
        </div>

        <form id="adminForgotForm" class="space-y-4" onsubmit="sendAdminOTP(event)">
            <div class="">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-envelope mr-2 text-sky-600"></i>
                    Email
                </label>
                <input
                    type="email"
                    id="adminForgotEmail"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    placeholder="Nhập email của bạn">
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="closeAdminForgotModal()"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                    Hủy
                </button>
                <button
                    type="submit"
                    id="adminSendOTPBtn"
                    class="flex-1 px-4 py-3 bg-sky-500 text-white rounded-lg font-semibold hover:bg-sky-600 transition-all">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    Gửi mã
                </button>
            </div>
        </form>
    </div>
</div>

<!-- OTP Modal -->
<div id="adminOTPModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminOTPModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Xác thực OTP</h2>
            <p class="text-gray-600 mt-2">Nhập mã 6 số đã được gửi đến email</p>
            <p class="text-sky-600 font-semibold mt-1" id="adminDisplayEmail"></p>
        </div>

        <form id="adminOTPForm" class="space-y-4" onsubmit="verifyAdminOTP(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-lock mr-2 text-sky-600"></i>
                    Mã OTP (6 số)
                </label>
                <input
                    type="text"
                    id="adminOTPCode"
                    required
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent text-center text-2xl tracking-widest font-bold"
                    placeholder="000000">
                <p class="text-sm text-gray-500 mt-2 text-center">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Mã có hiệu lực: <span id="adminOTPCountdown" class="font-semibold text-orange-600">10:00</span>
                </p>
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="closeAdminOTPModal()"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                    Quay lại
                </button>
                <button
                    type="submit"
                    id="adminVerifyOTPBtn"
                    class="flex-1 px-4 py-3 bg-sky-500 text-white rounded-lg font-semibold hover:bg-sky-600 transition-all">
                    <i class="fa-solid fa-check mr-2"></i>
                    Xác nhận
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="adminResetPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminResetModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-lock-open text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Đặt lại mật khẩu</h2>
        </div>

        <form id="adminResetPasswordForm" class="space-y-4" onsubmit="resetAdminPassword(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-key mr-2 text-purple-600"></i>
                    Mật khẩu mới
                </label>
                <input
                    type="password"
                    id="adminNewPassword"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Nhập mật khẩu mới">
                <p class="text-xs text-gray-500 mt-1">
                    Ít nhất 8 ký tự, có chữ HOA, chữ thường, số, ký tự đặc biệt
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-check-double mr-2 text-purple-600"></i>
                    Xác nhận mật khẩu
                </label>
                <input
                    type="password"
                    id="adminConfirmPassword"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Nhập lại mật khẩu">
            </div>

            <button
                type="submit"
                id="adminResetPasswordBtn"
                class="w-full px-4 py-3 bg-purple-500 text-white rounded-lg font-semibold hover:bg-purple-600 transition-all">
                <i class="fa-solid fa-save mr-2"></i>
                Đặt lại mật khẩu
            </button>
        </form>
    </div>
</div>

<script>
    let adminResetEmail = '';
    let adminOTPCountdownTimer = null;

    function openAdminForgotPasswordModal() {
        document.getElementById('adminForgotPasswordModal').classList.remove('hidden');
        document.getElementById('adminForgotPasswordModal').classList.add('flex');
    }

    function closeAdminForgotModal() {
        document.getElementById('adminForgotPasswordModal').classList.add('hidden');
        document.getElementById('adminForgotPasswordModal').classList.remove('flex');
    }

    function closeAdminOTPModal() {
        document.getElementById('adminOTPModal').classList.add('hidden');
        document.getElementById('adminOTPModal').classList.remove('flex');
        if (adminOTPCountdownTimer) clearInterval(adminOTPCountdownTimer);
    }

    function closeAdminResetModal() {
        document.getElementById('adminResetPasswordModal').classList.add('hidden');
        document.getElementById('adminResetPasswordModal').classList.remove('flex');
    }

    async function sendAdminOTP(event) {
        event.preventDefault();
        const email = document.getElementById('adminForgotEmail').value;
        const btn = document.getElementById('adminSendOTPBtn');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang gửi...';

        try {
            const res = await fetch('/admin/api/auth/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email
                })
            });

            const data = await res.json();

            if (data.success) {
                adminResetEmail = email;
                closeAdminForgotModal();
                document.getElementById('adminDisplayEmail').textContent = email;
                document.getElementById('adminOTPModal').classList.remove('hidden');
                document.getElementById('adminOTPModal').classList.add('flex');

                // Start countdown
                let seconds = 600; // 10 minutes
                adminOTPCountdownTimer = setInterval(() => {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    document.getElementById('adminOTPCountdown').textContent =
                        `${mins}:${secs.toString().padStart(2, '0')}`;

                    if (seconds <= 0) {
                        clearInterval(adminOTPCountdownTimer);
                    }
                    seconds--;
                }, 1000);

                showToast('Mã OTP đã được gửi đến email của bạn', 'success');
            } else {
                showToast(data.message || 'Không tìm thấy email này', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối server', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Gửi mã';
        }
    }

    async function verifyAdminOTP(event) {
        event.preventDefault();
        const otp = document.getElementById('adminOTPCode').value;
        const btn = document.getElementById('adminVerifyOTPBtn');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang xác thực...';

        try {
            const res = await fetch('/admin/api/auth/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: adminResetEmail,
                    otp
                })
            });

            const data = await res.json();

            if (data.success) {
                closeAdminOTPModal();
                document.getElementById('adminResetPasswordModal').classList.remove('hidden');
                document.getElementById('adminResetPasswordModal').classList.add('flex');
                showToast('OTP xác thực thành công!', 'success');
            } else {
                showToast(data.message || 'Mã OTP không đúng hoặc đã hết hạn', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối server', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Xác nhận';
        }
    }

    async function resetAdminPassword(event) {
        event.preventDefault();
        const newPassword = document.getElementById('adminNewPassword').value;
        const confirmPassword = document.getElementById('adminConfirmPassword').value;
        const btn = document.getElementById('adminResetPasswordBtn');

        // Validate password strength
        const errors = [];
        if (newPassword.length < 8) errors.push('• Ít nhất 8 ký tự');
        if (!/[A-Z]/.test(newPassword)) errors.push('• Có chữ HOA (A-Z)');
        if (!/[a-z]/.test(newPassword)) errors.push('• Có chữ thường (a-z)');
        if (!/[0-9]/.test(newPassword)) errors.push('• Có chữ số (0-9)');
        if (!/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\/\\|`~]/.test(newPassword)) errors.push('• Có ký tự đặc biệt (!@#$%...)');

        if (errors.length > 0) {
            showToast('Mật khẩu chưa đạt yêu cầu:\n' + errors.join('\n'), 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            showToast('Mật khẩu xác nhận không khớp', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang xử lý...';

        try {
            const res = await fetch('/admin/api/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: adminResetEmail,
                    password: newPassword
                })
            });

            const data = await res.json();

            if (data.success) {
                closeAdminResetModal();
                showToast('Đặt lại mật khẩu thành công! Vui lòng đăng nhập', 'success');

                // Clear forms
                document.getElementById('adminOTPCode').value = '';
                document.getElementById('adminNewPassword').value = '';
                document.getElementById('adminConfirmPassword').value = '';
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối server', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save mr-2"></i> Đặt lại mật khẩu';
        }
    }
</script>