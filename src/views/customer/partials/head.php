<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Website siêu thị mini MiniGo">

<title><?= $pageTitle ?? 'MiniGo - Siêu thị mini' ?></title>

<link rel="icon" href="/assets/images/minigo.png" type="image/png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/responsive.css">

<?php
// Customer session check - chỉ cho phép truy cập nếu đã đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLogged = !empty($_SESSION['customer']);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Danh sách các trang công khai (không cần đăng nhập)
// Khách vãng lai có thể: xem trang chủ, sản phẩm, chi tiết sản phẩm, tìm kiếm, lọc
$publicPaths = ['/login', '/register', '/forgot-password', '/', '/products'];
$isPublic = false;

foreach ($publicPaths as $p) {
    if ($p === '/' && $currentPath === '/') {
        $isPublic = true;
        break;
    } elseif ($p !== '/' && strpos($currentPath, $p) === 0) {
        $isPublic = true;
        break;
    }
}

// Danh sách các trang yêu cầu đăng nhập
$protectedPaths = ['/profile', '/checkout', '/cart', '/addresses', '/loyalty'];
$requiresAuth = false;

foreach ($protectedPaths as $p) {
    if (strpos($currentPath, $p) === 0) {
        $requiresAuth = true;
        break;
    }
}

// Nếu chưa đăng nhập và trang yêu cầu xác thực -> redirect về login
if (!$isLogged && $requiresAuth) {
    header('Location: /login');
    exit;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // Global variable for login status
    window.isUserLoggedIn = <?= json_encode($isLogged) ?>;
    window.customerData = <?= json_encode($isLogged ? $_SESSION['customer'] ?? null : null) ?>;
    
    // Auto-refresh token helper
    window.refreshTokenIfNeeded = async function() {
        if (!window.isUserLoggedIn) {
            return false;
        }
        
        try {
            const response = await fetch('/api/customer/refresh-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Token refreshed successfully
                return true;
            } else {
                // Refresh token expired - logout
                window.isUserLoggedIn = false;
                window.location.href = '/login';
                return false;
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
            window.location.href = '/login';
            return false;
        }
    };
    
    // Global fetch wrapper with auto-retry on 401
    window.fetchWithAuth = async function(url, options = {}) {
        // Add credentials to send session cookie
        options.credentials = 'same-origin';
        
        let response = await fetch(url, options);
        
        // If 401 Unauthorized, try to refresh token and retry once
        if (response.status === 401 && window.isUserLoggedIn) {
            console.log('[fetchWithAuth] Got 401, attempting token refresh...');
            const refreshed = await window.refreshTokenIfNeeded();
            
            if (refreshed) {
                console.log('[fetchWithAuth] Token refreshed, retrying request...');
                // Retry original request
                response = await fetch(url, options);
            } else {
                console.log('[fetchWithAuth] Token refresh failed, redirecting to login...');
            }
        }
        
        return response;
    };
    
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {
                        DEFAULT: '#002975'
                    }
                }
            }
        }
    }
</script>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

<!-- Toastify for notifications -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<!-- SweetAlert2 for voice search errors -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    window.showToast = function(message, type = 'info') {
        const box = document.getElementById('toast-container');
        if (!box) return;

        const toast = document.createElement('div');

        let colorClasses = '';
        let iconColor = '';
        let iconSvg = '';

        if (type === 'success') {
            colorClasses = 'text-green-700 border-green-400';
            iconColor = 'text-green-600';
            iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
        } else if (type === 'warning') {
            colorClasses = 'text-yellow-700 border-yellow-400';
            iconColor = 'text-yellow-600';
            iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 12a7 7 0 1114 0 7 7 0 01-14 0z" />`;
        } else if (type === 'info') {
            colorClasses = 'text-blue-700 border-blue-400';
            iconColor = 'text-blue-600';
            iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`;
        } else {
            colorClasses = 'text-red-700 border-red-400';
            iconColor = 'text-red-600';
            iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`;
        }

        toast.className =
            `flex items-center w-[450px] p-5 mb-3 
            text-base font-semibold bg-white 
            rounded-xl shadow-lg border-2 ${colorClasses}
            animate__animated animate__fadeInRight animate__fast`;

        toast.innerHTML = `
            <svg class="flex-shrink-0 w-6 h-6 ${iconColor} mr-3"
                xmlns="http://www.w3.org/2000/svg" fill="none" 
                viewBox="0 0 24 24" stroke="currentColor">
                ${iconSvg}
            </svg>
            <div class="flex-1">${message}</div>
        `;

        box.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("animate__fadeOutRight");
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
</script>

<div id="toast-container" class="fixed top-5 right-5 z-[60] flex flex-col items-end"></div>