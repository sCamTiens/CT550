<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
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
    </style>
</head>

<body class="bg-gray-50">
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

                <!-- Banner khuyến mãi -->
                <div class="mt-4 bg-gradient-to-br from-orange-400 to-red-500 rounded-lg p-6 text-white shadow-lg">
                    <div class="text-center">
                        <i class="fa-solid fa-gift text-4xl mb-3"></i>
                        <h3 class="font-bold text-lg mb-2">ƯU ĐÃI HOT</h3>
                        <p class="text-sm mb-3">Giảm giá đến 50%</p>
                        <a href="/promotions" class="inline-block bg-white text-orange-600 px-4 py-2 rounded-full text-sm font-semibold hover:bg-gray-100">
                            Xem ngay
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Nội dung chính -->
            <div class="flex-1">
                <!-- Hero Banner (chỉ hiển thị khi xem tất cả) -->
                <?php if (empty($selectedCategory)): ?>
                <section class="bg-gradient-to-r from-[#002975] to-[#0047AB] rounded-xl p-8 text-white mb-6 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="max-w-xl">
                            <h1 class="text-4xl font-bold mb-3">Chào mừng đến với MiniGo</h1>
                            <p class="text-lg mb-4">Siêu thị mini - Mọi thứ bạn cần, ngay tại đây!</p>
                            <div class="flex gap-3">
                                <span class="bg-white bg-opacity-20 px-4 py-2 rounded-lg backdrop-blur-sm">
                                    <i class="fa-solid fa-truck-fast mr-2"></i>Giao hàng nhanh
                                </span>
                                <span class="bg-white bg-opacity-20 px-4 py-2 rounded-lg backdrop-blur-sm">
                                    <i class="fa-solid fa-shield-halved mr-2"></i>Đảm bảo chất lượng
                                </span>
                            </div>
                        </div>
                        <i class="fa-solid fa-shopping-cart text-8xl opacity-20"></i>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Tiêu đề danh mục -->
                <div class="mb-6">
                    <?php if ($selectedCategory): ?>
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

                <!-- Danh sách sản phẩm -->
                <?php if (empty($products['data'])): ?>
                    <div class="bg-white rounded-xl shadow-md p-12 text-center">
                        <i class="fa-solid fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">Không có sản phẩm nào trong danh mục này</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-5 gap-4 mb-6">
                        <?php foreach ($products['data'] as $p): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                <a href="/products/<?= htmlspecialchars($p['slug'] ?? '') ?>">
                                    <div class="h-100 bg-gray-100 flex items-center justify-center overflow-hidden">
                                        <?php if (!empty($p['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($p['image_url']) ?>?t=<?= !empty($p['updated_at']) ? strtotime($p['updated_at']) : time() ?>"
                                                alt="<?= htmlspecialchars($p['name']) ?>" 
                                                class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                        <?php else: ?>
                                            <i class="fa-solid fa-image text-5xl text-gray-300"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3">
                                        <h3 class="font-medium text-gray-800 mb-2 line-clamp-2 text-sm h-10">
                                            <?= htmlspecialchars($p['name'] ?? 'No name') ?>
                                        </h3>
                                        <p class="text-xl font-bold text-red-600 mb-2">
                                            <?= number_format((float) ($p['price'] ?? 0), 0, ',', '.') ?>₫
                                        </p>
                                        <button class="w-full px-3 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors text-sm font-semibold">
                                            <i class="fa-solid fa-cart-plus mr-1"></i>
                                            Thêm vào giỏ
                                        </button>
                                    </div>
                                </a>
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
                                <a href="/?page=1<?= $categoryParam ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">1</a>
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
                                <a href="/?page=<?= $totalPages ?><?= $categoryParam ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"><?= $totalPages ?></a>
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
</body>

</html>