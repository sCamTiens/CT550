<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 flex items-center gap-3">
            <i class="fa-solid fa-credit-card text-[#002975]"></i>
            Thanh toán đơn hàng
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left side: Address & Products -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Địa chỉ giao hàng -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-[#002975]"></i>
                            Địa chỉ giao hàng
                        </h2>
                        <a href="/addresses" class="text-[#002975] hover:underline font-semibold">
                            <i class="fa-solid fa-pen-to-square mr-1"></i>
                            Quản lý địa chỉ
                        </a>
                    </div>

                    <?php if (empty($addresses)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">Bạn chưa có địa chỉ giao hàng</p>
                            <a href="/addresses" class="inline-block bg-[#002975] text-white px-6 py-2 rounded-lg hover:bg-[#001a54] transition-colors">
                                <i class="fa-solid fa-plus mr-2"></i>
                                Thêm địa chỉ mới
                            </a>
                        </div>
                    <?php else: ?>
                        <div id="selected-address" class="border-2 border-[#002975] rounded-lg p-4 bg-blue-50">
                            <?php $addr = $defaultAddress ?? $addresses[0]; ?>
                            <input type="hidden" id="address_id" value="<?= $addr['id'] ?>">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-bold text-lg"><?= htmlspecialchars($addr['recipient_name'] ?? $addr['receiver_name'] ?? '') ?></h3>
                                        <span class="text-gray-500">|</span>
                                        <span class="text-gray-600"><?= htmlspecialchars($addr['phone_number'] ?? $addr['receiver_phone'] ?? '') ?></span>
                                    </div>
                                    <p class="text-gray-700">
                                        <?= htmlspecialchars($addr['address_line'] ?? $addr['line1'] ?? '') ?>
                                    </p>
                                </div>
                                <?php if (count($addresses) > 1): ?>
                                    <button onclick="openAddressSelector()" class="text-[#002975] hover:underline font-semibold">
                                        Thay đổi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sản phẩm -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-box text-[#002975]"></i>
                        Sản phẩm đã chọn (<?= count($cartItems) ?>)
                    </h2>

                    <div class="space-y-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex gap-4 p-4 border rounded-lg hover:bg-gray-50">
                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="w-20 h-20 object-cover rounded-lg">
                                <div class="flex-1">
                                    <h3 class="font-bold mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                    <div class="text-gray-600 text-sm">Số lượng: <?= $item['quantity'] ?></div>
                                    <div class="text-red-600 font-semibold mt-2">
                                        <?= number_format($item['price'], 0, ',', '.') ?>₫
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-[#002975]">
                                        <?= number_format($item['subtotal'], 0, ',', '.') ?>₫
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right side: Payment info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-4 space-y-6">
                    <!-- Mã giảm giá -->
                    <div>
                        <h3 class="font-bold mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-ticket text-orange-600"></i>
                            Mã giảm giá
                        </h3>
                        <div class="flex gap-2">
                            <input type="text" id="voucher_code" placeholder="Nhập mã giảm giá"
                                class="flex-1 border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50">
                            <button onclick="applyVoucher()" class="bg-[#002975] text-white px-4 py-2 rounded-lg hover:bg-[#001a54] transition-colors">
                                Áp dụng
                            </button>
                        </div>
                        <div id="voucher-message" class="mt-2 text-sm"></div>
                    </div>

                    <!-- Phương thức thanh toán -->
                    <div>
                        <h3 class="font-bold mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-wallet text-green-600"></i>
                            Phương thức thanh toán
                        </h3>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="cod" checked
                                    class="w-5 h-5 text-[#002975]">
                                <i class="fa-solid fa-money-bill-wave text-green-600"></i>
                                <span class="font-semibold">Thanh toán khi nhận hàng (COD)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="momo"
                                    class="w-5 h-5 text-[#002975]">
                                <i class="fa-solid fa-mobile-screen text-pink-600"></i>
                                <span class="font-semibold">Ví MoMo</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="vnpay"
                                    class="w-5 h-5 text-[#002975]">
                                <i class="fa-solid fa-credit-card text-blue-600"></i>
                                <span class="font-semibold">VNPay</span>
                            </label>
                        </div>
                    </div>

                    <!-- Chi tiết thanh toán -->
                    <div class="border-t pt-4">
                        <h3 class="font-bold mb-3">Chi tiết thanh toán</h3>
                        <div class="space-y-2 text-gray-700">
                            <div class="flex justify-between">
                                <span>Tạm tính (<?= count($cartItems) ?> sản phẩm):</span>
                                <span id="subtotal"><?= number_format($subtotal, 0, ',', '.') ?>₫</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping-fee" class="text-green-600">Miễn phí</span>
                            </div>
                            <div class="flex justify-between" id="discount-row" style="display: none;">
                                <span>Giảm giá:</span>
                                <span id="discount" class="text-red-600">0₫</span>
                            </div>
                        </div>

                        <div class="border-t mt-4 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">Tổng cộng:</span>
                                <span class="text-2xl font-bold text-red-600" id="total-price">
                                    <?= number_format($subtotal, 0, ',', '.') ?>₫
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Nút thanh toán -->
                    <button onclick="processCheckout()"
                        class="w-full bg-[#002975] text-white px-6 py-4 rounded-lg hover:bg-[#001a54] transition-colors font-bold text-lg">
                        <i class="fa-solid fa-check-circle mr-2"></i>
                        Đặt hàng
                    </button>

                    <a href="/cart" class="block text-center text-gray-600 hover:text-[#002975] font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Address Selector Modal -->
    <?php if (count($addresses) > 1): ?>
        <div id="address-selector-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[80vh] overflow-y-auto">
                <div class="sticky top-0 bg-gradient-to-r from-[#002975] to-[#004bbd] text-white p-6 rounded-t-2xl flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Chọn địa chỉ giao hàng</h2>
                    <button onclick="closeAddressSelector()" class="text-white hover:text-gray-200">
                        <i class="fa-solid fa-xmark text-2xl"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="border-2 rounded-lg p-4 cursor-pointer hover:border-[#002975] hover:bg-blue-50 transition-all"
                            onclick="selectAddress(<?= $addr['id'] ?>, this)">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="font-bold text-lg"><?= htmlspecialchars($addr['recipient_name'] ?? $addr['receiver_name'] ?? '') ?></h3>
                                <span class="text-gray-500">|</span>
                                <span class="text-gray-600"><?= htmlspecialchars($addr['phone_number'] ?? $addr['receiver_phone'] ?? '') ?></span>
                                <?php if ($addr['is_default']): ?>
                                    <span class="bg-[#002975] text-white px-2 py-1 rounded text-xs font-semibold ml-auto">
                                        Mặc định
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-gray-700">
                                <?= htmlspecialchars($addr['address_line'] ?? $addr['line1'] ?? '') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Toast notification
        function showToast(msg, type = 'success') {
            const box = document.getElementById('toast-container');
            if (!box) return;
            box.innerHTML = '';

            const toast = document.createElement('div');
            toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
                ${type === 'success' ? 'text-green-700 border-green-400' : 'text-red-700 border-red-400'}
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

        let currentSubtotal = <?= $subtotal ?>;
        let currentDiscount = 0;

        function openAddressSelector() {
            document.getElementById('address-selector-modal').classList.remove('hidden');
        }

        function closeAddressSelector() {
            document.getElementById('address-selector-modal').classList.add('hidden');
        }

        function selectAddress(addressId, element) {
            // Get address data from the clicked element
            const name = element.querySelector('h3').textContent;
            const phone = element.querySelectorAll('.text-gray-600')[0].textContent;
            const address = element.querySelector('p').textContent;

            // Update selected address display
            document.getElementById('selected-address').innerHTML = `
                <input type="hidden" id="address_id" value="${addressId}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-bold text-lg">${name}</h3>
                            <span class="text-gray-500">|</span>
                            <span class="text-gray-600">${phone}</span>
                        </div>
                        <p class="text-gray-700">${address}</p>
                    </div>
                    <button onclick="openAddressSelector()" class="text-[#002975] hover:underline font-semibold">
                        Thay đổi
                    </button>
                </div>
            `;

            closeAddressSelector();
            showToast('Đã cập nhật địa chỉ giao hàng', 'success');
        }

        function applyVoucher() {
            const voucherCode = document.getElementById('voucher_code').value.trim();
            const messageEl = document.getElementById('voucher-message');

            if (!voucherCode) {
                messageEl.innerHTML = '<span class="text-red-600">Vui lòng nhập mã giảm giá</span>';
                return;
            }

            // TODO: Call API to validate voucher
            // For demo, just show error
            messageEl.innerHTML = '<span class="text-red-600">Mã giảm giá không hợp lệ</span>';
        }

        function updateTotal() {
            const total = currentSubtotal - currentDiscount;
            document.getElementById('total-price').textContent = new Intl.NumberFormat('vi-VN').format(total) + '₫';
        }

        function processCheckout() {
            const addressId = document.getElementById('address_id')?.value;
            if (!addressId) {
                showToast('Vui lòng chọn địa chỉ giao hàng', 'error');
                return;
            }

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            if (!paymentMethod) {
                showToast('Vui lòng chọn phương thức thanh toán', 'error');
                return;
            }

            const voucherCode = document.getElementById('voucher_code').value.trim();

            // Lấy items từ URL query (nếu có)
            const urlParams = new URLSearchParams(window.location.search);
            const items = urlParams.get('items');

            const data = {
                address_id: addressId,
                payment_method: paymentMethod,
                voucher_code: voucherCode || null
            };

            // Thêm items vào URL để backend biết checkout items nào
            let processUrl = '/checkout/process';
            if (items) {
                processUrl += '?items=' + items;
            }

            fetch(processUrl, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(res => {
                    if (res.status === 401) {
                        showToast('Phiên đăng nhập đã hết hạn', 'error');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 1500);
                        throw new Error('Unauthorized');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');

                        // Nếu có payment_url (VNPay), redirect đến trang thanh toán
                        if (data.payment_url) {
                            setTimeout(() => {
                                window.location.href = data.payment_url;
                            }, 500);
                        }
                        // Nếu có redirect_url (COD success page), redirect đến đó
                        else if (data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1000);
                        }
                        // Fallback: redirect về trang đơn hàng
                        else {
                            setTimeout(() => {
                                window.location.href = '/profile?tab=orders';
                            }, 1000);
                        }
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    if (err.message !== 'Unauthorized') {
                        console.error(err);
                        showToast('Không thể đặt hàng', 'error');
                    }
                });
        }
    </script>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>