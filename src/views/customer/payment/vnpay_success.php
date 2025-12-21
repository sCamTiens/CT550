<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <title>Thanh toán thành công - MiniGo</title>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <!-- Success Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <!-- Success Icon -->
                <div class="mb-6">
                    <div class="mx-auto w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-check text-5xl text-green-600"></i>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    Thanh toán thành công!
                </h1>

                <p class="text-gray-600 mb-8">
                    Cảm ơn bạn đã mua hàng tại MiniGo. Đơn hàng của bạn đã được xác nhận.
                </p>

                <!-- Order Details -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-bold text-lg mb-4 text-gray-800">Thông tin giao dịch</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Mã đơn hàng:</span>
                            <span
                                class="font-semibold text-gray-800">#<?= htmlspecialchars($order_code ?? 'N/A') ?></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Số tiền:</span>
                            <span class="font-semibold text-green-600">
                                <?= number_format($amount ?? 0, 0, ',', '.') ?>₫
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Mã giao dịch:</span>
                            <span
                                class="font-semibold text-gray-800"><?= htmlspecialchars($transaction_no ?? 'N/A') ?></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Ngân hàng:</span>
                            <span
                                class="font-semibold text-gray-800"><?= htmlspecialchars($bank_code ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 justify-center">
                    <a href="/profile?tab=orders"
                        class="px-6 py-3 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                        <i class="fa-solid fa-box mr-2"></i>
                        Xem đơn hàng
                    </a>

                    <a href="/"
                        class="px-6 py-3 border-2 border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-colors font-semibold">
                        <i class="fa-solid fa-home mr-2"></i>
                        Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>