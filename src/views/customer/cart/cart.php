<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <style>
        /* Hide number input spinner arrows */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Confirm Modal -->
    <div id="confirm-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeConfirmModal()"></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-95 opacity-0" id="confirm-modal-content">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i class="fa-solid fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Xác nhận</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <p class="text-gray-700 text-base leading-relaxed" id="confirm-message"></p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3 justify-end">
                    <button onclick="closeConfirmModal()"
                        class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                        <i class="fa-solid fa-times mr-2"></i>Hủy
                    </button>
                    <button id="confirm-ok-btn"
                        class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                        <i class="fa-solid fa-check mr-2"></i>Đồng ý
                    </button>
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-center">
            <h1 class="text-3xl font-bold mb-6 flex items-center gap-3 text-[#002975]">
                Giỏ hàng
            </h1>
        </div>

        <?php
        error_log("=== VIEW CART ===");
        error_log("cartItems in view: " . (isset($cartItems) ? count($cartItems) : 'NOT SET'));
        error_log("empty check: " . (empty($cartItems) ? 'TRUE' : 'FALSE'));
        ?>

        <?php if (empty($cartItems)): ?>
            <!-- Giỏ hàng trống -->
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fa-solid fa-cart-shopping text-8xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">Giỏ hàng trống</h2>
                <p class="text-gray-500 mb-6">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="/"
                    class="inline-block bg-[#002975] text-white px-8 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <!-- Có sản phẩm trong giỏ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Danh sách sản phẩm -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <!-- Select All Header -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 border-b">
                            <input type="checkbox" id="select-all"
                                class="w-5 h-5 text-[#002975] rounded border-gray-300 focus:ring-2 focus:ring-[#002975]"
                                onchange="toggleSelectAll(this.checked)">
                            <label for="select-all" class="font-semibold text-gray-700">
                                Chọn tất cả (<?= count($cartItems ?? []) ?> sản phẩm)
                            </label>
                        </div>

                        <?php
                        // Bước 1: Xác định combo trước
                        $processedCombos = [];
                        $comboProductIds = [];

                        error_log("=== COMBO DETECTION START ===");
                        error_log("Cart items: " . count($cartItems ?? []));
                        error_log("Promotions: " . json_encode(array_keys($promotions ?? [])));

                        foreach (($cartItems ?? []) as $item) {
                            $productId = $item['id'];
                            $itemPromotions = $promotions[$productId] ?? [];

                            error_log("Checking product $productId for combos, promotions count: " . count($itemPromotions));

                            foreach ($itemPromotions as $promo) {
                                if ($promo['promo_type'] === 'combo' && !empty($promo['combo_price'])) {
                                    $comboId = $promo['id'];
                                    error_log("Found combo promo ID $comboId for product $productId");
                                    if (!isset($processedCombos[$comboId])) {
                                        $comboItems = $promo['combo_items'] ?? [];
                                        $canApplyCombo = true;
                                        $comboProducts = [];
                                        $comboOriginalTotal = 0;
                                        $tempComboProductIds = []; // Temporary array

                                        foreach ($comboItems as $comboItem) {
                                            $found = false;
                                            foreach (($cartItems ?? []) as $cartItem) {
                                                if (
                                                    $cartItem['id'] == $comboItem['product_id'] &&
                                                    $cartItem['quantity'] >= $comboItem['required_qty']
                                                ) {
                                                    $found = true;
                                                    $comboProducts[] = $cartItem;
                                                    $comboOriginalTotal += $cartItem['price'] * $comboItem['required_qty'];
                                                    $tempComboProductIds[] = $cartItem['id']; // Add to temp
                                                    error_log("Combo item found: product {$cartItem['id']}, added to temp");
                                                    break;
                                                }
                                            }
                                            if (!$found) {
                                                $canApplyCombo = false;
                                                error_log("Combo item not found or insufficient qty, combo cannot apply");
                                                break;
                                            }
                                        }

                                        if ($canApplyCombo) {
                                            // Only add to comboProductIds if combo actually applies
                                            $comboProductIds = array_merge($comboProductIds, $tempComboProductIds);
                                            error_log("Combo $comboId CAN apply, added products to comboProductIds: " . json_encode($tempComboProductIds));

                                            $processedCombos[$comboId] = [
                                                'applied' => true,
                                                'combo_price' => $promo['combo_price'],
                                                'products' => $comboProducts,
                                                'name' => $promo['name'],
                                                'original_total' => $comboOriginalTotal
                                            ];
                                        } else {
                                            error_log("Combo $comboId CANNOT apply, NOT adding to comboProductIds");
                                        }
                                    }
                                }
                            }
                        }

                        error_log("Final comboProductIds: " . json_encode($comboProductIds));
                        error_log("=== COMBO DETECTION END ===");
                        ?>
                        <!-- Bước 2: Hiển thị combo trước -->
                        <?php foreach ($processedCombos as $comboId => $combo):
                            if (!$combo['applied']) continue;
                        ?>
                            <!-- COMBO HEADER -->
                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-t-lg p-3 m-4 mb-0">
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
                                <div class="flex gap-4 p-4 pl-8 border-b border-l-4 border-l-purple-400 bg-purple-50/30">
                                    <div class="flex-shrink-0 flex items-center">
                                        <input type="checkbox"
                                            class="item-checkbox w-5 h-5 text-[#002975] rounded border-gray-300 focus:ring-2 focus:ring-[#002975]"
                                            data-product-id="<?= $comboProduct['id'] ?>"
                                            data-price="<?= $combo['combo_price'] / count($combo['products']) ?>"
                                            data-original-price="<?= $comboProduct['price'] ?>"
                                            data-quantity="<?= $comboProduct['quantity'] ?>" onchange="updateSelectedTotal()" checked>
                                    </div>

                                    <div class="flex-shrink-0">
                                        <img src="<?= htmlspecialchars($comboProduct['image_url']) ?>"
                                            alt="<?= htmlspecialchars($comboProduct['name']) ?>"
                                            class="w-20 h-20 object-cover rounded-lg border-2">
                                    </div>

                                    <div class="flex-1">
                                        <h3 class="font-bold text-base mb-1">
                                            <?= htmlspecialchars($comboProduct['name']) ?>
                                        </h3>
                                        <div class="text-sm text-gray-600">Số lượng: <?= $comboProduct['quantity'] ?></div>
                                        <div class="text-sm text-purple-600 font-semibold mt-1">
                                            (Trong combo)
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end justify-center">
                                        <button onclick="removeItem(<?= $comboProduct['id'] ?>)"
                                            class="text-red-600 hover:text-red-800 p-2 mb-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <!-- Bước 3: Hiển thị sản phẩm thường -->
                        <?php
                        error_log("=== PRODUCT LOOP START ===");
                        foreach (($cartItems ?? []) as $item):
                            $productId = $item['id'];
                            error_log("Processing product ID: $productId");

                            // Skip nếu sản phẩm đã thuộc combo
                            if (in_array($productId, $comboProductIds)) {
                                error_log("Product $productId is in combo, skipping");
                                continue;
                            }

                            $itemPromotions = $promotions[$productId] ?? [];

                            // Tính giá
                            $originalPrice = $item['price'];
                            $currentPrice = $originalPrice;
                            $originalSubtotal = $originalPrice * $item['quantity'];
                            $currentSubtotal = $originalSubtotal;
                            $hasDiscount = false;
                            $hasBundle = false;
                            $bundleInfo = null;
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
                                    $bundleInfo = [
                                        'required_qty' => $requiredQty,
                                        'bundle_price' => $bundlePrice
                                    ];
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
                            <div class="flex gap-4 p-4 border-b hover:bg-gray-50" data-product-id="<?= $item['id'] ?>">
                                <div class="flex-shrink-0 flex items-center">
                                    <input type="checkbox"
                                        class="item-checkbox w-5 h-5 text-[#002975] rounded border-gray-300 focus:ring-2 focus:ring-[#002975]"
                                        data-product-id="<?= $item['id'] ?>" data-price="<?= $currentPrice ?>"
                                        data-original-price="<?= $originalPrice ?>"
                                        data-quantity="<?= $item['quantity'] ?>" onchange="updateSelectedTotal()" checked>
                                </div>

                                <div class="flex-shrink-0">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        class="w-24 h-24 object-cover rounded-lg border-2">
                                </div>

                                <div class="flex-1">
                                    <h3 class="font-bold text-lg mb-1">
                                        <a href="/products/<?= htmlspecialchars($item['slug']) ?>" class="hover:text-[#002975]">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </h3>

                                    <div class="mb-2">
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

                                        <?php if ($hasBundle && $bundleInfo): ?>
                                            <div class="text-sm text-green-600 mt-1">
                                                Mua <?= $bundleInfo['required_qty'] ?> chỉ <?= number_format($bundleInfo['bundle_price'], 0, ',', '.') ?>₫
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center border-2 rounded-lg">
                                            <button onclick="updateQty(<?= $item['id'] ?>, -1)"
                                                class="px-3 py-1 text-gray-600 hover:bg-gray-100 font-bold">-</button>
                                            <input type="number" id="qty-<?= $item['id'] ?>" value="<?= $item['quantity'] ?>"
                                                min="1" max="<?= $item['stock_qty'] ?>"
                                                class="w-16 text-center border-x-2 py-1 font-semibold"
                                                onchange="changeQty(<?= $item['id'] ?>, this.value)">
                                            <button onclick="updateQty(<?= $item['id'] ?>, 1)"
                                                class="px-3 py-1 text-gray-600 hover:bg-gray-100 font-bold">+</button>
                                        </div>
                                        <div class="text-gray-600 text-sm">Còn <?= $item['stock_qty'] ?> sản phẩm</div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end justify-between">
                                    <button onclick="removeItem(<?= $item['id'] ?>)"
                                        class="text-red-600 hover:text-red-800 p-2">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <div class="text-right">
                                        <?php if ($hasDiscount || $hasBundle): ?>
                                            <div class="text-sm text-gray-400 line-through">
                                                <?= number_format($originalSubtotal, 0, ',', '.') ?>₫
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-xl font-bold text-[#002975]" id="subtotal-<?= $item['id'] ?>">
                                            <?= number_format($currentSubtotal, 0, ',', '.') ?>₫
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QUÀ TẶNG NGAY DƯỚI SẢN PHẨM -->
                            <?php if ($hasGift && $giftInfo): ?>
                                <div class="flex gap-4 p-3 pl-12 border-b bg-yellow-50 ml-4">
                                    <div class="flex-shrink-0 flex items-center">
                                        <input type="checkbox" disabled class="w-5 h-5 opacity-50">
                                    </div>

                                    <div class="flex-shrink-0 relative">
                                        <img src="<?= htmlspecialchars($giftInfo['image_url']) ?>"
                                            alt="<?= htmlspecialchars($giftInfo['name']) ?>"
                                            class="w-20 h-20 object-cover rounded-lg border-2 border-yellow-400">
                                        <span class="absolute -top-2 -right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                            QUÀ TẶNG
                                        </span>
                                    </div>

                                    <div class="flex-1">
                                        <h3 class="font-bold text-base mb-1 text-yellow-800">
                                            <?= htmlspecialchars($giftInfo['name']) ?>
                                        </h3>
                                        <div class="text-sm text-gray-600 mb-1">
                                            Số lượng: <?= $giftInfo['quantity'] ?>
                                            (Mua <?= $giftInfo['required_qty'] ?> tặng <?= $giftInfo['gift_qty'] ?>)
                                        </div>
                                        <div class="text-green-600 font-semibold">0₫</div>
                                    </div>

                                    <div class="flex flex-col items-end justify-center">
                                        <div class="text-lg font-bold text-green-600">0₫</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tổng tiền -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-bold mb-4 pb-4 border-b">Tổng đơn hàng</h2>

                        <div class="space-y-3 mb-4">
                            <?php if ($totalDiscount > 0): ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Tạm tính (giá gốc):</span>
                                    <span class="line-through" id="original-total"><?= number_format($originalTotal, 0, ',', '.') ?>₫</span>
                                </div>
                                <div class="flex justify-between text-green-600 font-semibold">
                                    <span>Giảm giá:</span>
                                    <span id="discount-amount">-<?= number_format($totalDiscount, 0, ',', '.') ?>₫</span>
                                </div>
                                <div class="flex justify-between text-gray-900 font-semibold">
                                    <span>Tạm tính (sau giảm):</span>
                                    <span id="temp-total"><?= number_format($total, 0, ',', '.') ?>₫</span>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Tạm tính:</span>
                                    <span id="temp-total"><?= number_format($total, 0, ',', '.') ?>₫</span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping-fee">30.000₫</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Tổng cộng:</span>
                                <div class="text-right">
                                    <?php if ($totalDiscount > 0): ?>
                                        <div class="text-sm text-gray-400 line-through">
                                            <?= number_format($originalTotal + 30000, 0, ',', '.') ?>₫
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-2xl font-bold text-red-600" id="total-price">
                                        <?= number_format($total + 30000, 0, ',', '.') ?>₫
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button id="checkout-btn" type="button"
                            class="block w-full bg-[#002975] text-white text-center px-6 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold text-lg mb-3 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-credit-card mr-2"></i>
                            Thanh toán (<span id="selected-count">0</span> sản phẩm)
                        </button>

                        <a href="/"
                            class="block w-full bg-gray-200 text-gray-700 text-center px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                            <i class="fa-solid fa-arrow-left mr-2"></i>
                            Tiếp tục mua sắm
                        </a>

                        <button onclick="removeSelected()"
                            class="block w-full bg-orange-50 text-orange-600 text-center px-6 py-3 rounded-lg hover:bg-orange-100 transition-colors font-semibold mt-3">
                            <i class="fa-solid fa-trash-can mr-2"></i>
                            Xóa đã chọn
                        </button>

                        <button onclick="clearCart()"
                            class="block w-full bg-red-50 text-red-600 text-center px-6 py-3 rounded-lg hover:bg-red-100 transition-colors font-semibold mt-3">
                            <i class="fa-solid fa-trash mr-2"></i>
                            Xóa toàn bộ giỏ hàng
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Modal confirmation functions
        let confirmResolve = null;

        function showConfirmModal(message) {
            return new Promise((resolve) => {
                confirmResolve = resolve;
                const modal = document.getElementById('confirm-modal');
                const modalContent = document.getElementById('confirm-modal-content');
                const messageEl = document.getElementById('confirm-message');

                messageEl.textContent = message;
                modal.classList.remove('hidden');

                // Trigger animation
                setTimeout(() => {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Handle OK button
                const okBtn = document.getElementById('confirm-ok-btn');
                okBtn.onclick = () => {
                    closeConfirmModal();
                    resolve(true);
                };

                // Handle ESC key
                const escHandler = (e) => {
                    if (e.key === 'Escape') {
                        closeConfirmModal();
                        resolve(false);
                        document.removeEventListener('keydown', escHandler);
                    }
                };
                document.addEventListener('keydown', escHandler);
            });
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirm-modal');
            const modalContent = document.getElementById('confirm-modal-content');

            // Animate out
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                if (confirmResolve) {
                    confirmResolve(false);
                    confirmResolve = null;
                }
            }, 200);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedTotal();

            // Attach checkout handler
            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    proceedToCheckout();
                });
            }
        });

        // Toggle select all checkboxes
        function toggleSelectAll(checked) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = checked);
            updateSelectedTotal();
        }

        // Update selected total and button state
        function updateSelectedTotal() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const selectedCount = checkboxes.length;

            let total = 0;
            let originalTotal = 0;
            checkboxes.forEach(cb => {
                const price = parseFloat(cb.dataset.price);
                const originalPrice = parseFloat(cb.dataset.originalPrice || cb.dataset.price);
                const quantity = parseInt(cb.dataset.quantity);
                total += price * quantity;
                originalTotal += originalPrice * quantity;
            });

            const discount = originalTotal - total;
            const shippingFee = 30000; // Phí vận chuyển cố định
            const finalTotal = total + shippingFee;

            // Update UI
            document.getElementById('selected-count').textContent = selectedCount;
            document.getElementById('temp-total').textContent = new Intl.NumberFormat('vi-VN').format(total) + '₫';
            document.getElementById('total-price').textContent = new Intl.NumberFormat('vi-VN').format(finalTotal) + '₫';

            // Update original total and discount if elements exist
            const originalTotalEl = document.getElementById('original-total');
            const discountAmountEl = document.getElementById('discount-amount');

            if (originalTotalEl) {
                originalTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(originalTotal) + '₫';
            }

            if (discountAmountEl) {
                discountAmountEl.textContent = '-' + new Intl.NumberFormat('vi-VN').format(discount) + '₫';
            }

            // Enable/disable checkout button
            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.disabled = selectedCount === 0;

            // Update select-all checkbox state
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            const selectAllCheckbox = document.getElementById('select-all');
            selectAllCheckbox.checked = selectedCount === allCheckboxes.length && selectedCount > 0;
            selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < allCheckboxes.length;
        }

        // Proceed to checkout with selected items
        function proceedToCheckout() {
            try {
                const checkboxes = document.querySelectorAll('.item-checkbox:checked');
                console.log('Selected checkboxes:', checkboxes.length);

                if (checkboxes.length === 0) {
                    showToast('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán', 'error');
                    return false;
                }

                const selectedIds = Array.from(checkboxes).map(cb => cb.dataset.productId);
                console.log('Selected IDs:', selectedIds);

                // Redirect to checkout with selected items as query params
                const checkoutUrl = '/checkout?items=' + selectedIds.join(',');
                console.log('Redirecting to:', checkoutUrl);

                // Use setTimeout to ensure logs are visible before redirect
                setTimeout(() => {
                    window.location.href = checkoutUrl;
                }, 100);

                return false;
            } catch (error) {
                console.error('Error in proceedToCheckout:', error);
                showToast('Có lỗi xảy ra: ' + error.message, 'error');
                return false;
            }
        }

        // Toast notification function
        function showToast(msg, type = 'success') {
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

        function updateQty(productId, change) {
            const input = document.getElementById(`qty-${productId}`);
            let val = parseInt(input.value) || 1;
            const max = parseInt(input.max) || 999;

            val += change;

            // Kiểm tra giới hạn
            if (val < 1) {
                showToast('Số lượng tối thiểu là 1 sản phẩm', 'error');
                return;
            }

            if (val > max) {
                showToast(`Chỉ còn ${max} sản phẩm trong kho`, 'error');
                return;
            }

            input.value = val;
            changeQty(productId, val);
        }

        function changeQty(productId, quantity) {
            const input = document.getElementById(`qty-${productId}`);
            const max = parseInt(input.max) || 999;

            // Validate trước khi gửi
            if (quantity < 1) {
                showToast('Số lượng tối thiểu là 1 sản phẩm', 'error');
                input.value = 1;
                return;
            }

            if (quantity > max) {
                showToast(`Chỉ còn ${max} sản phẩm trong kho`, 'error');
                input.value = max;
                return;
            }

            fetch('/cart/update', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: parseInt(quantity)
                    })
                })
                .then(res => {
                    if (res.status === 401) {
                        showToast('Phiên đăng nhập hết hạn', 'error');
                        setTimeout(() => window.location.href = '/login', 1000);
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // Reload page để cập nhật quà tặng
                        showToast('Đã cập nhật số lượng', 'success');
                        setTimeout(() => location.reload(), 300);
                    } else if (data) {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể cập nhật giỏ hàng', 'error');
                });
        }

        async function removeItem(productId) {
            const confirmed = await showConfirmModal('Bạn có chắc muốn xóa sản phẩm này?');
            if (!confirmed) return;

            fetch('/cart/remove', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                })
                .then(res => {
                    if (res.status === 401) {
                        showToast('Phiên đăng nhập hết hạn', 'error');
                        setTimeout(() => window.location.href = '/login', 1000);
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        showToast('Đã xóa sản phẩm', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else if (data) {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể xóa sản phẩm', 'error');
                });
        }

        async function removeSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');

            if (checkboxes.length === 0) {
                showToast('Vui lòng chọn ít nhất 1 sản phẩm để xóa', 'error');
                return;
            }

            const confirmed = await showConfirmModal(`Bạn có chắc muốn xóa ${checkboxes.length} sản phẩm đã chọn?`);
            if (!confirmed) return;

            const productIds = Array.from(checkboxes).map(cb => cb.dataset.productId);

            // Delete each product
            let successCount = 0;
            let errorCount = 0;

            for (const productId of productIds) {
                try {
                    const res = await fetch('/cart/remove', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId
                        })
                    });

                    const data = await res.json();
                    if (data && data.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (err) {
                    console.error(err);
                    errorCount++;
                }
            }

            if (successCount > 0) {
                showToast(`Đã xóa ${successCount} sản phẩm`, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast('Không thể xóa sản phẩm', 'error');
            }
        }

        async function clearCart() {
            const confirmed = await showConfirmModal('Bạn có chắc muốn xóa toàn bộ giỏ hàng?');
            if (!confirmed) return;

            fetch('/cart/clear', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => {
                    if (res.status === 401) {
                        showToast('Phiên đăng nhập hết hạn', 'error');
                        setTimeout(() => window.location.href = '/login', 1000);
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        showToast('Đã xóa toàn bộ giỏ hàng', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else if (data) {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể xóa giỏ hàng', 'error');
                });
        }
    </script>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>