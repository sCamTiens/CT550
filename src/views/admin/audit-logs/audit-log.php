<?php
$pageTitle = 'Lịch Sử Thao Tác';
ob_start();
?>

<div x-data="auditLogPage()" x-init="init()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📋 Lịch Sử Thao Tác</h1>
        
        <div class="flex gap-3">
            <button @click="showStats = true" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Thống Kê
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" x-model="filters.search" @input="fetchLogs()" 
                       placeholder="Tìm theo tên, nội dung..."
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Entity Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại Đối Tượng</label>
                <select x-model="filters.entity_type" @change="fetchLogs()" 
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="products">Sản phẩm</option>
                    <option value="categories">Danh mục</option>
                    <option value="brands">Thương hiệu</option>
                    <option value="suppliers">Nhà cung cấp</option>
                    <option value="orders">Đơn hàng</option>
                    <option value="users">Người dùng</option>
                    <option value="coupons">Mã giảm giá</option>
                    <option value="promotions">Khuyến mãi</option>
                    <option value="purchase_orders">Phiếu nhập</option>
                    <option value="product_batches">Lô hàng</option>
                </select>
            </div>

            <!-- Action -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hành Động</label>
                <select x-model="filters.action" @change="fetchLogs()" 
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="create">Thêm mới</option>
                    <option value="update">Cập nhật</option>
                    <option value="delete">Xóa</option>
                    <option value="restore">Khôi phục</option>
                    <option value="status_change">Đổi trạng thái</option>
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ Ngày</label>
                <input type="date" x-model="filters.from_date" @change="fetchLogs()" 
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- To Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến Ngày</label>
                <input type="date" x-model="filters.to_date" @change="fetchLogs()" 
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-3 flex justify-end">
            <button @click="resetFilters()" 
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                Xóa Bộ Lọc
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Xem
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Hiển thị <span class="font-medium" x-text="((currentPage - 1) * perPage + 1)"></span>
                đến <span class="font-medium" x-text="Math.min(currentPage * perPage, filteredLogs.length)"></span>
                trong tổng số <span class="font-medium" x-text="filteredLogs.length"></span> bản ghi
            </div>
            <div class="flex gap-2">
                <button @click="currentPage--" :disabled="currentPage === 1" 
                        class="px-3 py-1 border rounded disabled:opacity-50">
                    Trước
                </button>
                <button @click="currentPage++" :disabled="currentPage === totalPages" 
                        class="px-3 py-1 border rounded disabled:opacity-50">
                    Sau
                </button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="showDetail" @click.self="showDetail = false" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">Chi Tiết Thao Tác</h3>
                <button @click="showDetail = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200 max-h-96 overflow-y-auto">
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
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div class="bg-white rounded-lg w-full max-w-5xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">📊 Thống Kê Hoạt Động</h3>
                <button @click="showStats = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-3 gap-6">
                    <!-- Stats by Action -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Theo Hành Động</h4>
                        <template x-if="stats.byAction.length">
                            <div class="space-y-2">
                                <template x-for="item in stats.byAction" :key="item.action">
                                    <div class="flex justify-between">
                                        <span x-text="getActionText(item.action)"></span>
                                        <span class="font-bold" x-text="item.count"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Stats by Entity -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Theo Đối Tượng</h4>
                        <template x-if="stats.byEntity.length">
                            <div class="space-y-2">
                                <template x-for="item in stats.byEntity" :key="item.entity_type">
                                    <div class="flex justify-between">
                                        <span x-text="getEntityTypeText(item.entity_type)"></span>
                                        <span class="font-bold" x-text="item.count"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Stats by User -->
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="font-bold mb-3">Top Người Dùng</h4>
                        <template x-if="stats.byUser.length">
                            <div class="space-y-2">
                                <template x-for="item in stats.byUser.slice(0, 10)" :key="item.actor_user_id">
                                    <div class="flex justify-between">
                                        <span class="truncate" x-text="item.full_name"></span>
                                        <span class="font-bold" x-text="item.total_actions"></span>
                                    </div>
                                </template>
                            </div>
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
        filters: {
            search: '',
            entity_type: '',
            action: '',
            from_date: '',
            to_date: '',
        },
        currentPage: 1,
        perPage: 20,
        showDetail: false,
        showStats: false,
        selectedLog: null,
        stats: {
            byAction: [],
            byEntity: [],
            byUser: [],
        },

        init() {
            this.fetchLogs();
        },

        async fetchLogs() {
            try {
                const params = new URLSearchParams();
                Object.keys(this.filters).forEach(key => {
                    if (this.filters[key]) params.append(key, this.filters[key]);
                });

                const res = await fetch(`/admin/api/audit-logs?${params}`);
                const data = await res.json();
                this.logs = data.items || [];
                this.currentPage = 1;
            } catch (err) {
                alert('Lỗi tải dữ liệu: ' + err.message);
            }
        },

        async fetchStats() {
            try {
                const params = new URLSearchParams();
                if (this.filters.from_date) params.append('from_date', this.filters.from_date);
                if (this.filters.to_date) params.append('to_date', this.filters.to_date);

                const [resAction, resEntity, resUser] = await Promise.all([
                    fetch(`/admin/api/audit-logs/stats/action?${params}`),
                    fetch(`/admin/api/audit-logs/stats/entity?${params}`),
                    fetch(`/admin/api/audit-logs/stats/user?${params}`),
                ]);

                this.stats.byAction = (await resAction.json()).stats || [];
                this.stats.byEntity = (await resEntity.json()).stats || [];
                this.stats.byUser = (await resUser.json()).stats || [];
            } catch (err) {
                alert('Lỗi tải thống kê: ' + err.message);
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                entity_type: '',
                action: '',
                from_date: '',
                to_date: '',
            };
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
            return Math.ceil(this.filteredLogs.length / this.perPage);
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
                'create': 'px-2 py-1 bg-green-100 text-green-800 rounded text-xs',
                'update': 'px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs',
                'delete': 'px-2 py-1 bg-red-100 text-red-800 rounded text-xs',
                'restore': 'px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs',
                'status_change': 'px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs',
            };
            return map[action] || 'px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs';
        },

        getEntityTypeText(type) {
            const map = {
                'products': 'Sản phẩm',
                'categories': 'Danh mục',
                'brands': 'Thương hiệu',
                'suppliers': 'Nhà cung cấp',
                'orders': 'Đơn hàng',
                'users': 'Người dùng',
                'coupons': 'Mã giảm giá',
                'promotions': 'Khuyến mãi',
                'purchase_orders': 'Phiếu nhập',
                'product_batches': 'Lô hàng',
            };
            return map[type] || type;
        },

        $watch(key, callback) {
            if (key === 'showStats') {
                return (value) => {
                    if (value) this.fetchStats();
                };
            }
        },
    };
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../_layout.php';
