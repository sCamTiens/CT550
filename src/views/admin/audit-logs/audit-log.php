<?php
$pageTitle = 'Lịch Sử Thao Tác';
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<div x-data="auditLogPage()" x-init="init()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Lịch Sử Thao Tác</h1>

        <div class="flex gap-3">
            <button @click="showStats = true"
                class="px-4 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Thống Kê
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- NGƯỜI THỰC HIỆN -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Người Thực Hiện
                </label>
                <div class="relative">
                    <select x-model="filters.user_id" @change="fetchLogs()"
                        class="appearance-none w-full px-3 py-2 pr-10 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tất cả nhân viên --</option>
                        <template x-for="staff in staffList" :key="staff.id">
                            <option :value="staff.id" x-text="staff.full_name + ' (' + staff.username + ')'"></option>
                        </template>
                    </select>

                    <!-- Nút X (clear) -->
                    <button x-show="filters.user_id" @click="filters.user_id = ''; fetchLogs();"
                        class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Mũi tên giả -->
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- LOẠI ĐỐI TƯỢNG -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Loại Đối Tượng
                </label>
                <div class="relative">
                    <select x-model="filters.entity_type" @change="fetchLogs()"
                        class="appearance-none w-full px-3 py-2 pr-10 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tất cả --</option>
                        <option value="products">Sản phẩm</option>
                        <option value="categories">Danh mục</option>
                        <option value="brands">Thương hiệu</option>
                        <option value="suppliers">Nhà cung cấp</option>
                        <option value="units">Đơn vị tính</option>
                        <option value="orders">Đơn hàng</option>
                        <option value="staff">Nhân viên</option>
                        <option value="customers">Khách hàng</option>
                        <option value="coupons">Mã giảm giá</option>
                        <option value="promotions">Khuyến mãi</option>
                        <option value="purchase_orders">Phiếu nhập</option>
                        <option value="product_batches">Lô hàng</option>
                    </select>

                    <!-- Nút X -->
                    <button x-show="filters.entity_type" @click="filters.entity_type = ''; fetchLogs();"
                        class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Mũi tên giả -->
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Hành động -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Hành Động</label>
                <div class="relative">
                    <select x-model="filters.action" @change="fetchLogs()"
                        class="appearance-none w-full px-3 py-2 pr-10 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tất cả --</option>
                        <option value="create">Thêm mới</option>
                        <option value="update">Cập nhật</option>
                        <option value="delete">Xóa</option>
                    </select>

                    <!-- Nút X (clear) -->
                    <button x-show="filters.action" @click="filters.action = ''; fetchLogs();"
                        class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Mũi tên giả -->
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Từ ngày -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ Ngày</label>
                <div class="relative">
                    <input type="text" x-ref="fromDate" placeholder="Chọn ngày"
                        class="w-full px-3 py-2 pr-8 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button x-show="filters.from_date" @click="clearFromDate()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Đến ngày -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến Ngày</label>
                <div class="relative">
                    <input type="text" x-ref="toDate" placeholder="Chọn ngày"
                        class="w-full px-3 py-2 pr-8 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button x-show="filters.to_date" @click="clearToDate()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Nút xóa bộ lọc -->
        <div class="mt-4 text-right">
            <button @click="resetFilters()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                Xóa Bộ Lọc
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Loading overlay bên trong bảng -->
        <template x-if="loading">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
            </div>
        </template>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời Gian</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người Thực Hiện</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hành Động</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đối Tượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chi Tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="log in paginatedLogs" :key="log.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm" x-text="log.id"></td>
                            <td class="px-4 py-3 text-sm" x-text="formatDateTime(log.created_at)"></td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium" x-text="log.actor_name || 'Hệ thống'"></div>
                                <div class="text-gray-500 text-xs" x-text="log.actor_username"></div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="getActionBadgeClass(log.action)"
                                    x-text="getActionText(log.action)"></span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium" x-text="getEntityTypeText(log.entity_type)"></div>
                                <div class="text-gray-500 text-xs">ID: <span x-text="log.entity_id"></span></div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <button @click="viewDetail(log)"
                                    class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Xem
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Khi rỗng -->
                    <template x-if="filteredLogs.length === 0">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <img src="/assets/images/Null.png" class="h-24 opacity-70 mb-2">
                                    <div>Không có dữ liệu</div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination -->
    <div class="px-4 py-3 border-t flex items-center justify-center gap-6">
        <div class="text-sm text-gray-700">
            Tổng cộng <span class="font-medium" x-text="filteredLogs.length"></span> bản ghi
        </div>
        <div class="flex items-center gap-2">
            <button @click="currentPage--" :disabled="currentPage === 1"
                class="px-2 py-1 border rounded disabled:opacity-50">&lt;</button>
            <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages"></span></span>
            <button @click="currentPage++" :disabled="currentPage === totalPages"
                class="px-2 py-1 border rounded disabled:opacity-50">&gt;</button>
            <div x-data="{ open: false }" class="relative">
                <button @click="open=!open" class="border rounded px-2 py-1 w-28 flex justify-between items-center">
                    <span x-text="perPage + ' / trang'"></span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open=false"
                    class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
                    <template x-for="opt in perPageOptions" :key="opt">
                        <div @click="perPage=opt;currentPage=1;open=false"
                            class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
                            x-text="opt + ' / trang'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="showDetail" @click.self="showDetail = false"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">Chi Tiết Thao Tác</h3>
                <button @click="showDetail = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <template x-if="selectedLog">
                    <div>
                        <!-- Info -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Người thực hiện:</label>
                                <div class="text-lg" x-text="selectedLog.actor_name || 'Hệ thống'"></div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Thời gian:</label>
                                <div class="text-lg" x-text="formatDateTime(selectedLog.created_at)"></div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Hành động:</label>
                                <div>
                                    <span :class="getActionBadgeClass(selectedLog.action)"
                                        x-text="getActionText(selectedLog.action)"></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Đối tượng:</label>
                                <div class="text-lg">
                                    <span x-text="getEntityTypeText(selectedLog.entity_type)"></span>
                                    <span class="text-gray-500">#<span x-text="selectedLog.entity_id"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Changes Comparison -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Before -->
                            <div>
                                <h4 class="font-bold text-red-600 mb-2">🔴 Trước Khi Thay Đổi</h4>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200 max-h-96 overflow-y-auto">
                                    <pre class="text-xs whitespace-pre-wrap"
                                        x-text="selectedLog.before_data ? JSON.stringify(selectedLog.before_data, null, 2) : '(Không có dữ liệu)'"></pre>
                                </div>
                            </div>

                            <!-- After -->
                            <div>
                                <h4 class="font-bold text-green-600 mb-2">🟢 Sau Khi Thay Đổi</h4>
                                <div
                                    class="bg-green-50 p-4 rounded-lg border border-green-200 max-h-96 overflow-y-auto">
                                    <pre class="text-xs whitespace-pre-wrap"
                                        x-text="selectedLog.after_data ? JSON.stringify(selectedLog.after_data, null, 2) : '(Không có dữ liệu)'"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Stats Modal -->
    <div x-show="showStats" @click.self="showStats = false"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-lg w-full max-w-5xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">Thống Kê Hoạt Động</h3>
                <button @click="showStats = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

                        <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-3 gap-6">
                    <!-- Stats by Action -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Theo Hành Động</h4>
                        <template x-if="stats.byAction && stats.byAction.length">
                            <div class="space-y-2">
                                <template x-for="item in stats.byAction" :key="item.action">
                                    <div class="flex justify-between">
                                        <span x-text="getActionText(item.action)"></span>
                                        <span class="font-semibold" x-text="item.count"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!stats.byAction || stats.byAction.length === 0">
                            <p class="text-gray-500 text-sm">Không có dữ liệu</p>
                        </template>
                    </div>

                    <!-- Stats by Entity -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Theo Đối Tượng</h4>
                        <template x-if="stats.byEntity && stats.byEntity.length">
                            <div class="space-y-2">
                                <template x-for="item in stats.byEntity" :key="item.entity_type">
                                    <div class="flex justify-between">
                                        <span x-text="getEntityTypeText(item.entity_type)"></span>
                                        <span class="font-semibold" x-text="item.count"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!stats.byEntity || stats.byEntity.length === 0">
                            <p class="text-gray-500 text-sm">Không có dữ liệu</p>
                        </template>
                    </div>

                    <!-- Stats by Staff -->
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Theo Nhân viên</h4>
                        <template x-if="stats.staff && stats.staff.length">
                            <div class="space-y-2">
                                <template x-for="(item, index) in stats.staff" :key="'staff-' + index">
                                    <div class="flex justify-between">
                                        <span x-text="item.actor_name || item.full_name || 'Hệ thống'"></span>
                                        <span class="font-semibold" x-text="item.total_actions"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!stats.staff || stats.staff.length === 0">
                            <p class="text-gray-500 text-sm">Không có dữ liệu</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function auditLogPage() {
        return {
            logs: [],
            staffList: [],
            filters: {
                search: '',
                user_id: '',
                entity_type: '',
                action: '',
                from_date: '',
                to_date: '',
            },
            currentPage: 1,
            perPage: 20,
            perPageOptions: [5, 10, 20, 50, 100],
            showDetail: false,
            showStats: false,
            selectedLog: null,
            stats: {
                byAction: [],
                byEntity: [],
                staff: [],
            },
            loading: false,

            init() {
                this.fetchStaffList();
                this.fetchLogs();

                // Initialize Flatpickr for date inputs
                this.$nextTick(() => {
                    const self = this;

                    flatpickr(this.$refs.fromDate, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        onChange: function (selectedDates, dateStr) {
                            self.filters.from_date = dateStr;

                            // Nếu từ ngày > đến ngày, tự động điều chỉnh đến ngày
                            if (selectedDates[0] && self.$refs.toDate._flatpickr) {
                                const toDate = self.$refs.toDate._flatpickr.selectedDates[0];
                                if (toDate && selectedDates[0] > toDate) {
                                    self.$refs.toDate._flatpickr.setDate(selectedDates[0]);
                                    self.filters.to_date = dateStr;
                                }
                                // Set minDate cho "Đến ngày"
                                self.$refs.toDate._flatpickr.set('minDate', selectedDates[0]);
                            }

                            self.fetchLogs();
                        }
                    });

                    flatpickr(this.$refs.toDate, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        onChange: function (selectedDates, dateStr) {
                            self.filters.to_date = dateStr;

                            // Nếu đến ngày < từ ngày, tự động điều chỉnh từ ngày
                            if (selectedDates[0] && self.$refs.fromDate._flatpickr) {
                                const fromDate = self.$refs.fromDate._flatpickr.selectedDates[0];
                                if (fromDate && selectedDates[0] < fromDate) {
                                    self.$refs.fromDate._flatpickr.setDate(selectedDates[0]);
                                    self.filters.from_date = dateStr;
                                }
                                // Set maxDate cho "Từ ngày"
                                self.$refs.fromDate._flatpickr.set('maxDate', selectedDates[0]);
                            }

                            self.fetchLogs();
                        }
                    });
                });

                // Watch showStats để tự động load stats khi mở modal
                this.$watch('showStats', (value) => {
                    if (value) {
                        this.fetchStats();
                    }
                });
            },

            clearFromDate() {
                this.filters.from_date = '';
                if (this.$refs.fromDate._flatpickr) {
                    this.$refs.fromDate._flatpickr.clear();
                    // Xóa maxDate constraint
                    if (this.$refs.toDate._flatpickr) {
                        this.$refs.toDate._flatpickr.set('minDate', null);
                    }
                }
                this.fetchLogs();
            },

            clearToDate() {
                this.filters.to_date = '';
                if (this.$refs.toDate._flatpickr) {
                    this.$refs.toDate._flatpickr.clear();
                    // Xóa minDate constraint
                    if (this.$refs.fromDate._flatpickr) {
                        this.$refs.fromDate._flatpickr.set('maxDate', null);
                    }
                }
                this.fetchLogs();
            },

            async fetchStaffList() {
                try {
                    const res = await fetch('/admin/api/audit-logs/staff-list');
                    const data = await res.json();
                    this.staffList = data.staff || [];
                } catch (err) {
                    console.error('Lỗi tải danh sách nhân viên:', err);
                }
            },

            async fetchLogs() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();

                    if (this.filters.entity_type) {
                        params.append('entity_type', this.filters.entity_type);
                    }

                    // Duyệt các filter khác
                    Object.keys(this.filters).forEach(key => {
                        if (!['entity_type'].includes(key) && this.filters[key]) {
                            params.append(key, this.filters[key]);
                        }
                    });

                    const res = await fetch(`/admin/api/audit-logs?${params}`);
                    const data = await res.json();

                    this.logs = data.items || [];
                    this.currentPage = 1;
                }
                catch (err) {
                    console.error('Lỗi tải dữ liệu:', err);
                    alert('Lỗi tải dữ liệu: ' + err.message);
                } finally {
                    this.loading = false;
                }
            },

            async fetchStats() {
                try {
                    console.log('Fetching stats...');
                    const params = new URLSearchParams();
                    if (this.filters.from_date) params.append('from_date', this.filters.from_date);
                    if (this.filters.to_date) params.append('to_date', this.filters.to_date);

                    const [resAction, resEntity, resStaff] = await Promise.all([
                        fetch(`/admin/api/audit-logs/stats/action?${params}`),
                        fetch(`/admin/api/audit-logs/stats/entity?${params}`),
                        fetch(`/admin/api/audit-logs/stats/staff?${params}`),
                    ]);

                    const [dataAction, dataEntity, dataStaff] = await Promise.all([
                        resAction.json(),
                        resEntity.json(),
                        resStaff.json(),
                    ]);

                    this.stats.byAction = dataAction.stats || [];
                    this.stats.byEntity = dataEntity.stats || [];
                    this.stats.staff = dataStaff.stats || [];

                    console.log('Stats loaded:', this.stats);
                } catch (err) {
                    console.error('Lỗi tải thống kê:', err);
                    alert('Lỗi tải thống kê: ' + err.message);
                }
            },

            resetFilters() {
                this.filters = {
                    search: '',
                    user_id: '',
                    entity_type: '',
                    action: '',
                    from_date: '',
                    to_date: '',
                };

                // Clear Flatpickr instances
                if (this.$refs.fromDate._flatpickr) {
                    this.$refs.fromDate._flatpickr.clear();
                    this.$refs.fromDate._flatpickr.set('maxDate', null);
                }
                if (this.$refs.toDate._flatpickr) {
                    this.$refs.toDate._flatpickr.clear();
                    this.$refs.toDate._flatpickr.set('minDate', null);
                }

                this.fetchLogs();
            },

            viewDetail(log) {
                this.selectedLog = log;
                this.showDetail = true;
            },

            get filteredLogs() {
                return this.logs;
            },

            get paginatedLogs() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredLogs.slice(start, start + this.perPage);
            },

            get totalPages() {
                return Math.ceil(this.filteredLogs.length / this.perPage) || 1;
            },

            formatDateTime(dt) {
                if (!dt) return '';
                const d = new Date(dt);
                return d.toLocaleString('vi-VN');
            },

            getActionText(action) {
                const map = {
                    'create': 'Thêm mới',
                    'update': 'Cập nhật',
                    'delete': 'Xóa',
                    'restore': 'Khôi phục',
                    'status_change': 'Đổi trạng thái',
                };
                return map[action] || action;
            },

            getActionBadgeClass(action) {
                const map = {
                    'create': 'px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold',
                    'update': 'px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold',
                    'delete': 'px-2 py-1 bg-red-200 text-red-900 rounded text-xs font-semibold',
                    'restore': 'px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold',
                    'status_change': 'px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-semibold',
                };
                return map[action] || 'px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold';
            },

            getEntityTypeText(type) {
                const map = {
                    'products': 'Sản phẩm',
                    'categories': 'Danh mục',
                    'brands': 'Thương hiệu',
                    'suppliers': 'Nhà cung cấp',
                    'units': 'Đơn vị tính',
                    'orders': 'Đơn hàng',
                    'staff': 'Nhân viên',
                    'customers': 'Khách hàng',
                    'coupons': 'Mã giảm giá',
                    'promotions': 'Khuyến mãi',
                    'purchase_orders': 'Phiếu nhập',
                    'product_batches': 'Lô hàng',
                };
                return map[type] || type;
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>