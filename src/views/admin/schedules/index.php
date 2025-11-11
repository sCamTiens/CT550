<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<style>
    /* === Tuần được chọn === */
    .flatpickr-day.week-selected {
        background-color: #002975 !important;
        color: white !important;
        border-radius: 0 !important;
    }

    /* Hover nhẹ hơn */
    .flatpickr-day.week-selected:hover {
        background-color: #003caa !important;
    }

    /* Cột số tuần */
    .flatpickr-calendar .flatpickr-weekwrapper {
        width: 45px;
        text-align: center;
        background: #f8fafc;
        font-weight: 600;
        border-right: 1px solid #e5e7eb;
        color: #475569;
    }
</style>

<div x-data="scheduleApp()" x-init="init()" class="p-6">
    <!-- Confirm Dialog -->
    <div x-show="confirmDialog.show"
        class="fixed inset-0 bg-black/40 z-[70] flex items-center justify-center p-5" style="display: none;">
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

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quản lý lịch làm việc</h1>
        <div class="flex gap-2">
            <button @click="openBulkModal()"
                class="border border-[#002975] hover:bg-[#002975] text-[#002975] hover:text-white px-4 py-2 rounded">
                Sắp lịch hàng loạt
            </button>
            <button @click="openCopyWeekModal()"
                class="border border-[#002975] hover:bg-[#002975] text-[#002975] hover:text-white px-4 py-2 rounded">
                Sao chép tuần
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Tuần -->
            <div>
                <label class="block text-sm font-semibold mb-1">Chọn tuần</label>
                <input id="weekPicker" type="text" class="border rounded px-3 py-2 w-full cursor-pointer" readonly>
            </div>

            <!-- Nhân viên -->
            <div>
                <label class="block text-sm font-semibold mb-1">Nhân viên</label>
                <select x-model="filterStaff" @change="loadWeekSchedules()" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <template x-for="s in staffList" :key="s.user_id">
                        <option :value="s.user_id" x-text="s.full_name"></option>
                    </template>
                </select>
            </div>

            <!-- Ca làm việc -->
            <div>
                <label class="block text-sm font-semibold mb-1">Ca làm việc</label>
                <select x-model="filterShift" @change="loadWeekSchedules()" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <template x-for="sh in shifts" :key="sh.id">
                        <option :value="sh.id"
                            x-text="sh.name + ' (' + sh.start_time.slice(0,5) + '-' + sh.end_time.slice(0,5) + ')'">
                        </option>
                    </template>
                </select>
            </div>

            <!-- Trạng thái -->
            <div>
                <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                <select x-model="filterStatus" @change="loadWeekSchedules()" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <option value="Làm việc">Làm việc</option>
                    <option value="Có phép">Có phép</option>
                    <option value="Không phép">Không phép</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Calendar View: Hiển thị lịch theo tuần -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-3 text-left">Nhân viên</th>
                        <template x-for="(day, index) in weekDays" :key="index">
                            <th class="border px-4 py-3 text-center" x-text="formatDayHeader(day)"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading -->
                    <template x-if="loading">
                        <tr>
                            <td :colspan="8" class="text-center py-12">
                                <div class="text-gray-500">Đang tải...</div>
                            </td>
                        </tr>
                    </template>

                    <!-- Data -->
                    <template x-if="!loading">
                        <template x-for="staff in filteredStaffList" :key="staff.user_id">
                            <tr>
                                <td class="border px-4 py-3 font-semibold bg-gray-50" x-text="staff.full_name"></td>
                                <template x-for="day in weekDays" :key="day">
                                    <td class="border px-2 py-2 text-center align-top">
                                        <div class="space-y-1">
                                            <template x-for="shift in shifts" :key="shift.id">
                                                <div @click="openScheduleModal(staff.user_id, shift.id, day)"
                                                    class="text-xs px-2 py-1 rounded cursor-pointer hover:shadow"
                                                    :class="getScheduleClass(staff.user_id, shift.id, day)">
                                                    <div x-text="shift.name" class="font-semibold"></div>
                                                    <div x-text="getScheduleStatus(staff.user_id, shift.id, day)"
                                                        class="text-xs"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Sắp lịch đơn -->
    <div x-show="showModal" x-cloak x-transition.opacity @click.away="showModal = false"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96" @click.stop>
            <h2 class="text-xl font-bold mb-4">Sắp lịch làm việc</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nhân viên</label>
                    <input type="text" :value="getStaffName(form.staff_id)" readonly
                        class="border rounded px-3 py-2 w-full bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ca làm việc</label>
                    <input type="text" :value="getShiftName(form.shift_id)" readonly
                        class="border rounded px-3 py-2 w-full bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ngày làm việc</label>
                    <input type="date" x-model="form.work_date" readonly
                        class="border rounded px-3 py-2 w-full bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                    <select x-model="form.status" class="border rounded px-3 py-2 w-full">
                        <option value="Làm việc">Làm việc</option>
                        <option value="Có phép">Có phép</option>
                        <option value="Không phép">Không phép</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ghi chú</label>
                    <textarea x-model="form.note" rows="3" class="border rounded px-3 py-2 w-full"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="showModal = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">
                    Hủy
                </button>
                <button @click="deleteSchedule()" x-show="form.id"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Xóa
                </button>
                <button @click="saveSchedule()" :disabled="submitting"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    <span x-show="!submitting">Lưu</span>
                    <span x-show="submitting">Đang lưu...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast lỗi nổi -->
    <div id="toast-container" class="z-[60]"></div>

    <!-- Modal: Sắp lịch hàng loạt -->
    <div x-show="showBulkModal" x-cloak x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click="showBulkModal = false">
        <div class="bg-white rounded-lg p-6 w-[1000px]" @click.stop x-data="{
                weekError: '',
                validateWeek() {
                    if (!$parent.bulkForm.start_date || !$parent.bulkForm.end_date) {
                        this.weekError = 'Vui lòng chọn tuần';
                    } else {
                        this.weekError = '';
                    }
                }
            }">

            <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                <h3 class="font-semibold text-2xl text-[#002975]">Sắp lịch hàng loạt</h3>
                <button class="text-slate-500 absolute right-5" @click="showBulkModal=false">✕</button>
            </div>

            <div class="space-y-4">
                <div @click.stop>
                    <label class="block text-sm font-semibold mb-1">Chọn tuần <span
                            class="text-red-500">*</span></label>
                    <input id="bulkWeekPicker" type="text" class="border rounded px-3 py-2 w-full cursor-pointer"
                        readonly @blur="validateWeek()">
                    <p class="text-xs text-red-600 mt-1" x-text="weekError"></p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Chọn nhân viên</label>
                    <div class="border rounded p-3 max-h-48 overflow-y-auto space-y-2">
                        <template x-for="staff in staffList" :key="staff.user_id">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" :value="staff.user_id" x-model="bulkForm.staff_ids"
                                    class="rounded">
                                <span x-text="staff.full_name + ' - ' + (staff.staff_role || '')"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ca làm việc</label>
                    <template x-for="shift in shifts" :key="shift.id">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :value="shift.id" x-model="bulkForm.shift_ids" class="rounded">
                            <span
                                x-text="shift.name + ' (' + shift.start_time.slice(0,5) + '-' + shift.end_time.slice(0,5) + ')'"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="showBulkModal = false"
                    class="border border-red-600 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded">
                    Hủy
                </button>
                <button @click="saveBulkSchedule()" :disabled="submitting"
                    class="border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white px-4 py-2 rounded">
                    <span x-show="!submitting">Tạo lịch</span>
                    <span x-show="submitting">Đang tạo...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Sao chép tuần -->
    <div x-show="showCopyModal" x-cloak x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click="showCopyModal = false">
        <div class="bg-white rounded-lg p-6 w-[800px]" @click.stop>
            <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                <h3 class="font-semibold text-2xl text-[#002975]">Sao chép lịch tuần</h3>
                <button class="text-slate-500 absolute right-5" @click="showCopyModal=false">✕</button>
            </div>

            <div class="space-y-4 mt-5">
                <div @click.stop>
                    <label class="block text-sm font-semibold mb-1">Tuần nguồn</label>
                    <input id="fromWeekPicker" type="text" class="border rounded px-3 py-2 w-full cursor-pointer"
                        readonly>
                </div>

                <div @click.stop>
                    <label class="block text-sm font-semibold mb-1">Tuần đích</label>
                    <input id="toWeekPicker" type="text" class="border rounded px-3 py-2 w-full cursor-pointer"
                        readonly>
                </div>

                <p class="text-sm text-gray-600">
                    Lịch từ tuần nguồn sẽ được sao chép sang tuần đích
                </p>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="showCopyModal = false"
                    class="border border-red-600 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded">
                    Hủy
                </button>
                <button @click="copyWeekSchedule()" :disabled="submitting"
                    class="border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white px-4 py-2 rounded">
                    <span x-show="!submitting">Sao chép</span>
                    <span x-show="submitting">Đang sao chép...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function scheduleApp() {
        return {
            loading: false,
            submitting: false,
            currentWeek: '',
            weekDays: [],
            staffList: [],
            shifts: [],
            schedules: [],
            filterStaff: '',
            filterShift: '',
            filterStatus: '',
            showModal: false,
            showBulkModal: false,
            showCopyModal: false,
            confirmDialog: {
                show: false,
                title: '',
                message: '',
                onConfirm: () => {},
                onCancel: () => {}
            },
            form: {
                id: null,
                staff_id: '',
                shift_id: '',
                work_date: '',
                status: 'Làm việc',
                note: ''
            },
            bulkForm: {
                start_date: '',
                end_date: '',
                staff_ids: [],
                shift_ids: []
            },
            copyForm: {
                from_date: '',
                to_date: ''
            },

            // Computed property: Danh sách nhân viên được filter
            get filteredStaffList() {
                if (this.filterStaff) {
                    // Nếu có filter, chỉ hiển thị nhân viên được chọn
                    return this.staffList.filter(s => s.user_id == this.filterStaff);
                }
                
                // Nếu không có filter, hiển thị tất cả nhân viên có lịch trong tuần
                const staffWithSchedules = new Set(this.schedules.map(s => s.staff_id));
                const filtered = this.staffList.filter(s => staffWithSchedules.has(s.user_id));
                
                // Nếu không có ai có lịch, hiển thị tất cả để user có thể thêm mới
                return filtered.length > 0 ? filtered : this.staffList;
            },

            async init() {
                // Set current week
                const today = new Date();
                const monday = this.getMonday(today);
                this.currentWeek = this.formatWeekInput(monday);
                // Khởi tạo weekDays để hiển thị header bảng
                this.weekDays = this.getWeekDays(monday);

                await this.loadMasterData();
                await this.loadWeekSchedules();
            },

            async loadMasterData() {
                try {
                    const [staffRes, shiftsRes] = await Promise.all([
                        fetch('/admin/api/schedules/staff-list'),
                        fetch('/admin/api/schedules/shifts')
                    ]);
                    const staffText = await staffRes.text();
                    const shiftsText = await shiftsRes.text();

                    const staffData = JSON.parse(staffText);
                    const shiftsData = JSON.parse(shiftsText);

                    this.staffList = staffData.staff || [];
                    this.shifts = shiftsData.shifts || [];
                } catch (e) {
                    this.showToast('Lỗi tải lịch: ' + e.message);
                }
            },

            async loadWeekSchedules() {
                if (!this.currentWeek) return;

                this.loading = true;
                try {
                    // Only recalculate weekDays if it's empty or not set yet
                    if (!this.weekDays || this.weekDays.length === 0) {
                        const monday = this.parseISOWeek(this.currentWeek);
                        this.weekDays = this.getWeekDays(monday);
                    }

                    const params = new URLSearchParams({
                        start_date: this.weekDays[0],
                        end_date: this.weekDays[6]
                    });

                    if (this.filterStaff) params.append('staff_id', this.filterStaff);
                    if (this.filterShift) params.append('shift_id', this.filterShift);
                    if (this.filterStatus) params.append('status', this.filterStatus);


                    const res = await fetch('/admin/api/schedules?' + params);
                    const resText = await res.text();
                    const data = JSON.parse(resText);

                    this.schedules = data.schedules || [];
                    console.log('📅 Loaded schedules:', this.schedules.length, 'records');
                    console.log('🔍 Filter params:', {
                        start_date: this.weekDays[0],
                        end_date: this.weekDays[6],
                        staff_id: this.filterStaff || 'all',
                        shift_id: this.filterShift || 'all',
                        status: this.filterStatus || 'all'
                    });
                } catch (e) {
                    console.error('❌ Error loading schedules:', e);
                    this.showToast('Lỗi tải lịch: ' + e.message);
                } finally {
                    this.loading = false;
                }
            },

            openScheduleModal(staffId, shiftId, workDate) {
                const existing = this.schedules.find(s =>
                    s.staff_id == staffId && s.shift_id == shiftId && s.work_date === workDate
                );

                if (existing) {
                    this.form = { ...existing };
                } else {
                    this.form = {
                        id: null,
                        staff_id: staffId,
                        shift_id: shiftId,
                        work_date: workDate,
                        status: 'Làm việc',
                        note: ''
                    };
                }

                this.showModal = true;
            },

            async saveSchedule() {
                this.submitting = true;
                try {
                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id
                        ? `/admin/api/schedules/${this.form.id}`
                        : '/admin/api/schedules';

                    const res = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });

                    const data = await res.json();
                    if (res.ok) {
                        this.showToast(data.message || 'Lưu thành công', 'success');
                        this.showModal = false;
                        await this.loadWeekSchedules();
                    } else {
                        this.showToast(data.error || 'Lỗi khi lưu', 'error');
                    }
                } catch (e) {
                    this.showToast('Lỗi: ' + e.message, 'error');
                } finally {
                    this.submitting = false;
                }
            },

            async deleteSchedule() {
                this.showConfirm(
                    'Xác nhận xóa',
                    'Xóa lịch này? Hành động không thể hoàn tác.',
                    async () => {
                        try {
                            const res = await fetch(`/admin/api/schedules/${this.form.id}`, {
                                method: 'DELETE'
                            });

                            if (res.ok) {
                                this.showToast('Xóa thành công', 'success');
                                this.showModal = false;
                                await this.loadWeekSchedules();
                            } else {
                                const data = await res.json();
                                this.showToast(data.error || 'Lỗi khi xóa', 'error');
                            }
                        } catch (e) {
                            this.showToast('Lỗi: ' + e.message, 'error');
                        }
                    }
                );
            },

            openBulkModal() {
                const monday = this.parseISOWeek(this.currentWeek);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);

                this.bulkForm = {
                    start_date: this.formatDate(monday),
                    end_date: this.formatDate(sunday),
                    staff_ids: [],
                    shift_ids: []
                };
                this.showBulkModal = true;

                // Initialize week picker after modal is shown
                this.$nextTick(() => {
                    if (document.getElementById('bulkWeekPicker')._flatpickr) {
                        document.getElementById('bulkWeekPicker')._flatpickr.destroy();
                    }

                    const app = this;

                    // Custom plugin without auto-close
                    function weekSelectPluginNoClose() {
                        return function (fp) {
                            function highlightWeekFor(date) {
                                const [monday, sunday] = getMondayAndSunday(date);
                                Array.from(fp.days.childNodes).forEach(dayElem => {
                                    const d = dayElem.dateObj;
                                    if (!d) return;
                                    const t = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
                                    if (t >= monday.getTime() && t <= sunday.getTime()) dayElem.classList.add('week-selected');
                                    else dayElem.classList.remove('week-selected');
                                });
                            }

                            fp.config.onOpen.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));
                            fp.config.onMonthChange.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));

                            fp.config.onDayCreate.push((dObj, dStr, fpInstance, dayElem) => {
                                dayElem.addEventListener('click', (ev) => {
                                    const clicked = dayElem.dateObj;
                                    if (!clicked) return;
                                    const [monday, sunday] = getMondayAndSunday(clicked);
                                    fp.setDate([monday, sunday], true);
                                    const display = `${formatDDMMYYYY(monday)} - ${formatDDMMYYYY(sunday)}`;
                                    if (fp.altInput) fp.altInput.value = display;
                                    else fp.input.value = display;
                                    highlightWeekFor(clicked);
                                    // NO auto-close for modal pickers
                                });
                            });

                            fp.config.onValueUpdate.push(([date]) => date && highlightWeekFor(date));
                        };
                    }

                    flatpickr("#bulkWeekPicker", {
                        locale: "vn",
                        weekNumbers: true,
                        disableMobile: true,
                        showMonths: 1,
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y - d/m/Y",
                        allowInput: false,
                        plugins: [weekSelectPluginNoClose()],
                        onReady: function (selectedDates, dateStr, fp) {
                            const monday = new Date(app.bulkForm.start_date);
                            const [weekMonday, sunday] = getMondayAndSunday(monday);
                            fp.setDate([weekMonday, sunday], true);
                            const display = `${formatDDMMYYYY(weekMonday)} - ${formatDDMMYYYY(sunday)}`;
                            if (fp.altInput) fp.altInput.value = display;
                        },
                        onChange: function (selectedDates) {
                            if (selectedDates.length > 0) {
                                const [monday, sunday] = getMondayAndSunday(selectedDates[0]);
                                app.bulkForm.start_date = app.formatDate(monday);
                                app.bulkForm.end_date = app.formatDate(sunday);
                            }
                        }
                    });
                });
            },

            async saveBulkSchedule() {
                if (!this.bulkForm.start_date || !this.bulkForm.end_date) {
                    this.showToast('Vui lòng chọn tuần', 'warning');
                    return;
                }

                if (this.bulkForm.staff_ids.length === 0 || this.bulkForm.shift_ids.length === 0) {
                    this.showToast('Vui lòng chọn nhân viên và ca làm việc', 'warning');
                    return;
                }

                this.submitting = true;
                try {
                    const schedules = [];
                    const startDate = new Date(this.bulkForm.start_date);
                    const endDate = new Date(this.bulkForm.end_date);

                    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                        const dateStr = d.toISOString().split('T')[0];

                        for (const staffId of this.bulkForm.staff_ids) {
                            for (const shiftId of this.bulkForm.shift_ids) {
                                schedules.push({
                                    staff_id: staffId,
                                    shift_id: shiftId,
                                    work_date: dateStr,
                                    status: 'Làm việc'
                                });
                            }
                        }
                    }

                    const res = await fetch('/admin/api/schedules/bulk', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ schedules })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        this.showToast(data.message || 'Tạo lịch thành công', 'success');
                        this.showBulkModal = false;
                        await this.loadWeekSchedules();
                    } else {
                        this.showToast(data.error || 'Lỗi khi tạo lịch', 'error');
                    }
                } catch (e) {
                    this.showToast('Lỗi: ' + e.message, 'error');
                } finally {
                    this.submitting = false;
                }
            },

            openCopyWeekModal() {
                // Parse ISO week to get Monday
                const monday = this.parseISOWeek(this.currentWeek);
                // Calculate next week's Monday
                const nextMonday = new Date(monday);
                nextMonday.setDate(monday.getDate() + 7);

                this.copyForm = {
                    from_date: this.formatDate(monday),
                    to_date: this.formatDate(nextMonday)
                };
                this.showCopyModal = true;

                // Initialize week pickers after modal is shown
                this.$nextTick(() => {
                    this.initCopyWeekPickers();
                });
            },

            initCopyWeekPickers() {
                // Destroy existing instances if any
                if (document.getElementById('fromWeekPicker')._flatpickr) {
                    document.getElementById('fromWeekPicker')._flatpickr.destroy();
                }
                if (document.getElementById('toWeekPicker')._flatpickr) {
                    document.getElementById('toWeekPicker')._flatpickr.destroy();
                }

                const app = this;

                // Custom plugin without auto-close
                function weekSelectPluginNoClose() {
                    return function (fp) {
                        function highlightWeekFor(date) {
                            const [monday, sunday] = getMondayAndSunday(date);
                            Array.from(fp.days.childNodes).forEach(dayElem => {
                                const d = dayElem.dateObj;
                                if (!d) return;
                                const t = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
                                if (t >= monday.getTime() && t <= sunday.getTime()) dayElem.classList.add('week-selected');
                                else dayElem.classList.remove('week-selected');
                            });
                        }

                        fp.config.onOpen.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));
                        fp.config.onMonthChange.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));

                        fp.config.onDayCreate.push((dObj, dStr, fpInstance, dayElem) => {
                            dayElem.addEventListener('click', (ev) => {
                                const clicked = dayElem.dateObj;
                                if (!clicked) return;
                                const [monday, sunday] = getMondayAndSunday(clicked);
                                fp.setDate([monday, sunday], true);
                                const display = `${formatDDMMYYYY(monday)} - ${formatDDMMYYYY(sunday)}`;
                                if (fp.altInput) fp.altInput.value = display;
                                else fp.input.value = display;
                                highlightWeekFor(clicked);
                                // NO auto-close for modal pickers
                            });
                        });

                        fp.config.onValueUpdate.push(([date]) => date && highlightWeekFor(date));
                    };
                }

                // From Week Picker
                flatpickr("#fromWeekPicker", {
                    locale: "vn",
                    weekNumbers: true,
                    disableMobile: true,
                    showMonths: 1,
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y - d/m/Y",
                    allowInput: false,
                    plugins: [weekSelectPluginNoClose()],
                    onReady: function (selectedDates, dateStr, fp) {
                        const monday = new Date(app.copyForm.from_date);
                        const [weekMonday, sunday] = getMondayAndSunday(monday);
                        fp.setDate([weekMonday, sunday], true);
                        const display = `${formatDDMMYYYY(weekMonday)} - ${formatDDMMYYYY(sunday)}`;
                        if (fp.altInput) fp.altInput.value = display;
                    },
                    onChange: function (selectedDates) {
                        if (selectedDates.length > 0) {
                            const [monday] = getMondayAndSunday(selectedDates[0]);
                            app.copyForm.from_date = app.formatDate(monday);
                        }
                    }
                });

                // To Week Picker
                flatpickr("#toWeekPicker", {
                    locale: "vn",
                    weekNumbers: true,
                    disableMobile: true,
                    showMonths: 1,
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y - d/m/Y",
                    allowInput: false,
                    plugins: [weekSelectPluginNoClose()],
                    onReady: function (selectedDates, dateStr, fp) {
                        const monday = new Date(app.copyForm.to_date);
                        const [weekMonday, sunday] = getMondayAndSunday(monday);
                        fp.setDate([weekMonday, sunday], true);
                        const display = `${formatDDMMYYYY(weekMonday)} - ${formatDDMMYYYY(sunday)}`;
                        if (fp.altInput) fp.altInput.value = display;
                    },
                    onChange: function (selectedDates) {
                        if (selectedDates.length > 0) {
                            const [monday] = getMondayAndSunday(selectedDates[0]);
                            app.copyForm.to_date = app.formatDate(monday);
                        }
                    }
                });
            },

            async copyWeekSchedule() {
                this.submitting = true;
                try {
                    const res = await fetch('/admin/api/schedules/copy-week', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.copyForm)
                    });

                    const data = await res.json();
                    if (res.ok) {
                        this.showToast(data.message || 'Sao chép thành công', 'success');
                        this.showCopyModal = false;
                        await this.loadWeekSchedules();
                    } else {
                        this.showToast(data.error || 'Lỗi khi sao chép', 'error');
                    }
                } catch (e) {
                    this.showToast('Lỗi: ' + e.message, 'error');
                } finally {
                    this.submitting = false;
                }
            },

            getScheduleClass(staffId, shiftId, workDate) {
                const schedule = this.schedules.find(s =>
                    s.staff_id == staffId && s.shift_id == shiftId && s.work_date === workDate
                );

                if (!schedule) return 'bg-gray-100 text-gray-400 border border-dashed';

                const statusColors = {
                    'Làm việc': 'bg-green-100 text-green-800 border border-green-300',
                    'Có phép': 'bg-blue-100 text-blue-800',
                    'Không phép': 'bg-red-100 text-red-800'
                };

                return statusColors[schedule.status] || 'bg-gray-100';
            },

            getScheduleStatus(staffId, shiftId, workDate) {
                const schedule = this.schedules.find(s =>
                    s.staff_id == staffId && s.shift_id == shiftId && s.work_date === workDate
                );

                return schedule ? schedule.status : '—';
            },

            getStaffName(staffId) {
                const staff = this.staffList.find(s => s.user_id == staffId);
                return staff ? staff.full_name : '';
            },

            getShiftName(shiftId) {
                const shift = this.shifts.find(s => s.id == shiftId);
                return shift ? shift.name : '';
            },

            getMonday(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(d.setDate(diff));
            },

            getWeekDays(monday) {
                const days = [];
                for (let i = 0; i < 7; i++) {
                    const date = new Date(monday);
                    date.setDate(monday.getDate() + i);
                    days.push(this.formatDate(date));
                }
                return days;
            },

            formatDate(date) {
                // Use local date parts to avoid timezone shift
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },

            formatWeekInput(date) {
                const year = date.getFullYear();
                const week = this.getWeekNumber(date);
                return `${year}-W${week.toString().padStart(2, '0')}`;
            },

            getWeekNumber(date) {
                const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
                const dayNum = d.getUTCDay() || 7;
                d.setUTCDate(d.getUTCDate() + 4 - dayNum);
                const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            },

            parseISOWeek(isoWeek) {
                const [year, week] = isoWeek.split('-W').map(Number);
                const jan4 = new Date(Date.UTC(year, 0, 4));
                const day = jan4.getUTCDay();
                const daysFromMonday = (day === 0 ? 6 : day - 1);
                const week1Monday = new Date(Date.UTC(year, 0, 4 - daysFromMonday));
                const targetMonday = new Date(week1Monday);
                targetMonday.setUTCDate(week1Monday.getUTCDate() + (week - 1) * 7);

                // Convert to local date
                return new Date(targetMonday.getUTCFullYear(), targetMonday.getUTCMonth(), targetMonday.getUTCDate());
            },

            formatDayHeader(dateStr) {
                const date = new Date(dateStr);
                const dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                const day = dayNames[date.getDay()];
                const dayNum = date.getDate();
                const month = date.getMonth() + 1;
                return `${day}\n${dayNum}/${month}`;
            },

            // ===== toast =====
            showToast(msg, type = 'error') {
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

                toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold ${colorClasses} bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                    <svg class="flex-shrink-0 w-6 h-6 ${iconColor} mr-3" 
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${iconSvg}
                    </svg>
                    <div class="flex-1">${msg}</div>
                `;

                box.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            },

            showConfirm(title, message, onConfirm, onCancel = () => {}) {
                this.confirmDialog.title = title;
                this.confirmDialog.message = message;
                this.confirmDialog.onConfirm = onConfirm;
                this.confirmDialog.onCancel = onCancel;
                this.confirmDialog.show = true;
            },
        }
    }
</script><script>
    /* Helper */
    function formatDDMMYYYY(d) {
        const day = String(d.getDate()).padStart(2, '0');
        const mon = String(d.getMonth() + 1).padStart(2, '0');
        return `${day}/${mon}/${d.getFullYear()}`;
    }
    function getMondayAndSunday(date) {
        const d = new Date(date);
        const day = d.getDay(); // 0..6 (0=Sun)
        // compute Monday
        const monday = new Date(d);
        monday.setDate(d.getDate() - ((day + 6) % 7));
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        // zero time
        monday.setHours(0, 0, 0, 0);
        sunday.setHours(0, 0, 0, 0);
        return [monday, sunday];
    }
    function isoWeekStringFromDate(date) {
        // return YYYY-Www
        const year = date.getFullYear();
        // get ISO week number
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        return `${year}-W${String(weekNo).padStart(2, '0')}`;
    }

    /* CSS for selection (if not injected earlier) */
    const css = `
    .flatpickr-day.week-selected { background-color:#002975 !important; color:#fff !important; border-radius:0 !important; }
    .flatpickr-day.week-selected:hover { background-color:#001f5a !important; }
    .flatpickr-weekwrapper { width:48px; text-align:center; background:#f8fafc; border-right:1px solid #e5e7eb; }
    `;
    if (!document.getElementById('weekpicker-custom-css')) {
        const s = document.createElement('style'); s.id = 'weekpicker-custom-css'; s.innerHTML = css; document.head.appendChild(s);
    }

    /* Plugin: highlight & select full week */
    function weekSelectPlugin() {
        return function (fp) {
            function highlightWeekFor(date) {
                const [monday, sunday] = getMondayAndSunday(date);
                Array.from(fp.days.childNodes).forEach(dayElem => {
                    const d = dayElem.dateObj;
                    if (!d) return;
                    // compare only date part
                    const t = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
                    if (t >= monday.getTime() && t <= sunday.getTime()) dayElem.classList.add('week-selected');
                    else dayElem.classList.remove('week-selected');
                });
            }

            // when calendar opened or month changed, restore highlight
            fp.config.onOpen.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));
            fp.config.onMonthChange.push(() => fp.selectedDates[0] && highlightWeekFor(fp.selectedDates[0]));

            // attach click to day cells to select full week
            fp.config.onDayCreate.push((dObj, dStr, fpInstance, dayElem) => {
                dayElem.addEventListener('click', (ev) => {
                    // ensure dateObj exists
                    const clicked = dayElem.dateObj;
                    if (!clicked) return;
                    const [monday, sunday] = getMondayAndSunday(clicked);

                    console.log('[Plugin] Week clicked:', monday, 'to', sunday);

                    // set both dates as selected -> this should trigger onChange but might not work
                    fp.setDate([monday, sunday], true);

                    // manually set alt/input display to range dd/mm/yyyy - dd/mm/yyyy
                    const display = `${formatDDMMYYYY(monday)} - ${formatDDMMYYYY(sunday)}`;
                    if (fp.altInput) fp.altInput.value = display;
                    else fp.input.value = display;

                    // highlight visually
                    highlightWeekFor(clicked);

                    // Manually update Alpine component directly
                    const root = fp.input.closest('[x-data]');
                    console.log('[Plugin] root element:', root);

                    // Try multiple ways to access Alpine component
                    let component = null;

                    // Method 1: Alpine 3.x uses _x_dataStack
                    if (root && root._x_dataStack && root._x_dataStack.length > 0) {
                        component = root._x_dataStack[0];
                        console.log('[Plugin] Found component via _x_dataStack');
                    }
                    // Method 2: Try __x (Alpine 2.x)
                    else if (root && root.__x) {
                        component = root.__x.$data;
                        console.log('[Plugin] Found component via __x');
                    }
                    // Method 3: Use Alpine.$data()
                    else if (root && typeof Alpine !== 'undefined' && Alpine.$data) {
                        component = Alpine.$data(root);
                        console.log('[Plugin] Found component via Alpine.$data');
                    }

                    if (component && typeof component.getWeekDays === 'function') {
                        const newWeek = isoWeekStringFromDate(monday);
                        const newWeekDays = component.getWeekDays(monday);

                        component.currentWeek = newWeek;
                        component.weekDays = [...newWeekDays];

                        // Load schedules
                        if (component.loadWeekSchedules) {
                            component.loadWeekSchedules();
                        }
                    } else {
                        console.error('[Plugin] Alpine component not found or invalid:', {
                            hasRoot: !!root,
                            hasDataStack: root ? !!root._x_dataStack : false,
                            has__x: root ? !!root.__x : false,
                            component: component
                        });
                    }

                    // close calendar after onChange completes
                    setTimeout(() => fp.close(), 150);
                });
            });

            // when value updated programmatically also highlight
            fp.config.onValueUpdate.push(([date]) => date && highlightWeekFor(date));
        };
    }

    /* Initialize week picker after Alpine.js is ready */
    let weekPickerInitialized = false;

    document.addEventListener('alpine:initialized', () => {
        // Prevent multiple initializations
        if (weekPickerInitialized) return;
        weekPickerInitialized = true;

        // Wait a bit more to ensure Alpine components are fully mounted
        setTimeout(() => {
            const pickerEl = document.getElementById('weekPicker');
            if (!pickerEl) return;

            // If already a flatpickr instance exists, destroy first
            const existing = pickerEl._flatpickr;
            if (existing) existing.destroy();

            flatpickr("#weekPicker", {
                locale: "vn",
                weekNumbers: true,
                disableMobile: true,
                showMonths: 1,
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y - d/m/Y",
                allowInput: false,
                plugins: [weekSelectPlugin()],
                onReady: function (selectedDates, dateStr, fp) {
                    // default to current week
                    const today = new Date();
                    const [monday, sunday] = getMondayAndSunday(today);
                    fp.setDate([monday, sunday], true);
                    const display = `${formatDDMMYYYY(monday)} - ${formatDDMMYYYY(sunday)}`;
                    if (fp.altInput) fp.altInput.value = display;
                    else fp.input.value = display;

                    // set Alpine if present
                    const root = fp.input.closest('[x-data]');
                    if (root && root.__x) {
                        const app = root.__x.$data;
                        if (app) {
                            app.currentWeek = isoWeekStringFromDate(monday);
                            // Khởi tạo weekDays ngay từ đầu
                            app.weekDays = app.getWeekDays(monday);
                        }
                    }

                    // ensure highlight
                    if (fp.selectedDates[0]) {
                        fp.config.onValueUpdate.forEach(fn => fn(fp.selectedDates));
                    }
                },
                onChange: function (selectedDates, dateStr, fp) {
                    // When user selects a new week
                    if (selectedDates[0]) {
                        const [monday, sunday] = getMondayAndSunday(selectedDates[0]);
                        const display = `${formatDDMMYYYY(monday)} - ${formatDDMMYYYY(sunday)}`;
                        if (fp.altInput) fp.altInput.value = display;
                        else fp.input.value = display;

                        // Update Alpine app state
                        const root = fp.input.closest('[x-data]');

                        if (root && root.__x) {
                            const app = root.__x.$data;

                            if (app) {
                                const newWeek = isoWeekStringFromDate(monday);
                                const newWeekDays = app.getWeekDays(monday);

                                // Update both currentWeek and weekDays synchronously
                                app.currentWeek = newWeek;
                                app.weekDays = [...newWeekDays];

                                // Then load schedules (which will NOT recalculate weekDays since it's already set)
                                if (app.loadWeekSchedules) {
                                    app.loadWeekSchedules();
                                }
                            }
                        }
                    }
                }
            });
        }, 100); // Wait 100ms for Alpine to fully mount
    });
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>