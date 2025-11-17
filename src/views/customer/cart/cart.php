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
            <i class="fa-solid fa-shopping-cart text-[#002975]"></i>
            Giỏ hàng của bạn
        </h1>

        <?php if (empty($cartItems)): ?>
            <!-- Giỏ hàng trống -->
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fa-solid fa-cart-shopping text-8xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">Giỏ hàng trống</h2>
                <p class="text-gray-500 mb-6">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="/" class="inline-block bg-[#002975] text-white px-8 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
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
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex gap-4 p-4 border-b last:border-b-0 hover:bg-gray-50" data-product-id="<?= $item['id'] ?>">
                                <!-- Hình ảnh -->
                                <div class="flex-shrink-0">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         class="w-24 h-24 object-cover rounded-lg border-2">
                                </div>

                                <!-- Thông tin -->
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg mb-1">
                                        <a href="/products/<?= htmlspecialchars($item['slug']) ?>" 
                                           class="hover:text-[#002975]">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </h3>
                                    <div class="text-red-600 font-semibold mb-2">
                                        <?= number_format($item['price'], 0, ',', '.') ?>₫
                                    </div>
                                    
                                    <!-- Số lượng -->
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center border-2 rounded-lg">
                                            <button onclick="updateQty(<?= $item['id'] ?>, -1)" 
                                                    class="px-3 py-1 text-gray-600 hover:bg-gray-100 font-bold">
                                                -
                                            </button>
                                            <input type="number" 
                                                   id="qty-<?= $item['id'] ?>"
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" 
                                                   max="<?= $item['stock_qty'] ?>"
                                                   class="w-16 text-center border-x-2 py-1 font-semibold"
                                                   onchange="changeQty(<?= $item['id'] ?>, this.value)">
                                            <button onclick="updateQty(<?= $item['id'] ?>, 1)" 
                                                    class="px-3 py-1 text-gray-600 hover:bg-gray-100 font-bold">
                                                +
                                            </button>
                                        </div>
                                        
                                        <div class="text-gray-600 text-sm">
                                            Còn <?= $item['stock_qty'] ?> sản phẩm
                                        </div>
                                    </div>
                                </div>

                                <!-- Thành tiền & Xóa -->
                                <div class="flex flex-col items-end justify-between">
                                    <button onclick="removeItem(<?= $item['id'] ?>)" 
                                            class="text-red-600 hover:text-red-800 p-2">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <div class="text-xl font-bold text-[#002975]" id="subtotal-<?= $item['id'] ?>">
                                        <?= number_format($item['subtotal'], 0, ',', '.') ?>₫
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tổng tiền -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-bold mb-4 pb-4 border-b">Tổng đơn hàng</h2>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-gray-600">
                                <span>Tạm tính:</span>
                                <span id="temp-total"><?= number_format($total, 0, ',', '.') ?>₫</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển:</span>
                                <span>Miễn phí</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Tổng cộng:</span>
                                <span class="text-2xl font-bold text-red-600" id="total-price">
                                    <?= number_format($total, 0, ',', '.') ?>₫
                                </span>
                            </div>
                        </div>

                        <a href="/checkout" 
                           class="block w-full bg-[#002975] text-white text-center px-6 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold text-lg mb-3">
                            <i class="fa-solid fa-credit-card mr-2"></i>
                            Thanh toán
                        </a>

                        <a href="/" 
                           class="block w-full bg-gray-200 text-gray-700 text-center px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                            <i class="fa-solid fa-arrow-left mr-2"></i>
                            Tiếp tục mua sắm
                        </a>

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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: parseInt(quantity) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật subtotal
                    const item = data.item;
                    if (item) {
                        const subtotalEl = document.getElementById(`subtotal-${productId}`);
                        if (subtotalEl) {
                            subtotalEl.textContent = new Intl.NumberFormat('vi-VN').format(item.subtotal) + '₫';
                        }
                        
                        // Cập nhật tổng tiền
                        const tempTotalEl = document.getElementById('temp-total');
                        const totalPriceEl = document.getElementById('total-price');
                        if (tempTotalEl && totalPriceEl) {
                            const formatted = new Intl.NumberFormat('vi-VN').format(data.total) + '₫';
                            tempTotalEl.textContent = formatted;
                            totalPriceEl.textContent = formatted;
                        }
                        
                        // Cập nhật badge giỏ hàng
                        const badge = document.getElementById('cart-badge');
                        if (badge && data.cart_count !== undefined) {
                            badge.textContent = data.cart_count;
                        }
                    }
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Không thể cập nhật giỏ hàng', 'error');
            });
        }

        function removeItem(productId) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
            
            fetch('/cart/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã xóa sản phẩm', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Không thể xóa sản phẩm', 'error');
            });
        }

        function clearCart() {
            if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;
            
            fetch('/cart/clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã xóa toàn bộ giỏ hàng', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
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
