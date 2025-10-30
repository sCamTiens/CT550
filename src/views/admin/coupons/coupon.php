<?php
// views/admin/coupons/coupon.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý khuyến mãi / <span class="text-slate-800 font-medium">Mã giảm giá</span>
</nav>

<div x-data="couponPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý mã giảm giá</h1>
        <div class="flex gap-2">
            <a href="/admin/import-history?table=coupons"
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử nhập
            </a>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="openImportModal()">
                <i class="fa-solid fa-file-import"></i> Nhập Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="exportExcel()">
                <i class="fa-solid fa-file-excel"></i> Xuất Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
                @click="openCreate()">+ Thêm mã giảm giá</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:200%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã giảm giá') ?>
                        <?= textFilterPopover('description', 'Mô tả') ?>
                        <?= selectFilterPopover('discount_type', 'Loại giảm giá', [
                            '' => '-- Tất cả --',
                            'percentage' => 'Phần trăm',
                            'fixed' => 'Số tiền cố định'
                        ]) ?>
                        <?= numberFilterPopover('discount_value', 'Giá trị giảm') ?>
                        <?= numberFilterPopover('min_order_value', 'Giá trị đơn tối thiểu') ?>
                        <?= numberFilterPopover('max_discount', 'Giảm tối đa') ?>
                        <?= numberFilterPopover('max_uses', 'Số lần dùng tối đa') ?>
                        <?= numberFilterPopover('used_count', 'Đã dùng') ?>
                        <?= dateFilterPopover('starts_at', 'Ngày bắt đầu') ?>
                        <?= dateFilterPopover('ends_at', 'Ngày kết thúc') ?>
                        <?= selectFilterPopover('is_active', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            '1' => 'Kích hoạt',
                            '0' => 'Vô hiệu hóa'
                        ]) ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in paginated()" :key="c.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <template x-if="c.used_count > 0">
                                </template>
                                <template x-if="!c.used_count || c.used_count === 0">
                                    <div class="space-x-2">
                                        <button @click="openEdit(c)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Sửa">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button @click="remove(c.id)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Xóa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </template>
                            </td>
                            <td class="py-2 px-4" x-text="c.code"></td>
                            <td class="py-2 px-4" x-text="c.description || '—'"></td>
                            <td class="py-2 px-4"
                                x-text="c.discount_type === 'percentage' ? 'Phần trăm' : 'Số tiền cố định'"></td>
                            <td class="py-2 px-4 text-right"
                                x-text="c.discount_type === 'percentage' ? (c.discount_value + '%') : formatCurrency(c.discount_value)">
                            </td>
                            <td class="py-2 px-4 text-right" x-text="formatCurrency(c.min_order_value)"></td>
                            <td class="py-2 px-4 text-right"
                                x-text="c.max_discount > 0 ? formatCurrency(c.max_discount) : '—'"></td>
                            <td class="py-2 px-4 text-center" x-text="c.max_uses || '∞'"></td>
                            <td class="py-2 px-4 text-center" x-text="c.used_count || 0"></td>
                            <td class="py-2 px-4 text-right" x-text="c.starts_at || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="c.ends_at || '—'"></td>
                            <td class="py-2 px-4 text-center">
                                <span :class="c.is_active == 1 ? 'text-green-600' : 'text-red-600'"
                                    x-text="c.is_active == 1 ? 'Kích hoạt' : 'Vô hiệu hóa'"></span>
                            </td>
                            <td class="py-2 px-4 text-right" x-text="c.created_at || '—'"></td>
                            <td class="py-2 px-4" x-text="c.created_by_name || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="c.updated_at || '—'"></td>
                            <td class="py-2 px-4" x-text="c.updated_by_name || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="filtered().length===0">
                        <td colspan="14" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Không có dữ liệu</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Create/Edit -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openForm" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openForm=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]"
                        x-text="form.id ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá'"></h3>
                    <button class="text-slate-500 absolute right-5" @click="openForm=false">✕</button>
                </div>

                <form class="flex flex-col flex-1 overflow-hidden" @submit.prevent="submit()">
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <?php require __DIR__ . '/form.php'; ?>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end gap-3 flex-shrink-0 bg-white">
                        <button type="button" @click="openForm=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded text-sm"
                            :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : (form.id ? 'Cập nhật' : 'Tạo')"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Import Excel -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="showImportModal" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="showImportModal=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Nhập dữ liệu từ Excel</h3>
                    <button class="text-slate-500 absolute right-5" @click="showImportModal=false">✕</button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Chọn file -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <input type="file" @change="handleFileSelect($event)" accept=".xlsx,.xls" class="hidden"
                            x-ref="fileInput">
                        <div x-show="!importFile" @click="$refs.fileInput.click()" class="cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-[#002975] mb-3"></i>
                            <p class="text-slate-600 mb-1">Nhấn để chọn file Excel</p>
                            <p class="text-sm text-slate-400">Hỗ trợ định dạng .xlsx, .xls</p>
                        </div>
                        <div x-show="importFile" class="space-y-3">
                            <div class="flex items-center justify-center gap-2 text-[#002975]">
                                <i class="fa-solid fa-file-excel text-2xl"></i>
                                <span x-text="importFile?.name" class="font-medium"></span>
                            </div>
                            <button type="button" @click="clearFile()" class="text-sm text-red-600 hover:underline">
                                Xóa file
                            </button>
                        </div>
                    </div>

                    <!-- Tải file mẫu -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-info text-[#002975] text-xl mt-0.5"></i>
                            <div class="flex-1">
                                <h4 class="font-semibold text-blue-900 mb-2">Hướng dẫn nhập file:</h4>
                                <ul class="text-sm text-blue-800 space-y-1 mb-3">
                                    <li>• Dòng đầu là tiêu đề, dữ liệu bắt đầu từ dòng 2</li>
                                    <li>• Trường có dấu <span class="text-red-600 font-bold">*</span> là bắt buộc</li>
                                    <li>• Ngày tháng theo định dạng: <strong>dd/mm/yyyy HH:MM:SS</strong></li>
                                    <li>• File phải có định dạng .xls hoặc .xlsx</li>
                                    <li>• File tối đa 10MB, không quá 10,000 dòng</li>
                                </ul>
                                <button type="button" @click="downloadTemplate()"
                                    class="text-sm text-red-400 hover:text-red-600 hover:underline font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-download"></i>
                                    Tải file mẫu Excel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nút hành động -->
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button"
                            class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white transition-colors"
                            @click="showImportModal=false">
                            Hủy
                        </button>
                        <button type="button" @click="submitImport()" :disabled="!importFile || importing"
                            class="px-4 py-2 rounded-md text-white bg-[#002975] hover:bg-[#001a56] disabled:opacity-50 disabled:cursor-not-allowed"
                            x-text="importing ? 'Đang nhập...' : 'Nhập dữ liệu'">
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="toast-container" class="z-[60]"></div>
    </div>

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
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open=false"
                    class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
                    <template x-for="opt in perPageOptions" :key="opt">
                        <div @click="perPage=opt;open=false"
                            class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
                            x-text="opt + ' / trang'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function couponPage() {
        const api = {
            list: '/admin/api/coupons',
            create: '/admin/coupons',
            update: (id) => `/admin/coupons/${id}`,
            remove: (id) => `/admin/coupons/${id}`,
        };

        return {
            // State
            items: <?= json_encode($items ?? [], JSON_UNESCAPED_UNICODE) ?>,
            openForm: false,
            submitting: false,
            form: {},
            errors: {},
            touched: {},

            // Import Excel
            showImportModal: false,
            importFile: null,
            importing: false,

            // Pagination
            currentPage: 1,
            perPage: 20,
            perPageOptions: [5, 10, 20, 50, 100],

            paginated() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filtered().slice(start, start + this.perPage);
            },

            totalPages() {
                return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
            },

            goToPage(page) {
                if (page < 1) page = 1;
                if (page > this.totalPages()) page = this.totalPages();
                this.currentPage = page;
            },

            // ===== FILTERS =====
            openFilter: {
                code: false, description: false, discount_type: false, discount_value: false,
                min_order_value: false, max_discount: false, max_uses: false, used_count: false,
                starts_at: false, ends_at: false, is_active: false, created_at: false, created_by: false,
                updated_at: false, updated_by: false,
            },
            filters: {
                code: '',
                description: '',
                discount_type: '',
                discount_value_type: '', discount_value_value: '', discount_value_from: '', discount_value_to: '',
                min_order_value_type: '', min_order_value_value: '', min_order_value_from: '', min_order_value_to: '',
                max_discount_type: '', max_discount_value: '', max_discount_from: '', max_discount_to: '',
                max_uses_type: '', max_uses_value: '', max_uses_from: '', max_uses_to: '',
                used_count_type: '', used_count_value: '', used_count_from: '', used_count_to: '',
                starts_at_type: '', starts_at_value: '', starts_at_from: '', starts_at_to: '',
                ends_at_type: '', ends_at_value: '', ends_at_from: '', ends_at_to: '',
                is_active: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by: '',
            },

            // -------------------------------------------
            // Hàm lọc tổng quát, hỗ trợ text / number / date
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
                        ? raw === query  // có dấu → so đúng dấu
                        : str === queryNoAccent; // không dấu → so không dấu

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

                // ---------------- NUMBER ----------------
                if (dataType === 'number') {
                    const parseNum = (v) => {
                        if (v === '' || v === null || v === undefined) return null;
                        const s = String(v).replace(/[^\d.-]/g, '');
                        const n = Number(s);
                        return isNaN(n) ? null : n;
                    };

                    const num = parseNum(val);
                    const v = parseNum(value);
                    const f = parseNum(from);
                    const t = parseNum(to);

                    if (num === null) return false;
                    if (!type) return true;

                    if (type === 'eq') return v === null ? true : num === v;
                    if (type === 'lt') return v === null ? true : num < v;
                    if (type === 'gt') return v === null ? true : num > v;
                    if (type === 'lte') return v === null ? true : num <= v;
                    if (type === 'gte') return v === null ? true : num >= v;
                    if (type === 'between') return f === null || t === null ? true : num >= f && num <= t;

                    // --- Lọc “mờ” theo chuỗi số ---
                    if (type === 'like') {
                        const raw = String(val).replace(/[^\d]/g, '');
                        const query = String(value || '').replace(/[^\d]/g, '');
                        return raw.includes(query);
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

                return true;
            },

            filtered() {
                let data = this.items;

                // --- Lọc theo chuỗi ---
                ['code', 'description', 'created_by', 'updated_by'].forEach(key => {
                    if (this.filters[key]) {
                        const field = key === 'created_by' ? 'created_by_name' : key;
                        data = data.filter(o =>
                            this.applyFilter(o[field], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo select ---
                ['is_active', 'discount_type'].forEach(key => {
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
                ['min_order_value', 'discount_value', 'max_discount', 'max_uses', 'used_count'].forEach(key => {
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

                // --- Lọc theo ngày ---
                ['starts_at', 'ends_at', 'created_at', 'updated_at'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'date'
                            })
                        );
                    }
                });

                return data;
            },

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['starts_at', 'ends_at', 'created_at', 'updated_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['discount_value', 'min_order_value', 'max_discount', 'max_uses', 'used_count'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            async init() {
                await this.fetchAll();
            },

            async fetchAll() {
                try {
                    const r = await fetch(api.list);
                    if (r.ok) {
                        const data = await r.json();
                        this.items = data.items || [];
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            openCreate() {
                this.form = {
                    id: null,
                    code: '',
                    description: '',
                    discount_type: 'percentage',
                    discount_value: 0,
                    min_order_value: 0,
                    max_discount: 0,
                    max_uses: null,
                    max_uses_per_customer: 0,
                    starts_at: '',
                    ends_at: '',
                    is_active: 1
                };
                this.errors = {};
                this.touched = {};
                this.openForm = true;

                // Khởi tạo lại flatpickr sau khi modal hiển thị
                this.$nextTick(() => {
                    if (window.initCouponDatePickers) {
                        window.initCouponDatePickers();
                    }
                });
            },

            openEdit(c) {
                this.form = {
                    ...c,
                    // Chuyển is_active về số để checkbox hoạt động đúng (kiểm tra chính xác)
                    is_active: (c.is_active === 1 || c.is_active === '1' || c.is_active === 'true' || c.is_active === true) ? 1 : 0,
                    // Đảm bảo các giá trị số là number, giữ nguyên giá trị 0
                    discount_value: c.discount_value !== null && c.discount_value !== undefined && c.discount_value !== '' ? Number(c.discount_value) : 0,
                    min_order_value: c.min_order_value !== null && c.min_order_value !== undefined && c.min_order_value !== '' ? Number(c.min_order_value) : 0,
                    max_discount: c.max_discount !== null && c.max_discount !== undefined && c.max_discount !== '' ? Number(c.max_discount) : 0,
                    max_uses: c.max_uses !== null && c.max_uses !== undefined && c.max_uses !== '' ? Number(c.max_uses) : null,
                    max_uses_per_customer: c.max_uses_per_customer !== null && c.max_uses_per_customer !== undefined && c.max_uses_per_customer !== '' ? Number(c.max_uses_per_customer) : 0,
                    // Đảm bảo dates được format đúng
                    starts_at: c.starts_at ? c.starts_at.split(' ')[0] : '',
                    ends_at: c.ends_at ? c.ends_at.split(' ')[0] : ''
                };
                this.errors = {};
                this.touched = {};
                this.openForm = true;

                // Khởi tạo lại flatpickr sau khi modal hiển thị
                this.$nextTick(() => {
                    if (window.initCouponDatePickers) {
                        window.initCouponDatePickers();
                    }
                });
            },

            validateField(field) {
                this.errors[field] = '';

                if (field === 'code' && (!this.form.code || this.form.code.trim() === '')) {
                    this.errors.code = 'Vui lòng nhập mã giảm giá';
                }

                if (field === 'discount_value') {
                    if (this.form.discount_value === '' || this.form.discount_value === null) {
                        this.errors.discount_value = 'Vui lòng nhập giá trị giảm';
                    } else if (this.form.discount_value <= 0) {
                        this.errors.discount_value = 'Giá trị giảm phải lớn hơn 0';
                    } else if (this.form.discount_type === 'percentage' && this.form.discount_value > 100) {
                        this.errors.discount_value = 'Phần trăm giảm không được vượt quá 100%';
                    }
                }

                if (field === 'min_order_value' && this.form.min_order_value < 0) {
                    this.errors.min_order_value = 'Giá trị đơn tối thiểu không được âm';
                }

                if (field === 'starts_at' && (!this.form.starts_at || this.form.starts_at.trim() === '')) {
                    this.errors.starts_at = 'Vui lòng chọn ngày bắt đầu';
                }

                if (field === 'ends_at' && (!this.form.ends_at || this.form.ends_at.trim() === '')) {
                    this.errors.ends_at = 'Vui lòng chọn ngày kết thúc';
                }
            },

            validateAll() {
                this.validateField('code');
                this.validateField('discount_value');
                this.validateField('min_order_value');
                this.validateField('starts_at');
                this.validateField('ends_at');

                this.touched = {
                    code: true,
                    discount_value: true,
                    min_order_value: true,
                    starts_at: true,
                    ends_at: true
                };

                return !Object.values(this.errors).some(err => err !== '');
            },

            async submit() {
                if (!this.validateAll()) {
                    this.showToast('Vui lòng kiểm tra lại thông tin!', 'error');
                    return;
                }

                if (this.submitting) return;
                this.submitting = true;

                try {
                    // Convert formatted values back to numbers before sending
                    const submitData = {
                        ...this.form,
                        discount_value: Number(String(this.form.discount_value).replace(/,/g, '')),
                        min_order_value: Number(String(this.form.min_order_value).replace(/,/g, '')),
                        max_discount: Number(String(this.form.max_discount).replace(/,/g, '')) || null,
                        max_uses: Number(String(this.form.max_uses).replace(/,/g, '')) || null,
                        max_uses_per_customer: Number(String(this.form.max_uses_per_customer).replace(/,/g, '')) || 0,
                        is_active: Number(this.form.is_active)
                    };

                    console.log('Submitting data:', submitData);

                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id ? api.update(this.form.id) : api.create;
                    const r = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(submitData)
                    });

                    if (!r.ok) {
                        const errorData = await r.json().catch(() => ({}));
                        console.error('Server error:', errorData);
                        throw new Error(errorData.error || 'Lỗi server');
                    }

                    await this.fetchAll();
                    this.openForm = false;
                    this.showToast('Thao tác thành công!', 'success');
                } catch (e) {
                    console.error('Submit error:', e);
                    this.showToast(e.message || 'Lỗi', 'error');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Xóa mã giảm giá này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                    this.showToast('Xóa thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message || 'Lỗi', 'error');
                }
            },

            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
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

            exportExcel() {
                const data = this.filtered().map(c => ({
                    code: c.code || '',
                    description: c.description || '',
                    discount_type: c.discount_type == 'percent' ? 'Phần trăm' : 'Số tiền',
                    discount_value: c.discount_value || 0,
                    min_order_value: c.min_order_value || 0,
                    max_discount: c.max_discount || 0,
                    max_uses: c.max_uses || 0,
                    used_count: c.used_count || 0,
                    starts_at: c.starts_at || '',
                    ends_at: c.ends_at || '',
                    is_active: c.is_active == 1 ? 'Hoạt động' : 'Không hoạt động',
                    created_at: c.created_at || '',
                    created_by_name: c.created_by_name || '',
                    updated_at: c.updated_at || '',
                    updated_by_name: c.updated_by_name || '',
                }));

                const now = new Date();
                const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
                const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
                const filename = `Ma_giam_gia_${dateStr}_${timeStr}.xlsx`;

                fetch('/admin/api/coupons/export', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: data })
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Export failed');
                        return res.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        this.showToast('Xuất Excel thành công!', 'success');
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast('Không thể xuất Excel');
                    });
            },

            // ===== Import Excel =====
            openImportModal() {
                this.importFile = null;
                this.showImportModal = true;
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (!file) return;

                // 1. Kiểm tra định dạng file
                const ext = file.name.split('.').pop().toLowerCase();
                if (!['xls', 'xlsx'].includes(ext)) {
                    this.showToast('File không đúng định dạng. Vui lòng chọn file Excel (.xls hoặc .xlsx)', 'error');
                    return;
                }

                // 2. Kiểm tra kích thước file (10MB)
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.showToast(`File vượt quá kích thước cho phép (tối đa 10MB). Kích thước file: ${(file.size / 1024 / 1024).toFixed(2)}MB`, 'error');
                    return;
                }

                // 3. Kiểm tra độ dài tên file
                if (file.name.length > 255) {
                    this.showToast(`Tên file quá dài (tối đa 255 ký tự). Độ dài hiện tại: ${file.name.length} ký tự`, 'error');
                    return;
                }

                // 4. Kiểm tra ký tự đặc biệt
                const fileName = file.name.split('.')[0];
                if (!/^[a-zA-Z0-9._\-\s()\[\]]+$/.test(fileName)) {
                    this.showToast('Tên file chứa ký tự đặc biệt không hợp lệ. Vui lòng chỉ sử dụng chữ cái, số, dấu gạch ngang, gạch dưới và khoảng trắng', 'error');
                    return;
                }

                this.importFile = file;
            },

            clearFile() {
                this.importFile = null;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            },

            downloadTemplate() {
                window.location.href = '/admin/api/coupons/template';
            },

            async submitImport() {
                if (!this.importFile) {
                    this.showToast('Vui lòng chọn file để nhập', 'error');
                    return;
                }

                this.importing = true;

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);

                    const response = await fetch('/admin/api/coupons/import', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || 'Có lỗi xảy ra khi nhập file');
                    }

                    // Hiển thị kết quả
                    let message = result.message || `Nhập thành công ${result.success} mã giảm giá`;
                    let toastType = 'success';

                    if (result.status === 'partial') {
                        toastType = 'error';
                    } else if (result.status === 'failed') {
                        toastType = 'error';
                    }

                    this.showToast(message, toastType);

                    // Đóng modal và reload data nếu có ít nhất 1 bản ghi thành công
                    if (result.success > 0) {
                        this.showImportModal = false;
                        this.importFile = null;
                        await this.fetchAll();
                    }

                } catch (error) {
                    this.showToast(error.message || 'Có lỗi xảy ra khi nhập file', 'error');
                } finally {
                    this.importing = false;
                }
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>