<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - MiniGo</title>
    <link rel="icon" href="/assets/images/minigo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Toast Container -->
    <div id="toast-container"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8 my-8">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-[#002975] mb-2">MiniGo</h1>
            <p class="text-gray-600">Tạo tài khoản mới</p>
        </div>

        <!-- Form đăng ký -->
        <form id="registerForm" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Họ tên -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-user mr-2 text-[#002975]"></i>
                        Họ và tên <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="full_name"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all"
                        placeholder="Nguyễn Văn A">
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-at mr-2 text-[#002975]"></i>
                        Tên đăng nhập <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="username"
                        required
                        pattern="[a-zA-Z0-9_]{3,20}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all"
                        placeholder="username123">
                    <p class="text-xs text-gray-500 mt-1">3-20 ký tự, chỉ chữ, số và dấu gạch dưới</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-envelope mr-2 text-[#002975]"></i>
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        name="email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all"
                        placeholder="email@example.com">
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-phone mr-2 text-[#002975]"></i>
                        Số điện thoại
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        pattern="[0-9]{10,11}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all"
                        placeholder="0123456789">
                    <p class="text-xs text-gray-500 mt-1">10-11 chữ số</p>
                </div>

                <!-- Mật khẩu -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-[#002975]"></i>
                        Mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all pr-12"
                            placeholder="Ít nhất 6 ký tự">
                        <button
                            type="button"
                            onclick="togglePassword('password', 'toggleIcon1')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fa-solid fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                </div>

                <!-- Xác nhận mật khẩu -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-[#002975]"></i>
                        Xác nhận mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all pr-12"
                            placeholder="Nhập lại mật khẩu">
                        <button
                            type="button"
                            onclick="togglePassword('confirm_password', 'toggleIcon2')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Điều khoản -->
            <div class="flex items-start">
                <input
                    type="checkbox"
                    id="terms"
                    required
                    class="mt-1 rounded border-gray-300 text-[#002975] focus:ring-[#002975]">
                <label for="terms" class="ml-2 text-sm text-gray-600">
                    Tôi đồng ý với
                    <a href="/terms" class="text-[#002975] hover:underline">Điều khoản dịch vụ</a>
                    và
                    <a href="/privacy" class="text-[#002975] hover:underline">Chính sách bảo mật</a>
                </label>
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                id="submitBtn"
                class="w-full bg-[#002975] text-white py-3 rounded-lg font-semibold hover:bg-[#001a54] transition-all transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-user-plus mr-2"></i>
                Đăng ký
            </button>
        </form>

        <!-- Đăng nhập -->
        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Đã có tài khoản?
                <a href="/login" class="text-[#002975] font-semibold hover:underline">
                    Đăng nhập ngay
                </a>
            </p>
        </div>
    </div>

    <script>
        // Toast notification function
        function showToast(msg, type = 'error') {
            const box = document.getElementById('toast-container');
            if (!box) return;
            box.innerHTML = '';

            const toast = document.createElement('div');
            toast.className =
                `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
                ${type === 'success'
                    ? 'text-green-700 border-green-400'
                    : 'text-red-700 border-red-400'}
                bg-white rounded-xl shadow-lg border-2`;

            toast.innerHTML = `
                <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'} mr-3" 
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    ${type === 'success'
                    ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
                    : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
                </svg>
                <div class="flex-1">${msg}</div>
            `;

            box.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const inputs = {
                username: form.querySelector('[name="username"]'),
                email: form.querySelector('[name="email"]'),
                phone: form.querySelector('[name="phone"]'),
                password: form.querySelector('[name="password"]'),
                confirmPassword: form.querySelector('[name="confirm_password"]'),
                fullName: form.querySelector('[name="full_name"]')
            };

            // Username validation
            inputs.username.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value.length < 3 || value.length > 20) {
                    showToast('Tên đăng nhập phải từ 3-20 ký tự', 'error');
                    this.focus();
                } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                    showToast('Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới', 'error');
                    this.focus();
                }
            });

            // Email validation
            inputs.email.addEventListener('blur', function() {
                const value = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value && !emailRegex.test(value)) {
                    showToast('Email không đúng định dạng', 'error');
                    this.focus();
                }
            });

            // Phone validation
            inputs.phone.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value && !/^0[0-9]{9,10}$/.test(value)) {
                    showToast('Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số', 'error');
                    this.focus();
                }
            });

            // Password validation
            inputs.password.addEventListener('blur', function() {
                const value = this.value;
                if (value.length < 8 || !/[A-Z]/.test(value) || !/[a-z]/.test(value) || !/[0-9]/.test(value) || !/[!@#$%^&*()_+\-=\[\]{};:'",.<>?\//\\|`~]/.test(value)) {
                    showToast('Mật khẩu phải có ít nhất 8 ký tự bao gồm ít nhất 1 chữ Cái in hoa, 1 chữ cái thường, 1 số và 1 ký tự đặc biệt', 'error');
                    this.focus();
                }
            });

            // Confirm password validation
            inputs.confirmPassword.addEventListener('blur', function() {
                if (this.value && this.value !== inputs.password.value) {
                    showToast('Mật khẩu xác nhận không khớp', 'error');
                    this.focus();
                }
            });

            // Full name validation
            inputs.fullName.addEventListener('blur', function() {
                const value = this.value.trim();
                if (!value) {
                    showToast('Vui lòng nhập họ và tên', 'error');
                    this.focus();
                }
            });
        });

        // Handle form submission
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(e.target);

            // Get values
            const username = formData.get('username').trim();
            const email = formData.get('email').trim();
            const phone = formData.get('phone').trim();
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            const fullName = formData.get('full_name').trim();

            // Validate full name
            if (!fullName) {
                showToast('Vui lòng nhập họ và tên', 'error');
                return;
            }

            // Validate username
            if (username.length < 3 || username.length > 20) {
                showToast('Tên đăng nhập phải từ 3-20 ký tự', 'error');
                return;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showToast('Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới', 'error');
                return;
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showToast('Email không đúng định dạng', 'error');
                return;
            }

            // Validate phone
            if (phone && !/^0[0-9]{9,10}$/.test(phone)) {
                showToast('Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số', 'error');
                return;
            }

            // Validate password
            if (password.length < 6) {
                showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
                return;
            }

            // Validate password match
            if (password !== confirmPassword) {
                showToast('Mật khẩu xác nhận không khớp', 'error');
                return;
            }

            // Validate terms checkbox
            if (!document.getElementById('terms').checked) {
                showToast('Vui lòng đồng ý với điều khoản dịch vụ', 'error');
                return;
            }

            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang xử lý...';

            try {
                const data = {
                    username: username,
                    email: email,
                    password: password,
                    confirm_password: confirmPassword,
                    full_name: fullName,
                    phone: phone
                };

                const response = await fetch('/api/customer/register', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Đăng ký thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || '/';
                    }, 1500);
                } else {
                    showToast(result.message || 'Đăng ký thất bại', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-user-plus mr-2"></i>Đăng ký';
                }
            } catch (error) {
                showToast('Có lỗi xảy ra, vui lòng thử lại', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-user-plus mr-2"></i>Đăng ký';
            }
        });
    </script>
</body>

</html>