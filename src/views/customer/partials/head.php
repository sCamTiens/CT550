<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Website siêu thị mini MiniGo">

<title><?= $pageTitle ?? 'MiniGo - Siêu thị mini' ?></title>

<link rel="icon" href="/assets/images/minigo.png" type="image/png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<?php
// Customer session check - chỉ cho phép truy cập nếu đã đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLogged = !empty($_SESSION['customer']);
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Danh sách các trang công khai (không cần đăng nhập)
$publicPaths = ['/login', '/register', '/forgot-password'];
$isPublic = false;

foreach ($publicPaths as $p) {
    if (strpos($currentPath, $p) === 0) {
        $isPublic = true;
        break;
    }
}

// Nếu chưa đăng nhập và không phải trang công khai -> redirect về login
if (!$isLogged && !$isPublic) {
    header('Location: /login');
    exit;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    tailwind.config = { 
        theme: { 
            extend: { 
                colors: { 
                    primary: { DEFAULT: '#002975' } 
                } 
            } 
        } 
    }
</script>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
