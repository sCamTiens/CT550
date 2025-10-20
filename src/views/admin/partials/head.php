<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Website siêu thị mini">

<title>MiniGo</title>

<link rel="icon" href="/assets/images/minigo.png" type="image/png"> <!-- Đảm bảo file tồn tại -->


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<?php
// Server-side session check: nếu không có session admin/user -> chuyển về login
if (session_status() === PHP_SESSION_NONE)
    session_start();
$isLogged = !empty($_SESSION['admin_user'] ?? $_SESSION['user'] ?? null);
// Tránh vòng redirect: nếu đang ở trang login hoặc các trang công khai thì không redirect
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicPaths = ['/admin/login', '/admin/logout', '/admin/forgot-password'];
$isPublic = false;
foreach ($publicPaths as $p) {
    if (strpos($currentPath, $p) === 0) {
        $isPublic = true;
        break;
    }
}

if (!$isLogged && !$isPublic) {
    header('Location: /admin/login');
    exit;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

<script>
    tailwind.config = { theme: { extend: { colors: { primary: { DEFAULT: '#0ea5e9' } } } } }
</script>

<!-- <script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.flatpickr && document.querySelector("input[name='date_of_birth']")) {
        flatpickr("input[name='date_of_birth']", {
            dateFormat: "d/m/Y",
            locale: window.Vietnamese || undefined,
            allowInput: true,
            maxDate: "today"
        });
    }
});
</script> -->