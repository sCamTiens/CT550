<!-- Flatpickr CSS -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

<?php
// views/admin/payroll/payroll.php
$month = $month ?? date('n');
$year = $year ?? date('Y');
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Quản lý bảng lương</span>
</nav>

<div x-data="payrollPage()" x-init="init()">

    <!-- Confirm Dialog -->
    <div x-show="confirmDialog.show"
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-5 mt-[-200px]" style="display: none;">
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

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý bảng lương</h1>
        <div class="flex gap-2 items-center">
            <!-- Filter Type Dropdown -->
            <div class="relative" @click.away="filterTypeOpen=false">
                <button type="button" @click="filterTypeOpen=!filterTypeOpen"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[130px]">
                    <span x-text="filterTypeLabel"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul x-show="filterTypeOpen" class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10">
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
                    <li @click="selectFilterType('custom', 'Tùy chọn')"
                        class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm">
                        Tùy chọn
                    </li>
                </ul>
            </div>

            <!-- Quarter Selector for quarter filter -->
            <div class="relative" x-show="filterType === 'quarter'" @click.away="quarterOpen=false">
                <button type="button" @click="quarterOpen=!quarterOpen"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]">
                    <span x-text="'Quý ' + selectedQuarter"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul x-show="quarterOpen" class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10">
                    <template x-for="q in [1,2,3,4]" :key="q">
                        <li @click="selectQuarter(q)"
                            class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                            x-text="'Quý ' + q"></li>
                    </template>
                </ul>
            </div>
            
            <!-- Month Selector for month filter -->
            <div class="relative" x-show="filterType === 'month'" @click.away="monthOpen=false">
                <button type="button" @click="monthOpen=!monthOpen"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[120px]">
                    <span x-text="'Tháng ' + selectedMonth"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul x-show="monthOpen"
                    class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                    <template x-for="m in [1,2,3,4,5,6,7,8,9,10,11,12]" :key="m">
                        <li @click="selectMonth(m)"
                            class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm"
                            x-text="'Tháng ' + m"></li>
                    </template>
                </ul>
            </div>

            <!-- Year Selector -->
            <div class="relative" x-show="filterType === 'quarter' || filterType === 'year' || filterType === 'month'"
                @click.away="yearOpen=false">
                <button type="button" @click="yearOpen=!yearOpen"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#002975] flex justify-between items-center min-w-[100px]">
                    <span x-text="'Năm ' + filterYear"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul x-show="yearOpen"
                    class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10 max-h-60 overflow-y-auto">
                    <template x-for="y in yearPeriods" :key="y">
                        <li @click="selectYear(y)"
                            class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-sm" x-text="y">
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Custom Date Range for custom filter -->
            <div x-show="filterType === 'custom'" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" x-model="customFromDate" data-filter-key="custom" data-filter-field="from"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975]"
                        placeholder="Từ ngày" readonly>
                </div>
                <span class="text-gray-500">→</span>
                <div class="relative">
                    <input type="text" x-model="customToDate" data-filter-key="custom" data-filter-field="to"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#002975]"
                        placeholder="Đến ngày" readonly>
                </div>
            </div>

            <!-- Reset Button -->
            <button type="button"
                class="text-base border border-gray-300 rounded-lg px-4 py-3 text-[#002975] hover:bg-[#002975] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#002975] flex items-center gap-2"
                @click="resetTimeFilter()" title="Trở lại tháng hiện tại">
                <i class="fa-solid fa-rotate-left"></i>
            </button>

            <!-- Nút xuất Excel -->
            <button @click="exportExcel()"
                class="px-4 py-2 text-[#002975] border border-[#002975] rounded-lg hover:bg-[#002975] hover:text-white font-semibold flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i>
                Xuất Excel
            </button>

            <!-- Nút tính lương -->
            <button @click="calculateAll()" x-show="!(items.length > 0 && items.length === countByStatus('Đã trả'))"
                class="px-4 py-2 text-[#002975] border border-[#002975] rounded-lg hover:bg-[#002975] hover:text-white font-semibold">
                Tính lương tất cả
            </button>

            <!-- Nút duyệt tất cả -->
            <button @click="approveAll()" x-show="countByStatus('Nháp') > 0"
                class="px-4 py-2 text-green-600 border border-green-600 rounded-lg hover:bg-green-600 hover:text-white font-semibold">
                Duyệt tất cả
            </button>

            <!-- Nút trả tất cả -->
            <button @click="payAll()" x-show="countByStatus('Đã duyệt') > 0"
                class="px-4 py-2 text-purple-600 border border-purple-600 rounded-lg hover:bg-purple-600 hover:text-white font-semibold">
                Trả tất cả
            </button>

            <!-- Nút xóa tất cả -->
            <button @click="deleteAll()" x-show="items.length > 0 && !(items.length === countByStatus('Đã trả'))"
                class="px-4 py-2 text-red-600 border border-red-600 rounded-lg hover:bg-red-600 hover:text-white font-semibold">
                Xóa tất cả
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng bảng lương</div>
            <div class="text-2xl font-bold text-blue-600" x-text="filtered().length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng tiền lương</div>
            <div class="text-lg font-bold text-green-600" x-text="formatMoney(filtered().reduce((sum, i) => sum + parseFloat(i.total_salary || 0), 0))"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đã duyệt</div>
            <div class="text-2xl font-bold text-purple-600" x-text="filtered().filter(i => i.status === 'Đã duyệt').length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đã thanh toán</div>
            <div class="text-2xl font-bold text-orange-600" x-text="filtered().filter(i => i.status === 'Đã trả').length"></div>
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
                <table style="width:120%; min-width:1200px; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-center">Thao tác</th>
                            <?= textFilterPopover('full_name', 'Nhân viên') ?>
                            <?= selectFilterPopover('staff_role', 'Vai trò', [
                                '' => '--Tất cả--',
                                'Nhân viên bán hàng' => 'Nhân viên bán hàng',
                                'Kho' => 'Kho',
                                'Hỗ trợ trực tuyến' => 'Hỗ trợ trực tuyến'
                            ]) ?>
                            <?= numberFilterPopover('total_shifts_worked', 'Số ca làm') ?>
                            <?= numberFilterPopover('required_shifts', 'Số ngày yêu cầu') ?>
                            <?= numberFilterPopover('base_salary', 'Lương cơ bản') ?>
                            <?= numberFilterPopover('actual_salary', 'Lương thực tế') ?>
                            <?= numberFilterPopover('bonus', 'Thưởng') ?>
                            <?= numberFilterPopover('deduction', 'Phạt') ?>
                            <?= numberFilterPopover('late_deduction', 'Phạt đi muộn') ?>
                            <?= numberFilterPopover('total_salary', 'Tổng lương') ?>
                            <?= selectFilterPopover('status', 'Trạng thái', [
                                '' => '--Tất cả--',
                                'Nháp' => 'Nháp',
                                'Đã duyệt' => 'Đã duyệt',
                                'Đã trả' => 'Đã trả'
                            ]) ?>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in filtered()" :key="item.id">
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 text-center">
                                    <div class="flex gap-2 justify-center" x-data>
                                        <template x-if="item.status !== 'Đã trả'">
                                            <button @click="editBonusDeduction(item)"
                                                class="text-[#002975] px-2 py-1 rounded hover:bg-gray-100"
                                                title="Sửa thưởng/phạt">
                                                <!-- Icon tiền -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4 1.343 4 3-1.79 3-4 3m0-15v2m0 13v2" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template x-if="item.status === 'Nháp'">
                                            <button @click="approve(item.id)"
                                                class="text-[#002975] px-2 py-1 rounded hover:bg-gray-100"
                                                title="Duyệt">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template x-if="item.status === 'Đã duyệt'">
                                            <button @click="pay(item)"
                                                class="text-[#002975] px-2 py-1 rounded hover:bg-gray-100"
                                                title="Trả lương (tạo phiếu chi)">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-4h4v4h-4v-4z" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template x-if="item.status !== 'Đã trả'">
                                            <button @click="deleteItem(item.id)"
                                                class="text-[#002975] px-2 py-1 rounded hover:bg-gray-100" title="Xóa">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 
                        00-1-1h-4a1 1 0-00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </template>

                                        <!-- Nếu không có nút nào hiển thị -->
                                        <template x-if="item.status === 'Đã trả'">
                                            <span class="text-gray-400">—</span>
                                        </template>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold" x-text="item.full_name"></div>
                                    <div class="text-sm text-gray-500" x-text="item.username"></div>
                                </td>
                                <td class="px-4 py-3 text-center" x-text="item.staff_role"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold" x-text="item.total_shifts_worked"></span>
                                </td>
                                <td class="px-4 py-3 text-center" x-text="item.required_shifts"></td>
                                <td class="px-4 py-3 text-right" x-text="formatMoney(item.base_salary)"></td>
                                <td class="px-4 py-3 text-right" x-text="formatMoney(item.actual_salary)"></td>
                                <td class="px-4 py-3 text-right text-green-600" x-text="formatMoney(item.bonus)"></td>
                                <td class="px-4 py-3 text-right text-red-600" x-text="formatMoney(item.deduction)"></td>
                                <td class="px-4 py-3 text-right text-orange-600"
                                    x-text="formatMoney(item.late_deduction || 0)"></td>
                                <td class="px-4 py-3 text-right font-bold text-lg text-green-700"
                                    x-text="formatMoney(item.total_salary)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="getStatusClass(item.status)">
                                        <span x-text="getStatusText(item.status)"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="filtered().length === 0">
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                    Chưa có dữ liệu bảng lương. Nhấn "Tính lương tất cả" để tạo bảng lương.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal Edit Bonus/Deduction -->
    <div x-show="showEditModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        style="display: none;">
        <div class="bg-white w-full max-w-md rounded-xl shadow-lg" @click.outside="showEditModal = false">
            <div class="px-5 py-3 border-b">
                <h3 class="text-xl font-bold text-gray-800">Sửa thưởng/phạt</h3>
            </div>
            <form @submit.prevent="submitBonusDeduction()" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nhân viên</label>
                    <input type="text" :value="editForm.full_name" disabled
                        class="border rounded px-3 py-2 w-full bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Thưởng (VNĐ)</label>
                    <input type="text" :value="formatNumberInput(editForm.bonus)"
                        @input="editForm.bonus = parseNumberInput($event.target.value)"
                        @blur="$event.target.value = formatNumberInput(editForm.bonus)"
                        class="border rounded px-3 py-2 w-full" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Phạt/Khấu trừ (VNĐ)</label>
                    <input type="text" :value="formatNumberInput(editForm.deduction)"
                        @input="editForm.deduction = parseNumberInput($event.target.value)"
                        @blur="$event.target.value = formatNumberInput(editForm.deduction)"
                        class="border rounded px-3 py-2 w-full" placeholder="0">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-600 hover:text-white">
                        Hủy
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container" class="z-[60]"></div>
</div>

<script>
    function payrollPage() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        return {
            loading: true,
            items: [],
            month: <?= $month ?>,
            year: <?= $year ?>,
            showEditModal: false,
            editForm: { id: null, full_name: '', bonus: 0, deduction: 0 },
            
            toast: {
                show: false,
                message: '',
                type: 'success'
            },
            confirmDialog: {
                show: false,
                title: '',
                message: '',
                onConfirm: () => { },
                onCancel: () => { }
            },

            // Filter controls
            filterType: 'month',
            filterTypeOpen: false,
            filterTypeLabel: 'Theo tháng',
            selectedMonth: currentMonth,
            selectedQuarter: Math.ceil(currentMonth / 3),
            filterYear: currentYear,
            monthOpen: false,
            quarterOpen: false,
            yearOpen: false,
            customFromDate: '',
            customToDate: '',
            yearPeriods: Array.from({ length: 10 }, (_, i) => currentYear - i),

            // ===== FILTERS =====
            openFilter: {
                full_name: false,
                staff_role: false,
                total_shifts_worked: false,
                required_shifts: false,
                base_salary: false,
                actual_salary: false,
                bonus: false,
                deduction: false,
                late_deduction: false,
                total_salary: false,
                status: false
            },

            filters: {
                full_name: '',
                staff_role: '',
                total_shifts_worked_type: '', total_shifts_worked_value: '', total_shifts_worked_from: '', total_shifts_worked_to: '',
                required_shifts_type: '', required_shifts_value: '', required_shifts_from: '', required_shifts_to: '',
                base_salary_type: '', base_salary_value: '', base_salary_from: '', base_salary_to: '',
                actual_salary_type: '', actual_salary_value: '', actual_salary_from: '', actual_salary_to: '',
                bonus_type: '', bonus_value: '', bonus_from: '', bonus_to: '',
                deduction_type: '', deduction_value: '', deduction_from: '', deduction_to: '',
                late_deduction_type: '', late_deduction_value: '', late_deduction_from: '', late_deduction_to: '',
                total_salary_type: '', total_salary_value: '', total_salary_from: '', total_salary_to: '',
                status: ''
            },

            async init() {
                // Khởi tạo custom date range (30 ngày gần nhất)
                const today = new Date();
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 30);
                
                // Format dd/mm/yyyy cho Flatpickr
                const formatDate = (date) => {
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    return `${day}/${month}/${year}`;
                };
                
                this.customFromDate = formatDate(thirtyDaysAgo);
                this.customToDate = formatDate(today);

                await this.loadData();
                
                // Khởi tạo Flatpickr sau khi load data
                this.$nextTick(() => {
                    this.initCustomDatePickers();
                });
            },

            initCustomDatePickers() {
                if (!window.flatpickr) return;

                const fromInput = document.querySelector('input[data-filter-key="custom"][data-filter-field="from"]');
                const toInput = document.querySelector('input[data-filter-key="custom"][data-filter-field="to"]');

                if (fromInput && !fromInput._flatpickr) {
                    flatpickr(fromInput, {
                        dateFormat: 'd/m/Y',
                        allowInput: true,
                        locale: {
                            firstDayOfWeek: 1,
                            weekdays: {
                                shorthand: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                                longhand: ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                            },
                            months: {
                                shorthand: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
                                longhand: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']
                            }
                        },
                        maxDate: this.customToDate || 'today',
                        onChange: (selectedDates, dateStr) => {
                            this.customFromDate = dateStr;
                            
                            // Disable các ngày nhỏ hơn "Từ ngày" trong lịch "Đến ngày"
                            if (toInput && toInput._flatpickr) {
                                toInput._flatpickr.set('minDate', dateStr);
                            }
                            
                            this.changeFilter();
                        }
                    });
                }

                if (toInput && !toInput._flatpickr) {
                    flatpickr(toInput, {
                        dateFormat: 'd/m/Y',
                        allowInput: true,
                        locale: {
                            firstDayOfWeek: 1,
                            weekdays: {
                                shorthand: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                                longhand: ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                            },
                            months: {
                                shorthand: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
                                longhand: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']
                            }
                        },
                        maxDate: 'today',
                        minDate: this.customFromDate || null,
                        onChange: (selectedDates, dateStr) => {
                            this.customToDate = dateStr;
                            
                            // Disable các ngày lớn hơn "Đến ngày" trong lịch "Từ ngày"
                            if (fromInput && fromInput._flatpickr) {
                                fromInput._flatpickr.set('maxDate', dateStr);
                            }
                            
                            this.changeFilter();
                        }
                    });
                }
            },

            // Hàm parse date từ dd/mm/yyyy
            parseDate(dateStr) {
                if (!dateStr) return null;
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    return new Date(parts[2], parts[1] - 1, parts[0]);
                }
                return null;
            },

            showToast(msg, type = 'success') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');

                // Xác định màu sắc theo type
                let colorClasses = '';
                let iconColor = '';
                let iconSvg = '';

                if (type === 'success') {
                    colorClasses = 'text-green-700 border-green-400';
                    iconColor = 'text-green-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
                } else if (type === 'warning') {
                    colorClasses = 'text-yellow-700 border-yellow-400';
                    iconColor = 'text-yellow-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 12a7 7 0 1114 0 7 7 0 01-14 0z" />`;
                } else {
                    colorClasses = 'text-red-700 border-red-400';
                    iconColor = 'text-red-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`;
                }

                toast.className = `fixed top-5 right-5 z-[60] flex items-center justify-between w-[500px] p-6 mb-4 text-base font-semibold ${colorClasses} bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                    <div class="flex items-center flex-1">
                        <svg class="flex-shrink-0 w-6 h-6 ${iconColor} mr-3" 
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            ${iconSvg}
                        </svg>
                        <div class="flex-1">${msg}</div>
                    </div>
                    <button class="ml-4 text-gray-400 hover:text-gray-700 transition" onclick="this.parentElement.remove()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

                box.appendChild(toast);

                // Auto ẩn sau 5s
                const timer = setTimeout(() => hideToast(), 5000);

                // Click nút X để đóng
                toast.querySelector('button').addEventListener('click', () => {
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
            },

            showConfirm(title, message, onConfirm, onCancel = () => { }) {
                this.confirmDialog.title = title;
                this.confirmDialog.message = message;
                this.confirmDialog.onConfirm = onConfirm;
                this.confirmDialog.onCancel = onCancel;
                this.confirmDialog.show = true;
            },

            async loadData() {
                this.loading = true;
                try {
                    let url = '';

                    if (this.filterType === 'month') {
                        url = `/admin/api/payroll?month=${this.selectedMonth}&year=${this.filterYear}`;
                    } else if (this.filterType === 'quarter') {
                        // Load tất cả 3 tháng trong quý
                        const startMonth = (this.selectedQuarter - 1) * 3 + 1;
                        const endMonth = startMonth + 2;

                        // Tạm thời load từng tháng và merge
                        let allItems = [];
                        for (let m = startMonth; m <= endMonth; m++) {
                            const res = await fetch(`/admin/api/payroll?month=${m}&year=${this.filterYear}`);
                            const data = await res.json();
                            allItems = allItems.concat(data.items || []);
                        }
                        this.items = allItems;
                        this.loading = false;
                        return;
                    } else if (this.filterType === 'year') {
                        // Load tất cả 12 tháng
                        let allItems = [];
                        for (let m = 1; m <= 12; m++) {
                            const res = await fetch(`/admin/api/payroll?month=${m}&year=${this.filterYear}`);
                            const data = await res.json();
                            allItems = allItems.concat(data.items || []);
                        }
                        this.items = allItems;
                        this.loading = false;
                        return;
                    } else if (this.filterType === 'custom') {
                        // Filter theo khoảng thời gian tùy chỉnh
                        // Chuyển đổi dd/mm/yyyy sang yyyy-mm-dd
                        const convertDate = (dateStr) => {
                            const parts = dateStr.split('/');
                            if (parts.length === 3) {
                                return `${parts[2]}-${parts[1]}-${parts[0]}`;
                            }
                            return dateStr;
                        };
                        
                        const fromDateStr = convertDate(this.customFromDate);
                        const toDateStr = convertDate(this.customToDate);
                        const fromDate = new Date(fromDateStr);
                        const toDate = new Date(toDateStr);

                        let allItems = [];
                        const currentDate = new Date(fromDate);

                        while (currentDate <= toDate) {
                            const m = currentDate.getMonth() + 1;
                            const y = currentDate.getFullYear();
                            const res = await fetch(`/admin/api/payroll?month=${m}&year=${y}`);
                            const data = await res.json();
                            allItems = allItems.concat(data.items || []);
                            currentDate.setMonth(currentDate.getMonth() + 1);
                        }
                        this.items = allItems;
                        this.loading = false;
                        return;
                    }

                    const res = await fetch(url);
                    const data = await res.json();
                    this.items = data.items || [];
                } catch (err) {
                    console.error('Lỗi tải dữ liệu:', err);
                    this.showToast('Lỗi tải dữ liệu', 'error');
                } finally {
                    this.loading = false;
                }
            },

            selectFilterType(type, label) {
                if (this.loading) return;
                this.filterType = type;
                this.filterTypeLabel = label;
                this.filterTypeOpen = false;
                
                // Nếu chuyển sang custom, khởi tạo lại Flatpickr
                if (type === 'custom') {
                    this.$nextTick(() => {
                        this.initCustomDatePickers();
                    });
                }
                
                this.changeFilter();
            },

            selectMonth(month) {
                if (this.loading) return;
                this.selectedMonth = month;
                this.monthOpen = false;
                this.changeFilter();
            },

            selectQuarter(quarter) {
                if (this.loading) return;
                this.selectedQuarter = quarter;
                this.quarterOpen = false;
                this.changeFilter();
            },

            selectYear(year) {
                if (this.loading) return;
                this.filterYear = year;
                this.yearOpen = false;
                this.changeFilter();
            },

            resetTimeFilter() {
                if (this.loading) return;
                const now = new Date();
                this.filterType = 'month';
                this.filterTypeLabel = 'Theo tháng';
                this.selectedMonth = now.getMonth() + 1;
                this.filterYear = now.getFullYear();
                this.changeFilter();
            },

            changeFilter() {
                this.loadData();
            },

            exportExcel() {
                let url = '';

                if (this.filterType === 'month') {
                    url = `/admin/api/payroll/export?month=${this.selectedMonth}&year=${this.filterYear}`;
                } else if (this.filterType === 'quarter') {
                    const startMonth = (this.selectedQuarter - 1) * 3 + 1;
                    url = `/admin/api/payroll/export?month=${startMonth}&year=${this.filterYear}&type=quarter&quarter=${this.selectedQuarter}`;
                } else if (this.filterType === 'year') {
                    url = `/admin/api/payroll/export?month=1&year=${this.filterYear}&type=year`;
                } else if (this.filterType === 'custom') {
                    // Chuyển đổi dd/mm/yyyy sang yyyy-mm-dd
                    const convertDate = (dateStr) => {
                        const parts = dateStr.split('/');
                        if (parts.length === 3) {
                            return `${parts[2]}-${parts[1]}-${parts[0]}`;
                        }
                        return dateStr;
                    };
                    const fromDate = convertDate(this.customFromDate);
                    const toDate = convertDate(this.customToDate);
                    url = `/admin/api/payroll/export?from=${fromDate}&to=${toDate}&type=custom`;
                }

                window.open(url, '_blank');
                this.showToast('Đang xuất file Excel...', 'success');
            },

            async calculateAll() {
                // Xác định tháng/năm dựa trên filterType
                let calcMonth, calcYear;
                
                if (this.filterType === 'month') {
                    calcMonth = this.selectedMonth;
                    calcYear = this.filterYear;
                } else if (this.filterType === 'quarter') {
                    // Tính từ tháng đầu quý
                    calcMonth = (this.selectedQuarter - 1) * 3 + 1;
                    calcYear = this.filterYear;
                } else if (this.filterType === 'year') {
                    calcMonth = 1;
                    calcYear = this.filterYear;
                } else {
                    // Custom: lấy từ customFromDate
                    const fromDate = this.parseDate(this.customFromDate);
                    if (fromDate) {
                        calcMonth = fromDate.getMonth() + 1;
                        calcYear = fromDate.getFullYear();
                    } else {
                        calcMonth = this.selectedMonth;
                        calcYear = this.filterYear;
                    }
                }
                
                this.showConfirm(
                    'Xác nhận tính lương',
                    `Tính lương cho tất cả nhân viên tháng ${calcMonth}/${calcYear}?`,
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch('/admin/api/payroll/calculate', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ month: calcMonth, year: calcYear })
                            });
                            const data = await res.json();

                            if (res.ok) {
                                this.showToast(data.message, 'success');
                                await this.loadData();
                            } else {
                                this.showToast(data.error || 'Lỗi tính lương', 'error');
                            }
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            editBonusDeduction(item) {
                this.editForm = {
                    id: item.id,
                    full_name: item.full_name,
                    bonus: item.bonus || 0,
                    deduction: item.deduction || 0
                };
                this.showEditModal = true;
            },

            async submitBonusDeduction() {
                try {
                    const res = await fetch(`/admin/api/payroll/${this.editForm.id}/bonus-deduction`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            bonus: parseFloat(this.editForm.bonus),
                            deduction: parseFloat(this.editForm.deduction)
                        })
                    });

                    if (res.ok) {
                        this.showToast('Cập nhật thành công', 'success');
                        this.showEditModal = false;
                        await this.loadData();
                    } else {
                        this.showToast('Lỗi cập nhật', 'error');
                    }
                } catch (err) {
                    this.showToast('Lỗi kết nối', 'error');
                }
            },

            async approve(id) {
                this.showConfirm(
                    'Xác nhận duyệt',
                    'Xác nhận duyệt bảng lương này?',
                    async () => {
                        try {
                            const res = await fetch(`/admin/api/payroll/${id}/approve`, { method: 'POST' });
                            if (res.ok) {
                                this.showToast('Duyệt thành công', 'success');
                                await this.loadData();
                            } else {
                                this.showToast('Lỗi duyệt', 'error');
                            }
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        }
                    }
                );
            },

            async approveAll() {
                const draftItems = this.items.filter(i => i.status === 'Nháp');

                this.showConfirm(
                    'Xác nhận duyệt tất cả',
                    `Xác nhận duyệt TẤT CẢ ${draftItems.length} bảng lương có trạng thái "Nháp" của tháng ${this.month}/${this.year}?`,
                    async () => {
                        this.loading = true;
                        try {
                            const approvePromises = draftItems.map(item =>
                                fetch(`/admin/api/payroll/${item.id}/approve`, { method: 'POST' })
                            );

                            const results = await Promise.all(approvePromises);
                            const successCount = results.filter(res => res.ok).length;

                            if (successCount === draftItems.length) {
                                this.showToast(`Đã duyệt thành công ${successCount} bảng lương`, 'success');
                            } else {
                                this.showToast(`Đã duyệt ${successCount}/${draftItems.length} bảng lương`, 'warning');
                            }

                            await this.loadData();
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async pay(item) {
                this.showConfirm(
                    'Xác nhận trả lương',
                    `Tạo phiếu chi và trả lương cho ${item.full_name}?\nSố tiền: ${this.formatMoney(item.total_salary)}`,
                    async () => {
                        try {
                            const res = await fetch(`/admin/api/payroll/${item.id}/pay`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ method: 'Tiền mặt' })
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.showToast('Trả lương thành công', 'success');
                                await this.loadData();
                            } else {
                                const error = await res.json();
                                this.showToast(error.error || 'Lỗi trả lương', 'error');
                            }
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        }
                    }
                );
            },

            async payAll() {
                const approvedItems = this.items.filter(i => i.status === 'Đã duyệt');

                const totalAmount = approvedItems.reduce((sum, i) => sum + parseFloat(i.total_salary || 0), 0);

                this.showConfirm(
                    'Xác nhận trả tất cả',
                    `Tạo ${approvedItems.length} phiếu chi và trả lương cho TẤT CẢ nhân viên đã duyệt?\nTổng số tiền: ${this.formatMoney(totalAmount)}`,
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch('/admin/api/payroll/pay-all', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    month: this.month,
                                    year: this.year,
                                    method: 'Tiền mặt'
                                })
                            });

                            if (res.ok) {
                                const data = await res.json();
                                if (data.success_count === data.total) {
                                    this.showToast(`Trả lương thành công cho ${data.success_count} nhân viên`, 'success');
                                } else {
                                    this.showToast(`Đã trả ${data.success_count}/${data.total} nhân viên. ${data.errors.length} lỗi`, 'warning');
                                }
                                await this.loadData();
                            } else {
                                const error = await res.json();
                                this.showToast(error.error || 'Lỗi tạo phiếu chi', 'error');
                            }
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async deleteItem(id) {
                this.showConfirm(
                    'Xác nhận xóa',
                    'Xác nhận xóa bảng lương này? Hành động này không thể hoàn tác.',
                    async () => {
                        try {
                            const res = await fetch(`/admin/api/payroll/${id}`, { method: 'DELETE' });
                            if (res.ok) {
                                this.showToast('Xóa thành công', 'success');
                                await this.loadData();
                            } else {
                                this.showToast('Lỗi xóa', 'error');
                            }
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        }
                    }
                );
            },

            async deleteAll() {
                this.showConfirm(
                    'Xác nhận xóa tất cả',
                    `Xác nhận xóa TẤT CẢ ${this.items.length} bảng lương của tháng ${this.month}/${this.year}? Hành động này không thể hoàn tác.`,
                    async () => {
                        this.loading = true;
                        try {
                            const deletePromises = this.items.map(item =>
                                fetch(`/admin/api/payroll/${item.id}`, { method: 'DELETE' })
                            );

                            const results = await Promise.all(deletePromises);
                            const successCount = results.filter(res => res.ok).length;

                            if (successCount === this.items.length) {
                                this.showToast(`Đã xóa thành công ${successCount} bảng lương`, 'success');
                            } else {
                                this.showToast(`Đã xóa ${successCount}/${this.items.length} bảng lương`, 'warning');
                            }

                            await this.loadData();
                        } catch (err) {
                            this.showToast('Lỗi kết nối', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            countByStatus(status) {
                return this.items.filter(i => i.status === status).length;
            },

            totalSalary() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.total_salary || 0), 0);
            },

            getStatusClass(status) {
                const map = {
                    // Status tiếng Việt
                    'Nháp': 'bg-gray-100 text-gray-800',
                    'Đã duyệt': 'bg-green-100 text-green-800',
                    'Đã trả': 'bg-blue-100 text-blue-800',
                    // Backwards compatibility
                    'draft': 'bg-gray-100 text-gray-800',
                    'approved': 'bg-green-100 text-green-800',
                    'paid': 'bg-blue-100 text-blue-800'
                };
                return map[status] || 'bg-gray-100 text-gray-800';
            },

            getStatusText(status) {
                // Status tiếng Việt từ database: 'Nháp', 'Đã duyệt', 'Đã trả'
                const map = {
                    'Nháp': 'Nháp',
                    'Đã duyệt': 'Đã duyệt',
                    'Đã trả': 'Đã trả',
                    // Backwards compatibility
                    'draft': 'Nháp',
                    'approved': 'Đã duyệt',
                    'paid': 'Đã trả'
                };
                return map[status] || status || '—';
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount || 0);
            },

            // Format số với dấu phẩy (10000 -> 10,000)
            formatNumberInput(num) {
                if (!num && num !== 0) return '0';
                return new Intl.NumberFormat('en-US').format(num);
            },

            // Parse số từ string có dấu phẩy (10,000 -> 10000)
            parseNumberInput(str) {
                if (!str) return 0;
                // Xóa tất cả dấu phẩy và chuyển thành số
                const num = parseFloat(str.replace(/,/g, ''));
                return isNaN(num) ? 0 : num;
            },

            // ===== FILTERS =====
            openFilter: {
                full_name: false,
                staff_role: false,
                total_shifts_worked: false,
                required_shifts: false,
                base_salary: false,
                actual_salary: false,
                bonus: false,
                deduction: false,
                late_deduction: false,
                total_salary: false,
                status: false
            },

            filters: {
                full_name: '',
                staff_role: '',
                total_shifts_worked_type: '', total_shifts_worked_value: '', total_shifts_worked_from: '', total_shifts_worked_to: '',
                required_shifts_type: '', required_shifts_value: '', required_shifts_from: '', required_shifts_to: '',
                base_salary_type: '', base_salary_value: '', base_salary_from: '', base_salary_to: '',
                actual_salary_type: '', actual_salary_value: '', actual_salary_from: '', actual_salary_to: '',
                bonus_type: '', bonus_value: '', bonus_from: '', bonus_to: '',
                deduction_type: '', deduction_value: '', deduction_from: '', deduction_to: '',
                late_deduction_type: '', late_deduction_value: '', late_deduction_from: '', late_deduction_to: '',
                total_salary_type: '', total_salary_value: '', total_salary_from: '', total_salary_to: '',
                status: ''
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

                // ---------------- NUMBER ----------------
                if (dataType === 'number') {
                    const num = parseFloat(val);
                    if (isNaN(num)) return false;

                    const v = value ? parseFloat(value) : null;
                    const f = from ? parseFloat(from) : null;
                    const t = to ? parseFloat(to) : null;

                    if (type === 'eq') return v !== null ? num === v : true;
                    if (type === 'lt') return v !== null ? num < v : true;
                    if (type === 'gt') return v !== null ? num > v : true;
                    if (type === 'lte') return v !== null ? num <= v : true;
                    if (type === 'gte') return v !== null ? num >= v : true;
                    if (type === 'between') return f !== null && t !== null ? num >= f && num <= t : true;

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
                ['full_name'].forEach(key => {
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

                // --- Lọc theo select ---
                ['staff_role', 'status'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'eq', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo số ---
                ['total_shifts_worked', 'required_shifts', 'base_salary', 'actual_salary', 'bonus', 'deduction', 'late_deduction', 'total_salary'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'number'
                            })
                        );
                    }
                });

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
                if (['total_shifts_worked', 'required_shifts', 'base_salary', 'actual_salary', 'bonus', 'deduction', 'late_deduction', 'total_salary'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>