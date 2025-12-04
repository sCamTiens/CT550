<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .category-item {
            transition: all 0.2s;
        }

        .category-item:hover {
            background-color: #f0f0f0;
        }

        .category-item.active {
            background-color: #cce0ff;
            border-left: 4px solid #002975;
        }

        .subcategory-item {
            font-size: 0.9rem;
        }

        .subcategory-item:hover {
            background-color: #f5f5f5;
        }

        .subcategory-item.active {
            background-color: #e6f0ff;
            border-left: 3px solid #002975;
        }

        .hot-label {
            color: white;
            background: linear-gradient(149deg, #5ba2feff, #2f84f5ff, #043fadff, #043fadff, #2f84f5ff, #5ba2feff);
            background-size: 1200% 1200%;
            animation: hot-animation 5s ease infinite;
        }

        @keyframes hot-animation {
            0% {
                background-position: 15% 0%
            }

            50% {
                background-position: 86% 100%
            }

            100% {
                background-position: 15% 0%
            }
        }

        .hot-label1 {
            color: white;
            background: linear-gradient(149deg, #8A2387, #E94057, #F27121, #F27121, #E94057, #8A2387);
            background-size: 1200% 1200%;
            animation: hot-animation 5s ease infinite, bounce 1s ease;

        }

        @keyframes hot-animation1 {
            0% {
                background-position: 15% 0%
            }

            50% {
                background-position: 86% 100%
            }

            100% {
                background-position: 15% 0%
            }
        }
    </style>
</head>

<body class="bg-gray-50" x-data="{ showFilterModal: false, ...filterModal() }">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="container mx-auto px-4 py-6">
        <div class="flex gap-4">
            <!-- Sidebar Danh mục -->
            <aside class="w-64 flex-shrink-0">
                <div class="bg-[#002975] text-white p-4 rounded-t-lg font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-bars"></i>
                    DANH MỤC SẢN PHẨM
                </div>
                <div class="bg-white rounded-b-lg shadow-md overflow-hidden">
                    <!-- Nút "Tất cả" -->
                    <a href="/"
                        class="category-item block px-4 py-3 border-b border-gray-200 hover:bg-gray-50 <?= empty($selectedCategory) ? 'active' : '' ?>">
                        <i class="fa-solid fa-th mr-2"></i>
                        <span class="font-semibold">Tất cả sản phẩm</span>
                    </a>

                    <?php foreach ($categories as $parent): ?>
                        <?php
                        $isParentActive = $selectedCategory && $selectedCategory['id'] == $parent['id'];
                        $hasActiveChild = false;
                        if ($selectedCategory && !empty($parent['children'])) {
                            foreach ($parent['children'] as $child) {
                                if ($child['id'] == $selectedCategory['id']) {
                                    $hasActiveChild = true;
                                    break;
                                }
                            }
                        }
                        $hasChildren = !empty($parent['children']);
                        ?>

                        <!-- Danh mục cha -->
                        <div class="border-b border-gray-200">
                            <a href="/?category=<?= urlencode($parent['slug']) ?>"
                                class="category-item block py-3 font-semibold <?= $isParentActive ? 'active' : '' ?> <?= $hasChildren ? 'pl-4' : 'pl-8' ?>">
                                <?php if ($hasChildren): ?>
                                    <i class="fa-solid fa-chevron-right mr-2 text-sm"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($parent['name']) ?>
                            </a>

                            <!-- Danh mục con -->
                            <?php if (!empty($parent['children']) && ($isParentActive || $hasActiveChild)): ?>
                                <div class="bg-gray-50">
                                    <?php foreach ($parent['children'] as $child): ?>
                                        <?php $isChildActive = $selectedCategory && $selectedCategory['id'] == $child['id']; ?>
                                        <a href="/?category=<?= urlencode($child['slug']) ?>"
                                            class="subcategory-item block pl-12 pr-4 py-2 text-gray-700 border-l-0 <?= $isChildActive ? 'active' : '' ?>">
                                            <i class="fa-solid fa-angle-right mr-2 text-xs"></i>
                                            <?= htmlspecialchars($child['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>

            <!-- Nội dung chính -->
            <div class="flex-1">
                <!-- Khuyến mãi (hiển thị khi có promotions) -->
                <?php if (!empty($promotions)): ?>
                    <section class="mb-6" x-data="promotionsSlider()">
                        <?php if (isset($_GET['debug'])): ?>
                            <pre style='background: #ffff99; padding: 10px; margin: 10px;'>
                                === IN VIEW (before loop) ===
                                Total promotions: <?= count($promotions) ?>

                                <?php foreach ($promotions as $idx => $p): ?>
                                            [<?= $idx ?>] ID: <?= $p['id'] ?>, Type: <?= $p['promo_type'] ?>, Name: <?= $p['name'] ?>

                                <?php endforeach; ?>
                            </pre>
                        <?php endif; ?>

                        <div class="relative bg-white rounded-xl shadow-lg overflow-hidden">
                            <!-- Slider Container -->
                            <div class="relative h-80">
                                <?php foreach ($promotions as $index => $promo): ?>
                                    <div x-show="currentSlide === <?= $index ?>"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 transform translate-x-full"
                                        x-transition:enter-end="opacity-100 transform translate-x-0"
                                        x-transition:leave="transition ease-in duration-300"
                                        x-transition:leave-start="opacity-100 transform translate-x-0"
                                        x-transition:leave-end="opacity-0 transform -translate-x-full"
                                        class="absolute inset-0 hot-label px-[100px] py-8 text-white">
                                        <div class="flex items-center justify-between h-full">
                                            <div class="flex-1 max-w-xl">
                                                <div
                                                    class="inline-block bg-white bg-opacity-20 backdrop-blur-sm px-4 py-1 rounded-full text-sm font-semibold mb-3 hot-label1">
                                                    <i class="fa-solid fa-fire mr-2"></i>
                                                    <?php
                                                    $typeLabels = [
                                                        'discount' => 'GIẢM GIÁ',
                                                        'combo' => 'COMBO',
                                                        'bundle' => 'MUA KÈM',
                                                        'gift' => 'TẶNG QUÀ'
                                                    ];
                                                    echo $typeLabels[$promo['promo_type']] ?? 'KHUYẾN MÃI';
                                                    ?>
                                                </div>
                                                <h2 class="text-4xl font-bold mb-3"><?= htmlspecialchars($promo['name']) ?></h2>

                                                <!-- DEBUG: Hiển thị index -->
                                                <?php if (isset($_GET['debug'])): ?>
                                                    <div
                                                        class="bg-yellow-400 text-black px-3 py-1 rounded inline-block text-sm font-bold mb-2">
                                                        DEBUG: Index <?= $index ?> - ID: <?= $promo['id'] ?> - Type:
                                                        <?= $promo['promo_type'] ?> - Name: <?= substr($promo['name'], 0, 20) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <p class="text-lg mb-4 line-clamp-2">
                                                    <?= htmlspecialchars($promo['description'] ?? '') ?>
                                                </p>
                                                <button @click="openModal(<?= $promo['id'] ?>)"
                                                    class="bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-600 hover:text-white transition-all shadow-lg">
                                                    <i class="fa-solid fa-gift mr-2"></i>
                                                    Xem chi tiết
                                                </button>
                                            </div>

                                            <!-- Hiển thị hình ảnh sản phẩm -->
                                            <div class="flex-shrink-0 ml-8">
                                                <?php if ($promo['promo_type'] === 'combo' && count($promo['images']) >= 2): ?>
                                                    <!-- Combo: 2 ảnh có nơ đỏ -->
                                                    <div class="relative flex gap-4">
                                                        <div class="w-40 h-40 bg-white rounded-lg p-3 shadow-xl">
                                                            <img src="<?= $promo['images'][0] ?>" alt=""
                                                                class="w-full h-full object-contain">
                                                        </div>
                                                        <div class="w-40 h-40 bg-white rounded-lg p-3 shadow-xl">
                                                            <img src="<?= $promo['images'][1] ?>" alt=""
                                                                class="w-full h-full object-contain">
                                                        </div>
                                                        <!-- Nơ đỏ ngang 2 ảnh -->
                                                        <div
                                                            class="absolute top-1/2 left-0 right-0 flex items-center justify-center transform -translate-y-1/2">
                                                            <div class="bg-red-600 h-8 w-full shadow-xl relative">
                                                                <div class="absolute inset-0 flex items-center justify-center">
                                                                    <div
                                                                        class="bg-red-700 rounded-full w-10 h-10 flex items-center justify-center shadow-xl">
                                                                        <i class="fa-solid fa-gift text-white text-2xl"></i>
                                                                    </div>
                                                                </div>
                                                                <!-- Mũi nơ -->
                                                                <div
                                                                    class="absolute -left-8 top-0 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-red-600">
                                                                </div>
                                                                <div
                                                                    class="absolute -right-8 top-0 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-l-8 border-l-red-600">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Các loại khác: grid ảnh -->
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <?php for ($i = 0; $i < min(4, count($promo['images'])); $i++): ?>
                                                            <div class="w-24 h-24 bg-white rounded-lg p-2 shadow-xl">
                                                                <img src="<?= $promo['images'][$i] ?>" alt=""
                                                                    class="w-full h-full object-contain">
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Navigation Arrows -->
                            <?php if (count($promotions) > 1): ?>
                                <button @click="prevSlide()"
                                    class="absolute left-10 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-50 hover:bg-opacity-100 rounded-full p-3 transition-all">
                                    <i class="fa-solid fa-chevron-left text-2xl text-gray-800"></i>
                                </button>
                                <button @click="nextSlide()"
                                    class="absolute right-10 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-50 hover:bg-opacity-100 rounded-full p-3 transition-all">
                                    <i class="fa-solid fa-chevron-right text-2xl text-gray-800"></i>
                                </button>

                                <!-- Dots Indicator -->
                                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2">
                                    <?php foreach ($promotions as $index => $promo): ?>
                                        <button @click="currentSlide = <?= $index ?>"
                                            :class="currentSlide === <?= $index ?> ? 'bg-white w-8' : 'bg-white bg-opacity-50 w-3'"
                                            class="h-3 rounded-full transition-all"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Modal Chi tiết khuyến mãi -->
                        <div x-show="showModal" x-cloak @click.self="showModal = false"
                            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
                                @click.stop>
                                <!-- Header -->
                                <div
                                    class="sticky top-0 bg-gradient-to-r from-orange-500 to-red-600 text-white p-6 flex items-center justify-between z-10">
                                    <h3 class="text-2xl font-bold" x-text="modalData.name"></h3>
                                    <button @click="showModal = false" class="text-white hover:text-gray-200">
                                        <i class="fa-solid fa-times text-2xl"></i>
                                    </button>
                                </div>

                                <!-- Body -->
                                <div class="p-6">
                                    <p class="text-gray-600 mb-4" x-text="modalData.description"></p>

                                    <!-- Thời gian khuyến mãi -->
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-6">
                                        <div class="flex items-center gap-4 text-sm">
                                            <div>
                                                <i class="fa-solid fa-calendar-check text-blue-600 mr-2"></i>
                                                <span class="text-gray-700">Bắt đầu:</span>
                                                <span class="font-semibold"
                                                    x-text="new Date(modalData.starts_at).toLocaleDateString('vi-VN')"></span>
                                            </div>
                                            <div>
                                                <i class="fa-solid fa-calendar-xmark text-blue-600 mr-2"></i>
                                                <span class="text-gray-700">Kết thúc:</span>
                                                <span class="font-semibold"
                                                    x-text="new Date(modalData.ends_at).toLocaleDateString('vi-VN')"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hiển thị chi tiết theo loại -->
                                    <template x-if="modalData.promo_type === 'combo'">
                                        <div>
                                            <h4 class="font-bold text-lg mb-4 text-[#002975]">
                                                <i class="fa-solid fa-box-open mr-2"></i>
                                                Sản phẩm trong combo
                                            </h4>
                                            <div class="grid grid-cols-2 gap-4 mb-6">
                                                <template x-for="item in modalData.products" :key="item.product_id">
                                                    <div class="border rounded-lg p-4 hover:shadow-lg transition-all">
                                                        <img :src="item.image_url || '/assets/images/no-image.png'"
                                                            :alt="item.name" class="w-full h-40 object-contain mb-3">
                                                        <h5 class="font-semibold mb-2" x-text="item.name"></h5>
                                                        <div class="text-sm text-gray-600 mb-1">
                                                            Số lượng: <span class="font-semibold"
                                                                x-text="item.required_qty"></span>
                                                        </div>
                                                        <div class="text-base font-semibold text-blue-600">
                                                            <span x-text="formatCurrency(item.sale_price)"></span>₫/sp
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="bg-orange-50 rounded-lg p-4 mb-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <div class="text-sm text-gray-600 mb-1">Tổng giá lẻ:</div>
                                                        <div class="text-lg text-gray-500 line-through"
                                                            x-text="formatCurrency(modalData.products.reduce((sum, p) => sum + (p.sale_price * p.required_qty), 0)) + '₫'">
                                                        </div>
                                                    </div>
                                                    <i class="fa-solid fa-arrow-right text-2xl text-orange-500"></i>
                                                    <div class="text-right">
                                                        <div class="text-sm text-gray-600 mb-1">Giá combo:</div>
                                                        <div class="text-2xl font-bold text-orange-600">
                                                            <span x-text="formatCurrency(modalData.combo_price)"></span>₫
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click="addComboToCart()"
                                                class="w-full bg-[#002975] text-white py-3 rounded-lg font-bold hover:bg-[#001a54] transition-all">
                                                <i class="fa-solid fa-cart-plus mr-2"></i>
                                                Thêm combo vào giỏ hàng
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="modalData.promo_type === 'discount'">
                                        <div>
                                            <div class="bg-red-50 rounded-lg p-4 mb-6">
                                                <div class="text-xl font-bold text-red-600">
                                                    Giảm <span
                                                        x-text="modalData.discount_type === 'percentage' ? modalData.discount_value + '%' : formatCurrency(modalData.discount_value) + '₫'"></span>
                                                </div>
                                            </div>
                                            <h4 class="font-bold text-lg mb-4 text-[#002975]">Sản phẩm được giảm giá</h4>
                                            <div class="grid grid-cols-4 gap-4">
                                                <template x-for="item in modalData.products" :key="item.product_id">
                                                    <div
                                                        class="border rounded-lg p-3 hover:shadow-lg transition-all flex flex-col h-80">
                                                        <div
                                                            class="flex-shrink-0 h-32 flex items-center justify-center mb-2 bg-white rounded">
                                                            <img :src="item.image_url || '/assets/images/no-image.png'"
                                                                :alt="item.name" class="max-h-28 object-contain">
                                                        </div>
                                                        <div class="flex-1">
                                                            <h6 class="text-sm font-semibold mb-2 line-clamp-2"
                                                                x-text="item.name"></h6>
                                                            <div class="text-sm text-gray-500 line-through"
                                                                x-text="formatCurrency(item.sale_price) + '₫'"></div>
                                                            <div class="text-lg font-bold text-red-600"
                                                                x-text="formatCurrency(calculateDiscountPrice(item.sale_price)) + '₫'">
                                                            </div>
                                                        </div>
                                                        <button @click="addToCart(item.product_id)"
                                                            class="mt-3 w-full border border-[#002975] text-[#002975] py-2 rounded text-sm hover:bg-[#002975] hover:text-white transition-all flex-shrink-0">
                                                            <i class="fa-solid fa-cart-plus mr-1"></i>
                                                            Thêm
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="modalData.promo_type === 'bundle'">
                                        <div>
                                            <h4 class="font-bold text-lg mb-4 text-[#002975]">Chương trình mua kèm</h4>
                                            <template x-for="item in modalData.products" :key="item.product_id">
                                                <div class="border rounded-lg p-4 mb-4 hover:shadow-lg transition-all">
                                                    <div class="flex gap-4">
                                                        <img :src="item.image_url || '/assets/images/no-image.png'"
                                                            :alt="item.name" class="w-32 h-32 object-contain">
                                                        <div class="flex-1">
                                                            <h5 class="font-semibold mb-2" x-text="item.name"></h5>
                                                            <div class="text-sm text-gray-600 mb-2">
                                                                Mua <span class="font-bold text-orange-600"
                                                                    x-text="item.required_qty"></span> sản phẩm
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <span class="text-gray-500 line-through"
                                                                    x-text="formatCurrency(item.sale_price * item.required_qty) + '₫'"></span>
                                                                <span class="text-xl font-bold text-red-600"
                                                                    x-text="formatCurrency(item.bundle_price) + '₫'"></span>
                                                            </div>
                                                            <button @click="addBundleToCart(item)"
                                                                class="mt-3 border border-[#002975] text-[#002975] px-6 py-2 rounded hover:bg-[#002975] hover-text-white transition-all">
                                                                <i class="fa-solid fa-cart-plus mr-2"></i>
                                                                Thêm vào giỏ
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="modalData.promo_type === 'gift'">
                                        <div>
                                            <h4 class="font-bold text-lg mb-4 text-[#002975]">Mua hàng nhận quà</h4>
                                            <template x-for="item in modalData.products" :key="item.product_id">
                                                <div
                                                    class="border rounded-lg p-4 mb-4 bg-gradient-to-r from-green-50 to-blue-50">
                                                    <div class="flex items-center gap-4">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-3 mb-3">
                                                                <img :src="item.image_url || '/assets/images/no-image.png'"
                                                                    :alt="item.name"
                                                                    class="w-24 h-24 object-contain border rounded p-2 bg-white">
                                                                <div>
                                                                    <h5 class="font-semibold" x-text="item.name"></h5>
                                                                    <div class="text-sm text-gray-600">
                                                                        Mua <span class="font-bold text-blue-600"
                                                                            x-text="item.required_qty"></span> sản phẩm
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <i class="fa-solid fa-arrow-right text-3xl text-green-600"></i>
                                                        <div class="flex-1">
                                                            <div
                                                                class="bg-yellow-100 border-2 border-yellow-400 rounded-lg p-3 relative">
                                                                <div
                                                                    class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-yellow-400 px-3 py-1 rounded-full text-xs font-bold">
                                                                    TẶNG QUÀ
                                                                </div>
                                                                <div class="flex items-center gap-3 mt-2">
                                                                    <img :src="item.gift_image_url || '/assets/images/no-image.png'"
                                                                        :alt="item.gift_name"
                                                                        class="w-24 h-24 object-contain">
                                                                    <div>
                                                                        <h5 class="font-semibold" x-text="item.gift_name">
                                                                        </h5>
                                                                        <div class="text-sm text-gray-600">
                                                                            Số lượng: <span class="font-bold"
                                                                                x-text="item.gift_qty"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button @click="addToCart(item.product_id, item.required_qty)"
                                                        class="mt-3 w-full border border-[#002975] text-[#002975] py-2 rounded hover:bg-[#002975] hover:text-white transition-all">
                                                        <i class="fa-solid fa-cart-plus mr-2"></i>
                                                        Thêm vào giỏ để nhận quà
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Tiêu đề danh mục + Nút Filter -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <?php if (isset($query) && !empty($query)): ?>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                <i class="fa-solid fa-search text-[#002975] mr-2"></i>
                                Kết quả tìm kiếm: "<?= htmlspecialchars($query) ?>"
                            </h2>
                            <p class="text-gray-600">
                                Tìm thấy <?= $products['total'] ?? 0 ?> sản phẩm
                            </p>
                        <?php elseif ($selectedCategory): ?>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                <i class="fa-solid fa-tag text-[#002975] mr-2"></i>
                                <?= htmlspecialchars($selectedCategory['name']) ?>
                            </h2>
                            <p class="text-gray-600">
                                Tìm thấy <?= $products['total'] ?? 0 ?> sản phẩm
                            </p>
                        <?php else: ?>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                <i class="fa-solid fa-sparkles text-[#002975] mr-2"></i>
                                Tất cả sản phẩm
                            </h2>
                            <p class="text-gray-600">
                                Khám phá <?= $products['total'] ?? 0 ?> sản phẩm của chúng tôi
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Nút Filter -->
                    <button @click="showFilterModal = true" title="Lọc sản phẩm"
                        class="flex items-center gap-2 px-6 py-3 bg-white border-2 border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-all font-semibold shadow-md">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>

                <!-- Danh sách sản phẩm -->
                <?php if (empty($products['data'])): ?>
                    <div class="bg-white rounded-xl shadow-md p-12 text-center">
                        <i class="fa-solid fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">Không có sản phẩm nào trong danh mục này</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-5 gap-4 mb-6">
                        <?php foreach ($products['data'] as $p): ?>
                            <div x-data="{ qty: 1 }"
                                class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                                <!-- Ảnh - Link đến chi tiết -->
                                <a href="/products/<?= htmlspecialchars($p['slug'] ?? '') ?>">
                                    <div class="h-64 bg-gray-100 flex items-center justify-center overflow-hidden p-2">
                                        <?php if (!empty($p['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($p['image_url']) ?>?t=<?= !empty($p['updated_at']) ? strtotime($p['updated_at']) : time() ?>"
                                                alt="<?= htmlspecialchars($p['name']) ?>"
                                                class="w-full h-full object-contain hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <i class="fa-solid fa-image text-5xl text-gray-300"></i>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="p-3">
                                    <!-- Tên sản phẩm - Link đến chi tiết -->
                                    <a href="/products/<?= htmlspecialchars($p['slug'] ?? '') ?>">
                                        <h3
                                            class="font-medium text-gray-800 mb-2 line-clamp-2 text-sm h-10 hover:text-[#002975] transition-colors">
                                            <?= htmlspecialchars($p['name'] ?? 'No name') ?>
                                        </h3>
                                    </a>

                                    <!-- Giá -->
                                    <p class="text-xl font-bold text-red-600 mb-3">
                                        <?= number_format((float) ($p['price'] ?? 0), 0, ',', '.') ?>₫
                                    </p>

                                    <!-- Số lượng -->
                                    <div class="flex items-center justify-center gap-2 mb-2">
                                        <button @click="qty = Math.max(0, Number(qty) - 1)" type="button"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                            <i class="fa-solid fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" x-model.number="qty"
                                            @blur="qty = Math.max(0, Math.min(9999, Number(qty) || 0))" min="0" max="9999"
                                            class="w-16 text-center border border-gray-300 rounded py-1 font-semibold text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        <button @click="qty = Math.min(9999, Number(qty) + 1)" type="button"
                                            class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                        </button>
                                    </div>

                                    <!-- Nút Thêm vào giỏ -->
                                    <button
                                        @click="addProductToCart(<?= (int) $p['id'] ?>, qty, <?= (int) ($p['stock_qty'] ?? 0) ?>)"
                                        class="w-full px-3 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-all text-sm font-semibold mb-2">
                                        <i class="fa-solid fa-cart-plus mr-1"></i>
                                        Thêm vào giỏ
                                    </button>

                                    <!-- Nút Mua ngay -->
                                    <button @click="buyProductNow(<?= (int) $p['id'] ?>, <?= (int) ($p['stock_qty'] ?? 0) ?>)"
                                        class="w-full px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all text-sm font-semibold">
                                        <i class="fa-solid fa-shopping-bag mr-1"></i>
                                        Mua ngay
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Phân trang -->
                    <?php if ($products['pages'] > 1): ?>
                        <div class="flex justify-center items-center gap-2 bg-white rounded-lg shadow-md p-4">
                            <?php
                            $currentPage = $products['page'] ?? 1;
                            $totalPages = $products['pages'];
                            $categoryParam = $categorySlug ? '&category=' . urlencode($categorySlug) : '';
                            ?>

                            <!-- Previous button -->
                            <?php if ($currentPage > 1): ?>
                                <a href="/?page=<?= $currentPage - 1 ?><?= $categoryParam ?>"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);

                            if ($startPage > 1): ?>
                                <a href="/?page=1<?= $categoryParam ?>"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="px-2">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="/?page=<?= $i ?><?= $categoryParam ?>"
                                    class="px-4 py-2 rounded-lg transition-colors <?= $i == $currentPage ? 'bg-[#002975] text-white font-bold' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="px-2">...</span>
                                <?php endif; ?>
                                <a href="/?page=<?= $totalPages ?><?= $categoryParam ?>"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"><?= $totalPages ?></a>
                            <?php endif; ?>

                            <!-- Next button -->
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="/?page=<?= $currentPage + 1 ?><?= $categoryParam ?>"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Modal -->
        <?php require __DIR__ . '/../partials/filter_modal.php'; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">MiniGo</h3>
                    <p class="text-gray-400">Siêu thị mini - Mọi thứ bạn cần, ngay tại đây!</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Liên kết</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/about" class="hover:text-white">Về chúng tôi</a></li>
                        <li><a href="/contact" class="hover:text-white">Liên hệ</a></li>
                        <li><a href="/terms" class="hover:text-white">Điều khoản</a></li>
                        <li><a href="/privacy" class="hover:text-white">Bảo mật</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Hỗ trợ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/faq" class="hover:text-white">FAQ</a></li>
                        <li><a href="/shipping" class="hover:text-white">Vận chuyển</a></li>
                        <li><a href="/returns" class="hover:text-white">Đổi trả</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Liên hệ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fa-solid fa-phone mr-2"></i> 0123 456 789</li>
                        <li><i class="fa-solid fa-envelope mr-2"></i> contact@minigo.vn</li>
                        <li><i class="fa-solid fa-location-dot mr-2"></i> TP. Hồ Chí Minh</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 MiniGo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function promotionsSlider() {
            return {
                currentSlide: 0,
                showModal: false,
                modalData: {
                    id: null,
                    name: '',
                    description: '',
                    promo_type: '',
                    discount_type: '',
                    discount_value: 0,
                    combo_price: 0,
                    products: []
                },
                // Drag/Swipe variables
                isDragging: false,
                startX: 0,
                currentX: 0,
                dragThreshold: 50,

                init() {
                    // Auto slide every 5 seconds
                    setInterval(() => {
                        this.nextSlide();
                    }, 5000);

                    // Setup drag/swipe listeners
                    this.$nextTick(() => {
                        const slider = this.$el.querySelector('.relative.h-80');
                        if (slider) {
                            // Mouse events
                            slider.addEventListener('mousedown', this.handleDragStart.bind(this));
                            slider.addEventListener('mousemove', this.handleDragMove.bind(this));
                            slider.addEventListener('mouseup', this.handleDragEnd.bind(this));
                            slider.addEventListener('mouseleave', this.handleDragEnd.bind(this));

                            // Touch events
                            slider.addEventListener('touchstart', this.handleDragStart.bind(this));
                            slider.addEventListener('touchmove', this.handleDragMove.bind(this));
                            slider.addEventListener('touchend', this.handleDragEnd.bind(this));

                            // Prevent default drag behavior
                            slider.style.cursor = 'grab';
                        }
                    });
                },

                handleDragStart(e) {
                    this.isDragging = true;
                    this.startX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
                    this.currentX = this.startX;

                    const slider = e.currentTarget;
                    slider.style.cursor = 'grabbing';
                },

                handleDragMove(e) {
                    if (!this.isDragging) return;

                    e.preventDefault();
                    this.currentX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
                },

                handleDragEnd(e) {
                    if (!this.isDragging) return;

                    this.isDragging = false;
                    const slider = e.currentTarget;
                    if (slider) {
                        slider.style.cursor = 'grab';
                    }

                    const diff = this.startX - this.currentX;

                    console.log('Drag diff:', diff, 'Threshold:', this.dragThreshold);

                    // If dragged more than threshold
                    if (Math.abs(diff) > this.dragThreshold) {
                        if (diff > 0) {
                            // Dragged left (startX > currentX) - next slide
                            console.log('Next slide');
                            this.nextSlide();
                        } else {
                            // Dragged right (startX < currentX) - previous slide
                            console.log('Previous slide');
                            this.prevSlide();
                        }
                    } else {
                        console.log('Drag too short, no slide change');
                    }

                    this.startX = 0;
                    this.currentX = 0;
                },

                nextSlide() {
                    const total = <?= count($promotions) ?>;
                    const oldSlide = this.currentSlide;
                    this.currentSlide = (this.currentSlide + 1) % total;
                },

                prevSlide() {
                    const total = <?= count($promotions) ?>;
                    const oldSlide = this.currentSlide;
                    this.currentSlide = (this.currentSlide - 1 + total) % total;
                },

                async openModal(promotionId) {
                    try {
                        const response = await fetch(`/api/promotions/${promotionId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.modalData = data.promotion;
                            this.showModal = true;
                        }
                    } catch (error) {
                        console.error('Error loading promotion details:', error);
                        alert('Không thể tải thông tin khuyến mãi');
                    }
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('vi-VN').format(amount);
                },

                calculateDiscountPrice(originalPrice) {
                    if (this.modalData.discount_type === 'percentage') {
                        return originalPrice * (1 - this.modalData.discount_value / 100);
                    } else {
                        return originalPrice - this.modalData.discount_value;
                    }
                },

                async addToCart(productId, quantity = 1) {
                    try {
                        // Check if user is logged in (using session)
                        if (!window.isUserLoggedIn) {
                            showToast('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        const response = await window.fetchWithAuth('/api/cart/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                product_id: productId,
                                quantity: quantity,
                                promotion_id: this.modalData.id
                            })
                        });

                        const data = await response.json();

                        if (response.status === 401) {
                            showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        if (data.success) {
                            showToast('Đã thêm vào giỏ hàng!', 'success');
                            window.dispatchEvent(new CustomEvent('cart-updated', {
                                detail: {
                                    cart_count: data.cart_count
                                }
                            }));
                        } else {
                            showToast(data.message || 'Không thể thêm vào giỏ hàng', 'error');
                        }
                    } catch (error) {
                        console.error('Error adding to cart:', error);
                        showToast('Có lỗi xảy ra', 'error');
                    }
                },

                async addComboToCart() {
                    try {
                        // Check if user is logged in (using session)
                        if (!window.isUserLoggedIn) {
                            showToast('Bạn cần đăng nhập để thêm combo vào giỏ hàng', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        const items = this.modalData.products.map(p => ({
                            product_id: p.product_id,
                            quantity: p.required_qty
                        }));

                        const response = await window.fetchWithAuth('/api/cart/add-combo', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                promotion_id: this.modalData.id,
                                items: items
                            })
                        });

                        const data = await response.json();

                        if (response.status === 401) {
                            showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        if (data.success) {
                            showToast('Đã thêm combo vào giỏ hàng!', 'success');
                            this.showModal = false;
                            window.dispatchEvent(new CustomEvent('cart-updated', {
                                detail: {
                                    cart_count: data.cart_count
                                }
                            }));
                        } else {
                            showToast(data.message || 'Không thể thêm combo', 'error');
                        }
                    } catch (error) {
                        console.error('Error adding combo:', error);
                        showToast('Có lỗi xảy ra', 'error');
                    }
                },

                async addBundleToCart(item) {
                    try {
                        // Check if user is logged in (using session)
                        if (!window.isUserLoggedIn) {
                            showToast('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        const response = await fetch('/api/cart/add-bundle', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                promotion_id: this.modalData.id,
                                product_id: item.product_id,
                                quantity: item.required_qty,
                                bundle_price: item.bundle_price
                            })
                        });

                        const data = await response.json();

                        if (response.status === 401) {
                            showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                            window.location.href = '/login';
                            return;
                        }

                        if (data.success) {
                            showToast('Đã thêm vào giỏ hàng!', 'success');
                            window.dispatchEvent(new CustomEvent('cart-updated', {
                                detail: {
                                    cart_count: data.cart_count
                                }
                            }));
                        } else {
                            showToast(data.message || 'Không thể thêm vào giỏ hàng', 'error');
                        }
                    } catch (error) {
                        console.error('Error adding bundle:', error);
                        showToast('Có lỗi xảy ra', 'error');
                    }
                }
            }
        }

        // Global functions for product cards in home page
        async function addProductToCart(productId, quantity, stockQty) {
            // Validate quantity
            if (!quantity || quantity <= 0) {
                showToast('Vui lòng nhập số lượng hợp lệ', 'error');
                return;
            }

            // Check stock
            if (quantity > stockQty) {
                showToast('Số lượng tồn kho không đủ', 'error');
                return;
            }

            // Check if user is logged in (using session)
            if (!window.isUserLoggedIn) {
                showToast('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng', 'error');
                window.location.href = '/login';
                return;
            }

            try {
                const response = await window.fetchWithAuth('/api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                });

                if (response.status === 401) {
                    showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                    window.location.href = '/login';
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    showToast(`Đã thêm ${quantity} sản phẩm vào giỏ hàng!`, 'success');
                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: {
                            cart_count: data.cart_count
                        }
                    }));
                } else {
                    showToast(data.message || 'Không thể thêm vào giỏ hàng', 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showToast('Có lỗi xảy ra', 'error');
            }
        }

        async function buyProductNow(productId, stockQty) {
            // Check stock (always buy 1 item)
            if (stockQty < 1) {
                showToast('Số lượng tồn kho không đủ', 'error');
                return;
            }

            // Check if user is logged in (using session)
            if (!window.isUserLoggedIn) {
                showToast('Bạn cần đăng nhập để mua hàng', 'error');
                window.location.href = '/login';
                return;
            }

            try {
                // Add 1 product to cart first
                const response = await window.fetchWithAuth('/api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                if (response.status === 401) {
                    showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại', 'error');
                    window.location.href = '/login';
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    // Redirect to checkout with only this product
                    window.location.href = `/checkout?product_id=${productId}`;
                } else {
                    showToast(data.message || 'Không thể mua hàng', 'error');
                }
            } catch (error) {
                console.error('Error buying product:', error);
                showToast('Có lỗi xảy ra', 'error');
            }
        }

        // Filter Modal Component
        function filterModal() {
            // Get current URL params
            const urlParams = new URLSearchParams(window.location.search);

            return {
                filters: {
                    min_price: urlParams.get('min_price') || '',
                    max_price: urlParams.get('max_price') || '',
                    brands: urlParams.get('brands') ? urlParams.get('brands').split(',').map(Number) : [],
                    sort: urlParams.get('sort') || 'newest'
                },

                toggleBrand(brandId) {
                    const index = this.filters.brands.indexOf(brandId);
                    if (index > -1) {
                        this.filters.brands.splice(index, 1);
                    } else {
                        this.filters.brands.push(brandId);
                    }
                },

                resetFilters() {
                    this.filters = {
                        min_price: '',
                        max_price: '',
                        brands: [],
                        sort: 'newest'
                    };
                },

                applyFilters() {
                    const params = new URLSearchParams(window.location.search);

                    // Keep category if exists
                    const category = params.get('category');

                    // Build new URL
                    const newParams = new URLSearchParams();
                    if (category) newParams.set('category', category);

                    if (this.filters.min_price) newParams.set('min_price', this.filters.min_price);
                    if (this.filters.max_price) newParams.set('max_price', this.filters.max_price);
                    if (this.filters.brands.length > 0) newParams.set('brands', this.filters.brands.join(','));
                    if (this.filters.sort && this.filters.sort !== 'newest') newParams.set('sort', this.filters.sort);

                    // Redirect
                    window.location.href = '/?' + newParams.toString();
                }
            };
        }
    </script>
</body>

</html>
