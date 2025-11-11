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

<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Thống kê & báo cáo</span>
</nav>

<div x-data="reportsPage()" x-init="init()" class="container">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-[#002975] mb-4">Thống Kê & Báo Cáo</h1>

        <!-- Bộ lọc điều kiện -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Chọn Tiêu Chí Thống Kê</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Chọn loại thống kê -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loại thống kê:</label>
                    <select x-model="filters.reportType" @change="onReportTypeChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                        <option value="staff">Nhân viên</option>
                        <option value="products">Sản phẩm</option>
                        <option value="customers">Khách hàng</option>
                        <option value="suppliers">Nhà cung cấp</option>
                        <option value="orders">Đơn hàng</option>
                        <option value="inventory">Tồn kho</option>
                    </select>
                </div>

                <!-- Chọn tiêu chí -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tiêu chí:</label>
                    <select x-model="filters.criteria"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                        <template x-for="option in criteriaOptions" :key="option.value">
                            <option :value="option.value" x-text="option.label"></option>
                        </template>
                    </select>
                </div>

                <!-- Từ ngày -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày:</label>
                    <input type="text" x-ref="fromDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                </div>

                <!-- Đến ngày -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày:</label>
                    <input type="text" x-ref="toDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                </div>
            </div>

            <!-- Dòng 2: Các bộ lọc bổ sung -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <!-- Dropdown chọn nhân viên -->
                <div x-show="showStaffFilter">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nhân viên:</label>
                    <div class="relative" x-data="{ open: false, search: '' }">
                        <button @click="open = !open; if(open) loadStaffList()" type="button"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975] text-left bg-white flex items-center justify-between">
                            <span x-text="getSelectedStaffName()"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-hidden">
                            <input type="text" x-model="search" placeholder="Tìm kiếm nhân viên..."
                                class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-1 focus:ring-[#002975]">
                            <div class="overflow-y-auto max-h-52">
                                <div @click="filters.staffId = ''; open = false; search = ''"
                                    class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                                    Tất cả nhân viên
                                </div>
                                <template x-for="staff in filteredStaffList(search)" :key="staff.staff_id">
                                    <div @click="filters.staffId = staff.staff_id; open = false; search = ''"
                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer"
                                        :class="{'bg-blue-100': filters.staffId == staff.staff_id}">
                                        <span x-text="staff.full_name"></span>
                                        <span class="text-xs text-gray-500" x-text="' - ' + staff.staff_role"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown chọn sản phẩm -->
                <div x-show="showProductFilter">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sản phẩm:</label>
                    <div class="relative" x-data="{ open: false, search: '' }">
                        <button @click="open = !open; if(open) loadProductList()" type="button"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975] text-left bg-white flex items-center justify-between">
                            <span x-text="getSelectedProductName()"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-hidden">
                            <input type="text" x-model="search" placeholder="Tìm kiếm sản phẩm..."
                                class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-1 focus:ring-[#002975]">
                            <div class="overflow-y-auto max-h-52">
                                <div @click="filters.productId = ''; open = false; search = ''"
                                    class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                                    Tất cả sản phẩm
                                </div>
                                <template x-for="product in filteredProductList(search)" :key="product.product_id">
                                    <div @click="filters.productId = product.product_id; open = false; search = ''"
                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer"
                                        :class="{'bg-blue-100': filters.productId == product.product_id}">
                                        <span x-text="product.name"></span>
                                        <span class="text-xs text-gray-500" x-text="' - ' + product.sku"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown chọn khách hàng -->
                <div x-show="showCustomerFilter">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Khách hàng:</label>
                    <div class="relative" x-data="{ open: false, search: '' }">
                        <button @click="open = !open; if(open) loadCustomerList()" type="button"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975] text-left bg-white flex items-center justify-between">
                            <span x-text="getSelectedCustomerName()"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-hidden">
                            <input type="text" x-model="search" placeholder="Tìm kiếm khách hàng..."
                                class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-1 focus:ring-[#002975]">
                            <div class="overflow-y-auto max-h-52">
                                <div @click="filters.customerId = ''; open = false; search = ''"
                                    class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                                    Tất cả khách hàng
                                </div>
                                <template x-for="customer in filteredCustomerList(search)" :key="customer.customer_id">
                                    <div @click="filters.customerId = customer.customer_id; open = false; search = ''"
                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer"
                                        :class="{'bg-blue-100': filters.customerId == customer.customer_id}">
                                        <span x-text="customer.full_name"></span>
                                        <span class="text-xs text-gray-500"
                                            x-text="customer.email ? ' - ' + customer.email : ''"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown chọn nhà cung cấp -->
                <div x-show="showSupplierFilter">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nhà cung cấp:</label>
                    <div class="relative" x-data="{ open: false, search: '' }">
                        <button @click="open = !open; if(open) loadSupplierList()" type="button"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975] text-left bg-white flex items-center justify-between">
                            <span x-text="getSelectedSupplierName()"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-hidden">
                            <input type="text" x-model="search" placeholder="Tìm kiếm nhà cung cấp..."
                                class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-1 focus:ring-[#002975]">
                            <div class="overflow-y-auto max-h-52">
                                <div @click="filters.supplierId = ''; open = false; search = ''"
                                    class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                                    Tất cả nhà cung cấp
                                </div>
                                <template x-for="supplier in filteredSupplierList(search)" :key="supplier.supplier_id">
                                    <div @click="filters.supplierId = supplier.supplier_id; open = false; search = ''"
                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer"
                                        :class="{'bg-blue-100': filters.supplierId == supplier.supplier_id}">
                                        <span x-text="supplier.supplier_name"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Giá trị từ -->
                <div x-show="showValueRange">
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        x-text="valueRangeLabel + ' từ:'"></label>
                    <input type="number" x-model="filters.valueFrom" placeholder="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                </div>

                <!-- Giá trị đến -->
                <div x-show="showValueRange">
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        x-text="valueRangeLabel + ' đến:'"></label>
                    <input type="number" x-model="filters.valueTo" placeholder="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                </div>

                <!-- Sắp xếp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sắp xếp:</label>
                    <select x-model="filters.sortOrder"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]">
                        <option value="desc">Cao nhất → Thấp nhất</option>
                        <option value="asc">Thấp nhất → Cao nhất</option>
                    </select>
                </div>
            </div>

            <!-- Nút hành động -->
            <div class="flex items-center gap-3 mt-4">
                <button type="button"
                    class="px-6 py-2 border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white rounded-lg focus:outline-none focus:ring-1 focus:ring-[#002975] flex items-center gap-2"
                    @click="applyFilters()">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Thống Kê</span>
                </button>

                <button type="button"
                    class="px-6 py-2 rounded-lg border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white focus:outline-none focus:ring-1 focus:ring-[#002975] flex items-center gap-2"
                    @click="exportExcel()" :disabled="!hasData">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Xuất Excel</span>
                </button>

                <button type="button"
                    class="px-6 py-2 border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white rounded-lg focus:outline-none focus:ring-1 focus:ring-[#002975] flex items-center gap-2"
                    @click="resetFilters()">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Đặt lại</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Kết quả thống kê -->
    <div class="bg-white rounded-lg shadow-md p-6" x-show="hasData">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800" x-text="resultTitle"></h2>
            <div class="text-sm text-gray-500">
                <span x-text="'Hiển thị ' + getFilteredTableData().length + ' / ' + totalResults + ' kết quả'"></span>
            </div>
        </div>

        <!-- Biểu đồ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Biểu đồ cột -->
            <div>
                <h4 class="font-bold text-base mb-3 text-gray-700">Biểu Đồ Cột</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <canvas x-ref="barChartCanvas" style="max-height: 350px;"></canvas>
                </div>
            </div>

            <!-- Biểu đồ tròn -->
            <div>
                <h4 class="font-bold text-base mb-3 text-gray-700">Biểu Đồ Tròn</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <canvas x-ref="pieChartCanvas" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse mb-[150px]">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                        <th class="px-4 py-3 text-left text-base font-bold text-gray-700">STT</th>
                        <template x-for="(col, index) in tableColumns" :key="col.key">
                            <th class="px-4 py-3 text-left text-base font-bold text-gray-700 relative">
                                <div class="flex items-center gap-1">
                                    <span x-text="col.label"></span>
                                    <button @click.stop="toggleTableFilter(index + 1)"
                                        class="p-1 rounded hover:bg-gray-200" :title="'Lọc ' + col.label">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Filter Popover -->
                                <div x-show="openTableFilter['column' + (index + 1)]" x-transition
                                    @click.outside="openTableFilter['column' + (index + 1)] = false"
                                    class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                    <div class="font-semibold mb-2" x-text="'Lọc: ' + col.label"></div>
                                    <input x-model.trim="tableFilters['column' + (index + 1)]"
                                        class="w-full border rounded px-3 py-2"
                                        :placeholder="'Nhập ' + col.label.toLowerCase()">
                                    <div class="mt-3 flex gap-2 justify-end">
                                        <button @click="applyTableFilter(index + 1)"
                                            class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                        <button @click="resetTableFilter(index + 1)"
                                            class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                            mới</button>
                                        <button @click="openTableFilter['column' + (index + 1)] = false"
                                            class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                    </div>
                                </div>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in getFilteredTableData()" :key="index">
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                            <td class="px-4 py-3 text-sm text-gray-600" x-text="index + 1"></td>
                            <template x-for="col in tableColumns" :key="col.key">
                                <td class="px-4 py-3 text-sm text-gray-800" x-html="formatCell(row, col)"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="!loading && getFilteredTableData().length===0">
                        <td colspan="12" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Trống</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Thông báo không có dữ liệu -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center" x-show="!hasData && isSearched">
        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Không tìm thấy dữ liệu</h3>
        <p class="text-gray-500">Vui lòng điều chỉnh tiêu chí lọc và thử lại</p>
    </div>
    <!-- Toast lỗi nổi -->
    <div id="toast-container" class="z-[60]"></div>
</div>

<script>
    function reportsPage() {
        return {
            // Filters
            filters: {
                reportType: 'staff',
                criteria: 'revenue',
                staffId: '',
                productId: '',
                customerId: '',
                supplierId: '',
                valueFrom: '',
                valueTo: '',
                sortOrder: 'desc',
                fromDate: '',
                toDate: ''
            },

            // Dropdown data
            staffList: [],
            productList: [],
            customerList: [],
            supplierList: [],

            // UI state
            hasData: false,
            isSearched: false,
            resultTitle: '',
            totalResults: 0,

            // Charts
            barChart: null,
            pieChart: null,

            // Table data
            tableColumns: [],
            tableData: [],
            tableDataFiltered: [], // Dữ liệu sau khi filter

            // Table filters
            tableFilters: {
                column0: '', // STT - không filter
                column1: '',
                column2: '',
                column3: '',
                column4: '',
                column5: ''
            },
            openTableFilter: {
                column1: false,
                column2: false,
                column3: false,
                column4: false,
                column5: false
            },

            get criteriaOptions() {
                const options = {
                    staff: [
                        { value: 'revenue', label: 'Doanh thu' },
                        { value: 'orders', label: 'Số đơn hàng' },
                        { value: 'avg_order_value', label: 'Giá trị đơn TB' }
                    ],
                    products: [
                        { value: 'revenue', label: 'Doanh thu' },
                        { value: 'quantity', label: 'Số lượng bán' },
                        { value: 'orders', label: 'Số đơn hàng' }
                    ],
                    customers: [
                        { value: 'total_spent', label: 'Tổng chi tiêu' },
                        { value: 'orders', label: 'Số đơn hàng' },
                        { value: 'avg_order_value', label: 'Giá trị đơn TB' }
                    ],
                    suppliers: [
                        { value: 'sales_value', label: 'Doanh thu bán' },
                        { value: 'purchase_value', label: 'Giá trị nhập' },
                        { value: 'purchases', label: 'Số lần nhập' }
                    ],
                    orders: [
                        { value: 'total', label: 'Tổng giá trị' },
                        { value: 'count', label: 'Số lượng đơn' },
                        { value: 'status', label: 'Theo trạng thái' }
                    ],
                    inventory: [
                        { value: 'low_stock', label: 'Sắp hết hàng' },
                        { value: 'high_stock', label: 'Tồn kho cao' },
                        { value: 'out_of_stock', label: 'Hết hàng' }
                    ]
                };
                return options[this.filters.reportType] || [];
            },

            get showSearchField() {
                return false; // Không dùng search field nữa
            },

            get searchFieldLabel() {
                return '';
            },

            get showStaffFilter() {
                // Hiển thị dropdown nhân viên khi muốn lọc theo nhân viên
                // Ví dụ: Xem sản phẩm nào nhân viên X đã bán
                return ['products', 'customers', 'orders'].includes(this.filters.reportType);
            },

            get showProductFilter() {
                // Hiển thị dropdown sản phẩm khi muốn lọc theo sản phẩm
                // - Thống kê sản phẩm: có thể chọn sản phẩm cụ thể để xem chi tiết
                // - Thống kê nhân viên: xem nhân viên nào bán sản phẩm X
                // - Thống kê khách hàng: xem khách hàng nào mua sản phẩm X
                return ['products', 'staff', 'customers', 'orders', 'inventory'].includes(this.filters.reportType);
            },

            get showCustomerFilter() {
                // Hiển thị dropdown khách hàng khi muốn lọc theo khách hàng
                return ['staff', 'products', 'orders'].includes(this.filters.reportType);
            },

            get showSupplierFilter() {
                // Hiển thị dropdown nhà cung cấp khi muốn lọc theo nhà cung cấp
                return ['products', 'suppliers'].includes(this.filters.reportType);
            },

            get showValueRange() {
                return this.filters.criteria !== 'status' && this.filters.reportType !== 'inventory';
            },

            get valueRangeLabel() {
                const labels = {
                    revenue: 'Doanh thu',
                    orders: 'Số đơn',
                    quantity: 'Số lượng',
                    total_spent: 'Chi tiêu',
                    sales_value: 'Doanh thu',
                    purchase_value: 'Giá trị nhập',
                    purchases: 'Số lần nhập',
                    avg_order_value: 'Giá trị TB',
                    total: 'Tổng giá trị',
                    count: 'Số lượng'
                };
                return labels[this.filters.criteria] || 'Giá trị';
            },

            init() {
                const checkLibraries = setInterval(() => {
                    if (typeof Chart !== 'undefined' && typeof flatpickr !== 'undefined') {
                        clearInterval(checkLibraries);
                        this.initDatePickers();
                    }
                }, 100);
            },

            onReportTypeChange() {
                // Reset criteria to first option
                this.filters.criteria = this.criteriaOptions[0]?.value || '';
                this.filters.staffId = '';
                this.filters.productId = '';
                this.filters.customerId = '';
                this.filters.supplierId = '';
                this.filters.valueFrom = '';
                this.filters.valueTo = '';
            },

            initDatePickers() {
                const self = this;
                const today = new Date();
                const lastMonth = new Date();
                lastMonth.setMonth(today.getMonth() - 1);

                flatpickr(this.$refs.fromDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: lastMonth,
                    maxDate: today,
                    onChange: function (selectedDates, dateStr) {
                        self.filters.fromDate = dateStr;
                        if (self.$refs.toDate._flatpickr && selectedDates[0]) {
                            self.$refs.toDate._flatpickr.set('minDate', selectedDates[0]);
                        }
                    }
                });

                flatpickr(this.$refs.toDate, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    defaultDate: today,
                    minDate: lastMonth,
                    maxDate: today,
                    onChange: function (selectedDates, dateStr) {
                        self.filters.toDate = dateStr;
                        if (self.$refs.fromDate._flatpickr && selectedDates[0]) {
                            self.$refs.fromDate._flatpickr.set('maxDate', selectedDates[0]);
                        }
                    }
                });

                this.filters.fromDate = this.formatDate(lastMonth);
                this.filters.toDate = this.formatDate(today);
            },

            formatDate(date) {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            },

            // Load danh sách nhân viên
            async loadStaffList() {
                if (this.staffList.length > 0) return; // Đã load rồi thì không load lại

                try {
                    const response = await fetch('/admin/api/reports/staff-list');
                    const result = await response.json();
                    if (result.success) {
                        this.staffList = result.data || [];
                    }
                } catch (err) {
                    console.error('Error loading staff list:', err);
                }
            },

            // Load danh sách sản phẩm
            async loadProductList() {
                if (this.productList.length > 0) return;

                try {
                    const response = await fetch('/admin/api/reports/product-list');
                    const result = await response.json();
                    if (result.success) {
                        this.productList = result.data || [];
                    }
                } catch (err) {
                    console.error('Error loading product list:', err);
                }
            },

            // Load danh sách khách hàng
            async loadCustomerList() {
                if (this.customerList.length > 0) return;

                try {
                    const response = await fetch('/admin/api/reports/customer-list');
                    const result = await response.json();
                    if (result.success) {
                        this.customerList = result.data || [];
                    }
                } catch (err) {
                    console.error('Error loading customer list:', err);
                }
            },

            // Load danh sách nhà cung cấp
            async loadSupplierList() {
                if (this.supplierList.length > 0) return;

                try {
                    const response = await fetch('/admin/api/reports/supplier-list');
                    const result = await response.json();
                    if (result.success) {
                        this.supplierList = result.data || [];
                    }
                } catch (err) {
                    console.error('Error loading supplier list:', err);
                }
            },

            // Lọc danh sách nhân viên theo search
            filteredStaffList(search) {
                if (!search) return this.staffList;
                const searchLower = search.toLowerCase();
                return this.staffList.filter(staff =>
                    staff.full_name.toLowerCase().includes(searchLower) ||
                    (staff.staff_role && staff.staff_role.toLowerCase().includes(searchLower))
                );
            },

            // Lọc danh sách sản phẩm theo search
            filteredProductList(search) {
                if (!search) return this.productList;
                const searchLower = search.toLowerCase();
                return this.productList.filter(product =>
                    product.name.toLowerCase().includes(searchLower) ||
                    (product.sku && product.sku.toLowerCase().includes(searchLower))
                );
            },

            // Lọc danh sách khách hàng theo search
            filteredCustomerList(search) {
                if (!search) return this.customerList;
                const searchLower = search.toLowerCase();
                return this.customerList.filter(customer =>
                    customer.full_name.toLowerCase().includes(searchLower) ||
                    (customer.email && customer.email.toLowerCase().includes(searchLower))
                );
            },

            // Lọc danh sách nhà cung cấp theo search
            filteredSupplierList(search) {
                if (!search) return this.supplierList;
                const searchLower = search.toLowerCase();
                return this.supplierList.filter(supplier =>
                    supplier.supplier_name.toLowerCase().includes(searchLower)
                );
            },

            // Lấy tên nhân viên đã chọn
            getSelectedStaffName() {
                if (!this.filters.staffId) return 'Tất cả nhân viên';
                const staff = this.staffList.find(s => s.staff_id == this.filters.staffId);
                return staff ? staff.full_name : 'Tất cả nhân viên';
            },

            // Lấy tên sản phẩm đã chọn
            getSelectedProductName() {
                if (!this.filters.productId) return 'Tất cả sản phẩm';
                const product = this.productList.find(p => p.product_id == this.filters.productId);
                return product ? product.name : 'Tất cả sản phẩm';
            },

            // Lấy tên khách hàng đã chọn
            getSelectedCustomerName() {
                if (!this.filters.customerId) return 'Tất cả khách hàng';
                const customer = this.customerList.find(c => c.customer_id == this.filters.customerId);
                return customer ? customer.full_name : 'Tất cả khách hàng';
            },

            // Lấy tên nhà cung cấp đã chọn
            getSelectedSupplierName() {
                if (!this.filters.supplierId) return 'Tất cả nhà cung cấp';
                const supplier = this.supplierList.find(s => s.supplier_id == this.filters.supplierId);
                return supplier ? supplier.supplier_name : 'Tất cả nhà cung cấp';
            },

            async applyFilters() {
                this.isSearched = true;

                const params = new URLSearchParams();
                params.append('report_type', this.filters.reportType);
                params.append('criteria', this.filters.criteria);
                params.append('from_date', this.filters.fromDate);
                params.append('to_date', this.filters.toDate);
                params.append('sort_order', this.filters.sortOrder);

                if (this.filters.staffId) params.append('staff_id', this.filters.staffId);
                if (this.filters.productId) params.append('product_id', this.filters.productId);
                if (this.filters.customerId) params.append('customer_id', this.filters.customerId);
                if (this.filters.supplierId) params.append('supplier_id', this.filters.supplierId);
                if (this.filters.valueFrom) params.append('value_from', this.filters.valueFrom);
                if (this.filters.valueTo) params.append('value_to', this.filters.valueTo);

                try {
                    const response = await fetch(`/admin/api/reports/filter?${params}`);
                    const result = await response.json();

                    if (result.success && result.data && result.data.length > 0) {
                        this.hasData = true;
                        this.tableData = result.data;
                        this.totalResults = result.data.length;
                        this.resultTitle = this.getResultTitle();
                        this.setupTableColumns();

                        this.$nextTick(() => {
                            this.renderCharts();
                        });
                    } else {
                        this.hasData = false;
                        this.tableData = [];
                        this.totalResults = 0;
                    }
                } catch (err) {
                    console.error('Error fetching data:', err);
                    this.showToast('Không thể tải dữ liệu thống kê!', 'error');
                    this.hasData = false;
                }
            },

            getResultTitle() {
                const typeLabels = {
                    staff: 'Nhân Viên',
                    products: 'Sản Phẩm',
                    customers: 'Khách Hàng',
                    suppliers: 'Nhà Cung Cấp',
                    orders: 'Đơn Hàng',
                    inventory: 'Tồn Kho'
                };

                const criteriaLabel = this.criteriaOptions.find(opt => opt.value === this.filters.criteria)?.label || '';
                return `Thống Kê ${typeLabels[this.filters.reportType]} - ${criteriaLabel}`;
            },

            setupTableColumns() {
                const columnConfigs = {
                    staff: [
                        { key: 'full_name', label: 'Tên nhân viên', type: 'text' },
                        { key: 'staff_role', label: 'Chức vụ', type: 'text' },
                        { key: 'total_revenue', label: 'Doanh thu', type: 'money' },
                        { key: 'total_orders', label: 'Số đơn', type: 'number' },
                        { key: 'avg_order_value', label: 'Giá trị TB', type: 'money' }
                    ],
                    products: [
                        { key: 'name', label: 'Tên sản phẩm', type: 'text' },
                        { key: 'sku', label: 'SKU', type: 'text' },
                        { key: 'total_revenue', label: 'Doanh thu', type: 'money' },
                        { key: 'total_quantity', label: 'Số lượng', type: 'number' },
                        { key: 'unit_name', label: 'Đơn vị', type: 'text' }
                    ],
                    customers: [
                        { key: 'full_name', label: 'Tên khách hàng', type: 'text' },
                        { key: 'email', label: 'Email', type: 'text' },
                        { key: 'total_spent', label: 'Tổng chi tiêu', type: 'money' },
                        { key: 'total_orders', label: 'Số đơn', type: 'number' },
                        { key: 'avg_order_value', label: 'Giá trị TB', type: 'money' }
                    ],
                    suppliers: [
                        { key: 'supplier_name', label: 'Tên nhà cung cấp', type: 'text' },
                        { key: 'total_sales_value', label: 'Doanh thu bán', type: 'money' },
                        { key: 'total_purchase_value', label: 'Giá trị nhập', type: 'money' },
                        { key: 'total_purchases', label: 'Số lần nhập', type: 'number' }
                    ],
                    orders: [
                        { key: 'order_id', label: 'Mã đơn', type: 'text' },
                        { key: 'customer_name', label: 'Khách hàng', type: 'text' },
                        { key: 'total_amount', label: 'Tổng tiền', type: 'money' },
                        { key: 'status', label: 'Trạng thái', type: 'text' },
                        { key: 'created_at', label: 'Ngày tạo', type: 'date' }
                    ],
                    inventory: [
                        { key: 'name', label: 'Tên sản phẩm', type: 'text' },
                        { key: 'sku', label: 'SKU', type: 'text' },
                        { key: 'current_stock', label: 'Tồn kho', type: 'number' },
                        { key: 'unit_name', label: 'Đơn vị', type: 'text' }
                    ]
                };

                this.tableColumns = columnConfigs[this.filters.reportType] || [];
            },

            formatCell(row, col) {
                const value = row[col.key];

                if (!value && value !== 0) return '-';

                switch (col.type) {
                    case 'money':
                        return this.formatMoney(value);
                    case 'number':
                        return new Intl.NumberFormat('vi-VN').format(value);
                    case 'image':
                        return `<img src="${value}" class="w-12 h-12 object-cover rounded" alt="Product">`;
                    case 'date':
                        return new Date(value).toLocaleDateString('vi-VN');
                    default:
                        return value;
                }
            },

            // Table filter functions
            toggleTableFilter(columnIndex) {
                const key = 'column' + columnIndex;
                Object.keys(this.openTableFilter).forEach(k => {
                    if (k !== key) this.openTableFilter[k] = false;
                });
                this.openTableFilter[key] = !this.openTableFilter[key];
            },

            applyTableFilter(columnIndex) {
                this.openTableFilter['column' + columnIndex] = false;
            },

            resetTableFilter(columnIndex) {
                this.tableFilters['column' + columnIndex] = '';
                this.openTableFilter['column' + columnIndex] = false;
            },

            getFilteredTableData() {
                if (!this.tableData || this.tableData.length === 0) return [];

                let filtered = [...this.tableData];

                // Apply filters for each column
                this.tableColumns.forEach((col, index) => {
                    const filterValue = this.tableFilters['column' + (index + 1)];
                    if (filterValue && filterValue.trim() !== '') {
                        const searchLower = filterValue.toLowerCase();
                        filtered = filtered.filter(row => {
                            const cellValue = row[col.key];
                            if (cellValue == null) return false;

                            // Convert to string for comparison
                            const cellStr = String(cellValue).toLowerCase();
                            return cellStr.includes(searchLower);
                        });
                    }
                });

                return filtered;
            },

            renderCharts() {
                this.destroyCharts();

                if (!this.tableData.length) return;

                const data = this.tableData.slice(0, 10); // Top 10
                const labels = this.getChartLabels(data);
                const values = this.getChartValues(data);
                const colors = this.generateColors(values.length);

                this.renderBarChart(labels, values, colors);
                this.renderPieChart(labels, values, colors);
            },

            getChartLabels(data) {
                const labelKeys = {
                    staff: 'full_name',
                    products: 'name',
                    customers: 'full_name',
                    suppliers: 'supplier_name',
                    orders: 'order_id',
                    inventory: 'name'
                };
                const key = labelKeys[this.filters.reportType];
                return data.map(item => item[key] || 'N/A');
            },

            getChartValues(data) {
                const valueKeys = {
                    revenue: 'total_revenue',
                    orders: 'total_orders',
                    quantity: 'total_quantity',
                    total_spent: 'total_spent',
                    sales_value: 'total_sales_value',
                    purchase_value: 'total_purchase_value',
                    purchases: 'total_purchases',
                    avg_order_value: 'avg_order_value',
                    total: 'total_amount',
                    count: 'order_count',
                    low_stock: 'current_stock',
                    high_stock: 'current_stock'
                };
                const key = valueKeys[this.filters.criteria];
                return data.map(item => parseFloat(item[key]) || 0);
            },

            generateColors(count) {
                const baseColors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];
                return baseColors.slice(0, count);
            },

            renderBarChart(labels, values, colors) {
                const canvas = this.$refs.barChartCanvas;
                if (!canvas) return;

                const ctx = canvas.getContext('2d');
                this.barChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: this.valueRangeLabel,
                            data: values,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const isMoney = ['revenue', 'total_spent', 'sales_value', 'purchase_value', 'avg_order_value', 'total'].includes(this.filters.criteria);
                                        return isMoney ? this.formatMoney(context.raw) : new Intl.NumberFormat('vi-VN').format(context.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => {
                                        const isMoney = ['revenue', 'total_spent', 'sales_value', 'purchase_value', 'avg_order_value', 'total'].includes(this.filters.criteria);
                                        return isMoney ? this.formatMoney(value) : new Intl.NumberFormat('vi-VN').format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            },

            renderPieChart(labels, values, colors) {
                const canvas = this.$refs.pieChartCanvas;
                if (!canvas) return;

                const ctx = canvas.getContext('2d');
                this.pieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 10,
                                    font: { size: 11 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const isMoney = ['revenue', 'total_spent', 'sales_value', 'purchase_value', 'avg_order_value', 'total'].includes(this.filters.criteria);
                                        const value = isMoney ? this.formatMoney(context.raw) : new Intl.NumberFormat('vi-VN').format(context.raw);
                                        return `${context.label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            destroyCharts() {
                if (this.barChart) {
                    this.barChart.destroy();
                    this.barChart = null;
                }
                if (this.pieChart) {
                    this.pieChart.destroy();
                    this.pieChart = null;
                }
            },

            async exportExcel() {
                if (!this.hasData) {
                    this.showToast('Không có dữ liệu để xuất!', 'error');
                    return;
                }

                const params = new URLSearchParams();
                params.append('report_type', this.filters.reportType);
                params.append('criteria', this.filters.criteria);
                params.append('from_date', this.filters.fromDate);
                params.append('to_date', this.filters.toDate);
                params.append('sort_order', this.filters.sortOrder);

                if (this.filters.staffId) params.append('staff_id', this.filters.staffId);
                if (this.filters.productId) params.append('product_id', this.filters.productId);
                if (this.filters.customerId) params.append('customer_id', this.filters.customerId);
                if (this.filters.supplierId) params.append('supplier_id', this.filters.supplierId);
                if (this.filters.valueFrom) params.append('value_from', this.filters.valueFrom);
                if (this.filters.valueTo) params.append('value_to', this.filters.valueTo);

                try {
                    const response = await fetch(`/admin/api/reports/export?${params}`);

                    if (!response.ok) throw new Error('Export failed');

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `thong-ke-${this.filters.reportType}-${Date.now()}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();

                    this.showToast('Xuất Excel thành công!', 'success');
                } catch (err) {
                    console.error('Export error:', err);
                    this.showToast('Không thể xuất Excel!', 'error');
                }
            },

            resetFilters() {
                this.filters.reportType = 'staff';
                this.filters.criteria = 'revenue';
                this.filters.staffId = '';
                this.filters.productId = '';
                this.filters.customerId = '';
                this.filters.supplierId = '';
                this.filters.valueFrom = '';
                this.filters.valueTo = '';
                this.filters.sortOrder = 'desc';

                // Reset table filters
                this.tableFilters = {
                    column0: '',
                    column1: '',
                    column2: '',
                    column3: '',
                    column4: '',
                    column5: ''
                };
                this.openTableFilter = {
                    column1: false,
                    column2: false,
                    column3: false,
                    column4: false,
                    column5: false
                };

                const today = new Date();
                const lastMonth = new Date();
                lastMonth.setMonth(today.getMonth() - 1);

                // Cập nhật filter
                this.filters.fromDate = this.formatDate(lastMonth);
                this.filters.toDate = this.formatDate(today);

                // Đặt lại Flatpickr
                if (this.$refs.fromDate._flatpickr) {
                    this.$refs.fromDate._flatpickr.clear();
                    this.$refs.fromDate._flatpickr.set('maxDate', today); // reset giới hạn
                    this.$refs.fromDate._flatpickr.setDate(lastMonth, true);
                }
                if (this.$refs.toDate._flatpickr) {
                    this.$refs.toDate._flatpickr.clear();
                    this.$refs.toDate._flatpickr.set('minDate', lastMonth); // reset giới hạn
                    this.$refs.toDate._flatpickr.set('maxDate', today);
                    this.$refs.toDate._flatpickr.setDate(today, true);
                }

                // Reset hiển thị
                this.hasData = false;
                this.isSearched = false;
                this.tableData = [];
                this.destroyCharts();

                // Bắt buộc hiển thị lại giá trị input để không bị trống
                this.$refs.fromDate.value = this.formatDate(lastMonth);
                this.$refs.toDate.value = this.formatDate(today);
            },

            formatMoney(amount) {
                if (!amount && amount !== 0) return '0 ₫';
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount);
            },

            showToast(msg, type = 'error') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
                    ${type === 'success' ? 'text-green-700 border-green-400' : 'text-red-700 border-red-400'}
                    bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                    <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'} mr-3" 
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${type === 'success'
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
                    </svg>
                    <div class="flex-1">${msg}</div>
                `;

                                box.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>