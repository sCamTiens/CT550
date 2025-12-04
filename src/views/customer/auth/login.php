<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    // Load Google Client ID from environment
    $googleClientId = getenv('GOOGLE_CLIENT_ID') ?: '';
    if (empty($googleClientId) && isset($_ENV['GOOGLE_CLIENT_ID'])) {
        $googleClientId = $_ENV['GOOGLE_CLIENT_ID'];
    }
    ?>
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
                    placeholder="Nhập tên đăng nhập hoặc email">
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
                        placeholder="Nhập mật khẩu">
                    <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
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
                class="w-full bg-[#002975] text-white py-3 rounded-lg font-semibold hover:bg-[#001a54] transition-all transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed">
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

        <!-- Google Sign In -->
        <button
            type="button"
            onclick="loginWithGoogle()"
            class="w-full flex items-center justify-center gap-3 bg-white border-2 border-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-50 hover:border-[#002975] hover:text-[#002975] transition-all transform hover:scale-[1.02]">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.8055 10.2292C19.8055 9.55056 19.7501 8.86708 19.6323 8.19824H10.2002V12.0492H15.6014C15.3773 13.2911 14.6571 14.3898 13.6025 15.0879V17.5866H16.8251C18.7174 15.8449 19.8055 13.2728 19.8055 10.2292Z" fill="#4285F4" />
                <path d="M10.2002 20.0006C12.9506 20.0006 15.2715 19.1151 16.8295 17.5865L13.6069 15.0879C12.7096 15.6979 11.5492 16.0433 10.2046 16.0433C7.54635 16.0433 5.28616 14.2832 4.48911 11.9169H1.16699V14.4927C2.76748 17.6843 6.30818 20.0006 10.2002 20.0006Z" fill="#34A853" />
                <path d="M4.48475 11.9169C4.04553 10.675 4.04553 9.33008 4.48475 8.08813V5.51233H1.16699C-0.388997 8.61644 -0.388997 12.3886 1.16699 15.4927L4.48475 11.9169Z" fill="#FBBC04" />
                <path d="M10.2002 3.95805C11.6214 3.936 13.0008 4.47247 14.0402 5.45722L16.8948 2.60218C15.1858 0.990848 12.9375 0.0808353 10.2002 0.104831C6.30818 0.104831 2.76748 2.42115 1.16699 5.61279L4.48475 8.18859C5.27744 5.81796 7.54199 3.95805 10.2002 3.95805Z" fill="#EA4335" />
            </svg>
            Đăng nhập bằng Google
        </button>
    </div>

    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <script>
        // Google Client ID from server config
        const GOOGLE_CLIENT_ID = '<?= htmlspecialchars($googleClientId) ?>';

        // Initialize Google Sign-In with OAuth2 for account chooser
        let tokenClient;

        function initializeGoogleSignIn() {
            if (typeof google !== 'undefined' && google.accounts) {
                // Initialize OAuth2 token client for full account chooser
                tokenClient = google.accounts.oauth2.initTokenClient({
                    client_id: GOOGLE_CLIENT_ID,
                    scope: 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
                    callback: async (response) => {
                        if (response.access_token) {
                            await handleOAuthCallback(response.access_token);
                        }
                    },
                });
            }
        }

        // Handle OAuth callback
        async function handleOAuthCallback(accessToken) {
            try {
                // Get user info from Google
                const userInfoResponse = await fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`
                    }
                });

                const userInfo = await userInfoResponse.json();

                // Debug: Log user info from Google
                console.log('Google user info:', userInfo);

                // Send to backend (reuse existing endpoint with modified data)
                const result = await fetch('/api/customer/google-login-oauth', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userInfo)
                });

                const data = await result.json();

                if (data.success) {
                    showToast('Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/';
                    }, 1500);
                } else {
                    showToast(data.message || 'Đăng nhập Google thất bại', 'error');
                }
            } catch (error) {
                showToast('Có lỗi xảy ra khi đăng nhập bằng Google', 'error');
            }
        }

        // Login with Google - show full account chooser popup
        function loginWithGoogle() {
            if (tokenClient) {
                // Request access token with account selection prompt
                tokenClient.requestAccessToken({
                    prompt: 'select_account'
                });
            } else {
                showToast('Google Sign-In chưa được tải. Vui lòng thử lại', 'error');
            }
        }

        // Initialize when page loads
        window.addEventListener('load', () => {
            initializeGoogleSignIn();
        });
    </script>

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
        ? `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
        : `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
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
                    credentials: 'include',
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
                showToast('Có lỗi xảy ra, vui lòng thử lại', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket mr-2"></i>Đăng nhập';
            }
        });
    </script>
</body>

</html>