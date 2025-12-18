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
                    <input
                        :value="filters.valueFrom !== undefined && filters.valueFrom !== null && filters.valueFrom !== '' ? Number(filters.valueFrom).toLocaleString('en-US') : ''"
                        @input="
        let val = $event.target.value.replace(/[^\d]/g, '');
        filters.valueFrom = val ? Number(val) : '';
        $event.target.value = filters.valueFrom !== '' ? Number(filters.valueFrom).toLocaleString('en-US') : '';
    " @blur="
        $event.target.value = filters.valueFrom !== '' ? Number(filters.valueFrom).toLocaleString('en-US') : '';
    " @focus="$event.target.select()" inputmode="numeric" placeholder="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]" />
                </div>

                <!-- Giá trị đến -->
                <div x-show="showValueRange">
                    <label class="block text-sm font-medium text-gray-700 mb-2"
                        x-text="valueRangeLabel + ' đến:'"></label>
                    <input
                        :value="filters.valueTo !== undefined && filters.valueTo !== null && filters.valueTo !== '' ? Number(filters.valueTo).toLocaleString('en-US') : ''"
                        @input="
        let val = $event.target.value.replace(/[^\d]/g, '');
        filters.valueTo = val ? Number(val) : '';
        $event.target.value = filters.valueTo !== '' ? Number(filters.valueTo).toLocaleString('en-US') : '';
    " @blur="
        $event.target.value = filters.valueTo !== '' ? Number(filters.valueTo).toLocaleString('en-US') : '';
    " @focus="$event.target.select()" inputmode="numeric" placeholder="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#002975]" />
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
        <div class="mb-6">
            <!-- Biểu đồ cột - Luôn hiển thị -->
            <!-- <div class="mb-6">
                <h4 class="font-bold text-base mb-3 text-gray-700" x-text="barChartTitle"></h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <canvas x-ref="barChartCanvas" style="max-height: 350px;"></canvas>
                </div>
            </div> -->

            <!-- Biểu đồ miền (Stacked Area by entity) - hidden when viewing any orders report -->
            <div class="mb-6" x-show="filters.reportType!=='orders'">
                <h4 class="font-bold text-base mb-3 text-gray-700" x-text="getAreaChartTitle()"></h4>
                <div class="bg-gray-50 rounded-lg p-4" style="height: 360px;">
                    <canvas x-ref="areaChartCanvas" style="width:100%; height:100%; display:block;"></canvas>
                </div>
            </div>

            <!-- Stacked bar for Orders by day x status (shown for any orders report) -->
            <div class="mb-6" x-show="filters.reportType==='orders'">
                <h4 class="font-bold text-base mb-3 text-gray-700">Biểu Đồ Cột Xếp Chồng - Đơn Hàng theo Ngày</h4>
                <div class="bg-gray-50 rounded-lg p-4" style="height: 360px;">
                    <canvas x-ref="ordersStackedBarCanvas" style="width:100%; height:100%; display:block;"></canvas>
                </div>
            </div>

            <!-- Grid các biểu đồ tròn - Hiển thị tất cả các filter "Tất cả" -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="(pieChart, index) in pieCharts" :key="index">
                    <div>
                        <h4 class="font-bold text-base mb-3 text-gray-700" x-text="pieChart.title"></h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <!-- Container cho biểu đồ với chiều cao cố định và căn giữa -->
                            <div
                                style="height: 280px; position: relative; display:flex; align-items:center; justify-content:center;">
                                <div
                                    style="width:100%; max-width:420px; height:100%; display:flex; align-items:center; justify-content:center;">
                                    <canvas :id="`pieChartCanvas${index}`"
                                        style="max-width:100%; max-height:100%; width:100%; height:100%; display:block;"></canvas>
                                </div>
                            </div>
                            <!-- Legend tùy chỉnh bên dưới biểu đồ -->
                            <div :id="`legend-${index}`" class="mt-4 max-h-48 overflow-y-auto"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tổng quan -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <template x-for="summary in summaryCards" :key="summary.label">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                    <div class="text-sm font-medium text-blue-600 mb-1" x-text="summary.label"></div>
                    <div class="text-2xl font-bold text-blue-900" x-text="summary.value"></div>
                </div>
            </template>
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
                                    <input x-model.trim="tableFilters['column' + (index + 1)]" @input="$nextTick(() => { 
                                            clearTimeout(chartRenderTimeout);
                                            chartRenderTimeout = setTimeout(() => { calculateSummary(); renderCharts(); }, 300);
                                        })" class="w-full border rounded px-3 py-2"
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
                    <template x-for="(row, index) in paginated()" :key="index">
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                            <td class="px-4 py-3 text-sm text-gray-600 break-words whitespace-pre-line"
                                x-text="(currentPage - 1) * perPage + index + 1"></td>
                            <template x-for="col in tableColumns" :key="col.key">
                                <td class="px-4 py-3 text-sm text-gray-800" x-html="formatCell(row, col)"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="filtered().length === 0">
                        <td colspan="12" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Trống</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
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

        <!-- Pagination -->
        <div class="flex items-center justify-center mt-4 px-4 gap-6">
            <div class="text-sm text-slate-600">
                Tổng cộng <span x-text="filtered().length"></span> bản ghi
            </div>
            <div class="flex items-center gap-2">
                <button @click="goToPage(currentPage-1)" :disabled="currentPage===1"
                    class="px-2 py-1 border rounded disabled:opacity-50">&lt;</button>
                <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages()"></span></span>
                <button @click="goToPage(currentPage+1)" :disabled="currentPage===totalPages()"
                    class="px-2 py-1 border rounded disabled:opacity-50">&gt;</button>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open" class="border rounded px-2 py-1 w-28 flex justify-between items-center">
                        <span x-text="perPage + ' / trang'"></span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open=false"
                        class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
                        <template x-for="opt in perPageOptions" :key="opt">
                            <div @click="perPage=opt;open=false"
                                class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
                                x-text="opt + ' / trang'">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function reportsPage() {
        return {
            currentPage: 1,
            perPage: 20,
            perPageOptions: [10, 20, 50, 100],

            // Bộ lọc
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
            // --- Hàm tiện ích ---
            formatInputNumber(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
                }
            },

            paginated() {
                const arr = this.filtered();
                const start = (this.currentPage - 1) * this.perPage;
                return arr.slice(start, start + this.perPage);
            },
            totalPages() {
                return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
            },
            goToPage(p) {
                if (p < 1) p = 1;
                if (p > this.totalPages()) p = this.totalPages();
                this.currentPage = p;
            },

            // Compatibility alias: some templates use `filtered()`; implement it
            // as a thin wrapper around `getFilteredTableData()` so code doesn't
            // break after adding pagination.
            filtered() {
                try {
                    return this.getFilteredTableData ? this.getFilteredTableData() : (this.tableData || []);
                } catch (e) {
                    console.warn('filtered() helper failed, falling back to tableData', e);
                    return this.tableData || [];
                }
            },

            // Dữ liệu cho dropdown
            staffList: [],
            productList: [],
            customerList: [],
            supplierList: [],

            // Trạng thái giao diện (UI)
            hasData: false,
            isSearched: false,
            resultTitle: '',
            totalResults: 0,

            // Biểu đồ
            barChart: null,
            areaChart: null,
            ordersStackedBar: null,
            pieCharts: [], // Mảng đối tượng {title, chart, data}
            pieChartInstances: [], // Mảng các instance của Chart.js

            // Dữ liệu bảng
            tableColumns: [],
            tableData: [],
            tableDataFiltered: [], // Dữ liệu sau khi filter

            // Thẻ tổng quan
            summaryCards: [],

            // Tiêu đề biểu đồ
            barChartTitle: 'Biểu Đồ Cột',
            pieChartTitle: 'Biểu Đồ Tròn',

            // Bộ lọc trên bảng
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

            // Bộ đếm debounce để trì hoãn cập nhật biểu đồ
            chartRenderTimeout: null,

            get criteriaOptions() {
                const options = {
                    staff: [{
                            value: 'revenue',
                            label: 'Doanh thu'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        },
                        {
                            value: 'avg_order_value',
                            label: 'Giá trị đơn TB'
                        }
                    ],
                    products: [{
                            value: 'revenue',
                            label: 'Doanh thu'
                        },
                        {
                            value: 'quantity',
                            label: 'Số lượng bán'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        }
                    ],
                    customers: [{
                            value: 'total_spent',
                            label: 'Tổng chi tiêu'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        },
                        {
                            value: 'avg_order_value',
                            label: 'Giá trị đơn TB'
                        }
                    ],
                    suppliers: [{
                            value: 'sales_value',
                            label: 'Doanh thu bán'
                        },
                        {
                            value: 'purchase_value',
                            label: 'Giá trị nhập'
                        },
                        {
                            value: 'purchases',
                            label: 'Số lần nhập'
                        }
                    ],
                    orders: [{
                            value: 'total',
                            label: 'Tổng giá trị'
                        },
                        {
                            value: 'count',
                            label: 'Số lượng đơn'
                        },
                        {
                            value: 'status',
                            label: 'Theo trạng thái'
                        }
                    ],
                    inventory: [{
                            value: 'low_stock',
                            label: 'Sắp hết hàng'
                        },
                        {
                            value: 'high_stock',
                            label: 'Tồn kho cao'
                        },
                        {
                            value: 'out_of_stock',
                            label: 'Hết hàng'
                        }
                    ]
                };
                return options[this.filters.reportType] || [];
            },

            // Return criteria options for an arbitrary report type (used when fetching
            // aggregated data for a different entity than the current main reportType).
            getCriteriaOptionsForType(reportType) {
                const options = {
                    staff: [{
                            value: 'revenue',
                            label: 'Doanh thu'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        },
                        {
                            value: 'avg_order_value',
                            label: 'Giá trị đơn TB'
                        }
                    ],
                    products: [{
                            value: 'revenue',
                            label: 'Doanh thu'
                        },
                        {
                            value: 'quantity',
                            label: 'Số lượng bán'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        }
                    ],
                    customers: [{
                            value: 'total_spent',
                            label: 'Tổng chi tiêu'
                        },
                        {
                            value: 'orders',
                            label: 'Số đơn hàng'
                        },
                        {
                            value: 'avg_order_value',
                            label: 'Giá trị đơn TB'
                        }
                    ],
                    suppliers: [{
                            value: 'sales_value',
                            label: 'Doanh thu bán'
                        },
                        {
                            value: 'purchase_value',
                            label: 'Giá trị nhập'
                        },
                        {
                            value: 'purchases',
                            label: 'Số lần nhập'
                        }
                    ],
                    orders: [{
                            value: 'total',
                            label: 'Tổng giá trị'
                        },
                        {
                            value: 'count',
                            label: 'Số lượng đơn'
                        },
                        {
                            value: 'status',
                            label: 'Theo trạng thái'
                        }
                    ],
                    inventory: [{
                            value: 'low_stock',
                            label: 'Sắp hết hàng'
                        },
                        {
                            value: 'high_stock',
                            label: 'Tồn kho cao'
                        },
                        {
                            value: 'out_of_stock',
                            label: 'Hết hàng'
                        }
                    ]
                };
                return options[reportType] || [];
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
                // - Nhà cung cấp: xem nhà cung cấp nào cung cấp sản phẩm X
                // - Tồn kho: lọc theo sản phẩm cụ thể
                return ['staff', 'customers', 'orders', 'suppliers', 'inventory'].includes(this.filters.reportType);
            },

            get showCustomerFilter() {
                // Hiển thị dropdown khách hàng khi muốn lọc theo khách hàng
                return ['staff', 'products', 'orders'].includes(this.filters.reportType);
            },

            get showSupplierFilter() {
                // Hiển thị dropdown nhà cung cấp khi muốn lọc theo nhà cung cấp
                return ['products'].includes(this.filters.reportType);
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
                // Set global defaults for bar hover styling so hovered bars get a
                // black border for better contrast/visibility.
                try {
                    if (typeof Chart !== 'undefined' && Chart.defaults && Chart.defaults.elements) {
                        // v4 uses 'rectangle' element for bars; set both for compatibility
                        if (!Chart.defaults.elements.rectangle) Chart.defaults.elements.rectangle = {};
                        Chart.defaults.elements.rectangle.hoverBorderColor = '#000';
                        Chart.defaults.elements.rectangle.hoverBorderWidth = 3;
                        Chart.defaults.elements.rectangle.borderSkipped = false;

                        // Backwards compat: some older code references 'bar'
                        if (!Chart.defaults.elements.bar) Chart.defaults.elements.bar = {};
                        Chart.defaults.elements.bar.hoverBorderColor = '#000';
                        Chart.defaults.elements.bar.hoverBorderWidth = 2;
                    }
                } catch (e) {
                    /* ignore */
                }

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
                    onChange: function(selectedDates, dateStr) {
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
                    onChange: function(selectedDates, dateStr) {
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
                if (this.filters.valueFrom !== '' && this.filters.valueFrom !== undefined && this.filters.valueFrom !== null)
                    params.append('value_from', this.filters.valueFrom);
                if (this.filters.valueTo !== '' && this.filters.valueTo !== undefined && this.filters.valueTo !== null)
                    params.append('value_to', this.filters.valueTo);

                const url = `/admin/api/reports/filter?${params}`;
                console.log('=== FETCHING REPORT DATA ===');
                console.log('URL:', url);
                console.log('Filters:', this.filters);

                try {
                    const response = await fetch(url);
                    console.log('Response status:', response.status, response.statusText);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('API Response:', result);

                    // Hỗ trợ fallback: API có thể trả dữ liệu dưới các khóa khác (data | rows | items)
                    // Chú ý: nếu `data` tồn tại nhưng rỗng, có thể API đặt payload trong `rows`.
                    // Vì vậy chọn mảng đầu tiên không rỗng trong `data`, `rows`, `items`.
                    let possibleData = [];
                    try {
                        if (result && Array.isArray(result.data) && result.data.length > 0) {
                            possibleData = result.data;
                        } else if (result && Array.isArray(result.rows) && result.rows.length > 0) {
                            possibleData = result.rows;
                        } else if (result && Array.isArray(result.items) && result.items.length > 0) {
                            possibleData = result.items;
                        } else if (result && Array.isArray(result.data) && result.data.length === 0) {
                            // nếu tất cả đều rỗng nhưng `data` là mảng rỗng, sử dụng `data` làm mảng mặc định
                            possibleData = result.data;
                        }
                    } catch (e) {
                        possibleData = [];
                    }

                    console.log('Processed data length:', possibleData.length);
                    if (possibleData.length > 0) {
                        console.log('Sample row:', possibleData[0]);
                    }

                    if (possibleData && possibleData.length > 0) {
                        this.tableData = possibleData;
                        // Reset pagination to first page when new dataset is loaded
                        this.currentPage = 1;
                        this.totalResults = possibleData.length;
                        this.hasData = true;
                        this.resultTitle = this.getResultTitle();

                        console.log('Setting up table and charts...');
                        this.setupTableColumns();
                        this.calculateSummary();

                        this.$nextTick(() => {
                            console.log('Rendering charts...');
                            this.renderCharts();
                        });
                    } else {
                        console.warn('No data returned from API');

                        // Vẫn hiển thị khu vực kết quả nhưng với dữ liệu trống
                        this.tableData = [];
                        this.currentPage = 1;
                        this.totalResults = 0;
                        this.hasData = true; // Vẫn set true để hiển thị UI
                        this.resultTitle = this.getResultTitle();

                        // Setup columns nhưng không có data
                        this.setupTableColumns();

                        // Clear summary cards
                        this.summaryCards = [];

                        // Destroy any existing charts
                        this.destroyCharts();

                        // Show user-friendly message
                        const typeLabels = {
                            staff: 'nhân viên',
                            products: 'sản phẩm',
                            customers: 'khách hàng',
                            suppliers: 'nhà cung cấp',
                            orders: 'đơn hàng',
                            inventory: 'tồn kho'
                        };
                        const entityName = typeLabels[this.filters.reportType] || 'dữ liệu';
                        this.showToast(`Không có dữ liệu ${entityName} trong khoảng thời gian từ ${this.filters.fromDate} đến ${this.filters.toDate}`, 'error');
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
                    staff: [{
                            key: 'full_name',
                            label: 'Tên nhân viên',
                            type: 'text'
                        },
                        {
                            key: 'staff_role',
                            label: 'Chức vụ',
                            type: 'text'
                        },
                        {
                            key: 'total_revenue',
                            label: 'Doanh thu',
                            type: 'money'
                        },
                        {
                            key: 'total_orders',
                            label: 'Số đơn',
                            type: 'number'
                        },
                        {
                            key: 'avg_order_value',
                            label: 'Giá trị TB',
                            type: 'money'
                        }
                    ],
                    products: [{
                            key: 'name',
                            label: 'Tên sản phẩm',
                            type: 'text'
                        },
                        {
                            key: 'sku',
                            label: 'SKU',
                            type: 'text'
                        },
                        {
                            key: 'total_revenue',
                            label: 'Doanh thu',
                            type: 'money'
                        },
                        {
                            key: 'total_quantity',
                            label: 'Số lượng',
                            type: 'number'
                        },
                        {
                            key: 'unit_name',
                            label: 'Đơn vị',
                            type: 'text'
                        }
                    ],
                    customers: [{
                            key: 'full_name',
                            label: 'Tên khách hàng',
                            type: 'text'
                        },
                        {
                            key: 'email',
                            label: 'Email',
                            type: 'text'
                        },
                        {
                            key: 'total_spent',
                            label: 'Tổng chi tiêu',
                            type: 'money'
                        },
                        {
                            key: 'total_orders',
                            label: 'Số đơn',
                            type: 'number'
                        },
                        {
                            key: 'avg_order_value',
                            label: 'Giá trị TB',
                            type: 'money'
                        }
                    ],
                    suppliers: [{
                            key: 'supplier_name',
                            label: 'Tên nhà cung cấp',
                            type: 'text'
                        },
                        {
                            key: 'total_sales_value',
                            label: 'Doanh thu bán',
                            type: 'money'
                        },
                        {
                            key: 'total_purchase_value',
                            label: 'Giá trị nhập',
                            type: 'money'
                        },
                        {
                            key: 'total_purchases',
                            label: 'Số lần nhập',
                            type: 'number'
                        }
                    ],
                    orders: [{
                            key: 'order_id',
                            label: 'Mã đơn',
                            type: 'text'
                        },
                        {
                            key: 'customer_name',
                            label: 'Khách hàng',
                            type: 'text'
                        },
                        {
                            key: 'total_amount',
                            label: 'Tổng tiền',
                            type: 'money'
                        },
                        {
                            key: 'status',
                            label: 'Trạng thái',
                            type: 'text'
                        },
                        {
                            key: 'created_at',
                            label: 'Ngày tạo',
                            type: 'date'
                        }
                    ],
                    inventory: [{
                            key: 'name',
                            label: 'Tên sản phẩm',
                            type: 'text'
                        },
                        {
                            key: 'sku',
                            label: 'SKU',
                            type: 'text'
                        },
                        {
                            key: 'current_stock',
                            label: 'Tồn kho',
                            type: 'number'
                        },
                        {
                            key: 'unit_name',
                            label: 'Đơn vị',
                            type: 'text'
                        }
                    ]
                };

                let columns = [...(columnConfigs[this.filters.reportType] || [])];

                // Thêm cột sản phẩm nếu có lọc theo sản phẩm cụ thể
                if (this.filters.productId && this.filters.reportType !== 'products') {
                    const productCol = {
                        key: 'product_name',
                        label: 'Sản phẩm',
                        type: 'text'
                    };
                    // Chèn sau cột đầu tiên (tên đối tượng)
                    columns.splice(1, 0, productCol);
                }

                // Thêm cột khách hàng nếu có lọc theo khách hàng cụ thể
                if (this.filters.customerId && this.filters.reportType !== 'customers') {
                    const customerCol = {
                        key: 'customer_name',
                        label: 'Khách hàng',
                        type: 'text'
                    };
                    // Chèn sau cột sản phẩm (nếu có) hoặc sau cột đầu tiên
                    const insertIndex = this.filters.productId ? 2 : 1;
                    columns.splice(insertIndex, 0, customerCol);
                }

                // Thêm cột nhân viên nếu có lọc theo nhân viên cụ thể
                if (this.filters.staffId && this.filters.reportType !== 'staff') {
                    const staffCol = {
                        key: 'staff_name',
                        label: 'Nhân viên',
                        type: 'text'
                    };
                    // Chèn sau các cột filter khác
                    let insertIndex = 1;
                    if (this.filters.productId) insertIndex++;
                    if (this.filters.customerId) insertIndex++;
                    columns.splice(insertIndex, 0, staffCol);
                }

                // Thêm cột nhà cung cấp nếu có lọc theo nhà cung cấp cụ thể
                if (this.filters.supplierId && this.filters.reportType !== 'suppliers') {
                    const supplierCol = {
                        key: 'supplier_name',
                        label: 'Nhà cung cấp',
                        type: 'text'
                    };
                    // Chèn sau các cột filter khác
                    let insertIndex = 1;
                    if (this.filters.productId) insertIndex++;
                    if (this.filters.customerId) insertIndex++;
                    if (this.filters.staffId) insertIndex++;
                    columns.splice(insertIndex, 0, supplierCol);
                }

                this.tableColumns = columns;
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
                // Recalculate summary and charts when filter is applied with debounce
                clearTimeout(this.chartRenderTimeout);
                this.chartRenderTimeout = setTimeout(() => {
                    // When applying table filters, return to first page
                    this.currentPage = 1;
                    // diagnostic log to help trace filter -> chart flow
                    try {
                        console.debug('applyTableFilter -> filtered length:', this.filtered().length, 'currentPage:', this.currentPage);
                    } catch (e) {}
                    this.calculateSummary();
                    this.renderCharts();
                }, 300);
            },

            resetTableFilter(columnIndex) {
                this.tableFilters['column' + columnIndex] = '';
                this.openTableFilter['column' + columnIndex] = false;
                // Recalculate summary and charts when filter is reset with debounce
                clearTimeout(this.chartRenderTimeout);
                this.chartRenderTimeout = setTimeout(() => {
                    // Reset to first page after clearing a table filter
                    this.currentPage = 1;
                    try {
                        console.debug('resetTableFilter -> filtered length:', this.filtered().length, 'currentPage:', this.currentPage);
                    } catch (e) {}
                    this.calculateSummary();
                    this.renderCharts();
                }, 300);
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

            calculateSummary() {
                this.summaryCards = [];

                // Sử dụng filtered data nếu có filter, nếu không thì dùng tableData
                const dataToUse = this.getFilteredTableData().length > 0 ? this.getFilteredTableData() : this.tableData;
                if (!dataToUse.length) return;

                const criteriaConfig = {
                    revenue: {
                        key: 'total_revenue',
                        label: 'Tổng Doanh Thu',
                        isMoney: true
                    },
                    orders: {
                        key: 'total_orders',
                        label: 'Tổng Số Đơn',
                        isMoney: false
                    },
                    quantity: {
                        key: 'total_quantity',
                        label: 'Tổng Số Lượng',
                        isMoney: false
                    },
                    total_spent: {
                        key: 'total_spent',
                        label: 'Tổng Chi Tiêu',
                        isMoney: true
                    },
                    sales_value: {
                        key: 'total_sales_value',
                        label: 'Tổng Doanh Thu Bán',
                        isMoney: true
                    },
                    purchase_value: {
                        key: 'total_purchase_value',
                        label: 'Tổng Giá Trị Nhập',
                        isMoney: true
                    },
                    purchases: {
                        key: 'total_purchases',
                        label: 'Tổng Số Lần Nhập',
                        isMoney: false
                    },
                    avg_order_value: {
                        key: 'avg_order_value',
                        label: 'Giá Trị TB',
                        isMoney: true
                    },
                    total: {
                        key: 'total_amount',
                        label: 'Tổng Giá Trị Đơn',
                        isMoney: true
                    },
                    count: {
                        key: 'order_count',
                        label: 'Tổng Số Đơn',
                        isMoney: false
                    },
                    low_stock: {
                        key: 'current_stock',
                        label: 'Tổng Tồn Kho',
                        isMoney: false
                    },
                    high_stock: {
                        key: 'current_stock',
                        label: 'Tổng Tồn Kho',
                        isMoney: false
                    },
                    out_of_stock: {
                        key: 'current_stock',
                        label: 'Tổng Tồn Kho',
                        isMoney: false
                    }
                };

                const config = criteriaConfig[this.filters.criteria];
                if (!config) return;

                // Tính tổng
                const total = dataToUse.reduce((sum, row) => sum + (parseFloat(row[config.key]) || 0), 0);
                const count = dataToUse.length;

                this.summaryCards.push({
                    label: config.label,
                    value: config.isMoney ? this.formatMoney(total) : new Intl.NumberFormat('vi-VN').format(total)
                });

                this.summaryCards.push({
                    label: 'Số Lượng',
                    value: new Intl.NumberFormat('vi-VN').format(count)
                });

                // Tính trung bình
                if (count > 0) {
                    const avg = total / count;
                    this.summaryCards.push({
                        label: 'Trung Bình',
                        value: config.isMoney ? this.formatMoney(avg) : new Intl.NumberFormat('vi-VN').format(avg)
                    });
                }

                // Tìm Max
                const maxItem = dataToUse.reduce((max, row) => {
                    const val = parseFloat(row[config.key]) || 0;
                    return val > (parseFloat(max[config.key]) || 0) ? row : max;
                }, dataToUse[0]);

                const maxValue = parseFloat(maxItem[config.key]) || 0;
                this.summaryCards.push({
                    label: 'Cao Nhất',
                    value: config.isMoney ? this.formatMoney(maxValue) : new Intl.NumberFormat('vi-VN').format(maxValue)
                });
            },

            renderCharts() {
                // Diagnostic: log invocation and sizes to help debugging filter issues
                try {
                    console.debug('renderCharts - reportType:', this.filters.reportType, 'filtered:', this.getFilteredTableData().length, 'tableData:', (this.tableData || []).length, 'currentPage:', this.currentPage);
                } catch (e) {}

                this.destroyCharts();

                const dataToUse = this.getFilteredTableData().length > 0 ? this.getFilteredTableData() : this.tableData;
                if (!dataToUse.length) {
                    try {
                        console.debug('renderCharts aborted: no dataToUse');
                    } catch (e) {}
                    return;
                }

                // Biểu đồ cột: Theo loại thống kê chính (top 10) - sử dụng filtered data
                const mainData = dataToUse.slice(0, 10);
                const mainLabels = this.getChartLabels(mainData);
                const mainValues = this.getChartValues(mainData);
                const mainColors = this.generateColors(mainValues.length);

                this.barChartTitle = this.getBarChartTitle();
                this.renderBarChart(mainLabels, mainValues, mainColors);

                // Vẽ biểu đồ miền xếp chồng theo từng đối tượng (mỗi đối tượng = 1 lớp màu)
                try {
                    if (this.filters.reportType === 'orders') {
                        this.renderOrdersStackedBar();
                    } else {
                        this.renderAreaChart();
                    }
                } catch (e) {
                    console.error('Chart render error', e);
                }

                // Biểu đồ tròn: Tạo tất cả các biểu đồ cho các filter "Tất cả" - sử dụng filtered data
                this.$nextTick(async () => {
                    await this.renderAllPieCharts();
                });
            },

            getBarChartTitle() {
                const typeLabels = {
                    staff: 'Nhân Viên',
                    products: 'Sản Phẩm',
                    customers: 'Khách Hàng',
                    suppliers: 'Nhà Cung Cấp',
                    orders: 'Đơn Hàng',
                    inventory: 'Tồn Kho'
                };
                return `${typeLabels[this.filters.reportType]} - ${this.valueRangeLabel}`;
            },

            getAreaChartTitle() {
                const typeLabels = {
                    staff: 'Nhân Viên',
                    products: 'Sản Phẩm',
                    customers: 'Khách Hàng',
                    suppliers: 'Nhà Cung Cấp',
                    orders: 'Đơn Hàng',
                    inventory: 'Tồn Kho'
                };
                const label = typeLabels[this.filters.reportType] || 'Đối Tượng';
                return `Biểu Đồ Miền - ${label}`;
            },

            getPieChartTitle() {
                // Ưu tiên: Sản phẩm > Khách hàng > Nhân viên > Nhà cung cấp
                if (!this.filters.productId && this.showProductFilter && this.filters.reportType !== 'products') {
                    return 'Phân Bổ Theo Sản Phẩm (%)';
                }
                if (!this.filters.customerId && this.showCustomerFilter && this.filters.reportType !== 'customers') {
                    return 'Phân Bổ Theo Khách Hàng (%)';
                }
                if (!this.filters.staffId && this.showStaffFilter && this.filters.reportType !== 'staff') {
                    return 'Phân Bổ Theo Nhân Viên (%)';
                }
                if (!this.filters.supplierId && this.showSupplierFilter && this.filters.reportType !== 'suppliers') {
                    return 'Phân Bổ Theo Nhà Cung Cấp (%)';
                }
                return 'Phân Bổ (%)';
            },

            async renderAllPieCharts() {
                this.pieCharts = [];
                this.pieChartInstances = [];

                const pieChartsToRender = [];
                const filteredData = this.getFilteredTableData();
                // Detect whether any table column filters are applied (regardless of match count)
                const hasTableFiltersApplied = Object.keys(this.tableFilters).some(k => {
                    const v = this.tableFilters[k];
                    return v !== undefined && v !== null && String(v).trim() !== '';
                });
                // legacy boolean for debug/log compatibility
                const hasTableFilters = hasTableFiltersApplied && filteredData.length > 0 && filteredData.length < this.tableData.length;
                // Các bộ lọc chính là những lựa chọn ở panel lọc (nhân viên/sản phẩm/khách hàng/nhà cung cấp/khoảng giá)
                const hasMainFilters = Boolean(this.filters.staffId || this.filters.productId || this.filters.customerId || this.filters.supplierId || this.filters.valueFrom !== '' || this.filters.valueTo !== '');

                console.debug('renderAllPieCharts called - hasTableFiltersApplied:', hasTableFiltersApplied, 'hasTableFilters:', hasTableFilters, 'hasMainFilters:', hasMainFilters, 'tableData length:', this.tableData.length, 'filteredData length:', filteredData.length);

                // If user filtered by product, log a concise summary: number of rows
                // and related suppliers, customers and staff for that product.
                if (this.filters.productId) {
                    const productName = this.getSelectedProductName();
                    const dataSource = filteredData.length ? filteredData : this.tableData;
                    const productRows = dataSource.filter(r => {
                        if (!r) return false;
                        if (r.product_id && String(r.product_id) === String(this.filters.productId)) return true;
                        const n = (r.product_name || r.name || '').toString().toLowerCase();
                        return n && n.includes(productName.toString().toLowerCase());
                    });

                    const suppliers = new Set();
                    const customers = new Set();
                    const staff = new Set();

                    productRows.forEach(r => {
                        if (r.supplier_name) suppliers.add(r.supplier_name);
                        if (r.supplier) suppliers.add(r.supplier);
                        if (r.customer_name) customers.add(r.customer_name);
                        if (r.full_name && r.customer_id) customers.add(r.full_name);
                        if (r.staff_name) staff.add(r.staff_name);
                        if (r.full_name && r.staff_id) staff.add(r.full_name);
                    });

                }

                // Kiểm tra từng loại filter - nếu chọn "Tất cả" thì thêm vào danh sách
                // KHÔNG hiển thị biểu đồ tròn cho inventory vì nó là snapshot hiện tại
                if (!this.filters.productId && this.showProductFilter && this.filters.reportType !== 'products' && this.filters.reportType !== 'inventory') {
                    pieChartsToRender.push({
                        type: 'product',
                        title: 'Phân Bổ Theo Sản Phẩm (%)',
                        key: 'product_name',
                        actualKey: 'name', // Tên trường thực tế trả về từ backend khi fetch báo cáo 'products'
                        idKey: 'product_id', // Tên trường id mong đợi cho hàng sản phẩm
                        reportTypeForAgg: 'products' // Loại báo cáo dùng để tổng hợp
                    });
                }

                if (!this.filters.customerId && this.showCustomerFilter && this.filters.reportType !== 'customers' && this.filters.reportType !== 'inventory') {
                    pieChartsToRender.push({
                        type: 'customer',
                        title: 'Phân Bổ Theo Khách Hàng (%)',
                        key: 'customer_name',
                        actualKey: 'customer_name', // Prefer explicit customer_name on order rows
                        idKey: 'customer_id', // Tên trường id mong đợi cho hàng khách hàng
                        reportTypeForAgg: 'customers' // Loại báo cáo dùng để tổng hợp
                    });
                }

                if (!this.filters.staffId && this.showStaffFilter && this.filters.reportType !== 'staff' && this.filters.reportType !== 'inventory') {
                    pieChartsToRender.push({
                        type: 'staff',
                        title: 'Phân Bổ Theo Nhân Viên (%)',
                        key: 'staff_name',
                        actualKey: 'full_name', // Staff data trả về field 'full_name', không phải 'staff_name'
                        idKey: 'staff_id', // Tên trường id mong đợi cho hàng nhân viên
                        reportTypeForAgg: 'staff' // Loại báo cáo dùng để tổng hợp
                    });
                }

                if (!this.filters.supplierId && this.showSupplierFilter && this.filters.reportType !== 'suppliers' && this.filters.reportType !== 'inventory') {
                    pieChartsToRender.push({
                        type: 'supplier',
                        title: 'Phán Bổ Theo Nhà Cung Cấp (%)',
                        key: 'supplier_name',
                        actualKey: 'supplier_name', // Tên trường thực tế trả về từ backend
                        idKey: 'supplier_id', // Tên trường id mong đợi cho hàng nhà cung cấp
                        reportTypeForAgg: 'suppliers' // Loại báo cáo dùng để tổng hợp
                    });
                }

                console.debug('pieChartsToRender configs:', pieChartsToRender);

                // Nếu không có filter nào "Tất cả" → Dùng data chính từ tableData hoặc filteredData
                if (pieChartsToRender.length === 0) {
                    // If table column filters are applied we must use the filteredData
                    // even when it is empty so pie charts reflect the filter (show placeholder).
                    const dataToUse = (hasTableFiltersApplied || hasMainFilters) ? filteredData : this.tableData;
                    const pieData = dataToUse.slice(0, 10).map(item => ({
                        label: this.getChartLabels([item])[0],
                        value: this.getChartValues([item])[0]
                    }));

                    this.pieCharts.push({
                        title: 'Phân Bổ (%)',
                        data: pieData
                    });
                } else {
                    // Nếu đã có các bộ lọc chính (nhân viên/sản phẩm/khách hàng/nhà cung cấp) hoặc bộ lọc cột bảng,
                    // ưu tiên tổng hợp (aggregate) cục bộ từ `tableData`/`filteredData` đã fetch để các biểu đồ tròn
                    // phản ánh chính xác các bộ lọc đang áp dụng (ví dụ: sản phẩm được nhân viên X bán).
                    if (hasTableFiltersApplied || hasMainFilters) {
                        // Use filteredData as the authoritative source when any table filters are applied.
                        const dataSource = hasTableFiltersApplied ? filteredData : (filteredData.length ? filteredData : this.tableData);
                        for (let config of pieChartsToRender) {
                            // Special case: when viewing Orders and the table has client-side
                            // filters applied, we must derive product breakdowns from the
                            // order items (order -> items -> product) and staff breakdowns
                            // from the order creator. This avoids mistakenly aggregating
                            // by customer_name or other ambiguous fields present on order rows.
                            try {
                                if (this.filters.reportType === 'orders' && hasTableFiltersApplied) {
                                    // PRODUCT pie should be computed from order items
                                    if (config.type === 'product') {
                                        const orderIds = dataSource.map(r => this.getOrderIdFromRow(r)).filter(Boolean);
                                        if (orderIds && orderIds.length) {
                                            const items = await this.fetchOrderItemsForOrderIds(orderIds);
                                            if (items && items.length) {
                                                const valueKey = this.getValueKeyForReportType('products') || this.getValueKeyForCriteria();
                                                // Aggregate items by product_id/product_name
                                                const aggMap = {};
                                                items.forEach(it => {
                                                    const pid = it.product_id || it.productId || it.pid || null;
                                                    const pname = it.product_name || it.name || it.title || (`#${pid || 'unknown'}`);
                                                    const val = parseFloat(it[valueKey]) || parseFloat(it.quantity) || parseFloat(it.total_price) || parseFloat(it.total_amount) || 0;
                                                    const lbl = pname || `#${pid}`;
                                                    aggMap[lbl] = (aggMap[lbl] || 0) + val;
                                                });
                                                const arr = Object.entries(aggMap).map(([label, value]) => ({
                                                    label,
                                                    value
                                                })).sort((a, b) => b.value - a.value).slice(0, 10);
                                                this.pieCharts.push({
                                                    title: config.title,
                                                    data: arr
                                                });
                                                continue; // next config
                                            } else {
                                                // No items found for the selected orders — show empty
                                                this.pieCharts.push({
                                                    title: config.title,
                                                    data: []
                                                });
                                                continue;
                                            }
                                        }
                                    }

                                    // STAFF pie should be computed from order creators (staff who created orders)
                                    if (config.type === 'staff') {
                                        const valueKey = this.getValueKeyForReportType('staff') || this.getValueKeyForCriteria();
                                        // Ensure staff lookup list is loaded so we can resolve ids -> names
                                        try {
                                            await this.loadStaffList();
                                        } catch (e) {
                                            /* ignore */
                                        }

                                        // Detect whether local rows contain staff identifiers/names
                                        const hasStaffIdOrName = dataSource.some(r => {
                                            if (!r) return false;
                                            if (r.staff_id || r.created_by) return true;
                                            if (r.staff_name || r.created_by_name) return true;
                                            return false;
                                        });

                                        if (!hasStaffIdOrName) {
                                            // Local order rows don't include staff info (common for filterOrders).
                                            // Fall back to fetching aggregated staff data from the server
                                            // to honor product/date filters and produce correct staff breakdown.
                                            try {
                                                console.debug('staff aggregation: local rows lack staff id/name — fetching aggregated staff data from API');
                                                const apiData = await this.fetchPieChartData('staff', config.actualKey);
                                                this.pieCharts.push({
                                                    title: config.title,
                                                    data: apiData
                                                });
                                                continue;
                                            } catch (e) {
                                                console.warn('staff aggregation fallback fetch failed', e);
                                                // fall through to local aggregation (which will produce 'Khác')
                                            }
                                        }

                                        const agg = {};
                                        dataSource.forEach(row => {
                                            // Prefer explicit staff identifiers (created_by / staff_id)
                                            const sid = this.getStaffIdFromRow(row) || row.staff_id || row.created_by || null;
                                            // Prefer explicit staff name fields; avoid using `full_name` when
                                            // the row represents a customer (i.e. has user_id/customer_id but no staff id).
                                            let labelFromRow = null;
                                            if (row.staff_name) labelFromRow = row.staff_name;
                                            else if (row.created_by_name) labelFromRow = row.created_by_name;
                                            else if (sid && row.full_name) labelFromRow = row.full_name; // only use full_name when we have a staff id

                                            let resolvedName = null;
                                            if (sid && this.staffList && this.staffList.length) {
                                                const s = this.staffList.find(x => String(x.staff_id) === String(sid));
                                                if (s) resolvedName = s.full_name || s.staff_name || null;
                                            }

                                            const name = resolvedName || labelFromRow || (sid ? `#${sid}` : 'Khác');
                                            const val = parseFloat(row[valueKey]) || parseFloat(row.total_amount) || parseFloat(row.total_revenue) || 0;
                                            // Debug: show per-row resolution when ambiguous
                                            try {
                                                console.debug('staff-agg row:', {
                                                    sid,
                                                    name,
                                                    value: val,
                                                    sampleRow: row
                                                });
                                            } catch (e) {}
                                            agg[name] = (agg[name] || 0) + val;
                                        });
                                        const arr = Object.entries(agg).map(([label, value]) => ({
                                            label,
                                            value
                                        })).sort((a, b) => b.value - a.value).slice(0, 10);
                                        this.pieCharts.push({
                                            title: config.title,
                                            data: arr
                                        });
                                        continue;
                                    }
                                }
                            } catch (e) {
                                console.warn('Special-case orders -> items/staff aggregation failed', e);
                                // fallback to existing local logic below
                            }

                            // existing local aggregation logic continues below
                            // Nếu các hàng dữ liệu cục bộ không chứa trường nhãn (ví dụ: tên khách hàng)
                            // thì việc tổng hợp cục bộ sẽ sai lệch. Trong trường hợp đó cần gọi API
                            // để lấy dữ liệu đã được tổng hợp cho biểu đồ tròn này.
                            // Yêu cầu: các hàng cục bộ phải thật sự thuộc về thực thể đang tổng hợp
                            // ví dụ: biểu đồ khách hàng cần có `customer_id` trên hàng; nếu không thì
                            // không nên dùng `staff.full_name` làm tên khách hàng.
                            const labelPresent = dataSource.some(row => {
                                if (!row || typeof row !== 'object') return false;
                                // Trường hợp lý tưởng: hàng có id của thực thể (product_id/customer_id/staff_id/...)
                                if (config.idKey && (row[config.idKey] !== undefined && row[config.idKey] !== null && row[config.idKey] !== '')) {
                                    // đồng thời đảm bảo trường nhãn tồn tại
                                    if (row[config.actualKey] !== undefined && row[config.actualKey] !== null && row[config.actualKey] !== '') return true;
                                }

                                // Trường hợp đặc biệt: sản phẩm — đôi khi hàng được fetch có tên sản phẩm
                                // và các trường số liệu (total_revenue/total_quantity) nhưng thiếu product_id.
                                // Trong trường hợp đó vẫn có thể tổng hợp cục bộ. Phát hiện bằng cách kiểm tra
                                // sự tồn tại của `actualKey` và một trường giá trị số dành cho sản phẩm.
                                if (config.type === 'product') {
                                    const valueKeyForProducts = this.getValueKeyForReportType('products');
                                    if ((row[config.actualKey] !== undefined && row[config.actualKey] !== null && row[config.actualKey] !== '') &&
                                        (row[valueKeyForProducts] !== undefined && row[valueKeyForProducts] !== null && row[valueKeyForProducts] !== '')) {
                                        return true;
                                    }
                                }

                                return false;
                            });

                            // Debug: in ra mẫu dữ liệu dùng để quyết định tổng hợp cục bộ hay gọi API
                            try {
                                console.log(`renderAllPieCharts - sample dataSource for ${config.type}:`, dataSource.slice(0, 8));
                            } catch (e) {}

                            if (!labelPresent) {
                                // Khi không có label field trong dataSource (ví dụ: đang xem báo cáo Staff
                                // nhưng cần biểu đồ Customer, staff data không chứa customer_name),
                                // PHẢI gọi API để lấy dữ liệu đúng (orders data chứa cả staff và customer info)
                                console.log(`Label not present for ${config.type}, calling API to fetch proper data`);
                                const apiData = await this.fetchPieChartData(config.type, config.actualKey);

                                // SPECIAL CASE: Nếu API không trả về data (ví dụ: suppliers không hỗ trợ filter by product_id)
                                // thì thử aggregate local từ dataSource nếu có supplier info
                                if (!apiData || apiData.length === 0) {
                                    if (config.type === 'supplier' && dataSource.some(r => r && r.supplier_name)) {
                                        console.log(`API returned no data for ${config.type}, trying local aggregation from dataSource`);
                                        const data = this.aggregateByKey(dataSource, 'supplier_name', config.reportTypeForAgg);
                                        this.pieCharts.push({
                                            title: config.title,
                                            data: data
                                        });
                                    } else {
                                        this.pieCharts.push({
                                            title: config.title,
                                            data: apiData
                                        });
                                    }
                                } else {
                                    this.pieCharts.push({
                                        title: config.title,
                                        data: apiData
                                    });
                                }
                            } else {
                                const data = this.aggregateByKey(dataSource, config.actualKey, config.reportTypeForAgg);
                                this.pieCharts.push({
                                    title: config.title,
                                    data: data
                                });
                            }
                        }
                    } else {
                        // Fetch data từ API cho từng loại biểu đồ tròn (lần đầu tiên hoặc không có filters)
                        for (let config of pieChartsToRender) {
                            const data = await this.fetchPieChartData(config.type, config.actualKey);

                            // SPECIAL CASE: Nếu API trả về empty (ví dụ: suppliers không có data hoặc không hỗ trợ filter)
                            // thử aggregate local từ tableData nếu có thông tin entity đó
                            if ((!data || data.length === 0) && config.type === 'supplier') {
                                console.log(`API returned no data for ${config.type}, trying local aggregation from tableData`);
                                const localData = this.tableData || [];
                                if (localData.some(r => r && r.supplier_name)) {
                                    const aggregated = this.aggregateByKey(localData, 'supplier_name', config.reportTypeForAgg);
                                    this.pieCharts.push({
                                        title: config.title,
                                        data: aggregated
                                    });
                                } else {
                                    this.pieCharts.push({
                                        title: config.title,
                                        data: data
                                    });
                                }
                            } else {
                                this.pieCharts.push({
                                    title: config.title,
                                    data: data
                                });
                            }
                        }
                    }
                }

                // Render tất cả biểu đồ tròn
                // Đợi DOM render xong, sau đó mới vẽ charts
                return new Promise((resolve) => {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            setTimeout(() => {
                                this.pieCharts.forEach((pieChart, index) => {
                                    const canvas = document.getElementById(`pieChartCanvas${index}`);
                                    if (!canvas) {
                                        console.warn(`Canvas pieChartCanvas${index} not found`);
                                        return;
                                    }

                                    // Nếu không có dữ liệu, vẽ một pie chart placeholder để không bỏ qua DOM
                                    let labels = [];
                                    let values = [];
                                    let colors = [];
                                    let chartOptions = {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        plugins: {
                                            legend: {
                                                display: false // Tắt legend mặc định
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
                                    };

                                    if (!pieChart.data || pieChart.data.length === 0) {
                                        console.warn(`No data for pie chart ${index}:`, pieChart);
                                        // Tạo placeholder để hiển thị ô trống/không có dữ liệu
                                        labels = ['Không có dữ liệu'];
                                        values = [1];
                                        colors = ['#E5E7EB']; // xám nhẹ
                                        // Ẩn legend và tooltip cho placeholder
                                        chartOptions.plugins.legend.display = false;
                                        chartOptions.plugins.tooltip.enabled = false;
                                    } else {
                                        // Lọc ra các mục có giá trị <= 0 để tránh hiển thị legend/labels không cần thiết
                                        const nonZero = pieChart.data.filter(it => {
                                            const v = Number(it && it.value);
                                            return !isNaN(v) && v > 0;
                                        });

                                        if (!nonZero || nonZero.length === 0) {
                                            // Nếu tất cả giá trị là 0, hiển thị placeholder
                                            console.warn(`All zero values for pie chart ${index}`, pieChart.data);
                                            labels = ['Không có dữ liệu'];
                                            values = [1];
                                            colors = ['#E5E7EB'];
                                            chartOptions.plugins.legend.display = false;
                                            chartOptions.plugins.tooltip.enabled = false;
                                        } else {
                                            labels = nonZero.map(item => item.label);
                                            values = nonZero.map(item => item.value);
                                            colors = this.generateColors(values.length);
                                        }
                                    }

                                    try {
                                        // Destroy any previous instance bound to this index
                                        if (this.pieChartInstances[index]) {
                                            try {
                                                this.pieChartInstances[index].destroy();
                                            } catch (err) {
                                                /* ignore */
                                            }
                                            this.pieChartInstances[index] = null;
                                        }

                                        const ctx = (canvas && canvas.getContext) ? canvas.getContext('2d') : null;
                                        if (!ctx) {
                                            console.error(`Cannot get 2D context for pieChartCanvas${index}`, {
                                                index,
                                                canvas
                                            });
                                            return;
                                        }

                                        const chartInstance = new Chart(ctx, {
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
                                            options: chartOptions
                                        });

                                        // Tạo legend tùy chỉnh bên dưới biểu đồ
                                        const legendContainer = document.getElementById(`legend-${index}`);
                                        if (legendContainer && labels && labels.length > 0 && !(labels.length === 1 && labels[0] === 'Không có dữ liệu')) {
                                            const isMoney = ['revenue', 'total_spent', 'sales_value', 'purchase_value', 'avg_order_value', 'total'].includes(this.filters.criteria);
                                            const total = values.reduce((sum, val) => sum + val, 0);

                                            let legendHTML = '<div class="grid grid-cols-1 gap-1 text-xs">';
                                            labels.forEach((label, i) => {
                                                const value = values[i];
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                const displayValue = isMoney ? this.formatMoney(value) : new Intl.NumberFormat('vi-VN').format(value);

                                                legendHTML += `
                                                        <div class="flex items-center gap-2 p-1 hover:bg-white rounded">
                                                            <div class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: ${colors[i]}"></div>
                                                            <div class="flex-1 truncate" title="${label}">${label}</div>
                                                            <div class="text-gray-600 font-medium">${displayValue} (${percentage}%)</div>
                                                        </div>
                                                    `;
                                            });
                                            legendHTML += '</div>';
                                            legendContainer.innerHTML = legendHTML;
                                        }

                                        this.pieChartInstances.push(chartInstance);
                                    } catch (e) {
                                        console.error('Error creating pie chart instance:', e);
                                    }
                                });
                                resolve();
                            }, 50);
                        });
                    });
                });
            },

            async fetchPieChartData(fetchType, groupByKey) {
                try {
                    const params = new URLSearchParams();

                    // LOGIC MỚI: Khi cần biểu đồ tròn cho entity khác với reportType hiện tại
                    // (ví dụ: đang xem báo cáo Staff nhưng cần biểu đồ Customer),
                    // thì GỌI API với report_type='orders' để lấy danh sách đơn hàng,
                    // rồi aggregate theo entity mong muốn ở frontend.
                    // Điều này đảm bảo các filter (staff_id, product_id, customer_id) được áp dụng đúng.

                    let reportTypeForPie = this.filters.reportType;

                    // Quyết định report_type nào sẽ được gọi để lấy dữ liệu biểu đồ tròn
                    // Khi đang xem báo cáo của một entity nhưng cần biểu đồ của entity khác:

                    // TH1: Đang xem Staff, cần biểu đồ Customer
                    // → Gọi report_type=orders với staff_id filter (orders có customer info)
                    if (this.filters.reportType === 'staff' && fetchType === 'customer') {
                        reportTypeForPie = 'orders';
                    }
                    // TH2: Đang xem Staff, cần biểu đồ Product  
                    // → Gọi report_type=products với staff_id filter (backend sẽ JOIN qua orders)
                    else if (this.filters.reportType === 'staff' && fetchType === 'product') {
                        reportTypeForPie = 'products';
                    }
                    // TH3: Đang xem Products, cần biểu đồ Customer
                    // → Gọi report_type=customers với product_id filter
                    else if (this.filters.reportType === 'products' && fetchType === 'customer') {
                        reportTypeForPie = 'customers';
                    }
                    // TH4: Đang xem Products, cần biểu đồ Staff
                    // → Gọi report_type=staff với product_id filter
                    else if (this.filters.reportType === 'products' && fetchType === 'staff') {
                        reportTypeForPie = 'staff';
                    }
                    // TH5: Đang xem Customers, cần biểu đồ Product/Staff
                    // → Gọi report_type tương ứng với customer_id filter
                    else if (this.filters.reportType === 'customers' && fetchType === 'product') {
                        reportTypeForPie = 'products';
                    } else if (this.filters.reportType === 'customers' && fetchType === 'staff') {
                        reportTypeForPie = 'staff';
                    }
                    // TH6: Đang xem Products, cần biểu đồ Supplier
                    // → Gọi report_type=suppliers KHÔNG gửi product_id (backend có thể không hỗ trợ)
                    // Thay vào đó sẽ aggregate local từ product data nếu có supplier info
                    else if (this.filters.reportType === 'products' && fetchType === 'supplier') {
                        reportTypeForPie = 'suppliers';
                    }
                    // Default: Giữ nguyên hoặc ánh xạ sang plural
                    else {
                        const fetchTypeMap = {
                            product: 'products',
                            customer: 'customers',
                            staff: 'staff',
                            supplier: 'suppliers'
                        };
                        if (fetchTypeMap[fetchType]) {
                            reportTypeForPie = fetchTypeMap[fetchType];
                        }
                    }

                    params.append('report_type', reportTypeForPie);
                    // Choose a criteria valid for the report type we're requesting.
                    // The current `this.filters.criteria` may not be valid for the
                    // aggregated report type (e.g. suppliers expect 'sales_value' or
                    // 'purchase_value' while the main page may still have 'revenue').
                    // Map criteria between report types intelligently:
                    let criteriaToSend = this.filters.criteria;
                    try {
                        const allowed = this.getCriteriaOptionsForType(reportTypeForPie).map(o => o.value);

                        // Smart mapping: if current criteria is not allowed, try to find equivalent
                        if (!allowed.includes(criteriaToSend)) {
                            // Map 'revenue' to appropriate equivalent for each report type
                            if (criteriaToSend === 'revenue') {
                                if (reportTypeForPie === 'customers') {
                                    criteriaToSend = 'total_spent'; // revenue equivalent for customers
                                } else if (reportTypeForPie === 'suppliers') {
                                    criteriaToSend = 'sales_value'; // revenue equivalent for suppliers
                                } else if (reportTypeForPie === 'orders') {
                                    criteriaToSend = 'total'; // revenue equivalent for orders
                                } else {
                                    criteriaToSend = 'revenue'; // keep for staff/products
                                }
                            }
                            // Map 'total_spent' to appropriate equivalent
                            else if (criteriaToSend === 'total_spent') {
                                if (reportTypeForPie === 'staff' || reportTypeForPie === 'products') {
                                    criteriaToSend = 'revenue'; // total_spent equivalent for staff/products
                                } else if (reportTypeForPie === 'suppliers') {
                                    criteriaToSend = 'sales_value'; // total_spent equivalent for suppliers
                                } else if (reportTypeForPie === 'orders') {
                                    criteriaToSend = 'total'; // total_spent equivalent for orders
                                }
                            }
                            // Map 'sales_value' or 'purchase_value' to appropriate equivalent
                            else if (criteriaToSend === 'sales_value' || criteriaToSend === 'purchase_value') {
                                if (reportTypeForPie === 'staff' || reportTypeForPie === 'products') {
                                    criteriaToSend = 'revenue';
                                } else if (reportTypeForPie === 'customers') {
                                    criteriaToSend = 'total_spent';
                                } else if (reportTypeForPie === 'orders') {
                                    criteriaToSend = 'total';
                                }
                            }
                            // Map 'total' to appropriate equivalent (from orders)
                            else if (criteriaToSend === 'total') {
                                if (reportTypeForPie === 'staff' || reportTypeForPie === 'products') {
                                    criteriaToSend = 'revenue';
                                } else if (reportTypeForPie === 'customers') {
                                    criteriaToSend = 'total_spent';
                                } else if (reportTypeForPie === 'suppliers') {
                                    criteriaToSend = 'sales_value';
                                }
                            }
                            // Map 'quantity' to appropriate equivalent (from products)
                            else if (criteriaToSend === 'quantity') {
                                // Quantity is product-specific, fallback to count-based criteria
                                if (reportTypeForPie === 'staff' || reportTypeForPie === 'customers') {
                                    criteriaToSend = 'orders'; // quantity equivalent for staff/customers
                                } else if (reportTypeForPie === 'suppliers') {
                                    criteriaToSend = 'purchases'; //quantity equivalent for suppliers
                                } else if (reportTypeForPie === 'orders') {
                                    criteriaToSend = 'count';
                                }
                            }
                            // Map 'purchases' to appropriate equivalent (from suppliers)
                            else if (criteriaToSend === 'purchases') {
                                if (reportTypeForPie === 'staff' || reportTypeForPie === 'customers' || reportTypeForPie === 'products') {
                                    criteriaToSend = 'orders';
                                } else if (reportTypeForPie === 'orders') {
                                    criteriaToSend = 'count';
                                }
                            }

                            // Final validation: if still not allowed after mapping, use first allowed
                            if (!allowed.includes(criteriaToSend)) {
                                criteriaToSend = allowed[0] || 'revenue';
                            }
                        }
                    } catch (e) {
                        // conservative fallback
                        console.warn('Criteria mapping failed, using default', e);
                        criteriaToSend = this.filters.criteria;
                    }
                    params.append('criteria', criteriaToSend);
                    params.append('from_date', this.filters.fromDate);
                    params.append('to_date', this.filters.toDate);
                    params.append('sort_order', this.filters.sortOrder);

                    // GỬI TẤT CẢ các filter NGOẠI TRỪ filter của chính entity đang vẽ pie chart
                    // Ví dụ: Nếu đang vẽ pie chart customer (fetchType='customer'), thì GỬI staff_id, product_id, supplier_id
                    // nhưng KHÔNG GỬI customer_id để có thể thấy phân bổ của TẤT CẢ khách hàng theo nhân viên đó
                    if (fetchType !== 'product' && this.filters.productId) {
                        params.append('product_id', this.filters.productId);
                    }
                    if (fetchType !== 'customer' && this.filters.customerId) {
                        params.append('customer_id', this.filters.customerId);
                    }
                    if (fetchType !== 'staff' && this.filters.staffId) {
                        params.append('staff_id', this.filters.staffId);
                    }
                    if (fetchType !== 'supplier' && this.filters.supplierId) {
                        params.append('supplier_id', this.filters.supplierId);
                    }

                    // When there is a product filter, request debug counts from API so
                    // we can determine whether backend has any matching orders.
                    if (this.filters.productId) {
                        params.append('include_counts', '1');
                    }

                    // KHÔNG gửi value_from và value_to cho biểu đồ tròn
                    // Biểu đồ tròn chỉ phân bổ theo entity, không nên bị ảnh hưởng bởi filter số
                    // Nếu người dùng đã áp dụng bộ lọc cột trên bảng (`tableFilters`), cố gắng chuyển
                    // các bộ lọc đó thành tham số API khi có thể để các cuộc gọi API vẽ biểu đồ tròn
                    // tôn trọng bộ lọc cấp bảng (ví dụ: lọc bảng theo tên nhân viên -> gửi staff_id).
                    try {
                        // Đảm bảo các danh sách tra cứu (lookup) đã được load để có thể ánh xạ tên -> id
                        await Promise.all([
                            this.loadStaffList(),
                            this.loadCustomerList(),
                            this.loadProductList(),
                            this.loadSupplierList()
                        ]);

                        const tableFilterParams = [];
                        Object.keys(this.tableFilters).forEach((tfKey, idx) => {
                            const val = this.tableFilters[tfKey];
                            if (!val || val.toString().trim() === '') return;
                            // Chỉ số cột -> tableColumns (tableColumns tương ứng với các cột hiển thị)
                            const colIndex = parseInt(tfKey.replace('column', ''), 10) - 1;
                            const col = this.tableColumns[colIndex];
                            if (!col) return;
                            const search = val.toString().trim().toLowerCase();
                            // Ánh xạ các khóa cột đã biết thành tham số id bằng cách sử dụng các danh sách đã load
                            if (['full_name', 'staff_name'].includes(col.key) && this.staffList && this.staffList.length) {
                                const match = this.staffList.find(s => s.full_name && s.full_name.toLowerCase().includes(search));
                                if (match && match.staff_id) tableFilterParams.push(['staff_id', match.staff_id]);
                            }
                            if (['customer_name', 'full_name'].includes(col.key) && this.customerList && this.customerList.length) {
                                const match = this.customerList.find(c => c.full_name && c.full_name.toLowerCase().includes(search));
                                if (match && match.customer_id) tableFilterParams.push(['customer_id', match.customer_id]);
                            }
                            if (['product_name', 'name'].includes(col.key) && this.productList && this.productList.length) {
                                // SPECIAL: Khi filter product theo tên (text search), có thể match NHIỀU products
                                // Thay vì chỉ lấy first match, tìm TẤT CẢ matching products
                                const matches = this.productList.filter(p => p.name && p.name.toLowerCase().includes(search));
                                if (matches && matches.length > 0) {
                                    // Lưu TẤT CẢ matching product IDs để xử lý sau
                                    tableFilterParams.push(['product_ids', matches.map(m => m.product_id)]);
                                    tableFilterParams.push(['product_names', matches.map(m => m.name)]);
                                }
                            }
                            if (['supplier_name'].includes(col.key) && this.supplierList && this.supplierList.length) {
                                const match = this.supplierList.find(s => s.supplier_name && s.supplier_name.toLowerCase().includes(search));
                                if (match && match.supplier_id) tableFilterParams.push(['supplier_id', match.supplier_id]);
                            }
                        });

                        if (tableFilterParams.length) {
                            // Trường hợp đặc biệt: khi đang xem báo cáo theo nhân viên và cần lấy biểu đồ
                            // sản phẩm, chỉ muốn các sản phẩm do nhân viên đó bán. Trong trường hợp này
                            // chỉ thêm `staff_id` (từ bộ lọc chung hoặc từ tableFilterParams) để tránh
                            // lọc quá mức bởi các bộ lọc ánh xạ khác (ví dụ: customer_id)
                            if (this.filters.reportType === 'staff' && fetchType === 'product') {
                                // Ưu tiên dùng staffId rõ ràng từ panel bộ lọc chính
                                if (this.filters.staffId) {
                                    if (!params.has('staff_id')) params.append('staff_id', this.filters.staffId);
                                } else {
                                    // nếu không có, thử tìm staff_id được ánh xạ trong tableFilterParams
                                    const staffPair = tableFilterParams.find(p => p[0] === 'staff_id');
                                    if (staffPair && !params.has('staff_id')) params.append('staff_id', staffPair[1]);
                                }
                            } else {
                                // Thêm các tham số duy nhất cho trường hợp tổng quát
                                tableFilterParams.forEach(([k, v]) => {
                                    if (!params.has(k)) params.append(k, v);
                                });
                            }
                            console.debug('fetchPieChartData - appended params from tableFilters:', Array.from(params.entries()));
                        }
                    } catch (e) {
                        console.warn('Error mapping tableFilters to API params for pie charts', e);
                    }

                    // Try multiple criteria options for the target report type if the
                    // first request returns empty. This helps when backend behaves
                    // differently for different criteria or when a particular
                    // criteria produces no grouped rows for the filter combination.
                    const allowedCriteria = this.getCriteriaOptionsForType(reportTypeForPie).map(o => o.value);
                    // Ensure chosen criteria is first
                    const startIdx = Math.max(0, allowedCriteria.indexOf(criteriaToSend));
                    const tryOrder = [...allowedCriteria.slice(startIdx), ...allowedCriteria.slice(0, startIdx)];

                    let finalResult = null;
                    let lastRawResult = null; // keep last raw API response for diagnostics
                    for (let c of tryOrder) {
                        // update params for this attempt
                        if (params.has('criteria')) params.set('criteria', c);
                        else params.append('criteria', c);
                        const url = `/admin/api/reports/filter?${params}`;
                        console.debug(`fetchPieChartData - request for ${fetchType} (criteria=${c}):`, url, Array.from(params.entries()));
                        try {
                            const response = await fetch(url);
                            const result = await response.json();
                            console.debug(`Raw API response for ${fetchType} (criteria=${c}):`, result);
                            // Support multiple possible payload shapes (data | rows | items)
                            let responseArray = null;
                            if (result && Array.isArray(result.data) && result.data.length > 0) responseArray = result.data;
                            else if (result && Array.isArray(result.rows) && result.rows.length > 0) responseArray = result.rows;
                            else if (result && Array.isArray(result.items) && result.items.length > 0) responseArray = result.items;

                            // remember last raw response even if empty, may contain debug_counts
                            lastRawResult = result;
                            if (result && result.success && Array.isArray(responseArray) && responseArray.length > 0) {
                                // attach the resolved data array so caller can aggregate reliably
                                finalResult = {
                                    result,
                                    criteriaUsed: c,
                                    dataArray: responseArray
                                };
                                break;
                            }
                        } catch (e) {
                            console.warn('fetchPieChartData - fetch error for', c, e);
                        }
                    }

                    // If no grouped rows were returned, check server-provided debug counts
                    // If the server explicitly reports there are NO matching order_items
                    // for this product + filters (items_count == 0), we should NOT retry
                    // without the product filter because that would misleadingly show
                    // "all customers/staff" instead of an empty result for the selected product.
                    if (!finalResult && this.filters.productId && lastRawResult && lastRawResult.debug_counts && typeof lastRawResult.debug_counts.items_count !== 'undefined') {
                        const itemsCount = Number(lastRawResult.debug_counts.items_count) || 0;
                        if (itemsCount === 0) {
                            console.debug(`fetchPieChartData - server debug_counts.items_count=0 for product_id=${this.filters.productId}; not retrying without product for ${fetchType}`);
                            return [];
                        }
                    }

                    if (finalResult) {
                        const {
                            result,
                            criteriaUsed,
                            dataArray
                        } = finalResult;
                        const dataForAgg = dataArray || result.data || result.rows || result.items || [];

                        // DEBUG: Log để kiểm tra dữ liệu trả về
                        console.log(`fetchPieChartData - Got ${dataForAgg.length} rows for ${fetchType}, reportType=${reportTypeForPie}`);
                        if (dataForAgg.length > 0) {
                            console.log(`Sample row:`, dataForAgg[0]);
                            console.log(`Available fields:`, Object.keys(dataForAgg[0]));
                            console.log(`groupByKey=${groupByKey}, sample values:`, dataForAgg.slice(0, 5).map(r => r[groupByKey] || r['customer_name'] || r['full_name'] || 'N/A'));
                        }

                        const aggregated = this.aggregateByKey(dataForAgg, groupByKey, reportTypeForPie);
                        return aggregated;
                    } else {
                        console.warn(`No data for pie chart ${fetchType} after trying criteria:`, tryOrder);
                        // If the request included a product filter and produced no grouped rows,
                        // only retry without the product filter when the server explicitly
                        // reported that there ARE matching order_items for the product via
                        // debug_counts.items_count > 0. If debug_counts is absent or zero,
                        // skip the retry to avoid showing a misleading "all entities" pie.
                        if (this.filters.productId) {
                            const hasDebug = lastRawResult && lastRawResult.debug_counts && typeof lastRawResult.debug_counts.items_count !== 'undefined';
                            const itemsCount = hasDebug ? Number(lastRawResult.debug_counts.items_count) : null;
                            if (!hasDebug) {
                                console.debug('fetchPieChartData - no debug_counts available; skipping retry without product to avoid showing global results');
                                return [];
                            }
                            if (itemsCount === 0) {
                                console.debug(`fetchPieChartData - server debug_counts.items_count=0 for product_id=${this.filters.productId}; skipping retry`);
                                return [];
                            }

                            try {
                                console.debug(`fetchPieChartData - server reported items_count=${itemsCount}; retrying without product filter`);
                                // Build params without product_id
                                const paramsNoProduct = new URLSearchParams(Array.from(params.entries()).filter(([k]) => k !== 'product_id'));
                                let finalResultNoProduct = null;
                                for (let c of tryOrder) {
                                    if (paramsNoProduct.has('criteria')) paramsNoProduct.set('criteria', c);
                                    else paramsNoProduct.append('criteria', c);
                                    const urlNoProduct = `/admin/api/reports/filter?${paramsNoProduct}`;
                                    console.debug(`fetchPieChartData - retry request without product (criteria=${c}):`, urlNoProduct);
                                    try {
                                        const response2 = await fetch(urlNoProduct);
                                        const res2 = await response2.json();
                                        console.debug(`Raw API response (no product) for ${fetchType} (criteria=${c}):`, res2);
                                        let responseArray2 = null;
                                        if (res2 && Array.isArray(res2.data) && res2.data.length > 0) responseArray2 = res2.data;
                                        else if (res2 && Array.isArray(res2.rows) && res2.rows.length > 0) responseArray2 = res2.rows;
                                        else if (res2 && Array.isArray(res2.items) && res2.items.length > 0) responseArray2 = res2.items;

                                        if (res2 && res2.success && Array.isArray(responseArray2) && responseArray2.length > 0) {
                                            finalResultNoProduct = {
                                                result: res2,
                                                criteriaUsed: c,
                                                dataArray: responseArray2
                                            };
                                            break;
                                        }
                                    } catch (e) {
                                        console.warn('fetchPieChartData - retry fetch error (no product) for', c, e);
                                    }
                                }

                                if (finalResultNoProduct) {
                                    const {
                                        result: resOk,
                                        criteriaUsed: cUsed,
                                        dataArray: dataArray2
                                    } = finalResultNoProduct;
                                    const dataForAgg2 = dataArray2 || resOk.data || resOk.rows || resOk.items || [];
                                    const aggregated2 = this.aggregateByKey(dataForAgg2, groupByKey, reportTypeForPie);
                                    return aggregated2;
                                } else {
                                    console.debug('fetchPieChartData - retry without product produced no data either');
                                }
                            } catch (e) {
                                console.warn('fetchPieChartData - unexpected error during product fallback retry', e);
                            }
                        }
                    }
                } catch (err) {
                    console.error('Error fetching pie chart data:', err);
                }
                return [];
            },

            // Helper: try to find the order id in a table row using common key names
            getOrderIdFromRow(row) {
                if (!row || typeof row !== 'object') return null;
                const candidates = ['order_id', 'id', 'order_number', 'invoice_no', 'ma_don', 'orderId', 'orderId'];
                for (const k of candidates) {
                    if (row[k] !== undefined && row[k] !== null && String(row[k]).toString().trim() !== '') return String(row[k]);
                }
                if (row.order && (row.order.id || row.order.order_id)) return String(row.order.id || row.order.order_id);
                return null;
            },

            // Helper: try to find staff id (creator) on an order row
            getStaffIdFromRow(row) {
                if (!row || typeof row !== 'object') return null;
                const candidates = ['staff_id', 'created_by', 'created_by_id', 'user_id', 'creator_id', 'staffId'];
                for (const k of candidates) {
                    if (row[k] !== undefined && row[k] !== null && String(row[k]).toString().trim() !== '') return String(row[k]);
                }
                if (row.created_by && typeof row.created_by === 'object' && (row.created_by.id || row.created_by.user_id)) return String(row.created_by.id || row.created_by.user_id);
                return null;
            },

            // Fetch order items for a list of order IDs. Expects backend route
            // `/admin/api/orders/items` to accept JSON { order_ids: [...] } and
            // return array under `items`|`data`|`rows`.
            async fetchOrderItemsForOrderIds(orderIds) {
                // The backend exposes GET /admin/api/orders/{id}/items for each order.
                // Call that endpoint for each order id in parallel and merge results.
                if (!orderIds || !orderIds.length) return [];
                try {
                    const calls = orderIds.map(id => fetch(`/admin/api/orders/${encodeURIComponent(id)}/items`)
                        .then(r => (r.ok ? r.json().catch(() => null) : null))
                        .catch(() => null)
                    );
                    const results = await Promise.all(calls);
                    const items = [];
                    for (const res of results) {
                        if (!res) continue;
                        if (Array.isArray(res.items) && res.items.length) items.push(...res.items);
                        else if (Array.isArray(res.data) && res.data.length) items.push(...res.data);
                        else if (Array.isArray(res.rows) && res.rows.length) items.push(...res.rows);
                    }
                    return items;
                } catch (e) {
                    console.warn('fetchOrderItemsForOrderIds error', e);
                    return [];
                }
            },

            async renderPieChartByFilter() {
                // Method này không dùng nữa, đã thay bằng renderAllPieCharts
            },

            aggregateByKey(data, key, reportTypeForAgg) {
                const grouped = {};
                // Determine the value key based on the report type being aggregated
                let valueKey = reportTypeForAgg ? this.getValueKeyForReportType(reportTypeForAgg) : this.getValueKeyForCriteria();

                // Nếu các hàng không chứa `valueKey` mong muốn (thường gặp khi tổng hợp cục bộ
                // từ `tableData` được fetch cho một loại báo cáo khác), thì quay về dùng khóa
                // giá trị của báo cáo hiện tại để cộng các số thực sự tồn tại trong hàng.
                const sampleRow = data && data.length ? data[0] : null;
                if (sampleRow && !(valueKey in sampleRow)) {
                    // Use the reportTypeForAgg as the first fallback because we aggregated
                    // data for that report type. If that key also doesn't exist, then
                    // fall back to the current page reportType's key or a generic criteria.
                    const fallbackKey = this.getValueKeyForReportType(reportTypeForAgg) || this.getValueKeyForReportType(this.filters.reportType) || this.getValueKeyForCriteria();
                    valueKey = fallbackKey;
                }

                // If the chosen valueKey is missing or produces only zeros, try a set
                // of candidate keys commonly returned by different report types.
                const candidateKeys = [
                    valueKey,
                    'total_sales_value',
                    'total_purchase_value',
                    'total_revenue',
                    'total_spent',
                    'total_amount',
                    'total_revenue_value',
                    'total' // fallback
                ];

                // Filter unique and existing keys on sampleRow
                const keysToTest = [...new Set(candidateKeys)];
                let chosenKey = valueKey;
                if (sampleRow) {
                    // Prefer first key that exists on sampleRow
                    for (let k of keysToTest) {
                        if (sampleRow.hasOwnProperty(k)) {
                            chosenKey = k;
                            break;
                        }
                    }
                }

                // If chosenKey exists but sums to zero across data, try to find a non-zero key
                const sumForKey = (k) => data.reduce((s, r) => s + (parseFloat(r[k]) || 0), 0);
                if (sampleRow && (sampleRow[chosenKey] === undefined || sumForKey(chosenKey) === 0)) {
                    for (let k of keysToTest) {
                        const s = sumForKey(k);
                        if (!isNaN(s) && s > 0) {
                            chosenKey = k;
                            break;
                        }
                    }
                }

                console.debug(`aggregateByKey - chosen value key: ${chosenKey}`);

                data.forEach((row, idx) => {
                    // Giải quyết tên nhãn với phương án dự phòng: backend có thể trả tên trường khác nhau
                    // tùy theo `report_type`.
                    let labels = row[key];

                    // SPECIAL CASE: Khi fetch orders data để aggregate theo entity khác
                    // Dựa vào `key` (groupByKey) để xác định entity type cần aggregate
                    if (labels === undefined || labels === null || labels === '') {
                        // Xác định entity type từ key name
                        if (key === 'customer_name' || key === 'full_name') {
                            // Aggregate theo khách hàng
                            labels = row['customer_name'] || row['full_name'] || row['customer'] || row['buyer_name'];
                        } else if (key === 'staff_name' || key === 'created_by_name') {
                            // Aggregate theo nhân viên
                            labels = row['staff_name'] || row['created_by_name'] || row['full_name'];
                        } else if (key === 'product_name' || key === 'name') {
                            // Aggregate theo sản phẩm
                            // Orders KHÔNG có product_name directly, cần extract từ order items
                            // Tạm thời thử các field có thể có
                            labels = row['product_name'] || row['name'] || row['item_name'];
                        } else if (key === 'supplier_name') {
                            // Aggregate theo nhà cung cấp
                            labels = row['supplier_name'] || row['supplier'];
                        }
                    }

                    if (labels === undefined || labels === null || labels === '') {
                        // Thử các khóa thay thế thông dụng — nhưng ưu tiên các khóa liên quan
                        // đến loại thực thể đang được tổng hợp (`reportTypeForAgg`) để tránh
                        // nhầm lẫn (ví dụ: khi tổng hợp 'staff' nhưng hàng chỉ có `customer_name`).
                        let fallbackKeys = [];
                        if (reportTypeForAgg === 'staff' || reportTypeForAgg === 'staffs') {
                            fallbackKeys = ['staff_name', 'full_name', 'name', 'product_name', 'customer_name', 'supplier_name', 'title'];
                        } else if (reportTypeForAgg === 'customers' || reportTypeForAgg === 'customer') {
                            fallbackKeys = ['customer_name', 'full_name', 'name', 'staff_name', 'product_name', 'supplier_name', 'title'];
                        } else if (reportTypeForAgg === 'products' || reportTypeForAgg === 'product') {
                            fallbackKeys = ['product_name', 'name', 'full_name', 'staff_name', 'customer_name', 'supplier_name', 'title'];
                        } else if (reportTypeForAgg === 'suppliers' || reportTypeForAgg === 'supplier') {
                            fallbackKeys = ['supplier_name', 'name', 'product_name', 'full_name', 'customer_name', 'staff_name', 'title'];
                        } else {
                            fallbackKeys = ['product_name', 'name', 'full_name', 'customer_name', 'staff_name', 'supplier_name', 'title'];
                        }

                        for (let fk of fallbackKeys) {
                            if (row[fk] !== undefined && row[fk] !== null && row[fk] !== '') {
                                labels = row[fk];
                                break;
                            }
                        }
                    }
                    labels = labels || 'Khác';
                    console.debug(`Row ${idx}: labels from row[${key}] resolved = "${labels}", value from row[${chosenKey}] = ${row[chosenKey]}`);

                    // Nếu là danh sách nhiều item (GROUP_CONCAT), tách ra 
                    // NHƯ CẢ LẦN TRƯỚC chia đều, nhưng giờ backend đã trả về mỗi item riêng row
                    // nên không nên còn GROUP_CONCAT nữa
                    if (typeof labels === 'string' && labels.includes(', ')) {
                        // Trường hợp này hiếm khi xảy ra vì đã đổi report_type
                        // Nếu vẫn có GROUP_CONCAT, chia đều cho từng item
                        const labelList = labels.split(', ');
                        labelList.forEach(label => {
                            const trimmedLabel = label.trim();
                            if (!grouped[trimmedLabel]) {
                                grouped[trimmedLabel] = 0;
                            }
                            // Use the chosen numeric key (chosenKey) rather than the original
                            // valueKey so we respect fallbacks when the preferred key
                            // isn't present on the rows.
                            grouped[trimmedLabel] += (parseFloat(row[chosenKey]) || 0) / labelList.length;
                        });
                    } else {
                        // Single item - cộng dồn bình thường
                        if (!grouped[labels]) {
                            grouped[labels] = 0;
                        }
                        grouped[labels] += parseFloat(row[chosenKey]) || 0;
                    }
                });

                console.debug(`aggregateByKey - grouped result:`, grouped);

                // Convert to array and sort
                const result = Object.entries(grouped)
                    .map(([label, value]) => ({
                        label,
                        value
                    }))
                    .sort((a, b) => b.value - a.value)
                    .slice(0, 10); // Top 10

                console.debug(`aggregateByKey - final result:`, result);
                return result;
            },

            getValueKeyForCriteria() {
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
                    high_stock: 'current_stock',
                    out_of_stock: 'current_stock'
                };
                return valueKeys[this.filters.criteria] || 'total_revenue';
            },

            getValueKeyForReportType(reportType) {
                // Map criteria to value key based on report type
                if (reportType === 'staff') {
                    const staffKeys = {
                        revenue: 'total_revenue',
                        orders: 'total_orders',
                        avg_order_value: 'avg_order_value'
                    };
                    return staffKeys[this.filters.criteria] || 'total_revenue';
                } else if (reportType === 'products') {
                    const productKeys = {
                        revenue: 'total_revenue',
                        quantity: 'total_quantity',
                        orders: 'total_orders'
                    };
                    return productKeys[this.filters.criteria] || 'total_revenue';
                } else if (reportType === 'customers') {
                    // Customers report always uses total_spent for "revenue" criteria
                    const customerKeys = {
                        revenue: 'total_spent',
                        orders: 'total_orders',
                        total_spent: 'total_spent',
                        avg_order_value: 'avg_order_value'
                    };
                    return customerKeys[this.filters.criteria] || 'total_spent';
                } else if (reportType === 'suppliers') {
                    const supplierKeys = {
                        revenue: 'total_sales_value',
                        sales_value: 'total_sales_value',
                        purchase_value: 'total_purchase_value',
                        purchases: 'total_purchases'
                    };
                    return supplierKeys[this.filters.criteria] || 'total_sales_value';
                } else if (reportType === 'orders') {
                    const orderKeys = {
                        total: 'total_amount',
                        count: 'order_count'
                    };
                    return orderKeys[this.filters.criteria] || 'total_amount';
                }
                return 'total_revenue';
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
                let key = labelKeys[this.filters.reportType];

                // Fallback: nếu key không tồn tại trong data, tự động tìm trường phù hợp
                if (data && data.length > 0) {
                    const sample = data[0];
                    if (!sample[key]) {
                        // Thử các trường thường gặp
                        const candidates = ['supplier_name', 'name', 'full_name', 'product_name', 'customer_name', 'staff_name'];
                        key = candidates.find(k => sample[k] !== undefined && sample[k] !== null) || key;
                    }
                }

                return data.map(item => item[key] || 'N/A');
            },

            getChartValues(data) {
                // Sử dụng hàm getValueKeyForReportType để lấy key phù hợp với report type
                let key = this.getValueKeyForReportType(this.filters.reportType);

                // Fallback: nếu key không có trong data, thử tìm key phù hợp
                if (data && data.length > 0) {
                    const sample = data[0];
                    if (sample && (sample[key] === undefined || sample[key] === null)) {
                        // Thử các trường giá trị thường gặp
                        const candidates = [
                            'total_revenue', 'total_sales_value', 'total_purchase_value',
                            'total_spent', 'total_orders', 'total_purchases', 'total_quantity',
                            'avg_order_value', 'total_amount', 'order_count', 'current_stock'
                        ];
                        const foundKey = candidates.find(k => sample[k] !== undefined && sample[k] !== null);
                        if (foundKey) {
                            key = foundKey;
                            console.debug('getChartValues - auto-detected value key:', key);
                        }
                    }
                }

                return data.map(item => parseFloat(item[key]) || 0);
            },

            generateColors(count) {
                const baseColors = [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
                ];
                return baseColors.slice(0, count);
            },

            // Generate visually distinct color using HSL (softer / paler palette)
            generateDistinctColor(index, total) {
                const h = Math.round((index / Math.max(1, total)) * 320); // spread hues
                // slightly lower saturation and higher lightness for paler, softer tones
                return `hsl(${h} 65% 68%)`;
            },

            // Convert hex or hsl to rgba with given alpha
            hexToRgba(hexOrHsl, alpha = 0.28) {
                if (!hexOrHsl) return `rgba(0,0,0,${alpha})`;
                if (hexOrHsl.startsWith('hsl')) {
                    // Chart.js accepts hsla as background when CSS color string with slash notation
                    try {
                        return hexOrHsl.replace('hsl(', 'hsla(').replace(')', ` / ${alpha})`);
                    } catch {
                        return `rgba(0,0,0,${alpha})`;
                    }
                }
                const hex = hexOrHsl.replace('#', '');
                if (hex.length === 3) {
                    const r = parseInt(hex[0] + hex[0], 16);
                    const g = parseInt(hex[1] + hex[1], 16);
                    const b = parseInt(hex[2] + hex[2], 16);
                    return `rgba(${r},${g},${b},${alpha})`;
                }
                const bigint = parseInt(hex, 16);
                const r = (bigint >> 16) & 255;
                const g = (bigint >> 8) & 255;
                const b = bigint & 255;
                return `rgba(${r},${g},${b},${alpha})`;
            },

            // Normalize arbitrary CSS color string (hex, hsl, rgb, named color, etc.)
            // to an `rgba(r,g,b,a)` string using an offscreen canvas, so we can
            // reliably set the alpha for hover fill regardless of original format.
            normalizeColorToRgba(color, alpha = 0.7) {
                try {
                    const cvs = document.createElement('canvas');
                    cvs.width = cvs.height = 1;
                    const c = cvs.getContext('2d');
                    c.clearRect(0, 0, 1, 1);
                    c.fillStyle = color;
                    const computed = c.fillStyle; // normalized to rgb(...) or rgba(...)
                    const m = computed.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)/);
                    if (m) {
                        return `rgba(${m[1]},${m[2]},${m[3]},${alpha})`;
                    }
                } catch (e) {
                    // Tiếp tục sang phần xử lý dự phòng bên dưới
                }
                // Phương án dự phòng: thử dùng hàm hexToRgba hiện có
                try {
                    return this.hexToRgba(color, alpha);
                } catch (e) {
                    return `rgba(0,0,0,${alpha})`;
                }
            },

            renderAreaChart() {
                // Hủy (destroy) bất kỳ instance biểu đồ cũ đang tồn tại
                if (this.areaChart) {
                    try {
                        this.areaChart.destroy();
                    } catch (e) {
                        /* ignore */
                    }
                    this.areaChart = null;
                }

                const dataToUse = this.getFilteredTableData().length > 0 ? this.getFilteredTableData() : this.tableData;
                console.debug('renderAreaChart - reportType:', this.filters.reportType, 'dataToUse length:', dataToUse ? dataToUse.length : 0);

                if (!dataToUse || dataToUse.length === 0) {
                    console.warn('renderAreaChart aborted: no data');
                    return;
                }

                // Xác định loại thực thể để gom nhóm (làm chung/generic)
                const entityType = this.filters.reportType || 'staff';
                const labelCandidatesMap = {
                    staff: ['staff_name', 'full_name', 'name', 'staff'],
                    products: ['product_name', 'name', 'product'],
                    customers: ['customer_name', 'full_name', 'name', 'customer'],
                    suppliers: ['supplier_name', 'name', 'supplier'],
                    // Orders: prefer status (to show stacked area by order status),
                    // then customer_name, then order_id as last resort
                    orders: ['status', 'customer_name', 'order_id']
                };
                const candidates = labelCandidatesMap[entityType] || ['name', 'full_name'];
                let labelKey = candidates.find(k => dataToUse.some(r => r && (r[k] !== undefined && r[k] !== null && r[k] !== '')));

                // Fallback: nếu không tìm thấy labelKey, dùng khóa đầu tiên có giá trị
                if (!labelKey) {
                    const sample = dataToUse.find(r => r && typeof r === 'object');
                    if (sample) {
                        console.warn('renderAreaChart - labelKey not found, sample row keys:', Object.keys(sample));
                        labelKey = Object.keys(sample).find(k => {
                            const v = sample[k];
                            return v !== undefined && v !== null && v !== '' && typeof v !== 'number';
                        }) || 'id';
                    }
                }

                console.debug('renderAreaChart - entityType:', entityType, 'labelKey:', labelKey, 'valueKey will be:', this.getValueKeyForReportType(entityType));

                // If viewing orders, prefer using the unique order identifier so
                // each order becomes its own series (each order = one color)
                if (entityType === 'orders') {
                    const orderIdCandidates = ['order_id', 'id', 'order_number', 'invoice_no'];
                    const found = orderIdCandidates.find(k => dataToUse.some(r => r && (r[k] !== undefined && r[k] !== null && r[k] !== '')));
                    if (found) {
                        labelKey = found;
                    }
                }

                const valueKey = this.getValueKeyForReportType(entityType) || this.getValueKeyForCriteria();

                // Hàm hỗ trợ phân tích / chuyển chuỗi ngày thành đối tượng Date
                // Sử dụng các mẫu rõ ràng (ISO yyyy-mm-dd, dd/mm/yyyy, ISO timestamp) để tránh
                // nhận diện nhầm các trường id (số) như 'product_id' hoặc 'staff_id' là ngày.
                const parseDate = raw => {
                    if (!raw && raw !== 0) return null;
                    // Date object already
                    if (raw instanceof Date && !isNaN(raw)) return raw;

                    // Numbers: treat as timestamp only if it's large (ms) and yields reasonable year
                    if (typeof raw === 'number') {
                        const d = new Date(raw);
                        if (!isNaN(d) && d.getFullYear() > 1970) return d;
                        return null;
                    }

                    if (typeof raw === 'string') {
                        const s = raw.trim();
                        if (!s) return null;

                        // Common date patterns
                        const isoPattern = /^\d{4}-\d{2}-\d{2}/; // 2023-11-18
                        const dmyPattern = /^\d{1,2}\/\d{1,2}\/\d{4}$/; // 18/11/2025

                        if (isoPattern.test(s)) {
                            const d = new Date(s);
                            if (!isNaN(d)) return d;
                        }

                        if (dmyPattern.test(s)) {
                            const [dd, mm, yyyy] = s.split('/');
                            const d = new Date(parseInt(yyyy, 10), parseInt(mm, 10) - 1, parseInt(dd, 10));
                            if (!isNaN(d)) return d;
                        }

                        // Fallback: try Date parsing only when string contains separators or time marker
                        if (s.includes('/') || s.includes('-') || s.includes('T') || s.includes(':')) {
                            const d = new Date(s);
                            if (!isNaN(d) && d.getFullYear() > 1970) return d;
                        }
                    }

                    return null;
                };

                const dateCandidates = ['created_at', 'date', 'order_date', 'report_date', 'day', 'date_created', 'createdAt'];
                let dateKey = dateCandidates.find(k => dataToUse.some(r => r && (r[k] !== undefined && r[k] !== null && r[k] !== '')));
                if (!dateKey) {
                    const sample = dataToUse.find(r => r && typeof r === 'object');
                    if (sample) {
                        for (const k of Object.keys(sample)) {
                            if (parseDate(sample[k])) {
                                dateKey = k;
                                console.log('renderAreaChart auto-detected dateKey:', dateKey);
                                break;
                            }
                        }
                    }
                }

                console.debug('renderAreaChart - dateKey:', dateKey, 'will render', dateKey ? 'multi-date' : 'single-label', 'chart');

                // Tổng hợp các giá trị theo đối tượng
                const agg = {};
                const entityTotals = {};
                const dateSet = new Set();

                dataToUse.forEach(row => {
                    const label = row[labelKey] || 'Khác';
                    const val = parseFloat(row[valueKey]) || 0;
                    entityTotals[label] = (entityTotals[label] || 0) + val;
                    if (dateKey) {
                        const d = parseDate(row[dateKey]);
                        if (d) {
                            const day = d.toISOString().slice(0, 10);
                            dateSet.add(day);
                            if (!agg[label]) agg[label] = {};
                            agg[label][day] = (agg[label][day] || 0) + val;
                        }
                    }
                });

                const dates = Array.from(dateSet).sort();
                const hasMultipleDates = dates.length > 1;

                // Chọn N đối tượng hàng đầu để giữ biểu đồ dễ đọc
                const TOP_N = 8;
                const entitiesSorted = Object.keys(entityTotals).sort((a, b) => entityTotals[b] - entityTotals[a]);
                const topEntities = entitiesSorted.slice(0, TOP_N);
                const otherEntities = entitiesSorted.slice(TOP_N);
                const finalEntities = [...topEntities];
                if (otherEntities.length) finalEntities.push('Khác');

                let labels = [];
                let datasets = [];
                // Render grouped (side-by-side) bars for all entity types to
                // show each entity as its own column instead of stacking.
                const isGrouped = true;

                if (!hasMultipleDates) {
                    // Single-label fallback: render a single category (no empty padding bars)
                    const singleLabel = this.valueRangeLabel || 'Giá trị';
                    labels = [singleLabel];
                    // For a single-label result, each dataset provides one value
                    datasets = finalEntities.map((e, idx) => {
                        const color = this.generateDistinctColor(idx, finalEntities.length);
                        const val = e === 'Khác' ? otherEntities.reduce((sum, o) => sum + (entityTotals[o] || 0), 0) : (entityTotals[e] || 0);
                        return {
                            label: e,
                            data: [val],
                            backgroundColor: this.hexToRgba(color, 0.85),
                            borderColor: color,
                            borderWidth: 0,
                            // Grouped rendering (no stacking)
                            stack: undefined,
                            borderSkipped: 'bottom',
                            // Control bar width so many bars fit
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        };
                    });
                } else {
                    labels = dates.map(d => {
                        const [y, m, day] = d.split('-');
                        return `${day}/${m}`;
                    });
                    // Create stacked bar datasets: one dataset per entity, values per date
                    datasets = finalEntities.map((e, idx) => {
                        const color = this.generateDistinctColor(idx, finalEntities.length);
                        const points = dates.map(day => e === 'Khác' ? otherEntities.reduce((sum, o) => sum + ((agg[o] && agg[o][day]) || 0), 0) : ((agg[e] && agg[e][day]) || 0));
                        return {
                            label: e,
                            data: points,
                            backgroundColor: this.hexToRgba(color, 0.85),
                            borderColor: color,
                            borderWidth: 0,
                            stack: isGrouped ? undefined : 'stack1',
                            borderSkipped: isGrouped ? 'bottom' : false,
                            barPercentage: isGrouped ? 0.6 : undefined,
                            categoryPercentage: isGrouped ? 0.8 : undefined
                        };
                    });
                }

                const canvas = this.$refs.areaChartCanvas;
                if (!canvas) {
                    console.error('renderAreaChart aborted: canvas ref not found');
                    return;
                }
                const ctx = canvas.getContext && canvas.getContext('2d');
                if (!ctx) {
                    console.error('renderAreaChart aborted: cannot get 2d context');
                    return;
                }

                console.debug('renderAreaChart - creating chart with', datasets.length, 'datasets,', labels.length, 'labels');



                // Plugin: draw a black outline only around the hovered bar element
                // This implementation avoids mutating dataset border arrays which
                // can cause shared-style issues; instead it draws directly on the
                // canvas in `afterDraw` for the active element(s).
                const createAreaChartHighlightPlugin = () => {
                    let activeDataIdx = null;
                    let activeDatasetIdx = null;
                    let legendHighlightDataset = null; // dataset index when hovering legend

                    function clearActive() {
                        activeDataIdx = null;
                        activeDatasetIdx = null;
                    }

                    return {
                        id: 'areaChartHighlight',

                        // Called by legend hover to request highlighting entire dataset
                        applyHighlightDataset(chart, datasetIdx) {
                            legendHighlightDataset = datasetIdx;
                            // trigger a redraw to show outlines for whole dataset
                            try {
                                chart.draw();
                            } catch (e) {
                                /* ignore */
                            }
                        },
                        restoreHighlights(chart) {
                            legendHighlightDataset = null;
                            clearActive();
                            try {
                                chart.draw();
                            } catch (e) {
                                /* ignore */
                            }
                        },

                        afterEvent(chart, args) {
                            const evt = args.event;
                            const nativeEvt = evt && evt.native ? evt.native : evt;
                            // if pointer is outside chart area, clear
                            try {
                                const ca = chart.chartArea || {};
                                const ex = nativeEvt && (nativeEvt.x !== undefined ? nativeEvt.x : nativeEvt.offsetX);
                                const ey = nativeEvt && (nativeEvt.y !== undefined ? nativeEvt.y : nativeEvt.offsetY);
                                if (ex === undefined || ey === undefined || ca.left === undefined) {
                                    // keep as-is
                                } else if (ex < ca.left || ex > ca.right || ey < ca.top || ey > ca.bottom) {
                                    if (activeDataIdx !== null || activeDatasetIdx !== null) {
                                        clearActive();
                                        try {
                                            chart.draw();
                                        } catch (e) {
                                            /* ignore */
                                        }
                                    }
                                    return;
                                }
                            } catch (e) {
                                /* ignore */
                            }

                            // Use nearest+intersect to pick the exact element under cursor
                            const elems = chart.getElementsAtEventForMode(nativeEvt, 'nearest', {
                                intersect: true
                            }, false);
                            const elem = (elems && elems.length) ? elems[0] : null;
                            const newDataIdx = elem ? elem.index : null;
                            const newDatasetIdx = elem ? elem.datasetIndex : null;

                            if (newDataIdx === activeDataIdx && newDatasetIdx === activeDatasetIdx) return;
                            activeDataIdx = newDataIdx;
                            activeDatasetIdx = newDatasetIdx;
                            // redraw so afterDraw will render the outline
                            try {
                                chart.draw();
                            } catch (e) {
                                /* ignore */
                            }
                        },

                        // Draw outlines for active element or for legend-highlighted dataset
                        afterDraw(chart) {
                            const ctx = chart.ctx;
                            if (!ctx) return;

                            const drawOutlineForElement = (el) => {
                                if (!el) return;
                                const vm = el;
                                const x = vm.x !== undefined ? vm.x : 0;
                                const w = vm.width !== undefined ? vm.width : (vm._model && vm._model.width) || 0;
                                const y = vm.y !== undefined ? vm.y : 0;
                                const base = vm.base !== undefined ? vm.base : (vm._model && vm._model.base) || 0;
                                const left = x - (w / 2);
                                const top = Math.min(y, base);
                                const height = Math.abs(base - y);

                                ctx.save();
                                ctx.beginPath();
                                ctx.lineWidth = 0.5;
                                ctx.strokeStyle = '#000000';
                                const pad = 1.5;
                                ctx.rect(Math.round(left - pad) + 0.5, Math.round(top - pad) + 0.5, Math.round(w + pad * 2), Math.round(height + pad * 2));
                                ctx.stroke();
                                ctx.restore();
                            };

                            // Legend-requested: outline entire dataset
                            if (legendHighlightDataset !== null && legendHighlightDataset !== undefined) {
                                const meta = chart.getDatasetMeta(legendHighlightDataset);
                                if (meta && meta.data) meta.data.forEach(el => drawOutlineForElement(el));
                                return;
                            }

                            // Prefer Chart.js tooltip active elements (reliable when tooltip mode is nearest+intersect)
                            try {
                                const active = (chart.tooltip && typeof chart.tooltip.getActiveElements === 'function') ? chart.tooltip.getActiveElements() : (chart.getActiveElements ? chart.getActiveElements() : []);
                                if (active && active.length) {
                                    // draw only the first active element (should be exactly the hovered bar)
                                    const elInfo = active[0];
                                    // elInfo may be {datasetIndex, index, element}
                                    if (elInfo.element) {
                                        drawOutlineForElement(elInfo.element);
                                    } else if (elInfo.datasetIndex !== undefined && elInfo.index !== undefined) {
                                        const meta = chart.getDatasetMeta(elInfo.datasetIndex);
                                        if (meta && meta.data && meta.data[elInfo.index]) drawOutlineForElement(meta.data[elInfo.index]);
                                    }
                                }
                            } catch (e) {
                                // fallback: do nothing
                            }
                        }
                    };
                };

                const highlightPlugin = createAreaChartHighlightPlugin();
                const LEGEND_THRESHOLD = 8;

                this.areaChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        // Force non-stacked scales so datasets render as grouped (side-by-side) bars
                        scales: {
                            x: {
                                stacked: false
                            },
                            y: {
                                stacked: false,
                                beginAtZero: true
                            }
                        },
                        // Use nearest+intersect so interactions target the single hovered bar
                        interaction: {
                            mode: 'nearest',
                            intersect: true
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                display: datasets.length <= LEGEND_THRESHOLD,
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                },
                                onHover: function(event, legendItem, legend) {
                                    try {
                                        const chart = legend.chart;
                                        const idx = legendItem.datasetIndex;
                                        if (highlightPlugin && typeof highlightPlugin.applyHighlightDataset === 'function') {
                                            highlightPlugin.applyHighlightDataset(chart, idx);
                                        }
                                    } catch (e) {
                                        /* ignore */
                                    }
                                },
                                onLeave: function(event, legendItem, legend) {
                                    try {
                                        if (highlightPlugin && typeof highlightPlugin.restoreHighlights === 'function') {
                                            const chart = legend && legend.chart ? legend.chart : null;
                                            if (chart) highlightPlugin.restoreHighlights(chart);
                                        }
                                    } catch (e) {
                                        /* ignore */
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'nearest',
                                intersect: true,
                                callbacks: {
                                    label: (context) => {
                                        const isMoney = ['revenue', 'total_spent', 'sales_value', 'purchase_value', 'avg_order_value', 'total'].includes(this.filters.criteria);
                                        const value = context.parsed.y || context.raw || 0;
                                        const formattedValue = isMoney ? this.formatMoney(value) : new Intl.NumberFormat('vi-VN').format(value);
                                        return `${context.dataset.label}: ${formattedValue}`;
                                    },
                                    title: (items) => {
                                        if (items && items[0]) {
                                            return `Ngày: ${items[0].label}`;
                                        }
                                        return '';
                                    }
                                }
                            }
                        }
                    },
                    plugins: [highlightPlugin]
                });

                // If legend is hidden due to many datasets, toggle it when user hovers chart container
                try {
                    const wrapper = canvas && canvas.parentElement;
                    if (wrapper && datasets.length > LEGEND_THRESHOLD) {
                        wrapper.addEventListener('mouseenter', () => {
                            try {
                                this.areaChart.options.plugins.legend.display = true;
                                this.areaChart.update();
                            } catch (e) {}
                        });
                        wrapper.addEventListener('mouseleave', () => {
                            try {
                                this.areaChart.options.plugins.legend.display = false;
                                this.areaChart.update();
                            } catch (e) {}
                        });
                    }
                } catch (e) {
                    /* ignore */
                }
            },

            renderBarChart(labels, values, colors) {
                const canvas = this.$refs.barChartCanvas;
                if (!canvas) return;

                const ctx = canvas.getContext('2d');
                this.barChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: this.valueRangeLabel,
                            data: values,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#3B82F6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
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

            renderOrdersStackedBar() {
                // destroy existing
                if (this.ordersStackedBar) {
                    try {
                        this.ordersStackedBar.destroy();
                    } catch (e) {
                        /* ignore */
                    }
                    this.ordersStackedBar = null;
                }

                const filteredLen = this.getFilteredTableData().length;
                const totalLen = (this.tableData || []).length;
                try {
                    console.debug('renderOrdersStackedBar - filteredLen:', filteredLen, 'totalLen:', totalLen);
                } catch (e) {}

                const dataToUse = filteredLen > 0 ? this.getFilteredTableData() : this.tableData;
                if (!dataToUse || dataToUse.length === 0) {
                    try {
                        console.debug('renderOrdersStackedBar aborted: no dataToUse');
                    } catch (e) {}
                    return;
                }

                // Render EACH ORDER as separate dataset with white borders
                const dateCandidates = ['created_at', 'date', 'order_date', 'report_date', 'day', 'date_created', 'createdAt'];
                const statusCandidates = ['status', 'order_status', 'state'];
                const valueCandidates = ['total_amount', 'total', 'total_revenue', 'grand_total', 'total_sales_value', 'total_spent'];
                const orderIdCandidates = ['order_id', 'id', 'order_number', 'invoice_no', 'ma_don'];

                const sample = dataToUse.find(r => r && typeof r === 'object');
                if (!sample) {
                    console.error('renderOrdersStackedBar: no valid sample data found');
                    return;
                }

                let dateKey = dateCandidates.find(k => sample && Object.prototype.hasOwnProperty.call(sample, k));
                if (!dateKey) {
                    for (const k of Object.keys(sample || {})) {
                        const v = sample[k];
                        if (typeof v === 'string' && /\d{4}-\d{2}-\d{2}/.test(v)) {
                            dateKey = k;
                            break;
                        }
                        if (typeof v === 'string' && /\d{1,2}\/\d{1,2}\/\d{4}/.test(v)) {
                            dateKey = k;
                            break;
                        }
                    }
                }

                if (!dateKey) {
                    dateKey = Object.keys(sample).find(k => k.toLowerCase().includes('date') || k.toLowerCase().includes('time'));
                }

                if (!dateKey) {
                    console.warn('renderOrdersStackedBar: no date field found, using fallback');
                    dateKey = 'created_at';
                }

                let statusKey = statusCandidates.find(k => sample && Object.prototype.hasOwnProperty.call(sample, k));
                if (!statusKey) {
                    statusKey = Object.keys(sample).find(k => k.toLowerCase().includes('status')) || 'status';
                }

                let orderIdKey = orderIdCandidates.find(k => sample && Object.prototype.hasOwnProperty.call(sample, k));
                if (!orderIdKey) {
                    orderIdKey = Object.keys(sample).find(k => k.toLowerCase().includes('order') && k.toLowerCase().includes('id')) || 'order_id';
                }

                let valueKey = valueCandidates.find(k => sample && Object.prototype.hasOwnProperty.call(sample, k)) || this.getValueKeyForReportType('orders') || 'total_amount';

                console.debug('renderOrdersStackedBar keys detected:', {
                    dateKey,
                    statusKey,
                    orderIdKey,
                    valueKey
                });

                // Parse date helper
                const parseDate = raw => {
                    if (!raw && raw !== 0) return null;
                    if (raw instanceof Date && !isNaN(raw)) return raw;
                    if (typeof raw === 'number') {
                        const d = new Date(raw);
                        if (!isNaN(d) && d.getFullYear() > 1970) return d;
                        return null;
                    }
                    if (typeof raw === 'string') {
                        const s = raw.trim();
                        const iso = /^\d{4}-\d{2}-\d{2}/;
                        const dmy = /^\d{1,2}\/\d{1,2}\/\d{4}$/;
                        if (iso.test(s)) return new Date(s);
                        if (dmy.test(s)) {
                            const [dd, mm, yy] = s.split('/');
                            return new Date(Number(yy), Number(mm) - 1, Number(dd));
                        }
                        const tryD = new Date(s);
                        if (!isNaN(tryD) && tryD.getFullYear() > 1970) return tryD;
                    }
                    return null;
                };

                // Collect all orders with their details
                const ordersMap = new Map(); // orderId -> {status, dates: {date: value}}
                const dateSet = new Set();

                dataToUse.forEach((row, idx) => {
                    try {
                        const d = parseDate(dateKey ? row[dateKey] : row.created_at || row.date || row.order_date);
                        const day = d ? d.toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10);
                        dateSet.add(day);

                        const status = (statusKey && row[statusKey]) ? String(row[statusKey]) : 'Không rõ';
                        const orderId = (orderIdKey && row[orderIdKey]) ? String(row[orderIdKey]) : `#${idx + 1}`;
                        const val = parseFloat(row[valueKey]) || 0;

                        if (!ordersMap.has(orderId)) {
                            ordersMap.set(orderId, {
                                status,
                                dates: {}
                            });
                        }
                        ordersMap.get(orderId).dates[day] = val;
                    } catch (e) {
                        console.warn('Error processing row:', e);
                    }
                });

                const dates = Array.from(dateSet).sort();

                // Status colors
                const statusColors = {
                    'Chờ xử lý': '#FFA500',
                    'Đang xử lý': '#3B82F6',
                    'Đang giao': '#8B5CF6',
                    'Hoàn tất': '#10B981',
                    'Đã hủy': '#EF4444',
                    'Không rõ': '#94A3B8'
                };

                // Limit orders to prevent performance issues
                const MAX_ORDERS = 50;
                const ordersArray = Array.from(ordersMap.entries());
                const limitedOrders = ordersArray.slice(0, MAX_ORDERS);
                const hasMore = ordersArray.length > MAX_ORDERS;

                // Build datasets: one per order
                const datasets = [];
                limitedOrders.forEach(([orderId, orderInfo], idx) => {
                    const color = statusColors[orderInfo.status] || this.generateDistinctColor(idx, limitedOrders.length);
                    const data = dates.map(d => orderInfo.dates[d] || 0);

                    datasets.push({
                        label: `Đơn ${orderId}`,
                        data,
                        backgroundColor: this.hexToRgba(color, 0.8),
                        borderColor: '#FFFFFF',
                        borderWidth: 2,
                        hoverBackgroundColor: this.hexToRgba(color, 0.95),
                        hoverBorderColor: '#000000',
                        hoverBorderWidth: 3,
                        stack: 'orders',
                        orderStatus: orderInfo.status,
                        orderId: orderId
                    });
                });

                // Add "Khác" dataset if needed
                if (hasMore) {
                    const remainingOrders = ordersArray.slice(MAX_ORDERS);
                    const otherData = dates.map(d => {
                        return remainingOrders.reduce((sum, [, info]) => sum + (info.dates[d] || 0), 0);
                    });
                    datasets.push({
                        label: `Khác (${remainingOrders.length} đơn)`,
                        data: otherData,
                        backgroundColor: this.hexToRgba('#94A3B8', 0.6),
                        borderColor: '#FFFFFF',
                        borderWidth: 2,
                        hoverBackgroundColor: this.hexToRgba('#94A3B8', 0.85),
                        hoverBorderColor: '#000000',
                        hoverBorderWidth: 3,
                        stack: 'orders'
                    });
                }

                const labels = dates.map(d => {
                    const [y, m, day] = d.split('-');
                    return `${day}/${m}`;
                });

                const canvas = this.$refs.ordersStackedBarCanvas;
                if (!canvas) return;
                const ctx = canvas.getContext && canvas.getContext('2d');
                if (!ctx) return;

                this.ordersStackedBar = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    callback: (v) => this.formatMoney(v),
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                display: false, // Ẩn legend vì có quá nhiều đơn
                                labels: {
                                    boxWidth: 12,
                                    padding: 8,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'nearest',
                                intersect: true,
                                callbacks: {
                                    title: (items) => {
                                        if (items && items[0]) {
                                            const dataset = items[0].dataset;
                                            const date = items[0].label;
                                            return `${dataset.label}\nNgày: ${date}`;
                                        }
                                        return '';
                                    },
                                    label: (ctx) => {
                                        try {
                                            const value = this.formatMoney(ctx.parsed.y);
                                            const status = ctx.dataset.orderStatus || 'N/A';
                                            return [
                                                `Trạng thái: ${status}`,
                                                `Giá trị: ${value}`
                                            ];
                                        } catch (e) {
                                            return this.formatMoney(ctx.parsed.y);
                                        }
                                    },
                                    afterLabel: (ctx) => {
                                        // Calculate total for this day
                                        try {
                                            const dateIndex = ctx.dataIndex;
                                            const totalForDay = datasets.reduce((sum, ds) => {
                                                return sum + (ds.data[dateIndex] || 0);
                                            }, 0);
                                            return `\nTổng ngày: ${this.formatMoney(totalForDay)}`;
                                        } catch (e) {
                                            return '';
                                        }
                                    }
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#FFFFFF',
                                titleFont: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                bodyColor: '#FFFFFF',
                                bodyFont: {
                                    size: 12
                                },
                                borderColor: '#FFFFFF',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: true,
                                boxWidth: 10,
                                boxHeight: 10
                            }
                        },
                        onHover: (event, activeElements) => {
                            try {
                                if (event && event.native && event.native.target) {
                                    event.native.target.style.cursor = activeElements && activeElements.length > 0 ? 'pointer' : 'default';
                                }
                            } catch (e) {
                                /* ignore */
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

                if (this.areaChart) {
                    try {
                        this.areaChart.destroy();
                    } catch (e) {
                        /* ignore */
                    }
                    this.areaChart = null;
                }

                if (this.ordersStackedBar) {
                    try {
                        this.ordersStackedBar.destroy();
                    } catch (e) {
                        /* ignore */
                    }
                    this.ordersStackedBar = null;
                }

                // Destroy tất cả pie charts
                this.pieChartInstances.forEach(chart => {
                    if (chart) chart.destroy();
                });
                this.pieChartInstances = [];
                this.pieCharts = [];
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