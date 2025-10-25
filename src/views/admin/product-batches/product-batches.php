<!-- Flatpickr CSS -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">

<?php
$items = $items ?? [];
$products = $products ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Lô sản phẩm</span>
</nav>

<div x-data="productBatchesPage()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý lô sản phẩm</h1>
        <!-- <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm lô</button> -->
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:140%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <!-- <th class="py-2 px-4 whitespace-nowrap text-center">Thao tác</th> -->
                        <?= textFilterPopover('product_name', 'Sản phẩm') ?>
                        <?= textFilterPopover('batch_code', 'Mã lô') ?>
                        <?= dateFilterPopover('mfg_date', 'Ngày sản xuất') ?>
                        <?= dateFilterPopover('exp_date', 'HSD') ?>
                        <?= numberFilterPopover('current_qty', 'Tồn hiện tại') ?>
                        <?= numberFilterPopover('unit_cost', 'Giá nhập') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Ngày tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in paginated()" :key="b.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <!-- <td class="py-2 px-4 text-center">
                                <button @click="openEdit(b)"
                                    class="p-2 rounded hover:bg-gray-100 text-[#002975]">Sửa</button>
                                <template x-if="b.current_qty>0">
                                    <button class="p-2 rounded text-slate-400 cursor-not-allowed"
                                        title="Không thể xóa lô có tồn">Khoá</button>
                                </template>
                                <template x-if="b.current_qty==0">
                                    <button @click="remove(b.id)"
                                        class="p-2 rounded hover:bg-gray-100 text-[#002975]">Xóa</button>
                                </template>
                            </td> -->
                            <!-- <td class="py-2 px-4 text-center space-x-2">
                                <template x-if="b.current_qty > 0">
                                    <span class="text-slate-400 text-sm" title="Không thể xóa lô còn tồn">—</span>
                                </template>

                                <template x-if="b.current_qty == 0">
                                    <div class="inline-flex space-x-2">
                                        <button @click="openEdit(b)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Sửa">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button @click="remove(b.id)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Xóa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </template>
                            </td> -->

                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                x-text="b.product_name || b.product_sku"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="b.batch_code"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(b.mfg_date || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="b.mfg_date || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(b.exp_date || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="b.exp_date || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="b.current_qty">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(b.unit_cost)"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(b.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="b.note || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(b.created_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="b.created_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(b.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="b.created_by_name || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="filtered().length===0">
                        <td colspan="7" class="py-12 text-center text-slate-500">
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
            <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="openForm=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]" x-text="form.id? 'Sửa lô':'Thêm lô'"></h3>
                    <button class="text-slate-500 absolute right-5" @click="openForm=false">✕</button>
                </div>

                <?php require __DIR__ . '/form.php'; ?>
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
    function productBatchesPage() {
        const api = {
            list: '/admin/api/product-batches',
            create: '/admin/api/product-batches',
            update: (id) => `/admin/api/product-batches/${id}`,
            remove: (id) => `/admin/api/product-batches/${id}`,
            products: '/admin/api/products'
        };

        return {
            // --- state ---
            items: [],
            products: [],
            filters: {},
            openFilter: {},
            openForm: false,
            submitting: false,
            form: {},
            touched: {},
            errors: {},

            // --- pagination ---
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
                product_name: false, batch_code: false, mfg_date: false,
                exp_date: false, current_qty: false,
                unit_cost: false, note: false,
                created_at: false, created_by: false,
            },

            filters: {
                product_name: '', batch_code: '',
                mfg_date_type: '', mfg_date_value: '', mfg_date_from: '', mfg_date_to: '',
                exp_date_type: '', exp_date_value: '', exp_date_from: '', exp_date_to: '',
                current_qty_type: '', current_qty_value: '', current_qty_from: '', current_qty_to: '',
                unit_cost_type: '', unit_cost_value: '', unit_cost_from: '', unit_cost_to: '',
                note: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: '',
            },

            // ------------------------------------------------------------------
            // Hàm lọc tổng quát — hỗ trợ TEXT, NUMBER, DATE
            // ------------------------------------------------------------------
            applyFilter(val, type, { value, from, to, dataType }) {
                if (val == null) return false;

                // -------- TEXT --------
                if (dataType === 'text') {
                    const hasAccent = (s) => /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(s);
                    const normalize = (str) => String(str || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
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
                        return hasAccent(query)
                            ? raw.includes(query)
                            : str.includes(queryNoAccent);
                    }

                    return true;
                }

                // -------- NUMBER --------
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

                    if (type === 'like') {
                        const raw = String(val).replace(/[^\d]/g, '');
                        const query = String(value || '').replace(/[^\d]/g, '');
                        return raw.includes(query);
                    }

                    return true;
                }

                // -------- DATE --------
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
                        return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
                    }
                    if (type === 'lte') {
                        if (!v) return true;
                        const nextDay = new Date(v);
                        nextDay.setDate(v.getDate() + 1);
                        return d < nextDay;
                    }
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                return true;
            },

            // ------------------------------------------------------------------
            // Áp dụng filter cho toàn bộ bảng
            // ------------------------------------------------------------------
            filtered() {
                let data = this.items; // đây là mảng danh sách phiếu xuất (s)

                // --- TEXT: các cột cấp phiếu ---
                ['product_name', 'batch_code', 'note', 'created_by'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(s =>
                            this.applyFilter(s[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- NUMBER ---
                // Tồn hiện tại
                if (this.filters.current_qty_type) {
                    data = data.filter(s =>
                        this.applyFilter(s.current_qty, this.filters.current_qty_type, {
                            value: this.filters.current_qty_value,
                            from: this.filters.current_qty_from,
                            to: this.filters.current_qty_to,
                            dataType: 'number'
                        })
                    );
                }

                // Giá nhập
                if (this.filters.unit_cost_type) {
                    data = data.filter(s =>
                        this.applyFilter(s.unit_cost, this.filters.unit_cost_type, {
                            value: this.filters.unit_cost_value,
                            from: this.filters.unit_cost_from,
                            to: this.filters.unit_cost_to,
                            dataType: 'number'
                        })
                    );
                }

                // --- DATE ---
                ['mfg_date', 'exp_date', 'created_at'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(s =>
                            this.applyFilter(s[key], this.filters[`${key}_type`], {
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

            // ------------------------------------------------------------------
            // Mở / đóng / reset filter
            // ------------------------------------------------------------------
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                // --- Date type ---
                if (['created_at', 'mfg_date', 'exp_date'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                }

                // --- Number type ---
                else if (['unit_cost', 'current_qty'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                }

                // --- Text type 
                else {
                    this.filters[key] = '';
                }

                // --- Close dropdown ---
                this.openFilter[key] = false;
            },

            // --- lifecycle ---
            async init() {
                await this.fetchAll();
                await this.fetchProducts();
            },

            // --- fetch API ---
            async fetchAll() {
                try {
                    const r = await fetch(api.list);
                    if (r.ok) {
                        const data = await r.json();
                        this.items = data.items || [];
                    }
                } catch (e) { console.error(e); }
            },

            async fetchProducts() {
                try {
                    const r = await fetch(api.products);
                    if (r.ok) {
                        const data = await r.json();
                        this.products = data.items || [];
                    }
                } catch (e) { console.error(e); }
            },

            // --- form control ---
            openCreate() {
                this.form = { id: null, product_id: '', batch_code: '', mfg_date: '', exp_date: '', initial_qty: 0, current_qty: 0, note: '', unit_cost: 0 };
                this.touched = {};
                this.errors = {};
                this.openForm = true;
            },
            openEdit(b) {
                this.form = Object.assign({}, b);
                this.touched = {};
                this.errors = {};
                this.openForm = true;
            },

            validateField(field) {
                // Sản phẩm - bắt buộc
                if (field === 'product_id') {
                    this.errors.product_id = this.form.product_id ? '' : 'Vui lòng chọn sản phẩm';
                }

                // Ngày sản xuất - bắt buộc
                if (field === 'mfg_date') {
                    if (!this.form.mfg_date || this.form.mfg_date.trim() === '') {
                        this.errors.mfg_date = 'Vui lòng nhập ngày sản xuất';
                    } else {
                        this.errors.mfg_date = '';
                    }
                }

                // Hạn sử dụng - bắt buộc
                if (field === 'exp_date') {
                    if (!this.form.exp_date || this.form.exp_date.trim() === '') {
                        this.errors.exp_date = 'Vui lòng nhập hạn sử dụng';
                    } else {
                        this.errors.exp_date = '';
                    }
                }

                // Số lượng - bắt buộc, phải > 0
                if (field === 'initial_qty') {
                    if (!this.form.initial_qty || this.form.initial_qty === '' || this.form.initial_qty === null) {
                        this.errors.initial_qty = 'Vui lòng nhập số lượng';
                    } else if (this.form.initial_qty <= 0) {
                        this.errors.initial_qty = 'Số lượng phải lớn hơn 0';
                    } else {
                        this.errors.initial_qty = '';
                    }
                }

                // Giá nhập - bắt buộc, không âm
                if (field === 'unit_cost') {
                    if (this.form.unit_cost === '' || this.form.unit_cost === null || this.form.unit_cost === undefined) {
                        this.errors.unit_cost = 'Vui lòng nhập giá nhập';
                    } else if (this.form.unit_cost < 0) {
                        this.errors.unit_cost = 'Giá nhập không được âm';
                    } else {
                        this.errors.unit_cost = '';
                    }
                }
            },

            validateAll() {
                this.validateField('product_id');
                this.validateField('mfg_date');
                this.validateField('exp_date');
                this.validateField('initial_qty');
                this.validateField('unit_cost');

                // Mark all as touched
                this.touched = {
                    product_id: true,
                    mfg_date: true,
                    exp_date: true,
                    initial_qty: true,
                    unit_cost: true
                };

                // Check if any errors exist
                return !Object.values(this.errors).some(err => err !== '');
            },

            async submit() {
                if (this.submitting) return;

                // Validate all fields before submit
                if (!this.validateAll()) {
                    this.showToast('Vui lòng kiểm tra lại thông tin!', 'error');
                    return;
                }

                this.submitting = true;
                try {
                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id ? api.update(this.form.id) : api.create;
                    const r = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                    this.openForm = false;
                    this.showToast('Thao tác thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message || 'Lỗi');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Xóa lô này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                    this.showToast('Xóa thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message || 'Lỗi');
                }
            },

            // --- utils ---
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
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>