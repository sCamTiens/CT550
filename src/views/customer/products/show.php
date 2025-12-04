<?php require __DIR__ . '/../partials/head.php'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<main class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Hình ảnh sản phẩm -->
            <div class="flex-shrink-0 w-full md:w-2/5">
                <div class="bg-gray-50 rounded-xl border-2 p-4 flex items-center justify-center">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>?t=<?= !empty($product['updated_at']) ? strtotime($product['updated_at']) : time() ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="w-full h-auto max-h-96 object-contain">
                </div>
                <?php if (!empty($images)): ?>
                    <div class="flex gap-2 mt-4 overflow-x-auto">
                        <?php foreach ($images as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>"
                                class="w-20 h-20 object-cover rounded-lg border-2 cursor-pointer hover:border-[#002975] transition-all">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Thông tin sản phẩm -->
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-3 text-gray-800"><?= htmlspecialchars($product['name']) ?></h1>

                <!-- Giá -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-600 mb-1">Giá bán:</div>
                    <div class="text-4xl font-bold text-red-600">
                        <?= number_format((float)($product['sale_price'] ?? $product['price'] ?? 0), 0, ',', '.') ?>₫
                    </div>
                    <?php if (!empty($product['cost_price']) && $product['cost_price'] != $product['sale_price']): ?>
                        <div class="text-sm text-gray-500 line-through mt-1">
                            <?= number_format((float)$product['cost_price'], 0, ',', '.') ?>₫
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Số lượng tồn kho -->
                <div class="mb-6">
                    <div class="text-sm text-gray-600 mb-2">Tình trạng:</div>
                    <?php
                    $stockQty = (int)($product['stock_qty'] ?? 0);
                    if ($stockQty > 0):
                    ?>
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 font-semibold">
                                <i class="fa-solid fa-circle-check mr-1"></i>
                                Còn hàng
                            </span>
                            <span class="text-gray-600">(Còn <?= $stockQty ?> sản phẩm)</span>
                        </div>
                    <?php else: ?>
                        <div class="text-red-600 font-semibold">
                            <i class="fa-solid fa-circle-xmark mr-1"></i>
                            Hết hàng
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Số lượng và nút mua -->
                <?php if ($stockQty > 0): ?>
                    <div class="mb-6">
                        <div class="text-sm text-gray-600 mb-2">Số lượng:</div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border-2 rounded-lg">
                                <button onclick="decreaseQty()"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 font-bold text-xl">
                                    -
                                </button>
                                <input type="number"
                                    id="quantity"
                                    value="1"
                                    min="0"
                                    max="9999"
                                    oninput="this.value = Math.max(0, Math.min(9999, parseInt(this.value) || 0))"
                                    class="w-20 text-center border-x-2 py-2 font-semibold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <button onclick="increaseQty()"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 font-bold text-xl">
                                    +
                                </button>
                            </div>
                            <button onclick="addToCart()"
                                class="flex-1 border border-[#002975] text-[#002975] px-8 py-3 rounded-lg hover:bg-[#002975] hover:text-white transition-colors font-semibold text-lg">
                                <i class="fa-solid fa-cart-plus mr-2"></i>
                                Thêm vào giỏ hàng
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="buyNow()"
                            class="flex-1 bg-orange-500 text-white px-8 py-3 rounded-lg hover:bg-orange-600 transition-colors font-semibold text-lg text-center">
                            <i class="fa-solid fa-shopping-bag mr-2"></i>
                            Mua ngay
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-100 text-gray-600 px-8 py-3 rounded-lg text-center font-semibold">
                        <i class="fa-solid fa-ban mr-2"></i>
                        Sản phẩm tạm hết hàng
                    </div>
                <?php endif; ?>

                <!-- Mô tả -->
                <?php if (!empty($product['description'])): ?>
                    <div class="mt-8 pt-6 border-t">
                        <h3 class="text-xl font-bold mb-3 text-gray-800">Mô tả sản phẩm</h3>
                        <div class="text-gray-700 leading-relaxed">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sản phẩm tương tự -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-box text-[#002975]"></i>
                Sản phẩm tương tự
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <a href="/products/<?= htmlspecialchars($relatedProduct['slug']) ?>"
                        class="bg-white border-2 rounded-xl p-4 hover:shadow-lg hover:border-[#002975] transition-all group">
                        <div class="aspect-square mb-3 bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center">
                            <img src="<?= htmlspecialchars($relatedProduct['image_url']) ?>"
                                alt="<?= htmlspecialchars($relatedProduct['name']) ?>"
                                class="w-full h-full object-contain group-hover:scale-105 transition-transform">
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2 text-sm">
                            <?= htmlspecialchars($relatedProduct['name']) ?>
                        </h3>
                        <div class="text-red-600 font-bold text-lg">
                            <?= number_format((float)($relatedProduct['price'] ?? 0), 0, ',', '.') ?>₫
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
    const maxQty = <?= $stockQty ?>;

    function increaseQty() {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value) || 0;
        if (val < 9999) {
            input.value = val + 1;
        }
    }

    function decreaseQty() {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value) || 0;
        if (val > 0) {
            input.value = val - 1;
        }
    }

    function addToCart() {
        const qty = parseInt(document.getElementById('quantity').value) || 0;
        const productId = <?= (int)$product['id'] ?>;
        const stockQty = <?= $stockQty ?>;

        // Validate quantity
        if (qty <= 0) {
            showToast('Vui lòng nhập số lượng hợp lệ', 'error');
            return;
        }

        // Check stock
        if (qty > stockQty) {
            showToast('Số lượng tồn kho không đủ', 'error');
            return;
        }

        // Send to cart API with JWT
        fetch('/api/cart/add', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: qty
                })
            })
            .then(res => {
                if (res.status === 401) {
                    showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                    setTimeout(() => window.location.href = '/login', 1000);
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;

                if (data.success) {
                    showToast('Đã thêm ' + qty + ' sản phẩm vào giỏ hàng!', 'success');
                    // Dispatch event to update cart badge
                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: {
                            cart_count: data.cart_count
                        }
                    }));
                } else {
                    showToast((data.message || 'Có lỗi xảy ra'), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Không thể thêm vào giỏ hàng', 'error');
            });
    }

    function buyNow() {
        const qty = parseInt(document.getElementById('quantity').value) || 0;
        const productId = <?= (int)$product['id'] ?>;
        const stockQty = <?= $stockQty ?>;

        // Validate quantity
        if (qty <= 0) {
            showToast('Vui lòng nhập số lượng hợp lệ', 'error');
            return;
        }

        // Check stock
        if (qty > stockQty) {
            showToast('Số lượng tồn kho không đủ', 'error');
            return;
        }

        // Add to cart first with JWT
        fetch('/api/cart/add', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: qty
                })
            })
            .then(res => {
                if (res.status === 401) {
                    showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                    setTimeout(() => window.location.href = '/login', 1000);
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;

                if (data.success) {
                    // Redirect to checkout immediately with this product
                    window.location.href = '/checkout?items=' + productId;
                } else {
                    showToast((data.message || 'Có lỗi xảy ra'), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Không thể mua hàng', 'error');
            });
    }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>