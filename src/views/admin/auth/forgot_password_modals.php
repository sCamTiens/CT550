<!-- Toast Container -->
<div id="adminToastContainer" class="fixed top-5 right-5 z-[100] space-y-3"></div>

<!-- Forgot Password Modal for Admin -->
<div id="adminForgotPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminForgotModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-key text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Quên mật khẩu</h2>
            <p class="text-gray-600 mt-2">Nhập email để nhận mã xác nhận</p>
        </div>

        <form id="adminForgotForm" class="space-y-4" onsubmit="sendAdminOTP(event)">
            <div class="">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-envelope mr-2 text-[#002975]"></i>
                    Email
                </label>
                <input
                    type="email"
                    id="adminForgotEmail"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent"
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
                    class="flex-1 px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    Gửi mã
                </button>
            </div>
        </form>
    </div>
</div>

<!-- OTP Modal -->
<div id="adminOTPModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminOTPModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Xác thực OTP</h2>
            <p class="text-gray-600 mt-2">Nhập mã 6 số đã được gửi đến email</p>
            <p class="text-[#002975] font-semibold mt-1" id="adminDisplayEmail"></p>
        </div>

        <form id="adminOTPForm" class="space-y-4" onsubmit="verifyAdminOTP(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-lock mr-2 text-[#002975]"></i>
                    Mã OTP (6 số)
                </label>
                <input
                    type="text"
                    id="adminOTPCode"
                    required
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent text-center text-2xl tracking-widest font-bold"
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
                    class="flex-1 px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                    <i class="fa-solid fa-check mr-2"></i>
                    Xác nhận
                </button>
            </div>

            <div class="text-center">
                <button
                    type="button"
                    onclick="resendAdminOTP()"
                    id="resendAdminOTPBtn"
                    class="text-sm text-[#002975] hover:underline">
                    <i class="fa-solid fa-rotate mr-1"></i>
                    Gửi lại mã
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="adminResetPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeAdminResetModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 animate__animated animate__zoomIn animate__faster" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-lock-open text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Đặt lại mật khẩu</h2>
            <p class="text-gray-600 mt-2">Nhập mật khẩu mới cho tài khoản</p>
        </div>

        <form id="adminResetPasswordForm" class="space-y-4" onsubmit="resetAdminPassword(event)">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-key mr-2 text-[#002975]"></i>
                    Mật khẩu mới
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="adminNewPassword"
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent pr-12"
                        placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)">
                    <button
                        type="button"
                        onclick="toggleAdminNewPassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-eye" id="toggleAdminNewPasswordIcon"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Ít nhất 8 ký tự, có chữ HOA, chữ thường, số, ký tự đặc biệt
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-check-double mr-2 text-[#002975]"></i>
                    Xác nhận mật khẩu
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="adminConfirmPassword"
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent pr-12"
                        placeholder="Nhập lại mật khẩu">
                    <button
                        type="button"
                        onclick="toggleAdminConfirmPassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-eye" id="toggleAdminConfirmPasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button
                type="submit"
                id="adminResetPasswordBtn"
                class="w-full px-4 py-3 bg-[#002975] text-white rounded-lg font-semibold hover:bg-[#001a54] transition-all">
                <i class="fa-solid fa-rotate mr-2"></i>
                Đặt lại mật khẩu
            </button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

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

    // Toast notification function
    function showToast(message, type = 'error') {
        const container = document.getElementById('adminToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 p-4 rounded-lg shadow-lg border-2 animate__animated animate__fadeInRight animate__faster min-w-[300px] max-w-md`;

        let bgColor, borderColor, iconColor, iconSvg;

        if (type === 'success') {
            bgColor = 'bg-green-50';
            borderColor = 'border-green-400';
            iconColor = 'text-green-600';
            iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
        } else if (type === 'warning') {
            bgColor = 'bg-yellow-50';
            borderColor = 'border-yellow-400';
            iconColor = 'text-yellow-600';
            iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 12a7 7 0 1114 0 7 7 0 01-14 0z" />';
        } else { // error
            bgColor = 'bg-red-50';
            borderColor = 'border-red-400';
            iconColor = 'text-red-600';
            iconSvg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
        }

        toast.className += ` ${bgColor} ${borderColor}`;

        toast.innerHTML = `
            <svg class="w-6 h-6 ${iconColor} flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                ${iconSvg}
            </svg>
            <div class="flex-1 text-sm font-medium text-gray-800 whitespace-pre-line">${message}</div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    // Toggle password visibility functions
    function toggleAdminNewPassword() {
        const input = document.getElementById('adminNewPassword');
        const icon = document.getElementById('toggleAdminNewPasswordIcon');

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

    function toggleAdminConfirmPassword() {
        const input = document.getElementById('adminConfirmPassword');
        const icon = document.getElementById('toggleAdminConfirmPasswordIcon');

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
                let seconds = data.expires_in_seconds || 600; // 10 minutes default
                adminOTPCountdownTimer = setInterval(() => {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    document.getElementById('adminOTPCountdown').textContent =
                        `${mins}:${secs.toString().padStart(2, '0')}`;

                    // Change color when less than 1 minute
                    if (seconds <= 60) {
                        document.getElementById('adminOTPCountdown').classList.remove('text-orange-600');
                        document.getElementById('adminOTPCountdown').classList.add('text-red-600');
                    }

                    if (seconds <= 0) {
                        clearInterval(adminOTPCountdownTimer);
                        document.getElementById('adminVerifyOTPBtn').disabled = true;
                        showToast('Mã OTP đã hết hạn. Vui lòng gửi lại', 'error');
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

    async function resendAdminOTP() {
        const btn = document.getElementById('resendAdminOTPBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang gửi...';

        try {
            const res = await fetch('/admin/api/auth/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: adminResetEmail
                })
            });

            const data = await res.json();

            if (data.success) {
                // Reset countdown
                if (adminOTPCountdownTimer) clearInterval(adminOTPCountdownTimer);

                let seconds = data.expires_in_seconds || 600;
                adminOTPCountdownTimer = setInterval(() => {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    document.getElementById('adminOTPCountdown').textContent =
                        `${mins}:${secs.toString().padStart(2, '0')}`;

                    if (seconds <= 60) {
                        document.getElementById('adminOTPCountdown').classList.remove('text-orange-600');
                        document.getElementById('adminOTPCountdown').classList.add('text-red-600');
                    }

                    if (seconds <= 0) {
                        clearInterval(adminOTPCountdownTimer);
                    }
                    seconds--;
                }, 1000);

                document.getElementById('adminVerifyOTPBtn').disabled = false;
                showToast('Mã OTP mới đã được gửi', 'success');
            } else {
                showToast(data.message || 'Không thể gửi lại mã', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối server', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate mr-1"></i> Gửi lại mã';
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
                document.getElementById('adminNewPassword').value = '';
                document.getElementById('adminConfirmPassword').value = '';
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

        if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\//\\|`~]/.test(newPassword)) {
            showToast('Mật khẩu phải có ít nhất 8 ký tự bao gồm ít nhất 1 chữ Cái in hoa, 1 chữ cái thường, 1 số và 1 ký tự đặc biệt');
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
                adminResetEmail = '';
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối server', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate mr-2"></i> Đặt lại mật khẩu';
        }
    }
</script>