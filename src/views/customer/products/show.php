<?php require __DIR__ . '/../partials/head.php'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<!-- Toast Container -->
<div id="toast-container"></div>

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
                                       min="1" 
                                       max="<?= $stockQty ?>"
                                       class="w-20 text-center border-x-2 py-2 font-semibold">
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
                        <a href="/cart" 
                           class="flex-1 bg-orange-500 text-white px-8 py-3 rounded-lg hover:bg-orange-600 transition-colors font-semibold text-lg text-center">
                            <i class="fa-solid fa-shopping-bag mr-2"></i>
                            Mua ngay
                        </a>
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
</main>

<script>
    const maxQty = <?= $stockQty ?>;
    
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
    
    function increaseQty() {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value) || 1;
        if (val < maxQty) {
            input.value = val + 1;
        }
    }
    
    function decreaseQty() {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value) || 1;
        if (val > 1) {
            input.value = val - 1;
        }
    }
    
    function addToCart() {
        const qty = parseInt(document.getElementById('quantity').value) || 1;
        const productId = <?= (int)$product['id'] ?>;
        
        // Send to cart
        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: qty
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Đã thêm ' + qty + ' sản phẩm vào giỏ hàng!', 'success');
                // Update cart count in header if exists
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge && data.cart_count) {
                    cartBadge.textContent = data.cart_count;
                }
            } else {
                showToast((data.message || 'Có lỗi xảy ra'), 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Không thể thêm vào giỏ hàng', 'error');
        });
    }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>