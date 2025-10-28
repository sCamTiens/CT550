<?php
$pageTitle = 'Thống Kê & Báo Cáo';
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<div x-data="reportsPage()" x-init="init()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Thống Kê & Báo Cáo</h1>

        <!-- Date Range Filter -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Từ ngày:</label>
                <input type="text" x-ref="fromDate"
                    class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 w-40">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Đến ngày:</label>
                <input type="text" x-ref="toDate"
                    class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 w-40">
            </div>
            <button @click="fetchAllData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                🔄 Làm mới
            </button>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Tổng doanh thu -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Tổng Doanh Thu</h3>
                <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-3xl font-bold" x-text="formatMoney(overview.totalRevenue)"></div>
            <div class="text-xs opacity-75 mt-1" x-text="'Từ ' + overview.totalOrders + ' đơn hàng'"></div>
        </div>

        <!-- Đơn hàng mới -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Đơn Hàng Mới</h3>
                <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div class="text-3xl font-bold" x-text="overview.totalOrders"></div>
            <div class="text-xs opacity-75 mt-1" x-text="'Giá trị TB: ' + formatMoney(overview.avgOrderValue)"></div>
        </div>

        <!-- Khách hàng mới -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Khách Hàng Mới</h3>
                <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="text-3xl font-bold" x-text="overview.newCustomers"></div>
            <div class="text-xs opacity-75 mt-1">Trong kỳ</div>
        </div>

        <!-- Sản phẩm bán ra -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Sản Phẩm Bán Ra</h3>
                <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="text-3xl font-bold" x-text="overview.totalProductsSold"></div>
            <div class="text-xs opacity-75 mt-1">Tổng số lượng</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b">
            <nav class="flex gap-1 p-4">
                <button @click="activeTab = 'staff'"
                    :class="activeTab === 'staff' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    👤 Nhân Viên
                </button>
                <button @click="activeTab = 'products'"
                    :class="activeTab === 'products' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    📦 Sản Phẩm
                </button>
                <button @click="activeTab = 'suppliers'"
                    :class="activeTab === 'suppliers' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    🚚 Nhà Cung Cấp
                </button>
                <button @click="activeTab = 'customers'"
                    :class="activeTab === 'customers' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    👥 Khách Hàng
                </button>
                <button @click="activeTab = 'inventory'"
                    :class="activeTab === 'inventory' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    📊 Tồn Kho
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Tab: Nhân Viên -->
            <div x-show="activeTab === 'staff'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top nhân viên theo số đơn -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">🏆 Top Nhân Viên (Số Đơn Hàng)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in staffStats.byOrders" :key="item.staff_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.full_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.staff_role"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600"
                                            x-text="item.total_orders + ' đơn'"></div>
                                        <div class="text-xs text-gray-500" x-text="formatMoney(item.total_revenue)">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Top nhân viên theo doanh thu -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">💰 Top Nhân Viên (Doanh Thu)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in staffStats.byRevenue" :key="item.staff_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.full_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.staff_role"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600"
                                            x-text="formatMoney(item.total_revenue)"></div>
                                        <div class="text-xs text-gray-500" x-text="item.total_orders + ' đơn'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Sản Phẩm -->
            <div x-show="activeTab === 'products'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top sản phẩm bán chạy -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">🔥 Top Sản Phẩm Bán Chạy (Số Lượng)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in productStats.byQuantity" :key="item.product_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <img :src="item.image_url"
                                        class="w-12 h-12 object-cover rounded-full border mx-auto" :alt="item.name">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="'SKU: ' + item.sku"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600"
                                            x-text="item.total_quantity + ' ' + item.unit_name"></div>
                                        <div class="text-xs text-gray-500" x-text="formatMoney(item.total_revenue)">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Top sản phẩm theo doanh thu -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">💎 Top Sản Phẩm (Doanh Thu)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in productStats.byRevenue" :key="item.product_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <img :src="item.image_url"
                                        class="w-12 h-12 object-cover rounded-full border mx-auto" :alt="item.name">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="'SKU: ' + item.sku"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600"
                                            x-text="formatMoney(item.total_revenue)"></div>
                                        <div class="text-xs text-gray-500"
                                            x-text="item.total_quantity + ' ' + item.unit_name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Nhà Cung Cấp -->
            <div x-show="activeTab === 'suppliers'" x-transition>
                <h4 class="font-bold text-lg mb-4">🚚 Thống Kê Nhà Cung Cấp</h4>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà Cung Cấp
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số Lần Nhập
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng Giá
                                    Trị Nhập</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Doanh Thu
                                    Sản Phẩm</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hiệu Suất
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in supplierStats" :key="item.supplier_id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm" x-text="index + 1"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium" x-text="item.supplier_name"></div>
                                        <div class="text-xs text-gray-500" x-text="item.contact_person"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm" x-text="item.total_purchases"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium"
                                        x-text="formatMoney(item.total_purchase_value)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-green-600"
                                        x-text="formatMoney(item.total_sales_value)"></td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="px-2 py-1 text-xs font-semibold rounded"
                                            :class="item.efficiency > 150 ? 'bg-green-100 text-green-800' : item.efficiency > 100 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'"
                                            x-text="item.efficiency + '%'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Khách Hàng -->
            <div x-show="activeTab === 'customers'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top khách hàng VIP -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">👑 Top Khách Hàng VIP (Chi Tiêu)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in customerStats.topSpenders" :key="item.user_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.full_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.email"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-purple-600"
                                            x-text="formatMoney(item.total_spent)"></div>
                                        <div class="text-xs text-gray-500" x-text="item.total_orders + ' đơn'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Khách hàng mua nhiều đơn nhất -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">🎯 Top Khách Hàng (Số Đơn)</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in customerStats.topBuyers" :key="item.user_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.full_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.email"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600"
                                            x-text="item.total_orders + ' đơn'"></div>
                                        <div class="text-xs text-gray-500" x-text="formatMoney(item.total_spent)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Tồn Kho -->
            <div x-show="activeTab === 'inventory'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Sản phẩm sắp hết hàng -->
                    <div>
                        <h4 class="font-bold text-lg mb-4 text-red-600">⚠️ Sản Phẩm Sắp Hết Hàng</h4>
                        <div class="space-y-3">
                            <template x-for="item in inventoryStats.lowStock" :key="item.product_id">
                                <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                    <img :src="item.image_url"
                                        class="w-12 h-12 object-cover rounded-full border mx-auto" :alt="item.name">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="'SKU: ' + item.sku"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-red-600"
                                            x-text="item.current_stock + ' ' + item.unit_name"></div>
                                        <div class="text-xs text-gray-500" x-text="'Min: ' + item.min_stock"></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="inventoryStats.lowStock.length === 0">
                                <div class="text-center text-gray-500 py-8">✅ Không có sản phẩm nào sắp hết hàng</div>
                            </template>
                        </div>
                    </div>

                    <!-- Sản phẩm tồn kho nhiều -->
                    <div>
                        <h4 class="font-bold text-lg mb-4 text-orange-600">📦 Sản Phẩm Tồn Kho Cao</h4>
                        <div class="space-y-3">
                            <template x-for="item in inventoryStats.highStock" :key="item.product_id">
                                <div
                                    class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg border border-orange-200">
                                    <img :src="item.image_url"
                                        class="w-12 h-12 object-cover rounded">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="'SKU: ' + item.sku"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-orange-600"
                                            x-text="item.current_stock + ' ' + item.unit_name"></div>
                                        <div class="text-xs text-gray-500" x-text="'Max: ' + item.max_stock"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function reportsPage() {
        return {
            activeTab: 'staff',
            dateRange: {
                from: '',
                to: ''
            },
            overview: {
                totalRevenue: 0,
                totalOrders: 0,
                avgOrderValue: 0,
                newCustomers: 0,
                totalProductsSold: 0
            },
            staffStats: {
                byOrders: [],
                byRevenue: []
            },
            productStats: {
                byQuantity: [],
                byRevenue: []
            },
            supplierStats: [],
            customerStats: {
                topSpenders: [],
                topBuyers: []
            },
            inventoryStats: {
                lowStock: [],
                highStock: []
            },

            init() {
                this.initDatePickers();
                this.fetchAllData();
            },

            initDatePickers() {
                const self = this;

                // Default: last 30 days
                const today = new Date();
                const last30Days = new Date();
                last30Days.setDate(today.getDate() - 30);

                flatpickr(this.$refs.fromDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: last30Days,
                    onChange: function (selectedDates, dateStr) {
                        self.dateRange.from = dateStr;
                    }
                });

                flatpickr(this.$refs.toDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: today,
                    onChange: function (selectedDates, dateStr) {
                        self.dateRange.to = dateStr;
                    }
                });

                // Set initial values
                this.dateRange.from = this.formatDate(last30Days);
                this.dateRange.to = this.formatDate(today);
            },

            formatDate(date) {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            },

            async fetchAllData() {
                const params = new URLSearchParams();
                if (this.dateRange.from) params.append('from_date', this.dateRange.from);
                if (this.dateRange.to) params.append('to_date', this.dateRange.to);

                try {
                    const [
                        overviewRes,
                        staffOrdersRes,
                        staffRevenueRes,
                        productQtyRes,
                        productRevRes,
                        supplierRes,
                        customerSpendRes,
                        customerOrdersRes,
                        lowStockRes,
                        highStockRes
                    ] = await Promise.all([
                        fetch(`/admin/api/reports/overview?${params}`),
                        fetch(`/admin/api/reports/staff/orders?${params}`),
                        fetch(`/admin/api/reports/staff/revenue?${params}`),
                        fetch(`/admin/api/reports/products/quantity?${params}`),
                        fetch(`/admin/api/reports/products/revenue?${params}`),
                        fetch(`/admin/api/reports/suppliers?${params}`),
                        fetch(`/admin/api/reports/customers/spenders?${params}`),
                        fetch(`/admin/api/reports/customers/orders?${params}`),
                        fetch(`/admin/api/reports/inventory/low-stock`),
                        fetch(`/admin/api/reports/inventory/high-stock`)
                    ]);

                    this.overview = await overviewRes.json();
                    this.staffStats.byOrders = (await staffOrdersRes.json()).data || [];
                    this.staffStats.byRevenue = (await staffRevenueRes.json()).data || [];
                    this.productStats.byQuantity = (await productQtyRes.json()).data || [];
                    this.productStats.byRevenue = (await productRevRes.json()).data || [];
                    this.supplierStats = (await supplierRes.json()).data || [];
                    this.customerStats.topSpenders = (await customerSpendRes.json()).data || [];
                    this.customerStats.topBuyers = (await customerOrdersRes.json()).data || [];
                    this.inventoryStats.lowStock = (await lowStockRes.json()).data || [];
                    this.inventoryStats.highStock = (await highStockRes.json()).data || [];

                } catch (err) {
                    console.error('Lỗi tải dữ liệu:', err);
                    alert('Không thể tải dữ liệu thống kê!');
                }
            },

            formatMoney(amount) {
                if (!amount) return '0 ₫';
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount);
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>