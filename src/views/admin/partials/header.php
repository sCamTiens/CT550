<!-- Header -->
<header class="flex items-center justify-between shadow px-6 py-3 bg-blue-50 text-slate-800">
    <!-- Bên trái: tiêu đề -->
    <div class="text-lg font-semibold text-gray-700">
        <h1 class="p-4 font-bold text-lg tracking-wide text-[#002975]" style="font-size: 50px;">MiniGo</h1>
    </div>

    <!-- Bên phải: thông báo + dropdown user -->
    <div class="flex items-center gap-4">
        <!-- Icon chuông thông báo -->
        <div class="relative" x-data="notificationBellData()">
            <button @click="toggleDropdown()"
                class="relative p-2 text-[#002975] hover:bg-[#002975] hover:text-white rounded-lg transition-colors border border-[#002975]">
                <i class="fa-solid fa-bell text-xl"></i>
                <!-- Badge đỏ khi có thông báo chưa đọc -->
                <span x-show="unreadCount > 0"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                    x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
            </button>

            <!-- Dropdown thông báo -->
            <div x-show="isOpen" @click.away="isOpen = false" x-transition
                class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border z-50 max-h-[500px] overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="px-4 py-3 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700">Thông báo</h3>
                    <button @click="markAllAsRead" x-show="unreadCount > 0"
                        class="text-sm text-blue-600 hover:text-blue-800">
                        Đọc tất cả
                    </button>
                </div>

                <!-- Danh sách thông báo -->
                <div class="overflow-y-auto flex-1">
                    <template x-if="notifications.length === 0">
                        <div class="p-8 text-center text-gray-500">
                            <i class="fa-solid fa-bell-slash text-4xl mb-2"></i>
                            <p>Không có thông báo nào</p>
                        </div>
                    </template>

                    <template x-for="notif in notifications" :key="notif.id">
                        <div @click="markAsRead(notif.id)" :class="notif.is_read ? 'bg-gray-100' : 'bg-blue-50'"
                            class="px-4 py-3 border-b hover:bg-gray-200 cursor-pointer transition-colors">

                            <div class="flex items-start gap-3">
                                <!-- Icon theo loại -->
                                <div class="mt-1">
                                    <i :class="{
                                        'fa-solid fa-triangle-exclamation text-yellow-500': notif.type === 'warning',
                                        'fa-solid fa-info-circle text-blue-500': notif.type === 'info',
                                        'fa-solid fa-check-circle text-green-500': notif.type === 'success',
                                        'fa-solid fa-exclamation-circle text-red-500': notif.type === 'error'
                                    }"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p :class="notif.is_read ? 'font-normal text-gray-600' : 'font-bold text-gray-900'"
                                        class="text-sm" x-text="notif.title"></p>
                                    <p :class="notif.is_read ? 'text-gray-500' : 'text-gray-700'" class="text-sm mt-1"
                                        x-text="notif.message"></p>
                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-2">
                                        <span x-text="formatTime(notif.created_at)"></span>
                                        <span x-show="!notif.is_read"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-500 text-white">
                                            Mới
                                        </span>
                                    </p>
                                </div>

                                <!-- Nút xóa -->
                                <button @click.stop="deleteNotification(notif.id)"
                                    class="text-gray-400 hover:text-red-500 p-1">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 border-t bg-gray-50 text-center">
                    <a href="/admin/stocks" class="text-sm text-blue-600 hover:text-blue-800">
                        Xem tồn kho →
                    </a>
                </div>
            </div>
        </div>

        <!-- Dropdown user -->
        <div class="relative">
            <?php
            $user = $_SESSION['admin_user'] ?? [];
            $avatar = !empty($user['avatar_url']) ? '/assets/images/avatar/' . $user['avatar_url'] : '/assets/images/default.png';
            $fullName = htmlspecialchars($user['full_name'] ?? 'Admin');
            ?>
            <button id="user-menu-btn" class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg focus:outline-none
           text-[#002975] hover:bg-[#002975] hover:text-white transition-colors border border-[#002975]">

                <!-- Avatar -->
                <img src="<?= $avatar ?>" alt="avatar"
                    class="w-8 h-8 rounded-full object-cover border border-gray-300" />

                <!-- Tên -->
                <span><?= $fullName ?></span>
                <i class="fa-solid fa-caret-down ml-1"></i>
            </button>

            <!-- Dropdown -->
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-50">
                <a href="/admin/profile"
                    class="group flex items-center px-4 py-2 text-sm text-[#002975] hover:bg-[#002975] hover:text-white transition-colors rounded-t-lg">
                    <i class="fa-solid fa-user mr-2 group-hover:text-white text-[#002975]"></i>
                    Hồ sơ cá nhân
                </a>
                <div class="border-t"></div>
                <a href="/admin/logout"
                    class="group block px-4 py-2 text-sm text-red-600 hover:bg-[#002975] hover:text-white rounded-b-lg">
                    <i class="fa-solid fa-right-from-bracket mr-2 text-red-600 group-hover:text-white"></i>
                    Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    // Notification Bell Component
    function notificationBellData() {
        return {
            isOpen: false,
            notifications: [],
            unreadCount: 0,

            async init() {
                await this.fetchUnreadCount();
                await this.fetchNotifications();

                // Poll mỗi 30s để cập nhật
                setInterval(() => {
                    this.fetchUnreadCount();
                    if (this.isOpen) {
                        this.fetchNotifications();
                    }
                }, 30000);
            },

            async toggleDropdown() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    await this.fetchNotifications();
                }
            },

            async fetchNotifications() {
                try {
                    const res = await fetch('/admin/api/notifications');
                    if (res.ok) {
                        this.notifications = await res.json();
                    } else {
                        console.error('Failed to fetch notifications:', res.status);
                    }
                } catch (e) {
                    console.error('Error fetching notifications:', e);
                }
            },

            async fetchUnreadCount() {
                try {
                    const res = await fetch('/admin/api/notifications/unread-count');
                    if (res.ok) {
                        const data = await res.json();
                        this.unreadCount = data.count || 0;
                    }
                } catch (e) {
                    console.error('Error fetching unread count:', e);
                }
            },

            async markAsRead(id) {
                try {
                    const res = await fetch(`/admin/api/notifications/${id}/read`, {
                        method: 'POST'
                    });

                    if (res.ok) {
                        const notif = this.notifications.find(n => n.id == id);
                        if (notif && !notif.is_read) {
                            notif.is_read = 1;
                            notif.read_at = new Date().toISOString();
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                        }
                    }
                } catch (e) {
                    console.error('Error marking as read:', e);
                }
            },

            async markAllAsRead() {
                try {
                    const res = await fetch('/admin/api/notifications/read-all', {
                        method: 'POST'
                    });

                    if (res.ok) {
                        this.notifications.forEach(n => {
                            n.is_read = 1;
                            n.read_at = new Date().toISOString();
                        });
                        this.unreadCount = 0;
                    }
                } catch (e) {
                    console.error('Error marking all as read:', e);
                }
            },

            async deleteNotification(id) {
                try {
                    const res = await fetch(`/admin/api/notifications/${id}`, {
                        method: 'DELETE'
                    });

                    if (res.ok) {
                        const index = this.notifications.findIndex(n => n.id == id);
                        if (index !== -1) {
                            if (!this.notifications[index].is_read) {
                                this.unreadCount = Math.max(0, this.unreadCount - 1);
                            }
                            this.notifications.splice(index, 1);
                        }
                    }
                } catch (e) {
                    console.error('Error deleting notification:', e);
                }
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000); // seconds

                if (diff < 60) return 'Vừa xong';
                if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
                if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
                if (diff < 2592000) return Math.floor(diff / 86400) + ' ngày trước';

                return date.toLocaleDateString('vi-VN');
            }
        };
    }

    // User dropdown (existing code)
    const btn = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-dropdown');
    btn?.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
    });

    // Click ra ngoài thì ẩn dropdown
    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Listen for avatar update event and update the header avatar
    window.addEventListener('avatar-updated', function (e) {
        const img = document.querySelector('#user-menu-btn img');
        if (img && e.detail && e.detail.url) {
            img.src = e.detail.url + '?t=' + Date.now(); // cache bust
        }
    });
</script>