<!-- Flatpickr CSS -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">

<?php
// views/admin/attendance/attendance.php
$month = $month ?? date('n');
$year = $year ?? date('Y');
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Quản lý chấm công</span>
</nav>

<div x-data="attendancePage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý chấm công</h1>
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Filter Mode Dropdown -->
            <div class="relative" @click.away="filterModeOpen=false">
                <button type="button" @click="filterModeOpen = !filterModeOpen"
                    class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[140px] justify-between">
                    <span x-text="filterModeLabel"></span>
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </button>
                <ul x-show="filterModeOpen"
                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                    <li @click="selectFilterMode('week', 'Theo tuần')"
                        class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">Theo tuần</li>
                    <li @click="selectFilterMode('month', 'Theo tháng')"
                        class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">Theo tháng</li>
                    <li @click="selectFilterMode('custom', 'Tùy chọn')"
                        class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">Tùy chọn</li>
                </ul>
            </div>

            <!-- Week Filter -->
            <template x-if="filterMode === 'week'">
                <div class="flex gap-2">

                    <!-- Week Dropdown -->
                    <div class="relative" @click.away="weekOpen=false">
                        <button type="button" @click="weekOpen = !weekOpen"
                            class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[160px] justify-between">
                            <span x-text="weekLabel"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <ul x-show="weekOpen"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template x-for="w in getWeeksInMonth(month, year)" :key="w.week">
                                <li @click="selectWeek(w.week, w.label)"
                                    class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Tuần ' + w.week + ' (' + w.label + ')'"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Month Dropdown -->
                    <div class="relative" @click.away="monthOpen=false">
                        <button type="button" @click="monthOpen = !monthOpen"
                            class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[110px] justify-between">
                            <span x-text="'Tháng ' + month"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <ul x-show="monthOpen"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template x-for="m in Array.from({length: 12}, (_, i) => i + 1)" :key="m">
                                <li @click="selectMonth(m)"
                                    class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Tháng ' + m"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Year Dropdown -->
                    <div class="relative" @click.away="yearOpen=false">
                        <button type="button" @click="yearOpen = !yearOpen"
                            class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[110px] justify-between">
                            <span x-text="'Năm ' + year"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <ul x-show="yearOpen"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template
                                x-for="y in Array.from({ length: new Date().getFullYear() - 2020 + 1 }, (_, i) => 2020 + i).reverse()"
                                :key="y">
                                <li @click="selectYear(y)"
                                    class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Năm ' + y"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>

            <!-- Month Filter -->
            <template x-if="filterMode === 'month'">
                <div class="flex gap-2">
                    <!-- Month Dropdown -->
                    <div class="relative" @click.away="monthOpen=false">
                        <button type="button" @click="monthOpen = !monthOpen"
                            class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[110px] justify-between">
                            <span x-text="'Tháng ' + month"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <ul x-show="monthOpen"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template x-for="m in Array.from({length: 12}, (_, i) => i + 1)" :key="m">
                                <li @click="selectMonth(m)"
                                    class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Tháng ' + m"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Year Dropdown -->
                    <div class="relative" @click.away="yearOpen=false">
                        <button type="button" @click="yearOpen = !yearOpen"
                            class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2 min-w-[110px] justify-between">
                            <span x-text="'Năm ' + year"></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <ul x-show="yearOpen"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template
                                x-for="y in Array.from({ length: new Date().getFullYear() - 2020 + 1 }, (_, i) => 2020 + i).reverse()"
                                :key="y">
                                <li @click="selectYear(y)"
                                    class="px-4 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                                    x-text="'Năm ' + y"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>

            <!-- Custom Date Range Filter -->
            <div x-show="filterMode === 'custom'" class="flex items-center gap-2">
                <div class="relative">
                    <label class="text-sm text-gray-600 mr-2">Từ:</label>
                    <input type="text" x-ref="customStartDatePicker" x-model="customStartDate"
                        class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] w-40"
                        placeholder="dd/mm/yyyy" readonly>
                </div>
                <span class="text-gray-500">→</span>
                <div class="relative">
                    <label class="text-sm text-gray-600 mr-2">Đến:</label>
                    <input type="text" x-ref="customEndDatePicker" x-model="customEndDate"
                        class="text-sm border border-gray-300 rounded-lg px-4 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] w-40"
                        placeholder="dd/mm/yyyy" readonly>
                </div>
            </div>

            <!-- Reset Button -->
            <button type="button"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2"
                @click="resetFilter()" title="Trở lại hiện tại">
                <i class="fa-solid fa-rotate-left text-[#002975]"></i>
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng lượt chấm công</div>
            <div class="text-2xl font-bold text-blue-600" x-text="items.length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đã hoàn thành</div>
            <div class="text-2xl font-bold text-green-600" x-text="countByStatus('present')"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Chưa checkout</div>
            <div class="text-2xl font-bold text-orange-600" x-text="countPending()"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng nhân viên</div>
            <div class="text-2xl font-bold text-purple-600" x-text="uniqueStaff()"></div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <template x-if="loading">
            <div class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-500">Đang tải dữ liệu...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div style="overflow-x:auto; max-width:100%;" class="pb-40">
                <table style="width:100%; min-width:1200px; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <?= textFilterPopover('id', 'ID') ?>
                            <?= textFilterPopover('full_name', 'Nhân viên') ?>
                            <?= selectFilterPopover('shift_name', 'Ca làm việc', [
                                '' => '--Tất cả--',
                                'Ca sáng' => 'Ca sáng',
                                'Ca chiều' => 'Ca chiều',
                            ]) ?>
                            <?= dateFilterPopover('attendance_date', 'Ngày') ?>
                            <?= datetimeFilterPopover('check_in_time', 'Giờ vào') ?>
                            <?= datetimeFilterPopover('check_out_time', 'Giờ ra') ?>
                            <?= numberFilterPopover('work_minutes', 'Tổng giờ làm (phút)') ?>
                            <?= selectFilterPopover('check_in_status', 'Trạng thái vào', [
                                '' => '--Tất cả--',
                                'Đúng giờ' => 'Đúng giờ',
                                'Muộn' => 'Muộn',
                                'Chưa chấm' => 'Chưa chấm'
                            ]) ?>
                            <?= selectFilterPopover('check_out_status', 'Trạng thái ra', [
                                '' => '--Tất cả--',
                                'Đúng giờ' => 'Đúng giờ',
                                'Sớm' => 'Sớm',
                                'Chưa chấm' => 'Chưa chấm'
                            ]) ?>
                            <!-- <th class="px-4 py-3 text-center">Thao tác</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in paginatedItems()" :key="item.id">
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3" x-text="item.id"></td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold" x-text="item.full_name"></div>
                                    <div class="text-sm text-gray-500" x-text="item.username"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold" x-text="item.shift_name"></div>
                                    <div class="text-sm text-gray-500">
                                        <span x-text="item.start_time"></span> - <span x-text="item.end_time"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right" x-text="formatDate(item.attendance_date)"></td>
                                <td class="px-4 py-3 text-right" x-text="formatDateTime(item.check_in_time)"></td>
                                <td class="px-4 py-3 text-right" x-text="formatDateTime(item.check_out_time) || '-'">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold text-blue-600"
                                        x-text="calculateWorkHours(item.check_in_time, item.check_out_time)"></span>
                                    <span class="text-xs text-gray-400 block" x-text="item.work_minutes ? '('+item.work_minutes+'p)' : ''"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="getCheckInStatusClass(item.check_in_status)">
                                        <span x-text="item.check_in_status || '—'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="getCheckOutStatusClass(item.check_out_status)">
                                        <span x-text="item.check_out_status || '—'"></span>
                                    </span>
                                </td>
                                <!-- <td class="px-4 py-3 text-center">
                                    <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-800 px-3 py-1 rounded hover:bg-red-50">
                                        Xóa
                                    </button>
                                </td> -->
                            </tr>
                        </template>
                        <tr x-show="!loading && filtered().length===0">
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                    <div class="text-lg text-slate-300">Trống</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-center mt-4 gap-6">
        <div class="text-sm text-slate-600">
            Tổng cộng <span x-text="filtered().length"></span> bản ghi
        </div>
        <div class="flex items-center gap-2">
            <button @click="currentPage--" :disabled="currentPage === 1"
                class="px-3 py-1 border rounded disabled:opacity-50">&lt;</button>
            <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages()"></span></span>
            <button @click="currentPage++" :disabled="currentPage === totalPages()"
                class="px-3 py-1 border rounded disabled:opacity-50">&gt;</button>
        </div>
    </div>

    <!-- Confirm Dialog -->
    <div x-show="confirmDialog.show" class="fixed inset-0 bg-black/40 z-[70] flex items-center justify-center p-5 mt-[-200px]"
        style="display: none;">
        <div class="bg-white w-full max-w-md rounded-xl shadow-lg" @click.outside="confirmDialog.show = false">
            <div class="px-5 py-4 border-b">
                <h3 class="text-xl font-bold text-[#002975]" x-text="confirmDialog.title"></h3>
            </div>
            <div class="p-5">
                <p class="text-gray-600" x-text="confirmDialog.message"></p>
            </div>
            <div class="px-5 py-4 border-t flex gap-2 justify-end">
                <button @click="confirmDialog.show = false; confirmDialog.onCancel()"
                    class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-600 hover:text-white">
                    Hủy
                </button>
                <button @click="confirmDialog.show = false; confirmDialog.onConfirm()"
                    class="px-4 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function attendancePage() {
        return {
            loading: true,
            items: [],
            allItems: [], // Lưu toàn bộ dữ liệu để filter client-side
            filterMode: 'month', // 'week', 'month', 'custom'
            month: <?= $month ?>,
            year: <?= $year ?>,
            currentWeek: 1,
            customStartDate: '',
            customEndDate: '',
            currentPage: 1,
            perPage: 20,

            confirmDialog: {
                show: false,
                title: '',
                message: '',
                onConfirm: () => {},
                onCancel: () => {}
            },

            showConfirm(title, message, onConfirm, onCancel = () => {}) {
                this.confirmDialog = {
                    show: true,
                    title,
                    message,
                    onConfirm,
                    onCancel
                };
            },

            // Dropdown states
            filterModeOpen: false,
            monthOpen: false,
            yearOpen: false,
            weekOpen: false,

            // Labels
            filterModeLabel: 'Theo tháng',
            weekLabel: 'Tuần 1',

            // Thêm vào object attendancePage()
            getWorkMinutes(checkIn, checkOut) {
                if (!checkIn || !checkOut) return null;
                const start = new Date(checkIn);
                const end = new Date(checkOut);
                const diffMs = end - start;
                if (diffMs < 0) return null;
                return Math.floor(diffMs / (1000 * 60)); // số phút
            },

            async init() {
                // Khởi tạo tuần hiện tại
                this.currentWeek = this.getCurrentWeek();
                const weeks = this.getWeeksInMonth(this.month, this.year);
                const currentWeek = weeks.find(w => w.week === this.currentWeek);
                this.weekLabel = currentWeek ? `Tuần ${currentWeek.week} (${currentWeek.label})` : 'Tuần 1';

                // Khởi tạo custom date range (từ đầu tháng đến hôm nay)
                const now = new Date();
                const startDate = new Date(now.getFullYear(), now.getMonth(), 1); // Ngày 1 của tháng
                const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()); // Hôm nay

                // Format dd/mm/yyyy cho Alpine
                this.customStartDate = this.formatDateDDMMYYYY(startDate);
                this.customEndDate = this.formatDateDDMMYYYY(endDate);

                // Khởi tạo Flatpickr
                await this.$nextTick();
                this.initFlatpickr();

                await this.loadData();
            },

            initFlatpickr() {
                if (!window.flatpickr) return;

                const startPicker = flatpickr(this.$refs.customStartDatePicker, {
                    dateFormat: "d/m/Y",
                    altInput: false,
                    locale: "vn",
                    maxDate: "today",
                    defaultDate: this.customStartDate,
                    onChange: (selectedDates, dateStr) => {
                        this.customStartDate = dateStr;
                        this.loadData();
                    }
                });

                const endPicker = flatpickr(this.$refs.customEndDatePicker, {
                    dateFormat: "d/m/Y",
                    altInput: false,
                    locale: "vn",
                    maxDate: "today",
                    defaultDate: this.customEndDate,
                    onChange: (selectedDates, dateStr) => {
                        this.customEndDate = dateStr;
                        this.loadData();
                    }
                });
            },

            formatDateDDMMYYYY(date) {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            },

            selectFilterMode(mode, label) {
                if (this.loading) return;
                this.filterMode = mode;
                this.filterModeLabel = label;
                this.filterModeOpen = false;

                if (mode === 'week') {
                    this.currentWeek = this.getCurrentWeek();
                }

                this.loadData();
            },

            selectMonth(m) {
                if (this.loading) return;
                this.month = m;
                this.monthOpen = false;

                if (this.filterMode === 'week') {
                    this.currentWeek = 1;
                    const weeks = this.getWeeksInMonth(this.month, this.year);
                    if (weeks.length > 0) {
                        this.weekLabel = `Tuần 1 (${weeks[0].label})`;
                    }
                }

                this.loadData();
            },

            selectYear(y) {
                if (this.loading) return;
                this.year = y;
                this.yearOpen = false;
                this.loadData();
            },

            selectWeek(weekNum, label) {
                if (this.loading) return;
                this.currentWeek = weekNum;
                this.weekLabel = `Tuần ${weekNum} (${label})`;
                this.weekOpen = false;
                this.filterItems();
            },

            resetFilter() {
                if (this.loading) return;

                const now = new Date();
                this.filterMode = 'month';
                this.filterModeLabel = 'Theo tháng';
                this.month = now.getMonth() + 1;
                this.year = now.getFullYear();

                this.loadData();
            },

            async loadData() {
                this.loading = true;
                try {
                    let url = '';

                    if (this.filterMode === 'month') {
                        // Load theo tháng
                        url = `/admin/api/attendance?month=${this.month}&year=${this.year}`;
                    } else if (this.filterMode === 'week') {
                        // Load cả tháng rồi filter theo tuần ở client
                        url = `/admin/api/attendance?month=${this.month}&year=${this.year}`;
                    } else if (this.filterMode === 'custom') {
                        // Load theo range của custom date - cần load TẤT CẢ dữ liệu trong khoảng
                        const startDate = this.parseDateDDMMYYYY(this.customStartDate);
                        const endDate = this.parseDateDDMMYYYY(this.customEndDate);

                        if (startDate && endDate) {
                            const startMonth = startDate.getMonth() + 1;
                            const startYear = startDate.getFullYear();
                            const endMonth = endDate.getMonth() + 1;
                            const endYear = endDate.getFullYear();

                            // Nếu cùng năm và cùng tháng
                            if (startYear === endYear && startMonth === endMonth) {
                                url = `/admin/api/attendance?month=${startMonth}&year=${startYear}`;
                            }
                            // Nếu cùng năm nhưng khác tháng - load theo năm
                            else if (startYear === endYear) {
                                // Load toàn bộ năm để đảm bảo có đủ dữ liệu
                                const promises = [];
                                for (let m = startMonth; m <= endMonth; m++) {
                                    promises.push(
                                        fetch(`/admin/api/attendance?month=${m}&year=${startYear}`)
                                            .then(res => res.json())
                                            .then(data => data.items || [])
                                    );
                                }
                                const results = await Promise.all(promises);
                                this.allItems = results.flat(); // Gộp tất cả dữ liệu
                                this.filterItems();
                                this.loading = false;
                                return;
                            }
                            // Nếu khác năm - load nhiều tháng từ start đến end
                            else {
                                const promises = [];
                                let currentDate = new Date(startYear, startMonth - 1, 1);
                                const endDateMonth = new Date(endYear, endMonth - 1, 1);

                                while (currentDate <= endDateMonth) {
                                    const m = currentDate.getMonth() + 1;
                                    const y = currentDate.getFullYear();
                                    promises.push(
                                        fetch(`/admin/api/attendance?month=${m}&year=${y}`)
                                            .then(res => res.json())
                                            .then(data => data.items || [])
                                    );
                                    currentDate.setMonth(currentDate.getMonth() + 1);
                                }

                                const results = await Promise.all(promises);
                                this.allItems = results.flat(); // Gộp tất cả dữ liệu
                                this.filterItems();
                                this.loading = false;
                                return;
                            }
                        } else {
                            // Fallback: load tháng hiện tại
                            url = `/admin/api/attendance?month=${this.month}&year=${this.year}`;
                        }
                    }

                    const res = await fetch(url);
                    const data = await res.json();
                    this.allItems = data.items || [];

                    console.log('⏰ Loaded attendance data:', this.allItems.length, 'records');
                    console.log('🔍 Filter mode:', this.filterMode, '| Month:', this.month, '| Year:', this.year);

                    // Filter dữ liệu theo mode
                    this.filterItems();

                    // Sau khi load xong dữ liệu, tính work_minutes cho từng item
                    this.items = this.items.map(item => ({
                        ...item,
                        work_minutes: this.getWorkMinutes(item.check_in_time, item.check_out_time)
                    }));
                } catch (err) {
                    console.error('Lỗi tải dữ liệu:', err);
                    this.items = [];
                } finally {
                    this.loading = false;
                }
            },

            filterItems() {
                if (this.filterMode === 'month') {
                    // Hiển thị toàn bộ tháng
                    this.items = this.allItems;
                    console.log('✅ Filter MONTH:', this.items.length, 'records');
                } else if (this.filterMode === 'week') {
                    // Filter theo tuần trong tháng
                    const weekRange = this.getWeekDateRange(this.currentWeek, this.month, this.year);
                    this.items = this.allItems.filter(item => {
                        const itemDate = new Date(item.attendance_date);
                        return itemDate >= weekRange.start && itemDate <= weekRange.end;
                    });
                    console.log('✅ Filter WEEK:', this.items.length, 'records from', weekRange.start, 'to', weekRange.end);
                } else if (this.filterMode === 'custom') {
                    // Filter theo custom date range (format dd/mm/yyyy)
                    const start = this.parseDateDDMMYYYY(this.customStartDate);
                    const end = this.parseDateDDMMYYYY(this.customEndDate);

                    if (start && end) {
                        // Set time to start of day for start date and end of day for end date
                        start.setHours(0, 0, 0, 0);
                        end.setHours(23, 59, 59, 999);

                        this.items = this.allItems.filter(item => {
                            const itemDate = new Date(item.attendance_date);
                            itemDate.setHours(0, 0, 0, 0);
                            return itemDate >= start && itemDate <= end;
                        });

                        console.log('Filter custom:', {
                            start: start.toLocaleDateString('vi-VN'),
                            end: end.toLocaleDateString('vi-VN'),
                            allItemsCount: this.allItems.length,
                            filteredCount: this.items.length
                        });
                    } else {
                        console.error('Invalid date range:', { start: this.customStartDate, end: this.customEndDate });
                        this.items = this.allItems;
                    }
                }

                // Reset về trang 1
                this.currentPage = 1;
            },

            parseDateDDMMYYYY(dateStr) {
                if (!dateStr) return null;
                const parts = dateStr.split('/');
                if (parts.length !== 3) return null;
                const day = parseInt(parts[0]);
                const month = parseInt(parts[1]) - 1;
                const year = parseInt(parts[2]);
                return new Date(year, month, day);
            },

            getCurrentWeek() {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const currentDay = now.getDate();
                return Math.ceil((currentDay + firstDay.getDay()) / 7);
            },

            getWeeksInMonth(month, year) {
                const firstDay = new Date(year, month - 1, 1);
                const lastDay = new Date(year, month, 0);
                const daysInMonth = lastDay.getDate();
                const weeks = [];

                let weekNum = 1;
                let startDay = 1;

                while (startDay <= daysInMonth) {
                    const weekStart = new Date(year, month - 1, startDay);
                    const endDay = Math.min(startDay + 6, daysInMonth);
                    const weekEnd = new Date(year, month - 1, endDay);

                    weeks.push({
                        week: weekNum,
                        label: `${startDay}/${month} - ${endDay}/${month}`,
                        start: weekStart,
                        end: weekEnd
                    });

                    startDay += 7;
                    weekNum++;
                }

                return weeks;
            },

            getWeekDateRange(weekNum, month, year) {
                const weeks = this.getWeeksInMonth(month, year);
                return weeks[weekNum - 1] || { start: new Date(), end: new Date() };
            },

            async deleteItem(id) {
                const item = this.items.find(i => i.id == id);
                const staffName = item ? item.full_name : 'chấm công này';
                
                this.showConfirm(
                    'Xác nhận xóa',
                    `Bạn có chắc chắn muốn xóa chấm công của "${staffName}"?`,
                    async () => {
                        try {
                            const res = await fetch(`/admin/api/attendance/${id}`, { method: 'DELETE' });
                            if (res.ok) {
                                alert('Xóa thành công');
                                await this.loadData();
                            }
                        } catch (err) {
                            alert('Lỗi xóa dữ liệu');
                        }
                    }
                );
            },

            countByStatus(status) {
                return this.items.filter(i => i.status === status && i.check_out_time).length;
            },

            countPending() {
                return this.items.filter(i => i.check_in_time && !i.check_out_time).length;
            },

            uniqueStaff() {
                return new Set(this.items.map(i => i.user_id)).size;
            },

            getCheckInStatusClass(status) {
                if (!status) return 'bg-gray-100 text-gray-600';

                // Trạng thái vào: "Đúng giờ" (xanh) hoặc "Muộn" (đỏ)
                if (status === 'Đúng giờ') return 'bg-green-100 text-green-700';
                if (status === 'Muộn') return 'bg-red-100 text-red-700';

                return 'bg-gray-100 text-gray-600';
            },

            getCheckOutStatusClass(status) {
                if (!status) return 'bg-gray-100 text-gray-600';

                // Trạng thái ra: "Đúng giờ" (xanh) hoặc "Sớm" (vàng)
                if (status === 'Đúng giờ') return 'bg-green-100 text-green-700';
                if (status === 'Sớm') return 'bg-yellow-100 text-yellow-700';

                return 'bg-gray-100 text-gray-600';
            },

            getStatusClass(item) {
                if (item.check_out_time) return 'bg-green-100 text-green-800';
                return 'bg-orange-100 text-orange-800';
            },

            getStatusText(item) {
                // Status tiếng Việt từ database: 'Có mặt', 'Vắng mặt', 'Đi muộn', 'Về sớm'
                const statusMap = {
                    'Có mặt': 'Có mặt',
                    'Vắng mặt': 'Vắng mặt',
                    'Đi muộn': 'Đi muộn',
                    'Về sớm': 'Về sớm'
                };
                return statusMap[item.status] || item.status || '—';
            },

            paginatedItems() {
                const filteredData = this.filtered();
                const start = (this.currentPage - 1) * this.perPage;
                return filteredData.slice(start, start + this.perPage);
            },

            totalPages() {
                const filteredData = this.filtered();
                return Math.ceil(filteredData.length / this.perPage) || 1;
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('vi-VN');
            },

            formatDateTime(dateTimeStr) {
                if (!dateTimeStr) return '';
                const d = new Date(dateTimeStr);
                return d.toLocaleString('vi-VN');
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount || 0);
            },

            calculateWorkHours(checkIn, checkOut) {
                if (!checkIn || !checkOut) return '—';

                const start = new Date(checkIn);
                const end = new Date(checkOut);

                // Tính số milliseconds chênh lệch
                const diffMs = end - start;

                // Nếu checkout trước checkin (lỗi dữ liệu)
                if (diffMs < 0) return 'Lỗi';

                // Chuyển sang giờ và phút
                const hours = Math.floor(diffMs / (1000 * 60 * 60));
                const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                // Format: 8h 30m hoặc 8h nếu không có phút
                if (minutes === 0) {
                    return `${hours}h`;
                }
                return `${hours}h ${minutes}m`;
            },

            // ===== FILTERS =====
            openFilter: {
                id: false, full_name: false,
                shift_name: false,
                attendance_date: false,
                check_in_time: false,
                check_out_time: false,
                check_in_status: false,
                check_out_status: false,
                work_minutes: false,
            },

            filters: {
                id: '',
                full_name: '',
                shift_name: '',
                attendance_date_type: '', attendance_date_value: '', attendance_date_from: '', attendance_date_to: '',
                check_in_time_type: '', check_in_time_value: '', check_in_time_from: '', check_in_time_to: '',
                check_out_time_type: '', check_out_time_value: '', check_out_time_from: '', check_out_time_to: '',
                check_in_status: '',
                check_out_status: '',
                work_minutes_type: '',
                work_minutes_value: '',
                work_minutes_from: '',
                work_minutes_to: ''
            },

            // -------------------------------------------
            // Hàm lọc tổng quát, hỗ trợ text / number / date
            // (Giữ nguyên giống mẫu chuẩn bạn gửi)
            // -------------------------------------------
            applyFilter(val, type, { value, from, to, dataType }) {
                if (val == null) return false;

                // ---------------- TEXT ----------------
                if (dataType === 'text') {
                    const hasAccent = (s) => /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(s);

                    const normalize = (str) => String(str || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // xóa dấu
                        .trim();

                    const raw = String(val || '').toLowerCase();
                    const str = normalize(val);
                    const query = String(value || '').toLowerCase();
                    const queryNoAccent = normalize(value);

                    if (!query) return true;

                    if (type === 'eq') return hasAccent(query)
                        ? raw === query
                        : str === queryNoAccent;

                    if (type === 'contains' || type === 'like') {
                        if (hasAccent(query)) {
                            // Có dấu → tìm chính xác theo dấu
                            return raw.includes(query);
                        } else {
                            // Không dấu → tìm theo không dấu
                            return str.includes(queryNoAccent);
                        }
                    }

                    return true;
                }

                // ---------------- DATE ----------------
                if (dataType === 'date') {
                    if (!val) return false;
                    const d = new Date(val);
                    const v = value ? new Date(value) : null;
                    const f = from ? new Date(from) : null;
                    const t = to ? new Date(to) : null;

                    if (type === 'eq') return v ? d.toDateString() === v.toDateString() : true;
                    if (type === 'lt') return v ? d < v : true;
                    if (type === 'gt') {
                        if (!v) return true;
                        // So sánh chỉ theo ngày, bỏ qua giờ phút giây
                        return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
                    }
                    if (type === 'lte') {
                        if (!v) return true;
                        const nextDay = new Date(v);
                        nextDay.setDate(v.getDate() + 1);
                        return d < nextDay; // <= nghĩa là nhỏ hơn ngày kế tiếp
                    }
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                // ---------------- DATETIME ----------------
                if (dataType === 'datetime') {
                    if (!val) return false;
                    const d = new Date(val);
                    const v = value ? new Date(value) : null;
                    const f = from ? new Date(from) : null;
                    const t = to ? new Date(to) : null;

                    // So sánh datetime bao gồm cả giờ phút giây
                    if (type === 'eq') {
                        if (!v) return true;
                        // So sánh chính xác đến phút
                        return d.getFullYear() === v.getFullYear() &&
                               d.getMonth() === v.getMonth() &&
                               d.getDate() === v.getDate() &&
                               d.getHours() === v.getHours() &&
                               d.getMinutes() === v.getMinutes();
                    }
                    if (type === 'lt') return v ? d < v : true;
                    if (type === 'gt') return v ? d > v : true;
                    if (type === 'lte') return v ? d <= v : true;
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                return true;
            },

            // -------------------------------------------
            // Lọc dữ liệu cho bảng
            // -------------------------------------------
            filtered() {
                let data = this.items;

                // --- Lọc theo chuỗi ---
                ['id', 'full_name'].forEach(key => {
                    if (this.filters[key]) {
                        const field = key.endsWith('_by') ? key + '_name' : key; // ví dụ: created_by → created_by_name
                        data = data.filter(o =>
                            this.applyFilter(o[field], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo ngày (chỉ attendance_date) ---
                if (this.filters.attendance_date_type) {
                    data = data.filter(o =>
                        this.applyFilter(o.attendance_date, this.filters.attendance_date_type, {
                            value: this.filters.attendance_date_value,
                            from: this.filters.attendance_date_from,
                            to: this.filters.attendance_date_to,
                            dataType: 'date'
                        })
                    );
                }

                // --- Lọc theo datetime (check_in_time, check_out_time) ---
                ['check_in_time', 'check_out_time'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'datetime'
                            })
                        );
                    }
                });

                // --- Lọc theo select ---
                ['check_in_status', 'check_out_status', 'shift_name'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'eq', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo tổng giờ làm (phút) ---
                if (this.filters.work_minutes_type) {
                    data = data.filter(o => {
                        const val = o.work_minutes ?? this.getWorkMinutes(o.check_in_time, o.check_out_time);
                        if (val == null) return false;
                        
                        const type = this.filters.work_minutes_type;
                        const value = parseFloat(this.filters.work_minutes_value);
                        const from = parseFloat(this.filters.work_minutes_from);
                        const to = parseFloat(this.filters.work_minutes_to);
                        
                        if (type === 'eq') return value ? val === value : true;
                        if (type === 'lt') return value ? val < value : true;
                        if (type === 'gt') return value ? val > value : true;
                        if (type === 'lte') return value ? val <= value : true;
                        if (type === 'gte') return value ? val >= value : true;
                        if (type === 'between') return from && to ? val >= from && val <= to : true;
                        
                        return true;
                    });
                }

                return data;
            },

            // -------------------------------------------
            // Bật/tắt/reset filter
            // -------------------------------------------
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['check_in_time', 'check_out_time', 'attendance_date'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (key === 'work_minutes') {
                    this.filters.work_minutes_type = '';
                    this.filters.work_minutes_value = '';
                    this.filters.work_minutes_from = '';
                    this.filters.work_minutes_to = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>