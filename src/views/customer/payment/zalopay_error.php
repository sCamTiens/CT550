<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <title>Thanh toán thất bại - MiniGo</title>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <!-- Error Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <!-- Error Icon -->
                <div class="mb-6">
                    <div class="mx-auto w-24 h-24 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-times text-5xl text-red-600"></i>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    Thanh toán thất bại
                </h1>

                <!-- Error Message -->
                <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-8">
                    <p class="text-red-700 font-medium">
                        <?= htmlspecialchars($message ?? 'Đã có lỗi xảy ra trong quá trình thanh toán') ?>
                    </p>
                </div>

                <?php if (!empty($order_id)): ?>
                    <p class="text-gray-600 mb-8">
                        Mã đơn hàng: <span class="font-semibold">#<?= htmlspecialchars($order_id) ?></span>
                    </p>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-4 justify-center">
                    <a href="/checkout"
                        class="px-6 py-3 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                        <i class="fa-solid fa-rotate-left mr-2"></i>
                        Thử lại
                    </a>

                    <a href="/cart"
                        class="px-6 py-3 border-2 border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-colors font-semibold">
                        <i class="fa-solid fa-shopping-cart mr-2"></i>
                        Về giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>
