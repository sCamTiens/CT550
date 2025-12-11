<style>
    [x-cloak] {
        display: none !important;
    }

    /* Voice Overlay Styles */
    #voiceOverlay {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 99999;
        width: 100vw;
        height: 100vh;
        display: none;
        justify-content: center;
        align-items: center;
    }

    .voice-backdrop {
        position: absolute;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .voice-popup {
        z-index: 999999;
        width: 300px;
        height: 300px;
        background-color: white;
        border-radius: 50%;
        padding: 15px;
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.8);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .voice-mic-icon {
        font-size: 80px;
        color: #002975;
        animation: pulse 1.5s ease-in-out infinite;
    }

    .voice-text {
        font-size: 16px;
        color: #002975;
        font-weight: 600;
        text-align: center;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }
</style>

<!-- Voice Overlay -->
<div id="voiceOverlay">
    <div class="voice-backdrop"></div>
    <div class="voice-popup">
        <i class="fas fa-microphone voice-mic-icon"></i>
        <p class="voice-text">Đang lắng nghe...</p>
    </div>
</div>

<!-- Header for Customer -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4 gap-4">
            <!-- Logo -->
            <a href="/" class="flex items-center gap-3 flex-shrink-0">
                <img src="/assets/images/minigo.png" alt="MiniGo" class="h-12 w-12">
                <span class="text-2xl font-bold text-[#002975]">MiniGo</span>
            </a>

            <!-- Search Bar -->
            <form action="/search" method="GET" class="flex-1 max-w-2xl">
                <div class="relative flex items-center">
                    <input type="text" name="q" id="searchInput" placeholder="Tìm kiếm sản phẩm..."
                        class="w-full px-4 py-2 pr-24 border-2 border-gray-300 rounded-lg focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-20 outline-none">
                    <div class="absolute right-2 flex items-center gap-2">
                        <!-- Voice Search Button -->
                        <button type="button" id="micButton"
                            class="text-gray-600 hover:text-[#002975] transition-colors p-2" title="Tìm bằng giọng nói">
                            <i class="fas fa-microphone text-lg"></i>
                        </button>
                        <!-- Search Button -->
                        <button type="submit"
                            class="text-gray-600 hover:text-[#002975] px-2 py-1 rounded-lg transition-colors">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Right side -->
            <div class="flex items-center gap-4 flex-shrink-0">
                <!-- Cart -->
                <a href="/cart" class="relative text-gray-700 hover:text-[#002975] transition-colors" title="Giỏ hàng">
                    <i class="fa-solid fa-shopping-cart text-xl"></i>
                    <span id="cart-badge"
                        class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?php
                        $cartCount = 0;
                        if (!empty($_SESSION['customer']['id'])) {
                            // Load cart from database
                            try {
                                $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
                                $cart = $cartRepo->loadCartFromDB($_SESSION['customer']['id']);
                                $cartCount = $cartRepo->countItems($cart);
                            } catch (\Exception $e) {
                                $cartCount = 0;
                            }
                        }
                        echo $cartCount;
                        ?>
                    </span>
                </a>

                <!-- Notification Bell -->
                <?php if (!empty($_SESSION['customer'])): ?>
                    <div class="relative" x-data="customerNotifications">
                        <button @click="toggle()" class="relative text-gray-700 hover:text-[#002975] transition-colors p-2" title="Thông báo">
                            <i class="fa-solid fa-bell text-xl"></i>
                            <span x-show="unreadCount > 0" x-text="unreadCount"
                                class="absolute -top-0 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="close()" x-transition
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border py-2 z-[60]"
                            style="display: none;">
                            <div class="px-4 py-2 border-b flex justify-between items-center bg-gray-50">
                                <h3 class="font-semibold text-gray-800 text-sm">Thông báo</h3>
                                <button @click="markAllReadRemote()" class="text-xs text-[#002975] hover:underline">Đã đọc tất cả</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="loading">
                                    <div class="p-4 text-center text-gray-500 text-sm">Đang tải...</div>
                                </template>
                                <template x-if="!loading && notifications.length === 0">
                                    <div class="p-4 text-center text-gray-500 text-sm py-8">
                                        <i class="fa-regular fa-bell-slash text-2xl mb-2 text-gray-300"></i>
                                        <p>Không có thông báo mới</p>
                                    </div>
                                </template>
                                <template x-for="note in notifications" :key="note.id">
                                    <a :href="note.related_link || '#'" class="block px-4 py-3 hover:bg-gray-50 border-b last:border-0 transition-colors" :class="{'bg-blue-50': note.is_read == 0}">
                                        <div class="flex gap-3">
                                            <div class="flex-shrink-0 mt-1">
                                                <template x-if="note.type === 'success'"><i class="fa-solid fa-circle-check text-green-500"></i></template>
                                                <template x-if="note.type === 'order_status'"><i class="fa-solid fa-truck-fast text-blue-500"></i></template>
                                                <template x-if="note.type === 'info' || !note.type"><i class="fa-solid fa-info-circle text-gray-500"></i></template>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-800" x-text="note.title"></div>
                                                <div class="text-xs text-gray-600 mt-1 leading-snug" x-text="note.message"></div>
                                                <div class="text-[10px] text-gray-400 mt-1" x-text="formatDate(note.created_at)"></div>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User dropdown -->
                <?php if (!empty($_SESSION['customer'])): ?>
                    <?php
                    $customer = $_SESSION['customer'];
                    // Kiểm tra nếu avatar là URL đầy đủ (từ Google) hoặc local file
                    if (!empty($customer['avatar_url'])) {
                        if (filter_var($customer['avatar_url'], FILTER_VALIDATE_URL)) {
                            // Google avatar (full URL)
                            $avatar = $customer['avatar_url'];
                        } else {
                            // Local avatar file
                            $avatar = '/assets/images/avatar/' . $customer['avatar_url'];
                        }
                    } else {
                        $avatar = '/assets/images/avatar/default.png';
                    }
                    $fullName = htmlspecialchars($customer['full_name'] ?? 'Khách hàng');
                    ?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center gap-2 text-gray-700 hover:text-[#002975] transition-colors">
                            <img src="<?= $avatar ?>"
                                alt="<?= $fullName ?>"
                                referrerpolicy="no-referrer"
                                crossorigin="anonymous"
                                onerror="console.error('Avatar load failed:', this.src); this.onerror=null; this.src='/assets/images/avatar/default.png';"
                                class="w-8 h-8 rounded-full object-cover border-2 border-gray-300">
                            <span class="hidden md:block font-medium"><?= $fullName ?></span>
                            <i class="fa-solid fa-caret-down"></i>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 z-50"
                            style="display: none;">
                            <a href="/profile" class="block px-4 py-2 hover:bg-[#002975] hover:text-white rounded-lg">
                                <i class="fa-solid fa-user mr-2"></i>
                                Thông tin cá nhân
                            </a>
                            <a href="/profile?tab=orders"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors hover:bg-[#002975] hover:text-white">
                                <i class="fa-solid fa-box"></i>
                                <span>Đơn hàng</span>
                            </a>
                            <a href="/profile?tab=loyalty"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors hover:bg-[#002975] hover:text-white">
                                <i class="fa-solid fa-gift"></i>
                                <span>Điểm tích lũy</span>
                            </a>
                            <div class="border-t my-2"></div>
                            <button onclick="handleLogout()"
                                class="w-full text-left block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i>
                                Đăng xuất
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2">
                        <a href="/login"
                            class="px-4 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-colors">
                            Đăng nhập
                        </a>
                        <a href="/register"
                            class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors">
                            Đăng ký
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerNotifications', () => ({
            open: false,
            loading: false,
            notifications: [],
            unreadCount: 0,
            init() {
                this.fetchData();
                setInterval(() => this.fetchData(), 30000); // Check every 30s
            },
            async fetchData() {
                try {
                    const res = await fetch('/api/customer/notifications');
                    const data = await res.json();
                    if (data.success) {
                        this.notifications = data.notifications;
                        this.unreadCount = data.unread_count;
                    }
                } catch (e) {}
            },
            toggle() {
                this.open = !this.open;
                if (this.open && this.unreadCount > 0) {
                    this.markAllReadRemote();
                }
            },
            close() {
                this.open = false;
            },
            async markAllReadRemote() {
                this.unreadCount = 0;
                this.notifications.forEach(n => n.is_read = 1);
                await fetch('/api/customer/notifications/read', {
                    method: 'POST'
                });
            },
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }))
    });

    // Handle logout
    function handleLogout() {
        window.location.href = '/logout';
    }

    // Update cart badge when cart is updated
    window.addEventListener('cart-updated', function(event) {
        // Update badge from event detail if available
        if (event.detail && event.detail.cart_count !== undefined) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                badge.textContent = event.detail.cart_count;
            }
        }
    });

    // Voice Search Implementation
    document.addEventListener("DOMContentLoaded", function() {
        const micButton = document.getElementById("micButton");
        const searchInput = document.getElementById("searchInput");
        const voiceOverlay = document.getElementById("voiceOverlay");
        const searchForm = micButton.closest("form");

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            micButton.disabled = true;
            micButton.title = "Trình duyệt không hỗ trợ tìm kiếm bằng giọng nói";
            return;
        }

        const recognition = new SpeechRecognition();
        recognition.lang = "vi-VN";
        recognition.interimResults = false;

        // Bắt đầu ghi âm
        micButton.addEventListener("click", () => {
            recognition.start();
            voiceOverlay.style.display = "flex";
        });

        // Khi nhấn vào overlay để hủy
        voiceOverlay.addEventListener("click", () => {
            recognition.stop();
            voiceOverlay.style.display = "none";
        });

        // Khi ghi âm thành công
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            searchInput.value = transcript.replace(/\.*$/, "");
            voiceOverlay.style.display = "none";

            // Tự động submit form tìm kiếm
            if (searchForm) {
                searchForm.submit();
            }
        };

        // Nếu có lỗi
        recognition.onerror = function() {
            voiceOverlay.style.display = "none";
            if (typeof Swal !== 'undefined') {
                Swal.fire("Lỗi", "Không thể nhận diện giọng nói. Hãy thử lại!", "error");
            } else {
                alert("Không thể nhận diện giọng nói. Hãy thử lại!");
            }
        };

        // Khi kết thúc
        recognition.onend = function() {
            voiceOverlay.style.display = "none";
        };
    });
</script>

<!-- Chat Widget & Go to Top - Available on all pages -->
<?php require __DIR__ . '/chat_widget.php'; ?>