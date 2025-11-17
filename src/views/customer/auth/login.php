<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - MiniGo</title>
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-[#002975] mb-2">MiniGo</h1>
            <p class="text-gray-600">Đăng nhập để tiếp tục mua sắm</p>
        </div>

        <!-- Form đăng nhập -->
        <form id="loginForm" class="space-y-6">
            <!-- Username hoặc Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-user mr-2 text-[#002975]"></i>
                    Tên đăng nhập hoặc Email
                </label>
                <input 
                    type="text" 
                    name="username" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all"
                    placeholder="Nhập tên đăng nhập hoặc email"
                >
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-lock mr-2 text-[#002975]"></i>
                    Mật khẩu
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002975] focus:border-transparent transition-all pr-12"
                        placeholder="Nhập mật khẩu"
                    >
                    <button 
                        type="button" 
                        onclick="togglePassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                    >
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember me & Forgot password -->
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" class="rounded border-gray-300 text-[#002975] focus:ring-[#002975]">
                    <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                </label>
                <a href="/forgot-password" class="text-sm text-[#002975] hover:underline">
                    Quên mật khẩu?
                </a>
            </div>

            <!-- Submit button -->
            <button 
                type="submit" 
                id="submitBtn"
                class="w-full bg-[#002975] text-white py-3 rounded-lg font-semibold hover:bg-[#001a54] transition-all transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                Đăng nhập
            </button>
        </form>

        <!-- Đăng ký -->
        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Chưa có tài khoản?
                <a href="/register" class="text-[#002975] font-semibold hover:underline">
                    Đăng ký ngay
                </a>
            </p>
        </div>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500">Hoặc</span>
            </div>
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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
            const form = document.getElementById('loginForm');
            const usernameInput = form.querySelector('[name="username"]');
            const passwordInput = form.querySelector('[name="password"]');

            // Username validation
            usernameInput.addEventListener('blur', function() {
                const value = this.value.trim();
                if (!value) {
                    showToast('Vui lòng nhập tên đăng nhập hoặc email', 'error');
                    this.focus();
                }
            });

            // Password validation
            passwordInput.addEventListener('blur', function() {
                const value = this.value;
                if (!value) {
                    showToast('Vui lòng nhập mật khẩu', 'error');
                    this.focus();
                } else if (value.length < 6) {
                    showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
                    this.focus();
                }
            });
        });

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(e.target);
            
            // Get values
            const username = formData.get('username').trim();
            const password = formData.get('password');

            // Validate username
            if (!username) {
                showToast('Vui lòng nhập tên đăng nhập hoặc email', 'error');
                return;
            }

            // Validate password
            if (!password) {
                showToast('Vui lòng nhập mật khẩu', 'error');
                return;
            }

            if (password.length < 6) {
                showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
                return;
            }
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang xử lý...';
            
            try {
                const data = {
                    username: username,
                    password: password
                };
                
                const response = await fetch('/api/customer/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || '/';
                    }, 1500);
                } else {
                    showToast(result.message || 'Đăng nhập thất bại', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket mr-2"></i>Đăng nhập';
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra, vui lòng thử lại', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket mr-2"></i>Đăng nhập';
            }
        });
    </script>
</body>
</html>
