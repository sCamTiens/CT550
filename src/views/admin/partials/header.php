<style>
    [x-cloak] {
        display: none !important;
    }

    @keyframes slide-in {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
        transition: all 0.3s ease-out;
    }
</style>

<!-- Header -->
<header class="flex items-center justify-between shadow px-6 py-3 bg-blue-50 text-slate-800">
    <!-- Bên trái: tiêu đề -->
    <div class="text-lg font-semibold text-gray-700">
        <h1 class="p-4 font-bold text-lg tracking-wide text-[#002975]" style="font-size: 50px;">MiniGo</h1>
    </div>

    <!-- Bên phải: chấm công + thông báo + dropdown user -->
    <div class="flex items-center gap-4">
        <?php
        // Chỉ hiển thị nút chấm công cho nhân viên (không phải Admin)
        $isAdmin = ($_SESSION['user']['staff_role'] ?? '') === 'Admin';
        if (!$isAdmin):
            ?>
            <!-- Nút chấm công -->
            <div class="relative" x-data="attendanceButtonData()">
                <template x-if="currentShift && isInWorkingHours">
                    <button @click="handleAttendance()" :disabled="processing"
                        class="flex items-center gap-2 p-2 rounded-lg font-semibold transition-all border-2 disabled:opacity-50"
                        :class="{
                        'border border-green-600 text-green-600 hover:bg-green-600 hover:text-white': !hasCheckedIn,
                        'border border-orange-500 hover:bg-orange-600 text-orange-600 border-orange-600 hover:text-white': hasCheckedIn && !hasCheckedOut
                    }">
                        <i class="fa-solid" :class="{
                        'fa-right-to-bracket': !hasCheckedIn,
                        'fa-right-from-bracket': hasCheckedIn && !hasCheckedOut
                    }"></i>
                        <span
                            x-text="processing ? 'Đang xử lý...' : (hasCheckedIn && !hasCheckedOut ? 'Ra ca' : 'Vào ca')"></span>
                    </button>
                </template>
            </div>
        <?php endif; ?>

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
            <div x-show="isOpen" @click.away="isOpen = false" x-transition x-cloak
                class="absolute right-0 mt-2 w-[700px] bg-white rounded-lg shadow-xl border z-50 max-h-[500px] overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="px-4 py-3 border-b bg-gray-50 flex justify-center items-center">
                    <h3 class="font-bold text-[#002975]">Thông báo</h3>
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
                        <div @click="markAsRead(notif.id)"
                            class="px-4 py-3 border-b hover:bg-gray-200 cursor-pointer transition-colors"
                            :class="notif.is_read ? 'bg-gray-100 italic' : 'bg-white'">

                            <div class="flex items-start gap-3">
                                <!-- Icon theo loại -->
                                <div class="mt-1">
                                    <i :class="{
                                    'fa-solid fa-triangle-exclamation text-yellow-600': notif.type === 'warning',
                                    'fa-solid fa-info-circle text-blue-500': notif.type === 'info',
                                    'fa-solid fa-check-circle text-green-500': notif.type === 'success',
                                    'fa-solid fa-exclamation-circle text-red-600': notif.type === 'error'
                                }" class="text-lg"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p :class="{
                                    'font-bold text-red-900': notif.type === 'error' && !notif.is_read,
                                    'font-bold text-yellow-900': notif.type === 'warning' && !notif.is_read,
                                    'font-bold text-gray-900': !notif.is_read && notif.type !== 'error' && notif.type !== 'warning',
                                    'text-gray-600 italic': notif.is_read
                                }" class="text-sm" x-html="notif.title"></p>

                                    <p :class="notif.is_read ? 'text-gray-500 italic' : 'text-gray-700'"
                                        class="text-sm mt-1"
                                        x-html="notif.message + (notif.is_read ? ' <span class=\'text-gray-400\'>(Đã đọc)</span>' : '')">
                                    </p>

                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-2">
                                        <span x-text="formatTime(notif.created_at)"></span>
                                        <span x-show="!notif.is_read" :class="{
                                            'bg-red-500': notif.type === 'error',
                                            'bg-yellow-500': notif.type === 'warning',
                                            'bg-blue-500': notif.type !== 'error' && notif.type !== 'warning'
                                        }"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white">
                                            Mới
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 border-t bg-gray-50 text-center">
                    <button @click="markAllAsRead" x-show="unreadCount > 0"
                        class="text-sm text-blue-600 hover:text-blue-800">
                        Đọc tất cả
                    </button>
                </div>
            </div>
        </div>

        <!-- Dropdown user -->
        <div class="relative">
            <?php
            $user = $_SESSION['admin_user'] ?? [];
            $avatar = !empty($user['avatar_url']) ? '/assets/images/avatar/' . $user['avatar_url'] : '/assets/images/avatar/default.png';
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
    // Attendance Button Component
    function attendanceButtonData() {
        return {
            currentShift: null,
            hasCheckedIn: false,
            hasCheckedOut: false,
            isInWorkingHours: false,
            processing: false,

            async init() {
                await this.checkCurrentShift();
                // Cập nhật mỗi phút
                setInterval(() => {
                    this.checkCurrentShift();
                }, 60000);
            },

            async checkCurrentShift() {
                try {
                    const res = await fetch('/admin/api/attendance/today-shift');

                    // Lấy raw text trước để debug
                    const rawText = await res.text();

                    if (!res.ok) {
                        console.error('Failed to fetch shift:', res.status);
                        return;
                    }

                    // Parse JSON từ text
                    const data = JSON.parse(rawText);

                    if (!data.success || !data.data || data.data.length === 0) {

                        // DEMO MODE: Hiển thị nút với ca giả lập
                        this.currentShift = {
                            shift_id: 1,
                            shift_name: 'Ca sáng (Demo)',
                            start_time: '06:00:00',
                            end_time: '14:00:00',
                            check_in_time: null,
                            check_out_time: null
                        };
                        this.hasCheckedIn = false;
                        this.hasCheckedOut = false;
                        this.isInWorkingHours = true; // Always show in demo mode
                        return;
                    }

                    // Lấy giờ hiện tại
                    const now = new Date();
                    const currentMinutes = now.getHours() * 60 + now.getMinutes();

                    // Tìm ca làm việc hiện tại (trong khung giờ làm việc)
                    for (const shift of data.data) {
                        const [startHour, startMin] = shift.start_time.split(':').map(Number);
                        const [endHour, endMin] = shift.end_time.split(':').map(Number);

                        const shiftStart = startHour * 60 + startMin;
                        const shiftEnd = endHour * 60 + endMin;

                        // Cho phép hiển thị nút từ 2 giờ trước ca đến 30 phút sau ca kết thúc
                        // Nhân viên có thể check-in bất cứ lúc nào, kể cả đi trễ
                        const canShowButton = currentMinutes >= (shiftStart - 120) && currentMinutes <= (shiftEnd + 30);

                        // Nếu trong khung giờ ca làm việc
                        if (canShowButton) {
                            this.currentShift = shift;
                            this.hasCheckedIn = !!shift.check_in_time;
                            this.hasCheckedOut = !!shift.check_out_time;
                            this.isInWorkingHours = true;

                            return;
                        }
                    }

                    // Không có ca nào phù hợp
                    console.log('No matching shift for current time');
                    this.currentShift = null;
                    this.isInWorkingHours = false;

                } catch (e) {
                    console.error('Error checking shift:', e);
                    this.isInWorkingHours = false;
                }
            },

            async handleAttendance() {
                if (this.processing || !this.currentShift) return;

                this.processing = true;

                try {
                    const endpoint = this.hasCheckedIn && !this.hasCheckedOut
                        ? '/admin/api/attendance/check-out'
                        : '/admin/api/attendance/check-in';

                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            shift_id: this.currentShift.shift_id
                        })
                    });

                    const data = await res.json();

                    if (data.success) {
                        // Hiển thị thông báo thành công
                        this.showNotification(data.message, 'success');

                        // Cập nhật trạng thái
                        await this.checkCurrentShift();
                    } else {
                        this.showNotification(data.message, 'error');
                    }

                } catch (e) {
                    this.showNotification('Lỗi kết nối: ' + e.message, 'error');
                } finally {
                    this.processing = false;
                }
            },

            showNotification(message, type) {
                // Tạo toast notification
                const toast = document.createElement('div');
                toast.className = `
        fixed top-5 right-5 z-[60] flex items-center justify-between w-[400px] p-4 mb-4 text-base font-semibold
        ${type === 'success'
                        ? 'text-green-700 border-green-400'
                        : 'text-red-700 border-red-400'}
        bg-white rounded-xl shadow-lg border-2 animate-slide-in
    `;

                toast.innerHTML = `
        <div class="flex items-center flex-1">
            <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'} mr-3"
                xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                ${type === 'success'
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 13l4 4L19 7" />`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
            </svg>
            <div class="flex-1">${message}</div>
        </div>
        <button class="ml-4 text-gray-400 hover:text-gray-700 transition" id="toast-close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;

                document.body.appendChild(toast);

                // Auto ẩn sau 5s
                const timer = setTimeout(() => hideToast(), 5000);

                // Click nút X để đóng
                toast.querySelector('#toast-close-btn').addEventListener('click', () => {
                    clearTimeout(timer);
                    hideToast();
                });

                // Hàm ẩn toast
                function hideToast() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }
            }
        };
    }

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
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('user-menu-btn');
        const dropdown = document.getElementById('user-dropdown');

        if (btn && dropdown) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            // Click ra ngoài thì ẩn dropdown
            document.addEventListener('click', (e) => {
                if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
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