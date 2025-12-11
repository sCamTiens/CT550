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
                                    <?php
                                    // Debug: Check what data we have
                                    // var_dump(['ward' => $addr['ward'] ?? 'NULL', 'province' => $addr['province'] ?? 'NULL']);

                                    $parts = [
                                        $addr['address_line'] ?? $addr['line1'] ?? null,
                                        !empty($addr['ward']) ? $addr['ward'] : null,
                                        !empty($addr['province']) ? $addr['province'] : null,
                                    ];
                                    $parts = array_filter($parts);
                                    ?>
                                    <p class="text-gray-700">
                                        <?= htmlspecialchars(implode(', ', $parts)) ?>
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
                        <?php
                        // Bước 1: Xác định combo trước
                        $processedCombos = [];
                        $comboProductIds = [];

                        foreach ($cartItems as $item) {
                            $productId = $item['id'];
                            $itemPromotions = $promotions[$productId] ?? [];

                            foreach ($itemPromotions as $promo) {
                                if ($promo['promo_type'] === 'combo' && !empty($promo['combo_price'])) {
                                    $comboId = $promo['id'];
                                    if (!isset($processedCombos[$comboId])) {
                                        $comboItems = $promo['combo_items'] ?? [];
                                        $canApplyCombo = true;
                                        $comboProducts = [];
                                        $comboOriginalTotal = 0;
                                        $tempComboProductIds = []; // Temporary array

                                        foreach ($comboItems as $comboItem) {
                                            $found = false;
                                            foreach ($cartItems as $cartItem) {
                                                if (
                                                    $cartItem['id'] == $comboItem['product_id'] &&
                                                    $cartItem['quantity'] >= $comboItem['required_qty']
                                                ) {
                                                    $found = true;
                                                    $comboProducts[] = $cartItem;
                                                    $comboOriginalTotal += $cartItem['price'] * $comboItem['required_qty'];
                                                    $tempComboProductIds[] = $cartItem['id']; // Add to temp
                                                    break;
                                                }
                                            }
                                            if (!$found) {
                                                $canApplyCombo = false;
                                                break;
                                            }
                                        }

                                        if ($canApplyCombo) {
                                            // Only add to comboProductIds if combo actually applies
                                            $comboProductIds = array_merge($comboProductIds, $tempComboProductIds);

                                            $processedCombos[$comboId] = [
                                                'applied' => true,
                                                'combo_price' => $promo['combo_price'],
                                                'products' => $comboProducts,
                                                'name' => $promo['name'],
                                                'original_total' => $comboOriginalTotal
                                            ];
                                        }
                                    }
                                }
                            }
                        }

                        // Bước 2: Hiển thị combo trước
                        foreach ($processedCombos as $comboId => $combo):
                            if (!$combo['applied']) continue;
                        ?>
                            <!-- COMBO HEADER -->
                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-t-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                            🎁 COMBO
                                        </span>
                                        <span class="font-bold text-purple-900"><?= htmlspecialchars($combo['name']) ?></span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500 line-through">
                                            <?= number_format($combo['original_total'], 0, ',', '.') ?>₫
                                        </div>
                                        <div class="text-xl font-bold text-purple-600">
                                            <?= number_format($combo['combo_price'], 0, ',', '.') ?>₫
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- COMBO PRODUCTS -->
                            <?php foreach ($combo['products'] as $comboProduct): ?>
                                <div class="flex gap-4 p-4 pl-8 border-l-4 border-l-purple-400 bg-purple-50/30">
                                    <img src="<?= htmlspecialchars($comboProduct['image_url']) ?>"
                                        alt="<?= htmlspecialchars($comboProduct['name']) ?>"
                                        class="w-16 h-16 object-cover rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-bold mb-1"><?= htmlspecialchars($comboProduct['name']) ?></h3>
                                        <div class="text-gray-600 text-sm">Số lượng: <?= $comboProduct['quantity'] ?></div>
                                        <div class="text-sm text-purple-600 font-semibold mt-1">(Trong combo)</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <!-- Bước 3: Hiển thị sản phẩm thường -->
                        <?php
                        foreach ($cartItems as $item):
                            $productId = $item['id'];

                            // Skip nếu sản phẩm đã thuộc combo
                            if (in_array($productId, $comboProductIds)) continue;

                            $itemPromotions = $promotions[$productId] ?? [];

                            // Tính giá
                            $originalPrice = $item['price'];
                            $currentPrice = $originalPrice;
                            $originalSubtotal = $originalPrice * $item['quantity'];
                            $currentSubtotal = $originalSubtotal;
                            $hasDiscount = false;
                            $hasBundle = false;
                            $hasGift = false;
                            $giftInfo = null;

                            foreach ($itemPromotions as $promo) {
                                if ($promo['promo_type'] === 'discount') {
                                    if ($promo['discount_type'] === 'percentage') {
                                        $currentPrice = $originalPrice * (1 - $promo['discount_value'] / 100);
                                    } else {
                                        $currentPrice = $originalPrice - $promo['discount_value'];
                                    }
                                    $currentSubtotal = $currentPrice * $item['quantity'];
                                    $hasDiscount = true;
                                    break;
                                }

                                if ($promo['promo_type'] === 'bundle' && $item['quantity'] >= ($promo['required_qty'] ?? 1)) {
                                    $requiredQty = $promo['required_qty'] ?? 1;
                                    $bundlePrice = $promo['bundle_price'] ?? $originalPrice;
                                    $bundleSets = floor($item['quantity'] / $requiredQty);
                                    $currentSubtotal = $bundlePrice * $bundleSets;
                                    $remainingQty = $item['quantity'] % $requiredQty;
                                    if ($remainingQty > 0) {
                                        $currentSubtotal += $originalPrice * $remainingQty;
                                    }
                                    $hasBundle = true;
                                    break;
                                }

                                if ($promo['promo_type'] === 'gift' && $item['quantity'] >= ($promo['required_qty'] ?? 1)) {
                                    $giftQty = floor($item['quantity'] / $promo['required_qty']) * $promo['gift_qty'];
                                    $hasGift = true;
                                    $giftInfo = [
                                        'name' => $promo['gift_name'] ?? 'Quà tặng',
                                        'image_url' => $promo['gift_image_url'] ?? '/assets/images/default-product.png',
                                        'quantity' => $giftQty,
                                        'required_qty' => $promo['required_qty'],
                                        'gift_qty' => $promo['gift_qty']
                                    ];
                                }
                            }
                        ?>
                            <!-- SẢN PHẨM CHÍNH -->
                            <div class="flex gap-4 p-4 border rounded-lg hover:bg-gray-50">
                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="w-20 h-20 object-cover rounded-lg">
                                <div class="flex-1">
                                    <h3 class="font-bold mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                    <div class="text-gray-600 text-sm mb-2">Số lượng: <?= $item['quantity'] ?></div>

                                    <?php if ($hasDiscount || $hasBundle): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400 line-through text-sm">
                                                <?= number_format($originalPrice, 0, ',', '.') ?>₫
                                            </span>
                                            <span class="text-red-600 font-semibold">
                                                <?= number_format($currentPrice, 0, ',', '.') ?>₫
                                            </span>
                                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-semibold">
                                                GIẢM GIÁ
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-red-600 font-semibold">
                                            <?= number_format($originalPrice, 0, ',', '.') ?>₫
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <?php if ($hasDiscount || $hasBundle): ?>
                                        <div class="text-sm text-gray-400 line-through">
                                            <?= number_format($originalSubtotal, 0, ',', '.') ?>₫
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-lg font-bold text-[#002975]">
                                        <?= number_format($currentSubtotal, 0, ',', '.') ?>₫
                                    </div>
                                </div>
                            </div>

                            <!-- QUÀ TẶNG NGAY DƯỚI SẢN PHẨM -->
                            <?php if ($hasGift && $giftInfo): ?>
                                <div class="flex gap-4 p-3 pl-12 border rounded-lg bg-yellow-50 ml-4">
                                    <div class="relative">
                                        <img src="<?= htmlspecialchars($giftInfo['image_url']) ?>"
                                            alt="<?= htmlspecialchars($giftInfo['name']) ?>"
                                            class="w-16 h-16 object-cover rounded-lg border-2 border-yellow-400">
                                        <span class="absolute -top-2 -right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                            QUÀ TẶNG
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold mb-1 text-yellow-800"><?= htmlspecialchars($giftInfo['name']) ?></h3>
                                        <div class="text-gray-600 text-sm mb-1">
                                            Số lượng: <?= $giftInfo['quantity'] ?>
                                            (Mua <?= $giftInfo['required_qty'] ?> tặng <?= $giftInfo['gift_qty'] ?>)
                                        </div>
                                        <div class="text-green-600 font-semibold">0₫</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600">0₫</div>
                                    </div>
                                </div>
                            <?php endif; ?>
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
                                <input type="radio" name="payment_method" value="zalopay"
                                    class="w-5 h-5 text-[#002975]">
                                <i class="fa-solid fa-wallet text-blue-600"></i>
                                <span class="font-semibold">ZaloPay</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="vnpay"
                                    class="w-5 h-5 text-[#002975]">
                                <i class="fa-solid fa-credit-card text-red-600"></i>
                                <span class="font-semibold">VNPay</span>
                            </label>
                        </div>
                    </div>

                    <!-- Chi tiết thanh toán -->
                    <div class="border-t pt-4">
                        <h3 class="text-xl font-bold mb-4 pb-4 border-b">Tổng đơn hàng</h3>

                        <div class="space-y-3 mb-4">
                            <?php if ($totalDiscount > 0): ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Tạm tính (giá gốc):</span>
                                    <span class="line-through"><?= number_format($originalSubtotal, 0, ',', '.') ?>₫</span>
                                </div>
                                <div class="flex justify-between text-green-600 font-semibold">
                                    <span>Giảm giá:</span>
                                    <span>-<?= number_format($totalDiscount, 0, ',', '.') ?>₫</span>
                                </div>
                                <div class="flex justify-between text-gray-900 font-semibold">
                                    <span>Tạm tính (sau giảm):</span>
                                    <span id="subtotal"><?= number_format($subtotal, 0, ',', '.') ?>₫</span>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Tạm tính:</span>
                                    <span id="subtotal"><?= number_format($subtotal, 0, ',', '.') ?>₫</span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping-fee">Miễn phí</span>
                            </div>
                            <div class="flex justify-between" id="discount-row" style="display: none;">
                                <span>Giảm giá:</span>
                                <span id="discount" class="text-red-600">0₫</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Tổng cộng:</span>
                                <div class="text-right">
                                    <?php if ($totalDiscount > 0): ?>
                                        <div class="text-sm text-gray-400 line-through">
                                            <?= number_format($originalSubtotal, 0, ',', '.') ?>₫
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-2xl font-bold text-red-600" id="total-price">
                                        <?= number_format($subtotal, 0, ',', '.') ?>₫
                                    </span>
                                </div>
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
                            <?php
                            $modalParts = [
                                $addr['address_line'] ?? $addr['line1'] ?? null,
                                !empty($addr['ward']) ? $addr['ward'] : null,
                                !empty($addr['province']) ? $addr['province'] : null,
                            ];
                            $modalParts = array_filter($modalParts);
                            ?>
                            <p class="text-gray-700">
                                <?= htmlspecialchars(implode(', ', $modalParts)) ?>
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

            // XỬ LÝ ZALOPAY RIÊNG
            if (paymentMethod === 'zalopay') {
                handleZaloPayCheckout(addressId, items, voucherCode);
                return;
            }

            // XỬ LÝ COD và VNPAY như cũ
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

        // Hàm xử lý thanh toán ZaloPay riêng
        async function handleZaloPayCheckout(addressId, itemsQuery, voucherCode) {
            try {
                // Lấy danh sách items đã chọn và tính tổng tiền CHÍNH XÁC
                const cartItemsData = await getSelectedCartItemsData(itemsQuery);

                if (!cartItemsData || cartItemsData.items.length === 0) {
                    showToast('Không có sản phẩm nào được chọn', 'error');
                    return;
                }

                const payload = {
                    amount: cartItemsData.subtotal, // Chỉ tính items được chọn
                    address_id: addressId,
                    cart_items: cartItemsData.items,
                    selected_item_ids: cartItemsData.item_ids,
                    voucher_code: voucherCode || null
                };

                const res = await fetch('/api/payment/zalopay/create', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (res.status === 401) {
                    showToast('Phiên đăng nhập đã hết hạn', 'error');
                    setTimeout(() => window.location.href = '/login', 1500);
                    return;
                }

                const data = await res.json();

                if (data.success && data.payment_url) {
                    showToast('Đang chuyển đến ZaloPay...', 'success');
                    setTimeout(() => {
                        window.location.href = data.payment_url;
                    }, 500);
                } else {
                    showToast(data.message || 'Không thể tạo thanh toán ZaloPay', 'error');
                }
            } catch (err) {
                console.error('ZaloPay checkout error:', err);
                showToast('Lỗi kết nối, vui lòng thử lại', 'error');
            }
        }

        // Hàm lấy chi tiết items đã chọn từ server
        async function getSelectedCartItemsData(itemsQuery) {
            try {
                // Parse PHP cart items từ backend
                const cartItemsFromPHP = <?= json_encode($cartItems ?? []) ?>;
                const selectedIds = itemsQuery ? itemsQuery.split(',').map(id => parseInt(id)) : [];

                let filteredItems = cartItemsFromPHP;

                // Nếu có query items, chỉ lấy các items được chọn
                if (selectedIds.length > 0) {
                    filteredItems = cartItemsFromPHP.filter(item => selectedIds.includes(parseInt(item.id)));
                }

                // Tính tổng tiền
                const subtotal = filteredItems.reduce((sum, item) => {
                    return sum + (parseFloat(item.subtotal) || 0);
                }, 0);

                return {
                    items: filteredItems,
                    item_ids: filteredItems.map(item => parseInt(item.id)),
                    subtotal: Math.round(subtotal) // Làm tròn để tránh lỗi floating point
                };
            } catch (err) {
                console.error('Error getting cart items:', err);
                return null;
            }
        }
    </script>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>