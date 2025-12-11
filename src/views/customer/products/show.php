<?php require __DIR__ . '/../partials/head.php'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<?php
// Use images from database (product_images table already ordered by is_primary DESC, sort_order ASC)
$allImages = !empty($images) && is_array($images) ? $images : [];

// Fallback: if no images in DB, use product image_url
if (empty($allImages) && !empty($product['image_url'])) {
    $timestamp = !empty($product['updated_at']) ? strtotime($product['updated_at']) : time();
    $allImages[] = $product['image_url'] . '?t=' . $timestamp;
}
?>

<style>
    /* Force horizontal layout for thumbnails - Enhanced */
    .product-thumbnails {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        gap: 0.75rem !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        padding-bottom: 0.5rem !important;
        width: 100% !important;
        align-items: center !important;
    }

    .product-thumbnails img {
        flex-shrink: 0 !important;
        width: 90px !important;
        height: 90px !important;
        object-fit: cover !important;
    }

    /* Scrollbar styling for thumbnails */
    .product-thumbnails::-webkit-scrollbar {
        height: 6px;
    }

    .product-thumbnails::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .product-thumbnails::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .product-thumbnails::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<main class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Hình ảnh sản phẩm -->
            <div class="flex-shrink-0 w-full md:w-2/5">
                <!-- Image Gallery -->
                <div x-data="productGallery()" x-init="init()">
                    <!-- Main Image với Navigation Arrows -->
                    <div class="relative bg-gray-50 rounded-xl border-2 p-4 mb-4">
                        <div class="relative flex items-center justify-center min-h-[400px]">
                            <img x-show="currentImage"
                                x-bind:src="currentImage"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-full h-auto max-h-96 object-contain">

                            <!-- Navigation Arrows -->
                            <template x-if="imagesList.length > 1">
                                <div>
                                    <button x-on:click="previousImage()" type="button"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all hover:scale-110 border-2 border-gray-200">
                                        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>

                                    <button x-on:click="nextImage()" type="button"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all hover:scale-110 border-2 border-gray-200">
                                        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Horizontal Thumbnails - NẰM NGANG (PHP conditional) -->
                    <?php if (count($allImages) > 1): ?>
                        <div class="product-thumbnails">
                            <template x-for="(img, index) in imagesList" x-bind:key="index">
                                <img x-bind:src="img"
                                    x-on:click="selectImage(index)"
                                    x-bind:class="currentIndex === index ? 'border-[#002975] border-4 ring-2 ring-[#002975]/30' : 'border-gray-300 border-2 hover:border-[#002975]'"
                                    alt="Thumbnail"
                                    class="flex-shrink-0 object-cover rounded-lg cursor-pointer transition-all">
                            </template>
                        </div>
                    <?php endif; ?>
                </div>
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
                                <button onclick="decreaseQty()" type="button"
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
                                <button onclick="increaseQty()" type="button"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 font-bold text-xl">
                                    +
                                </button>
                            </div>
                            <button onclick="addToCart()" type="button"
                                class="flex-1 border border-[#002975] text-[#002975] px-8 py-3 rounded-lg hover:bg-[#002975] hover:text-white transition-colors font-semibold text-lg">
                                <i class="fa-solid fa-cart-plus mr-2"></i>
                                Thêm vào giỏ hàng
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="buyNow()" type="button"
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
    // Alpine.js component for product gallery
    function productGallery() {
        return {
            imagesList: <?= json_encode($allImages) ?>,
            currentIndex: 0,
            currentImage: '',

            init() {
                console.log('📸 Images loaded:', this.imagesList.length, 'images');
                console.log('Images array:', this.imagesList);
                if (this.imagesList.length > 0) {
                    this.currentImage = this.imagesList[0];
                }
            },

            selectImage(index) {
                this.currentIndex = index;
                this.currentImage = this.imagesList[index];
            },

            nextImage() {
                this.currentIndex = (this.currentIndex + 1) % this.imagesList.length;
                this.currentImage = this.imagesList[this.currentIndex];
            },

            previousImage() {
                this.currentIndex = (this.currentIndex - 1 + this.imagesList.length) % this.imagesList.length;
                this.currentImage = this.imagesList[this.currentIndex];
            }
        }
    }

    // Quantity controls
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

        if (qty <= 0) {
            showToast('Vui lòng nhập số lượng hợp lệ', 'error');
            return;
        }

        if (qty > stockQty) {
            showToast('Số lượng tồn kho không đủ', 'error');
            return;
        }

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

        if (qty <= 0) {
            showToast('Vui lòng nhập số lượng hợp lệ', 'error');
            return;
        }

        if (qty > stockQty) {
            showToast('Số lượng tồn kho không đủ', 'error');
            return;
        }

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