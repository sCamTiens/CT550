<?php
// views/admin/index.php  (Dashboard)

// Fallback demo tránh notice nếu chưa truyền dữ liệu
$orders_today = $orders_today ?? 0;
$revenue_today = $revenue_today ?? 0;
$customers_today = $customers_today ?? 0;
$low_stock = $low_stock ?? 0;

// Mở layout chung (đã có <html>, <head>, <body>, sidebar và <main class="flex-1 p-6">)
require __DIR__ . '/partials/layout-start.php';
?>

<!-- Nội dung trang -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Dashboard</span>
</nav>

<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-slate-500 text-sm">Đơn mới hôm nay</div>
        <div class="mt-1 text-2xl font-semibold"><?= (int) $orders_today ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-slate-500 text-sm">Doanh thu hôm nay</div>
        <div class="mt-1 text-2xl font-semibold">
            <?= number_format((float) $revenue_today, 0, ',', '.') ?> đ
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-slate-500 text-sm">Khách hàng mới</div>
        <div class="mt-1 text-2xl font-semibold"><?= (int) $customers_today ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-slate-500 text-sm">Sắp hết hàng</div>
        <div class="mt-1 text-2xl font-semibold"><?= (int) $low_stock ?></div>
    </div>
</section>

<footer class="p-4 text-center text-slate-500 mt-6">
    © <?= date('Y') ?> Mini Market
</footer>

<?php
// Đóng </main>, </div>, </body>, </html>
require __DIR__ . '/partials/layout-end.php';