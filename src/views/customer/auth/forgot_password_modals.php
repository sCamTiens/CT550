<!-- Modal 1: Nhập Email -->
<div id="forgotPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeForgotPasswordModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-key text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Quên mật khẩu</h2>
            <p class="text-gray-600 mt-2">Nhập email để nhận mã xác nhận</p>
        </div>

        <form id="forgotPasswordForm" class="space-y-4" onsubmit="sendOTP(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-envelope mr-2 text-[#002975]"></i>
                    Email
                </label>
                <input
                    type="email"
                    id="forgotEmail"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent"
                    placeholder="Nhập email của bạn">
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="closeForgotPasswordModal()"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                    Hủy
                </button>
                <button
                    type="submit"
                    id="sendOTPBtn"
                    class="flex-1 px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    Gửi mã
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Nhập OTP -->
<div id="otpModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeOTPModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Xác thực OTP</h2>
            <p class="text-gray-600 mt-2">Nhập mã 6 số đã được gửi đến email</p>
            <p class="text-[#002975] font-semibold mt-1" id="displayEmail"></p>
        </div>

        <form id="otpForm" class="space-y-4" onsubmit="verifyOTP(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-lock mr-2 text-[#002975]"></i>
                    Mã OTP (6 số)
                </label>
                <input
                    type="text"
                    id="otpCode"
                    required
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent text-center text-2xl tracking-widest font-bold"
                    placeholder="000000">
                <p class="text-sm text-gray-500 mt-2 text-center">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Mã có hiệu lực: <span id="otpCountdown" class="font-semibold text-orange-600">10:00</span>
                </p>
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="closeOTPModal()"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition-all">
                    Quay lại
                </button>
                <button
                    type="submit"
                    id="verifyOTPBtn"
                    class="flex-1 px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                    <i class="fa-solid fa-check mr-2"></i>
                    Xác nhận
                </button>
            </div>

            <div class="text-center">
                <button
                    type="button"
                    onclick="resendOTP()"
                    id="resendOTPBtn"
                    class="text-sm text-[#002975] hover:underline">
                    <i class="fa-solid fa-rotate mr-1"></i>
                    Gửi lại mã
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Đặt lại mật khẩu -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeResetPasswordModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-lock-open text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Đặt lại mật khẩu</h2>
            <p class="text-gray-600 mt-2">Nhập mật khẩu mới cho tài khoản</p>
        </div>

        <form id="resetPasswordForm" class="space-y-4" onsubmit="resetPassword(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-key mr-2 text-[#002975]"></i>
                    Mật khẩu mới
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="newPassword"
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent pr-12"
                        placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)">
                    <button
                        type="button"
                        onclick="toggleNewPassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-eye" id="toggleNewPasswordIcon"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-check-double mr-2 text-[#002975]"></i>
                    Xác nhận mật khẩu
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="confirmPassword"
                        required
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent pr-12"
                        placeholder="Nhập lại mật khẩu mới">
                    <button
                        type="button"
                        onclick="toggleConfirmPassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-eye" id="toggleConfirmPasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button
                type="submit"
                id="resetPasswordBtn"
                class="w-full px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                <i class="fa-solid fa-rotate mr-2"></i>
                Đặt lại mật khẩu
            </button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<script>
    // Global variables
    let forgotPasswordEmail = '';
    let forgotPasswordOTP = '';
    let otpExpiresAt = null;
    let countdownInterval = null;

    // Countdown timer function
    function startOTPCountdown(expiresInSeconds) {
        let remaining = expiresInSeconds;

        // Clear any existing countdown
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Update countdown every second
        countdownInterval = setInterval(() => {
            remaining--;

            if (remaining <= 0) {
                clearInterval(countdownInterval);
                document.getElementById('otpCountdown').textContent = '00:00';
                document.getElementById('otpCountdown').classList.remove('text-orange-600');
                document.getElementById('otpCountdown').classList.add('text-red-600');
                showToast('Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.', 'error');

                // Disable verify button
                document.getElementById('verifyOTPBtn').disabled = true;
                return;
            }

            // Format as MM:SS
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            document.getElementById('otpCountdown').textContent = timeString;

            // Change color when less than 1 minute remaining
            if (remaining <= 60) {
                document.getElementById('otpCountdown').classList.remove('text-orange-600');
                document.getElementById('otpCountdown').classList.add('text-red-600');
            }
        }, 1000);
    }

    // Modal functions
    function openForgotPasswordModal() {
        document.getElementById('forgotPasswordModal').classList.remove('hidden');
        document.getElementById('forgotPasswordModal').classList.add('flex');
        document.getElementById('forgotEmail').value = '';
    }

    function closeForgotPasswordModal() {
        document.getElementById('forgotPasswordModal').classList.add('hidden');
        document.getElementById('forgotPasswordModal').classList.remove('flex');
    }

    function closeOTPModal() {
        // Clear countdown
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }

        document.getElementById('otpModal').classList.add('hidden');
        document.getElementById('otpModal').classList.remove('flex');
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
        document.getElementById('resetPasswordModal').classList.remove('flex');
    }

    // Toggle password visibility
    function toggleNewPassword() {
        const input = document.getElementById('newPassword');
        const icon = document.getElementById('toggleNewPasswordIcon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function toggleConfirmPassword() {
        const input = document.getElementById('confirmPassword');
        const icon = document.getElementById('toggleConfirmPasswordIcon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Step 1: Send OTP
    async function sendOTP(e) {
        e.preventDefault();

        const btn = document.getElementById('sendOTPBtn');
        const email = document.getElementById('forgotEmail').value.trim();

        if (!email) {
            showToast('Vui lòng nhập email', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang gửi...';

        try {
            const response = await fetch('/api/customer/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email
                })
            });

            const data = await response.json();

            if (data.success) {
                forgotPasswordEmail = email;
                showToast(data.message, 'success');
                closeForgotPasswordModal();

                // Open OTP modal
                setTimeout(() => {
                    document.getElementById('displayEmail').textContent = email;
                    document.getElementById('otpModal').classList.remove('hidden');
                    document.getElementById('otpModal').classList.add('flex');
                    document.getElementById('otpCode').value = '';
                    document.getElementById('otpCode').focus();

                    // Reset countdown color
                    document.getElementById('otpCountdown').classList.remove('text-red-600');
                    document.getElementById('otpCountdown').classList.add('text-orange-600');

                    // Enable verify button
                    document.getElementById('verifyOTPBtn').disabled = false;

                    // Start countdown timer
                    if (data.expires_in_seconds) {
                        startOTPCountdown(data.expires_in_seconds);
                    } else {
                        startOTPCountdown(600); // Default 10 minutes
                    }
                }, 500);
            } else {
                showToast(data.message || 'Không thể gửi mã OTP', 'error');
            }
        } catch (error) {
            showToast('Có lỗi xảy ra. Vui lòng thử lại', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i>Gửi mã';
        }
    }

    // Resend OTP
    async function resendOTP() {
        const btn = document.getElementById('resendOTPBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Đang gửi...';

        try {
            const response = await fetch('/api/customer/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: forgotPasswordEmail
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Mã OTP mới đã được gửi', 'success');
            } else {
                showToast(data.message || 'Không thể gửi lại mã', 'error');
            }
        } catch (error) {
            showToast('Có lỗi xảy ra', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate mr-1"></i>Gửi lại mã';
        }
    }

    // Step 2: Verify OTP
    async function verifyOTP(e) {
        e.preventDefault();

        const btn = document.getElementById('verifyOTPBtn');
        const otpCode = document.getElementById('otpCode').value.trim();

        console.log('[Verify OTP] Email:', forgotPasswordEmail);
        console.log('[Verify OTP] OTP Code:', otpCode);
        console.log('[Verify OTP] OTP Code Length:', otpCode.length);
        console.log('[Verify OTP] OTP Code Type:', typeof otpCode);

        if (!otpCode || otpCode.length !== 6) {
            showToast('Vui lòng nhập mã OTP 6 số', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang xác thực...';

        try {
            const requestBody = {
                email: forgotPasswordEmail,
                otp_code: otpCode
            };

            console.log('[Verify OTP] Request Body:', JSON.stringify(requestBody));

            const response = await fetch('/api/customer/verify-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            console.log('[Verify OTP] Response Status:', response.status);
            console.log('[Verify OTP] Response OK:', response.ok);

            const data = await response.json();
            console.log('[Verify OTP] Response Data:', data);

            if (data.success) {
                forgotPasswordOTP = otpCode;
                showToast(data.message, 'success');
                closeOTPModal();

                // Open reset password modal
                setTimeout(() => {
                    document.getElementById('resetPasswordModal').classList.remove('hidden');
                    document.getElementById('resetPasswordModal').classList.add('flex');
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                    document.getElementById('newPassword').focus();
                }, 500);
            } else {
                showToast(data.message || 'Mã OTP không đúng', 'error');
            }
        } catch (error) {
            console.error('[Verify OTP] Exception:', error);
            showToast('Có lỗi xảy ra. Vui lòng thử lại', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i>Xác nhận';
        }
    }

    // Step 3: Reset Password
    async function resetPassword(e) {
        e.preventDefault();

        const btn = document.getElementById('resetPasswordBtn');
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\//\\|`~]/.test(newPassword)) {
            showToast('Mật khẩu phải có ít nhất 8 ký tự bao gồm ít nhất 1 chữ Cái in hoa, 1 chữ cái thường, 1 số và 1 ký tự đặc biệt');
            return;
        }

        if (newPassword !== confirmPassword) {
            showToast('Mật khẩu xác nhận không khớp', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang xử lý...';

        try {
            const response = await fetch('/api/customer/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: forgotPasswordEmail,
                    otp_code: forgotPasswordOTP,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeResetPasswordModal();

                // Reset variables
                forgotPasswordEmail = '';
                forgotPasswordOTP = '';

                // Optionally redirect to login or auto-fill username
                setTimeout(() => {
                    // Clear and focus login form
                    document.getElementById('loginForm').reset();
                }, 1500);
            } else {
                showToast(data.message || 'Không thể đặt lại mật khẩu', 'error');
            }
        } catch (error) {
            showToast('Có lỗi xảy ra. Vui lòng thử lại', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate mr-2"></i>Đặt lại mật khẩu';
        }
    }
</script>