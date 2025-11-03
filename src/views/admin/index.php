<?php
// views/admin/index.php  (Dashboard)

// Fallback demo
$orders_today = $orders_today ?? 0;
$revenue_today = $revenue_today ?? 0;
$customers_today = $customers_today ?? 0;
$low_stock = $low_stock ?? 0;
$recent_orders = $recent_orders ?? [];
$top_products = $top_products ?? [];
$low_stock_products = $low_stock_products ?? [];
$chart_data = $chart_data ?? [
    'labels' => ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
    'revenue' => [0, 0, 0, 0],
    'expense' => [0, 0, 0, 0],
    'total_revenue' => 0,
    'total_expense' => 0,
    'profit' => 0
];

require __DIR__ . '/partials/layout-start.php';
?>

<div x-data="dashboardPage()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#002975]">Dashboard</h1>
            <p class="text-slate-500 text-sm mt-1">Tổng quan hệ thống siêu thị MINIGO</p>
        </div>
        <div class="text-right">
            <div class="text-sm text-slate-500">Hôm nay</div>
            <div class="text-lg font-semibold text-[#002975]"><?= date('d/m/Y') ?></div>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-blue-100 text-sm font-medium">Đơn hàng hôm nay</div>
                    <div class="mt-2 text-3xl font-bold"><?= (int) $orders_today ?></div>
                    <div class="mt-2 text-xs text-blue-100">
                        <i class="fa-solid fa-cart-shopping"></i> Tổng đơn trong ngày
                    </div>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fa-solid fa-shopping-cart text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-green-100 text-sm font-medium">Doanh thu hôm nay</div>
                    <div class="mt-2 text-2xl font-bold">
                        <?php if ($revenue_today >= 1000000): ?>
                            <?= number_format((float) $revenue_today / 1000000, 1, ',', '.') ?>M đ
                        <?php else: ?>
                            <?= number_format((float) $revenue_today, 0, ',', '.') ?> đ
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-xs text-green-100">
                        <i class="fa-solid fa-money-bill-wave"></i> Đơn đã hoàn thành
                    </div>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fa-solid fa-dollar-sign text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-purple-100 text-sm font-medium">Khách hàng mới</div>
                    <div class="mt-2 text-3xl font-bold"><?= (int) $customers_today ?></div>
                    <div class="mt-2 text-xs text-purple-100">
                        <i class="fa-solid fa-user-plus"></i> Đăng ký hôm nay
                    </div>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fa-solid fa-users text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-orange-100 text-sm font-medium">Sắp hết hàng</div>
                    <div class="mt-2 text-3xl font-bold"><?= (int) $low_stock ?></div>
                    <div class="mt-2 text-xs text-orange-100">
                        <i class="fa-solid fa-exclamation-triangle"></i> Cần nhập thêm
                    </div>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fa-solid fa-boxes text-3xl"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Biểu đồ Thu Chi - Full Width -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-[#002975]">Biểu đồ Thu Chi</h2>
                <div class="flex items-center gap-4 mt-2">
                        <!-- Tổng Thu -->
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span class="text-sm text-gray-600">Tổng thu:
                                <strong class="text-green-600" x-text="formatMoney(chartData.total_revenue)"></strong>
                            </span>
                        </div>
                        <!-- Tổng Chi -->
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                            <span class="text-sm text-gray-600">Tổng chi:
                                <strong class="text-orange-600" x-text="formatMoney(chartData.total_expense)"></strong>
                            </span>
                        </div>
                        <!-- Lợi nhuận -->
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-arrow-trend-up text-blue-600"></i>
                            <span class="text-sm text-gray-600">Lợi nhuận:
                                <strong :class="chartData.profit >= 0 ? 'text-blue-600' : 'text-red-600'"
                                    x-text="formatMoney(chartData.profit)"></strong>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Filter -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Filter Type Dropdown -->
                    <div class="relative" @click.away="filterTypeOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[130px]"
                            @click="filterTypeOpen=!filterTypeOpen">
                            <span x-text="filterTypeLabel"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="filterTypeOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10">
                            <li @click="selectFilterType('month', 'Theo tháng')"
                                class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">
                                Theo tháng
                            </li>
                            <li @click="selectFilterType('quarter', 'Theo quý')"
                                class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">
                                Theo quý
                            </li>
                            <li @click="selectFilterType('year', 'Theo năm')"
                                class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">
                                Theo năm
                            </li>
                            <li @click="selectFilterType('custom', 'Tùy chỉnh')"
                                class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">
                                Tùy chọn
                            </li>
                        </ul>
                    </div>

                    <!-- Quarter Selector for quarter filter -->
                    <div class="relative" x-show="filterType === 'quarter'" @click.away="quarterOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]"
                            @click="quarterOpen=!quarterOpen">
                            <span x-text="'Quý ' + selectedQuarter"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="quarterOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10">
                            <template x-for="q in [1, 2, 3, 4]" :key="q">
                                <li @click="selectQuarter(q)"
                                    class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Quý ' + q"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Year Selector for quarter filter -->
                    <div class="relative" x-show="filterType === 'quarter'" @click.away="yearOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]"
                            @click="yearOpen=!yearOpen">
                            <span x-text="'Năm ' + filterYear"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="yearOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                            <template x-for="yr in yearPeriods" :key="yr">
                                <li @click="selectYear(yr)"
                                    class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="yr"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Month Selector for month filter -->
                    <div class="relative" x-show="filterType === 'month'" @click.away="periodOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[120px]"
                            @click="periodOpen=!periodOpen">
                            <span x-text="onlyMonths.find(m => m.value === selectedMonth).label"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="periodOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                            <template x-for="m in onlyMonths" :key="m.value">
                                <li @click="selectMonth(m.value, m.label)"
                                    class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="m.label"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Year Selector for month filter -->
                    <div class="relative" x-show="filterType === 'month'" @click.away="yearOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]"
                            @click="yearOpen=!yearOpen">
                            <span x-text="'Năm ' + filterYear"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="yearOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                            <template x-for="yr in yearPeriods" :key="yr">
                                <li @click="selectYear(yr)"
                                    class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="yr"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Year Selector for year filter -->
                    <div class="relative" x-show="filterType === 'year'" @click.away="yearOpen=false">
                        <button type="button"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]"
                            @click="yearOpen=!yearOpen">
                            <span x-text="'Năm ' + filterYear"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="yearOpen"
                            class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                            <template x-for="yr in yearPeriods" :key="yr">
                                <li @click="selectYear(yr)"
                                    class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="yr"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Custom Date Range for custom filter -->
                    <div x-show="filterType === 'custom'" class="flex items-center gap-2">
                        <div class="relative">
                            <input type="text" 
                                x-model="customFromDate"
                                class="flatpickr text-sm border border-gray-300 rounded-lg px-3 py-2 pr-10 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975]"
                                placeholder="Từ ngày"
                                autocomplete="off"
                                data-filter-key="custom"
                                data-filter-field="from">
                            <i class="fa-solid fa-calendar absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                        <span class="text-gray-500">→</span>
                        <div class="relative">
                            <input type="text" 
                                x-model="customToDate"
                                class="flatpickr text-sm border border-gray-300 rounded-lg px-3 py-2 pr-10 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975]"
                                placeholder="Đến ngày"
                                autocomplete="off"
                                data-filter-key="custom"
                                data-filter-field="to">
                            <i class="fa-solid fa-calendar absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <button type="button"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2"
                        @click="resetFilter()"
                        title="Trở lại hiện tại">
                        <i class="fa-solid fa-rotate-left text-[#002975]"></i>
                        <span class="text-[#002975] font-medium"></span>
                    </button>
                </div>
            </div>
            <canvas id="revenueChart" height="60"></canvas>
        </div>
    </div>

    <!-- 2 Biểu đồ Tròn -->
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <!-- Biểu đồ Doanh thu theo loại sản phẩm -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-[#002975] mb-4">Doanh thu theo Loại sản phẩm</h2>
            <div class="flex justify-center items-center" style="height: 500px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Biểu đồ Trạng thái Đơn hàng -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-[#002975] mb-4">Trạng thái Đơn hàng</h2>
            <div class="flex justify-center items-center" style="height: 350px; margin-top: 90px;">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?= $order_status['completed'] ?? 0 ?></div>
                    <div class="text-xs text-gray-600 mt-1">Hoàn tất</div>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600"><?= $order_status['pending'] ?? 0 ?></div>
                    <div class="text-xs text-gray-600 mt-1">Chờ xử lý</div>
                </div>
                <div class="p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600"><?= $order_status['cancelled'] ?? 0 ?></div>
                    <div class="text-xs text-gray-600 mt-1">Đã hủy</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Cột: Đơn hàng + Sản phẩm + Tồn kho -->
    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-[#002975] mb-4">Đơn hàng mới nhất</h2>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center text-slate-400 py-8">
                        <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                        <p class="text-sm">Chưa có đơn hàng nào</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="border-l-4 pl-3 py-2 hover:bg-gray-50 transition-colors
                            <?= $order['status'] === 'Hoàn tất' ? 'border-green-500' :
                                ($order['status'] === 'Chờ xử lý' ? 'border-orange-500' : 'border-red-500') ?>">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-semibold text-sm"><?= htmlspecialchars($order['code']) ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($order['customer_name'] ?? 'Khách lẻ') ?>
                                    </div>
                                    <div class="text-xs font-semibold text-[#002975] mt-1">
                                        <?= number_format($order['total_amount'], 0, ',', '.') ?> đ
                                    </div>
                                </div>
                                <span
                                    class="text-xs px-2 py-1 rounded-full
                                    <?= $order['status'] === 'Hoàn tất' ? 'bg-green-100 text-green-700' :
                                        ($order['status'] === 'Chờ xử lý' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-[#002975] mb-4">Sản phẩm bán chạy</h2>
            <div class="space-y-3">
                <?php if (empty($top_products)): ?>
                    <div class="text-center text-slate-400 py-8">
                        <i class="fa-solid fa-chart-line text-4xl mb-2"></i>
                        <p class="text-sm">Chưa có dữ liệu bán hàng</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div
                                class="flex-shrink-0 w-8 h-8 rounded-full bg-[#002975] text-white flex items-center justify-center font-bold">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="text-xs text-slate-500">
                                    Đã bán: <span
                                        class="font-semibold text-[#002975]"><?= (int) $product['total_sold'] ?></span>
                                    sản phẩm
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-green-600">
                                    <?= number_format($product['total_revenue'], 0, ',', '.') ?> đ
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-[#002975] mb-4">Cảnh báo tồn kho</h2>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php if (empty($low_stock_products)): ?>
                    <div class="text-center text-slate-400 py-8">
                        <i class="fa-solid fa-check-circle text-4xl mb-2 text-green-400"></i>
                        <p class="text-sm">Tất cả sản phẩm đều đủ hàng</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($low_stock_products as $product): ?>
                        <?php
                        $isOutOfStock = (int) $product['stock'] === 0;
                        $borderColor = $isOutOfStock ? 'border-red-500' : 'border-orange-500';
                        $bgColor = $isOutOfStock ? 'bg-red-50' : 'bg-orange-50';
                        $textColor = $isOutOfStock ? 'text-red-600' : 'text-orange-600';
                        $icon = $isOutOfStock ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
                        ?>
                        <div class="flex items-center gap-3 p-3 <?= $bgColor ?> rounded-lg border-l-4 <?= $borderColor ?>">
                            <div class="flex-shrink-0">
                                <i class="fa-solid <?= $icon ?> text-xl <?= $textColor ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="text-xs text-slate-500">
                                    SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>
                                    <span class="mx-1">•</span>
                                    An toàn: <?= (int) $product['safety_stock'] ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <?php if ($isOutOfStock): ?>
                                    <div class="text-sm font-bold text-red-600">Hết hàng</div>
                                    <div class="text-xs text-slate-500">0 / <?= (int) $product['safety_stock'] ?></div>
                                <?php else: ?>
                                    <div class="text-lg font-bold <?= $textColor ?>"><?= (int) $product['stock'] ?></div>
                                    <div class="text-xs text-slate-500">/ <?= (int) $product['safety_stock'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($low_stock_products)): ?>
                <div class="mt-4 pt-4 border-t">
                    <a href="/admin/stocks"
                        class="block text-center text-sm text-[#002975] hover:text-blue-700 font-semibold">
                        Xem tất cả tồn kho →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="text-center text-slate-500 mt-8 py-4">
        © <?= date('Y') ?> MiniGo - Hệ thống quản lý siêu thị mini
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function dashboardPage() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;
        const defaultPeriod = currentYear + '-' + String(currentMonth).padStart(2, '0');

        // Tạo danh sách tháng (3 năm gần nhất)
        const monthPeriods = [];
        const monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                            'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
        for (let y = currentYear; y >= currentYear - 2; y--) {
            for (let m = 12; m >= 1; m--) {
                if (y === currentYear && m > currentMonth) continue;
                const monthName = monthNames[m] + ' ' + y;
                const value = y + '-' + String(m).padStart(2, '0');
                monthPeriods.push({ value: value, label: monthName });
            }
        }

        // Tạo danh sách năm (10 năm gần nhất)
        const yearPeriods = Array.from({ length: 10 }, (_, i) => currentYear - i);

        return {
            chartData: <?= json_encode($chart_data) ?>,
            categoryData: <?= json_encode($category_revenue ?? []) ?>,
            orderStatusData: <?= json_encode($order_status ?? ['completed' => 0, 'pending' => 0, 'cancelled' => 0]) ?>,
            filterType: 'month',
            filterPeriod: defaultPeriod,
            selectedWeek: 0, // 0 = tất cả, 1-4 = tuần cụ thể
            selectedQuarter: 1, // Quý hiện tại
            chart: null,
            categoryChart: null,
            orderStatusChart: null,
            loading: false,
            selectedMonth: String(currentMonth).padStart(2, '0'), // Khởi tạo tháng hiện tại
            filterYear: currentYear,
            customFromDate: '',
            customToDate: '',

            // Filter controls
            filterTypeOpen: false,
            weekOpen: false,
            periodOpen: false,
            yearOpen: false,
            quarterOpen: false,
            filterTypeLabel: 'Theo tháng',
            weekLabel: 'Tất cả',
            periodLabel: monthNames[currentMonth] + ' ' + currentYear,
            yearLabel: currentYear,
            monthPeriods: monthPeriods,
            yearPeriods: yearPeriods,

            // Danh sách tháng (1–12)
            onlyMonths: Array.from({ length: 12 }, (_, i) => {
                const m = i + 1;
                return {
                    value: String(m).padStart(2, '0'),
                    label: 'Tháng ' + m
                };
            }),

            init() {
                // Tính quý hiện tại
                const now = new Date();
                const currentMonth = now.getMonth() + 1;
                this.selectedQuarter = Math.ceil(currentMonth / 3);
                
                // Khởi tạo custom date range (30 ngày gần nhất)
                const today = new Date();
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 30);
                this.customFromDate = thirtyDaysAgo.toISOString().split('T')[0];
                this.customToDate = today.toISOString().split('T')[0];
                
                this.$nextTick(() => {
                    this.initChart();
                    this.initCategoryChart();
                    this.initOrderStatusChart();
                    this.initCustomDatePickers();
                });
            },

            initCustomDatePickers() {
                if (!window.flatpickr) return;

                const fromInput = document.querySelector('input[data-filter-key="custom"][data-filter-field="from"]');
                const toInput = document.querySelector('input[data-filter-key="custom"][data-filter-field="to"]');

                if (fromInput && !fromInput._flatpickr) {
                    const fromPicker = flatpickr(fromInput, {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y",
                        locale: "vn",
                        maxDate: "today",
                        defaultDate: this.customFromDate,
                        onChange: (selectedDates, dateStr) => {
                            // Cập nhật giá trị Alpine.js
                            this.customFromDate = dateStr;
                            
                            if (selectedDates.length > 0 && toInput._flatpickr) {
                                toInput._flatpickr.set('minDate', selectedDates[0]);
                            }
                            
                            // Trigger changeFilter khi cả 2 ngày đều đã chọn
                            if (this.customFromDate && this.customToDate) {
                                this.changeFilter();
                            }
                        }
                    });
                }

                if (toInput && !toInput._flatpickr) {
                    const toPicker = flatpickr(toInput, {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y",
                        locale: "vn",
                        maxDate: "today",
                        defaultDate: this.customToDate,
                        onChange: (selectedDates, dateStr) => {
                            // Cập nhật giá trị Alpine.js
                            this.customToDate = dateStr;
                            
                            if (selectedDates.length > 0 && fromInput._flatpickr) {
                                fromInput._flatpickr.set('maxDate', selectedDates[0]);
                            }
                            
                            // Trigger changeFilter khi cả 2 ngày đều đã chọn
                            if (this.customFromDate && this.customToDate) {
                                this.changeFilter();
                            }
                        }
                    });
                }
            },

            selectFilterType(type, label) {
                if (this.loading) return;
                this.filterType = type;
                this.filterTypeLabel = label;
                this.filterTypeOpen = false;

                // Reset period khi chuyển filter type
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;
                const monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                                    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];

                if (type === 'month') {
                    // Reset về tháng và năm hiện tại
                    this.selectedMonth = String(currentMonth).padStart(2, '0');
                    this.filterYear = currentYear;
                } else if (type === 'quarter') {
                    // Reset về quý hiện tại
                    this.selectedQuarter = Math.ceil(currentMonth / 3);
                    this.filterYear = currentYear;
                } else if (type === 'year') {
                    // Reset về năm hiện tại
                    this.filterYear = currentYear;
                    this.yearLabel = currentYear;
                } else if (type === 'custom') {
                    // Giữ nguyên customFromDate và customToDate đã được init
                }

                this.changeFilter();
            },

            resetFilter() {
                if (this.loading) return;
                
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;
                
                // Reset về filter tháng, tháng hiện tại
                this.filterType = 'month';
                this.filterTypeLabel = 'Theo tháng';
                this.selectedMonth = String(currentMonth).padStart(2, '0');
                this.filterYear = currentYear;
                
                this.changeFilter();
            },

            // Chọn quý (khi filterType = 'quarter')
            selectQuarter(quarter) {
                if (this.loading) return;
                this.selectedQuarter = quarter;
                this.quarterOpen = false;
                this.changeFilter();
            },

            // Chọn tháng (khi filterType = 'month')
            selectMonth(value, label) {
                if (this.loading) return;
                this.selectedMonth = value;
                this.periodOpen = false;
                this.changeFilter();
            },

            selectPeriod(value, label) {
                if (this.loading) return;
                this.filterPeriod = value;
                this.periodLabel = label;
                this.periodOpen = false;
                this.changeFilter();
            },

            // Chọn năm (khi filterType = 'month' hoặc 'year')
            selectYear(year) {
                if (this.loading) return;
                this.filterYear = year;
                this.yearLabel = year;
                this.yearOpen = false;
                this.changeFilter();
            },

            initChart() {
                const ctx = document.getElementById('revenueChart');
                if (!ctx) return;

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.chartData.labels,
                        datasets: [
                            {
                                label: 'Thu (triệu đồng)',
                                data: this.chartData.revenue,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Chi (triệu đồng)',
                                data: this.chartData.expense,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#f97316',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 41, 117, 0.9)',
                                padding: 12,
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': ' + context.parsed.y + ' triệu đồng';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return value + 'M';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 10,
                                    callback: function(value, index, ticks) {
                                        const label = this.getLabelForValue(value);
                                        // Với tháng (31 ngày): hiển thị một số label để tránh rối
                                        // Ngày 1 giữ nguyên "Ngày 1", các ngày khác chỉ hiển thị số
                                        if (label.includes('Ngày')) {
                                            const dayNum = parseInt(label.replace('Ngày ', ''));
                                            // Chỉ hiển thị ngày 1, 5, 10, 15, 20, 25, 30
                                            if ([1, 5, 10, 15, 20, 25, 30].includes(dayNum)) {
                                                // Ngày 1 giữ nguyên, các ngày khác chỉ hiển thị số
                                                return dayNum === 1 ? label : dayNum.toString();
                                            }
                                            return '';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            initCategoryChart() {
                const ctx = document.getElementById('categoryChart');
                if (!ctx || this.categoryData.length === 0) return;

                // Helper function để format số tiền
                const formatRevenue = (value) => {
                    const num = Number(value);
                    if (isNaN(num)) return '0';
                    
                    if (num >= 1) {
                        return num.toFixed(1) + 'M';
                    } else if (num > 0) {
                        return (num * 1000).toFixed(0) + 'K';
                    }
                    return '0';
                };

                this.categoryChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: this.categoryData.map(c => c.name),
                        datasets: [{
                            data: this.categoryData.map(c => c.revenue),
                            backgroundColor: [
                                '#002975',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444',
                                '#8b5cf6'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                align: 'center',
                                labels: {
                                    padding: 12,
                                    font: {
                                        size: 13,
                                        family: 'Arial, sans-serif'
                                    },
                                    color: '#334155',
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                return {
                                                    text: label,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 41, 117, 0.9)',
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                        return context.label + ': ' + formatRevenue(value) + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: {
                                left: 20,
                                right: 20
                            }
                        }
                    }
                });
            },

            initOrderStatusChart() {
                const ctx = document.getElementById('orderStatusChart');
                if (!ctx) return;

                const data = [
                    this.orderStatusData.completed || 0,
                    this.orderStatusData.pending || 0,
                    this.orderStatusData.cancelled || 0
                ];

                this.orderStatusChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Hoàn tất', 'Chờ xử lý', 'Đã hủy'],
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#10b981',
                                '#f59e0b',
                                '#ef4444'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
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
                                backgroundColor: 'rgba(0, 41, 117, 0.9)',
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                        return context.label + ': ' + value + ' đơn (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            },

            async changeFilter() {
                if (this.loading) {
                    console.log('Already loading, skipping...');
                    return;
                }

                this.loading = true;

                try {
                    let period;
                    let url;
                    
                    if (this.filterType === 'month') {
                        // Ghép tháng + năm thành Y-m
                        period = this.filterYear + '-' + this.selectedMonth;
                        url = `/admin/api/dashboard/revenue-expense?type=${this.filterType}&period=${period}`;
                    } else if (this.filterType === 'quarter') {
                        // Gửi quý + năm
                        period = this.filterYear + '-Q' + this.selectedQuarter;
                        url = `/admin/api/dashboard/revenue-expense?type=${this.filterType}&period=${period}`;
                    } else if (this.filterType === 'year') {
                        // Gửi năm đã chọn
                        period = this.filterYear.toString();
                        url = `/admin/api/dashboard/revenue-expense?type=${this.filterType}&period=${period}`;
                    } else if (this.filterType === 'custom') {
                        // Gửi from_date và to_date
                        url = `/admin/api/dashboard/revenue-expense?type=${this.filterType}&from_date=${this.customFromDate}&to_date=${this.customToDate}`;
                    }

                    const res = await fetch(url);
                    if (res.ok) {
                        const newData = await res.json();

                        // Cập nhật từng property thay vì gán cả object
                        this.chartData.labels = newData.labels;
                        this.chartData.revenue = newData.revenue;
                        this.chartData.expense = newData.expense;
                        this.chartData.total_revenue = newData.total_revenue;
                        this.chartData.total_expense = newData.total_expense;
                        this.chartData.profit = newData.profit;

                        this.updateChart();
                    } else {
                        console.error('API Error:', res.status, await res.text());
                    }
                } catch (e) {
                    console.error('Error loading chart data:', e);
                } finally {
                    this.loading = false;
                }
            },

            updateChart() {
                if (!this.chart) return;

                // Destroy chart cũ và tạo lại
                this.chart.destroy();
                this.initChart();
            },

            formatMoney(value) {
                if (value >= 1000) {
                    return (value / 1000).toFixed(1) + ' tỷ';
                }
                return value.toFixed(1) + ' triệu';
            }
        };
    }
</script>

<?php
require __DIR__ . '/partials/layout-end.php';