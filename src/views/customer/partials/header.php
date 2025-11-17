<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<!-- Header for Customer -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <!-- Logo -->
            <a href="/" class="flex items-center gap-3">
                <img src="/assets/images/minigo.png" alt="MiniGo" class="h-12 w-12">
                <span class="text-2xl font-bold text-[#002975]">MiniGo</span>
            </a>

            <!-- Navigation -->
            <nav class="hidden md:flex items-center gap-6">
                <a href="/" class="text-gray-700 hover:text-[#002975] transition-colors font-medium">
                    Trang chủ
                </a>
                <a href="/products" class="text-gray-700 hover:text-[#002975] transition-colors font-medium">
                    Sản phẩm
                </a>
                <a href="/promotions" class="text-gray-700 hover:text-[#002975] transition-colors font-medium">
                    Khuyến mãi
                </a>
                <a href="/about" class="text-gray-700 hover:text-[#002975] transition-colors font-medium">
                    Về chúng tôi
                </a>
            </nav>

            <!-- Right side -->
            <div class="flex items-center gap-4">
                <!-- Search -->
                <button class="text-gray-700 hover:text-[#002975] transition-colors">
                    <i class="fa-solid fa-search text-xl"></i>
                </button>

                <!-- Cart -->
                <a href="/cart" class="relative text-gray-700 hover:text-[#002975] transition-colors">
                    <i class="fa-solid fa-shopping-cart text-xl"></i>
                    <span id="cart-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?php 
                        $cartCount = 0;
                        if (!empty($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $item) {
                                $qty = is_array($item) ? ($item['qty'] ?? 0) : $item;
                                $cartCount += (int)$qty;
                            }
                        }
                        echo $cartCount;
                        ?>
                    </span>
                </a>

                <!-- User dropdown -->
                <?php if (!empty($_SESSION['customer'])): ?>
                    <?php
                    $customer = $_SESSION['customer'];
                    $avatar = !empty($customer['avatar_url']) ? '/assets/images/avatar/' . $customer['avatar_url'] : '/assets/images/avatar/default.png';
                    $fullName = htmlspecialchars($customer['full_name'] ?? 'Khách hàng');
                    ?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 text-gray-700 hover:text-[#002975] transition-colors">
                            <img src="<?= $avatar ?>" alt="avatar" class="w-8 h-8 rounded-full object-cover border-2 border-gray-300">
                            <span class="hidden md:block font-medium"><?= $fullName ?></span>
                            <i class="fa-solid fa-caret-down"></i>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 z-50"
                             style="display: none;">
                            <a href="/profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-user mr-2"></i>
                                Thông tin cá nhân
                            </a>
                            <div class="border-t my-2"></div>
                            <a href="/logout" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i>
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors">
                        Đăng nhập
                    </a>
                <?php endif; ?>

                <!-- Mobile menu button -->
                <button class="md:hidden text-gray-700" x-data @click="$dispatch('toggle-mobile-menu')">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-data="{ open: false }" 
         @toggle-mobile-menu.window="open = !open"
         x-show="open" 
         x-transition
         class="md:hidden border-t"
         style="display: none;">
        <nav class="container mx-auto px-4 py-4 flex flex-col gap-3">
            <a href="/" class="text-gray-700 hover:text-[#002975] py-2">Trang chủ</a>
            <a href="/products" class="text-gray-700 hover:text-[#002975] py-2">Sản phẩm</a>
            <a href="/promotions" class="text-gray-700 hover:text-[#002975] py-2">Khuyến mãi</a>
            <a href="/about" class="text-gray-700 hover:text-[#002975] py-2">Về chúng tôi</a>
        </nav>
    </div>
</header>
