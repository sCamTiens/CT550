<?php
$pageTitle = 'Thống Kê & Báo Cáo';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

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
            <!-- Reset Button -->
            <button type="button"
                class="text-sm border border-gray-300 rounded-lg px-3 py-3 bg-white hover:bg-[#002975] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center justify-center"
                @click="resetDateRange()" title="Reset về 30 ngày gần nhất">
                <i class="fa-solid fa-rotate-left"></i>
                <span class="text-[#002975] font-medium"></span>
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
                    :class="activeTab === 'staff' ? 'bg-[#002975] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    Nhân Viên
                </button>
                <button @click="activeTab = 'products'"
                    :class="activeTab === 'products' ? 'bg-[#002975] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    Sản Phẩm
                </button>
                <button @click="activeTab = 'suppliers'"
                    :class="activeTab === 'suppliers' ? 'bg-[#002975] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    Nhà Cung Cấp
                </button>
                <button @click="activeTab = 'customers'"
                    :class="activeTab === 'customers' ? 'bg-[#002975] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    Khách Hàng
                </button>
                <button @click="activeTab = 'inventory'"
                    :class="activeTab === 'inventory' ? 'bg-[#002975] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                    Tồn Kho
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Tab: Nhân Viên -->
            <div x-show="activeTab === 'staff'" x-transition style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Biểu đồ doanh thu nhân viên -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Phân Bổ Doanh Thu Theo Nhân Viên</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <canvas x-ref="staffRevenueChartCanvas" style="max-height: 400px;"></canvas>
                        </div>
                    </div>

                    <!-- Top nhân viên theo doanh thu -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Top Nhân Viên (Doanh Thu)</h4>
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
            <div x-show="activeTab === 'products'" x-transition style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Biểu đồ doanh thu sản phẩm -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Phân Bổ Doanh Thu Theo Sản Phẩm</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <canvas x-ref="productRevenueChartCanvas" style="max-height: 400px;"></canvas>
                        </div>
                    </div>

                    <!-- Top sản phẩm theo doanh thu -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Top Sản Phẩm (Doanh Thu)</h4>
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
            <div x-show="activeTab === 'suppliers'" x-transition style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Biểu đồ doanh thu nhà cung cấp -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Phân Bổ Doanh Thu Theo Nhà Cung Cấp</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <canvas x-ref="supplierRevenueChartCanvas" style="max-height: 400px;"></canvas>
                        </div>
                    </div>

                    <!-- Danh sách nhà cung cấp -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Top Nhà Cung Cấp</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in supplierStats" :key="item.supplier_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.supplier_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.total_purchases + ' lần nhập'"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600"
                                            x-text="formatMoney(item.total_sales_value)"></div>
                                        <div class="text-xs text-gray-500" x-text="formatMoney(item.total_purchase_value) + ' nhập'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Khách Hàng -->
            <div x-show="activeTab === 'customers'" x-transition style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Biểu đồ doanh thu khách hàng -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Phân Bổ Doanh Thu Theo Khách Hàng</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <canvas x-ref="customerRevenueChartCanvas" style="max-height: 400px;"></canvas>
                        </div>
                    </div>

                    <!-- Top khách hàng VIP -->
                    <div>
                        <h4 class="font-bold text-lg mb-4">Top Khách Hàng (Chi Tiêu)</h4>
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
                </div>
            </div>

            <!-- Tab: Tồn Kho -->
            <div x-show="activeTab === 'inventory'" x-transition style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Sản phẩm sắp hết hàng -->
                    <div>
                        <h4 class="font-bold text-lg mb-4 text-red-600">Sản Phẩm Sắp Hết Hàng</h4>
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
                                    </div>
                                </div>
                            </template>
                            <template x-if="inventoryStats.lowStock.length === 0">
                                <div class="text-center text-gray-500 py-8">Không có sản phẩm nào sắp hết hàng</div>
                            </template>
                        </div>
                    </div>

                    <!-- Sản phẩm tồn kho nhiều -->
                    <div>
                        <h4 class="font-bold text-lg mb-4 text-orange-600">Sản Phẩm Tồn Kho Cao</h4>
                        <div class="space-y-3">
                            <template x-for="item in inventoryStats.highStock" :key="item.product_id">
                                <div
                                    class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg border border-orange-200">
                                    <img :src="item.image_url" class="w-12 h-12 object-cover rounded">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="'SKU: ' + item.sku"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-orange-600"
                                            x-text="item.current_stock + ' ' + item.unit_name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Toast lỗi nổi -->
    <div id="toast-container" class="z-[60]"></div>
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
            
            // Charts
            staffRevenueChart: null,
            productRevenueChart: null,
            supplierRevenueChart: null,
            customerRevenueChart: null,

            resetDateRange() {
                const today = new Date();
                const lastMonth = new Date();
                lastMonth.setMonth(today.getMonth() - 1); // Lùi lại 1 tháng, giữ nguyên ngày
                
                // Reset dateRange values trước
                this.dateRange.from = this.formatDate(lastMonth);
                this.dateRange.to = this.formatDate(today);
                
                // Reset flatpickr instances và cập nhật input value
                if (this.$refs.fromDate._flatpickr) {
                    this.$refs.fromDate._flatpickr.setDate(lastMonth, false);
                    this.$refs.fromDate.value = this.dateRange.from; // Cập nhật input value thủ công
                }
                if (this.$refs.toDate._flatpickr) {
                    this.$refs.toDate._flatpickr.setDate(today, false);
                    this.$refs.toDate.value = this.dateRange.to; // Cập nhật input value thủ công
                }
                
                // Manually fetch data once
                this.fetchAllData();
            },

            init() {
                // Đợi Chart.js và Flatpickr load xong
                const checkLibraries = setInterval(() => {
                    if (typeof Chart !== 'undefined' && typeof flatpickr !== 'undefined') {
                        clearInterval(checkLibraries);
                        this.initDatePickers();
                        this.fetchAllData();
                        
                        // Watch activeTab để render biểu đồ khi chuyển tab
                        this.$watch('activeTab', (newTab, oldTab) => {
                            // Destroy chart của tab cũ trước khi chuyển
                            if (oldTab) {
                                this.destroyChartForTab(oldTab);
                            }
                            
                            this.$nextTick(() => {
                                this.renderChartsForTab(newTab);
                            });
                        });
                    }
                }, 100);
            },
            
            destroyChartForTab(tab) {
                switch(tab) {
                    case 'staff':
                        if (this.staffRevenueChart) {
                            this.staffRevenueChart.stop(); // Stop animations
                            this.staffRevenueChart.destroy();
                            this.staffRevenueChart = null;
                        }
                        break;
                    case 'products':
                        if (this.productRevenueChart) {
                            this.productRevenueChart.stop();
                            this.productRevenueChart.destroy();
                            this.productRevenueChart = null;
                        }
                        break;
                    case 'suppliers':
                        if (this.supplierRevenueChart) {
                            this.supplierRevenueChart.stop();
                            this.supplierRevenueChart.destroy();
                            this.supplierRevenueChart = null;
                        }
                        break;
                    case 'customers':
                        if (this.customerRevenueChart) {
                            this.customerRevenueChart.stop();
                            this.customerRevenueChart.destroy();
                            this.customerRevenueChart = null;
                        }
                        break;
                }
            },
            
            renderChartsForTab(tab) {
                // Đợi DOM update và x-show animation xong trước khi render chart
                setTimeout(() => {
                    switch(tab) {
                        case 'staff':
                            this.renderStaffRevenueChart();
                            break;
                        case 'products':
                            this.renderProductRevenueChart();
                            break;
                        case 'suppliers':
                            this.renderSupplierRevenueChart();
                            break;
                        case 'customers':
                            this.renderCustomerRevenueChart();
                            break;
                    }
                }, 200);
            },

            initDatePickers() {
                const self = this;

                // Default: 1 tháng trước (cùng ngày)
                const today = new Date();
                const lastMonth = new Date();
                lastMonth.setMonth(today.getMonth() - 1); // Lùi lại 1 tháng

                flatpickr(this.$refs.fromDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: lastMonth,
                    maxDate: today, // Không cho chọn ngày trong tương lai quá hôm nay
                    onChange: function (selectedDates, dateStr) {
                        self.dateRange.from = dateStr;
                        
                        // Set maxDate cho toDate = ngày vừa chọn ở fromDate
                        if (self.$refs.toDate._flatpickr && selectedDates[0]) {
                            self.$refs.toDate._flatpickr.set('minDate', selectedDates[0]);
                        }
                        
                        self.fetchAllData();
                    }
                });

                flatpickr(this.$refs.toDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: today,
                    minDate: lastMonth, // Không cho chọn ngày trước fromDate mặc định
                    maxDate: today, // Không cho chọn ngày trong tương lai
                    onChange: function (selectedDates, dateStr) {
                        self.dateRange.to = dateStr;
                        
                        // Set maxDate cho fromDate = ngày vừa chọn ở toDate
                        if (self.$refs.fromDate._flatpickr && selectedDates[0]) {
                            self.$refs.fromDate._flatpickr.set('maxDate', selectedDates[0]);
                        }
                        
                        self.fetchAllData();
                    }
                });

                // Set initial values
                this.dateRange.from = this.formatDate(lastMonth);
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

                    // Render biểu đồ cho tab đang active
                    this.$nextTick(() => {
                        this.renderChartsForTab(this.activeTab);
                    });

                } catch (err) {
                    this.showToast('Không thể tải dữ liệu thống kê!', 'error');
                }
            },

            formatMoney(amount) {
                if (!amount) return '0 ₫';
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount);
            },

            renderStaffRevenueChart() {
                // Destroy existing chart trước
                if (this.staffRevenueChart) {
                    this.staffRevenueChart.stop();
                    this.staffRevenueChart.destroy();
                    this.staffRevenueChart = null;
                }
                
                const data = this.staffStats.byRevenue.slice(0, 10); // Top 10
                
                // Kiểm tra nếu không có dữ liệu
                if (!data.length) {
                    return;
                }
                
                // Kiểm tra canvas element tồn tại
                const canvas = this.$refs.staffRevenueChartCanvas;
                if (!canvas) {
                    console.log('Staff revenue canvas not found');
                    return;
                }
                
                // Kiểm tra canvas có đang hiển thị không
                if (canvas.offsetParent === null) {
                    console.log('Staff revenue canvas is not visible');
                    return;
                }
                
                const labels = data.map(item => item.full_name);
                const revenues = data.map(item => parseFloat(item.total_revenue));
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];

                try {
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        console.error('Cannot get context from staff revenue canvas');
                        return;
                    }
                    
                this.staffRevenueChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: revenues,
                            backgroundColor: colors.slice(0, revenues.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: false, // Tắt animation để tránh conflict khi switch tab
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 10,
                                    font: { size: 12 },
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            return {
                                                text: `${label}: ${this.formatMoney(value)}`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        return `${context.label}: ${this.formatMoney(context.raw)}`;
                                    }
                                }
                            }
                        }
                    }
                });
                } catch (err) {
                    console.error('Error rendering staff revenue chart:', err);
                }
            },

            renderProductRevenueChart() {
                // Destroy existing chart trước
                if (this.productRevenueChart) {
                    this.productRevenueChart.stop();
                    this.productRevenueChart.destroy();
                    this.productRevenueChart = null;
                }
                
                const data = this.productStats.byRevenue.slice(0, 10); // Top 10
                
                // Kiểm tra nếu không có dữ liệu
                if (!data.length) {
                    console.log('No product revenue data');
                    return;
                }
                
                // Kiểm tra canvas element tồn tại
                const canvas = this.$refs.productRevenueChartCanvas;
                if (!canvas) {
                    console.log('Product revenue canvas not found');
                    return;
                }
                
                // Kiểm tra canvas có đang hiển thị không
                if (canvas.offsetParent === null) {
                    console.log('Product revenue canvas is not visible');
                    return;
                }
                
                const labels = data.map(item => item.name);
                const revenues = data.map(item => parseFloat(item.total_revenue));
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];

                try {
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        console.error('Cannot get context from product revenue canvas');
                        return;
                    }
                    
                this.productRevenueChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: revenues,
                            backgroundColor: colors.slice(0, revenues.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: false, // Tắt animation để tránh conflict khi switch tab
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 10,
                                    font: { size: 12 },
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            return {
                                                text: `${label}: ${this.formatMoney(value)}`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        return `${context.label}: ${this.formatMoney(context.raw)}`;
                                    }
                                }
                            }
                        }
                    }
                });
                } catch (err) {
                    console.error('Error rendering product revenue chart:', err);
                }
            },

            renderSupplierRevenueChart() {
                // Destroy existing chart trước
                if (this.supplierRevenueChart) {
                    this.supplierRevenueChart.stop();
                    this.supplierRevenueChart.destroy();
                    this.supplierRevenueChart = null;
                }
                
                const data = this.supplierStats.slice(0, 10); // Top 10
                
                // Kiểm tra nếu không có dữ liệu
                if (!data.length) {
                    console.log('No supplier revenue data');
                    return;
                }
                
                // Kiểm tra canvas element tồn tại
                const canvas = this.$refs.supplierRevenueChartCanvas;
                if (!canvas) {
                    console.log('Supplier revenue canvas not found');
                    return;
                }
                
                // Kiểm tra canvas có đang hiển thị không
                if (canvas.offsetParent === null) {
                    console.log('Supplier revenue canvas is not visible');
                    return;
                }
                
                const labels = data.map(item => item.supplier_name);
                const revenues = data.map(item => parseFloat(item.total_sales_value));
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];

                try {
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        console.error('Cannot get context from supplier revenue canvas');
                        return;
                    }
                    
                this.supplierRevenueChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: revenues,
                            backgroundColor: colors.slice(0, revenues.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: false, // Tắt animation để tránh conflict khi switch tab
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 10,
                                    font: { size: 12 },
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            return {
                                                text: `${label}: ${this.formatMoney(value)}`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        return `${context.label}: ${this.formatMoney(context.raw)}`;
                                    }
                                }
                            }
                        }
                    }
                });
                } catch (err) {
                    console.error('Error rendering supplier revenue chart:', err);
                }
            },

            renderCustomerRevenueChart() {
                // Destroy existing chart trước
                if (this.customerRevenueChart) {
                    this.customerRevenueChart.stop();
                    this.customerRevenueChart.destroy();
                    this.customerRevenueChart = null;
                }
                
                const data = this.customerStats.topSpenders.slice(0, 10); // Top 10
                
                // Kiểm tra nếu không có dữ liệu
                if (!data.length) {
                    console.log('No customer revenue data');
                    return;
                }
                
                // Kiểm tra canvas element tồn tại
                const canvas = this.$refs.customerRevenueChartCanvas;
                if (!canvas) {
                    console.log('Customer revenue canvas not found');
                    return;
                }
                
                // Kiểm tra canvas có đang hiển thị không
                if (canvas.offsetParent === null) {
                    console.log('Customer revenue canvas is not visible');
                    return;
                }
                
                const labels = data.map(item => item.full_name);
                const revenues = data.map(item => parseFloat(item.total_spent));
                const colors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];

                try {
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        console.error('Cannot get context from customer revenue canvas');
                        return;
                    }
                    
                this.customerRevenueChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: revenues,
                            backgroundColor: colors.slice(0, revenues.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: false, // Tắt animation để tránh conflict khi switch tab
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 10,
                                    font: { size: 12 },
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            return {
                                                text: `${label}: ${this.formatMoney(value)}`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        return `${context.label}: ${this.formatMoney(context.raw)}`;
                                    }
                                }
                            }
                        }
                    }
                });
                } catch (err) {
                    console.error('Error rendering customer revenue chart:', err);
                }
            },

            showToast(msg, type = 'error') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className =
                    `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
                                        ${type === 'success'
                        ? 'text-green-700 border-green-400'
                        : 'text-red-700 border-red-400'}
                                        bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                                        <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'} mr-3" 
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            ${type === 'success'
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M5 13l4 4L19 7" />`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
                                        </svg>
                                        <div class="flex-1">${msg}</div>
                                    `;

                box.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>