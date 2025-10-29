<?php
// views/admin/expenses/expense.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý chi / <span class="text-slate-800 font-medium">Phiếu chi</span>
</nav>

<div x-data="expensePage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý phiếu chi</h1>
        <div class="flex gap-2">
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="exportExcel()">
                <i class="fa-solid fa-file-excel"></i> Xuất Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
                @click="openCreate()">+ Thêm phiếu chi</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:180%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu chi') ?>
                        <?= textFilterPopover('purchase_order_code', 'Phiếu nhập') ?>
                        <?= textFilterPopover('supplier_name', 'Nhà cung cấp') ?>
                        <?= selectFilterPopover('method', 'PT thanh toán', [
                            '' => '-- Tất cả --',
                            'Tiền mặt' => 'Tiền mặt',
                            'Chuyển khoản' => 'Chuyển khoản'
                        ]) ?>
                        <?= numberFilterPopover('amount', 'Số tiền') ?>
                        <?= textFilterPopover('paid_by_name', 'Người chi') ?>
                        <?= dateFilterPopover('paid_at', 'Ngày chi') ?>
                        <?= textFilterPopover('txn_ref', 'Mã giao dịch') ?>
                        <?= dateFilterPopover('bank_time', 'Xác nhận NH') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(e, idx) in paginated()" :key="e.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="remove(e.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="e.code"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(e.purchase_order_code || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="e.purchase_order_code || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.supplier_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="e.supplier_name || '—'"></td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-green-100 text-green-800': e.method === 'Tiền mặt',
                                        'bg-red-100 text-orange-800': e.method === 'Chuyển khoản',
                                    }" x-text="getPaymentMethodText(e.method)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(e.amount)">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.paid_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="e.paid_by_name || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.paid_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="e.paid_at ? e.paid_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.txn_ref || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="e.txn_ref || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.bank_time || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="e.bank_time ? e.bank_time : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="e.note || '—'">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.created_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="e.created_at ? e.created_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(e.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="e.created_by_name || '—'"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="13" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Trống</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL: Create -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openAdd" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="openAdd=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu chi</h3>
                    <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 border rounded" @click="openAdd=false">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded"
                            :disabled="submitting">Lưu</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Edit -->
        <!-- <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster" x-show="openEdit"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster" @click.outside="openEdit=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu chi</h3>
                    <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                    
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 border rounded" @click="openEdit=false">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded"
                            :disabled="submitting">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div> -->

        <!-- Toast lỗi nổi -->
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
                        <button class="block w-full text-left px-4 py-2 hover:bg-gray-100"
                            @click="perPage=opt; open=false">{{ opt }}</button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function expensePage() {
        const api = {
            list: '/admin/api/expense_vouchers',
            create: '/admin/api/expense_vouchers',
            update: (id) => `/admin/api/expense_vouchers/${id}`,
            remove: (id) => `/admin/api/expense_vouchers/${id}`,
            nextCode: '/admin/api/expense_vouchers/next-code',
        };

        const MAX_AMOUNT = 1_000_000_000;
        const MAXLEN = 255;

        return {
            // ===== STATE =====
            loading: true,
            submitting: false,
            openAdd: false,
            openEdit: false,

            suppliers: [],
            purchaseOrders: [],
            users: [],

            items: [], // Sẽ được load từ PHP hoặc API

            // ===== PAGINATION =====
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

            // ===== FORM =====
            form: {
                id: null,
                code: '',
                supplier_id: '',
                purchase_order_id: '',
                method: '',
                txn_ref: '',
                amount: 0,
                amountFormatted: '',
                paid_by: '',
                paid_at: '',
                bank_time: '',
                note: '',
            },

            errors: {},
            touched: {},

            // ===== INIT =====
            async init() {
                await this.fetchAll();
            },

            async fetchAll() {
                this.loading = true;
                try {
                    const res = await fetch(api.list);
                    if (!res.ok) {
                        const text = await res.text();
                        console.error('API error:', res.status, text);
                        this.showToast('Không thể tải dữ liệu phiếu chi');
                        return;
                    }
                    const data = await res.json();
                    this.items = data.items || [];
                } catch (e) {
                    console.error('Fetch error:', e);
                    this.showToast('Không thể tải dữ liệu phiếu chi');
                } finally {
                    this.loading = false;
                }
            },

            getPaymentMethodText(payment_method) {
                const map = {
                    'Tiền mặt': 'Tiền mặt',
                    'Chuyển khoản': 'Chuyển khoản',
                };
                return map[payment_method] || payment_method;
            },

            // ===== FILTERS =====
            openFilter: {
                code: false, purchase_order_code: false, supplier_name: false, method: false,
                amount: false, paid_by_name: false, paid_at: false, txn_ref: false,
                bank_time: false, note: false, created_at: false, created_by: false
            },
            filters: {
                code: '',
                purchase_order_code: '',
                supplier_name: '',
                method: '',
                amount_type: '', amount_value: '', amount_from: '', amount_to: '',
                paid_by_name: '',
                paid_at_type: '', paid_at_value: '', paid_at_from: '', paid_at_to: '',
                txn_ref: '',
                bank_time_type: '', bank_time_value: '', bank_time_from: '', bank_time_to: '',
                note: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: ''
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
                ['code', 'purchase_order_code', 'supplier_name', 'paid_by_name', 'received_by', 'txn_ref', 'note', 'created_by'].forEach(key => {
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
                ['method'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'eq', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc các trường số thông thường ---
                ['amount'].forEach(key => {
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
                // Ngày xuất, Ngày tạo
                ['bank_time', 'created_at', 'paid_at'].forEach(key => {
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

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['created_at', 'bank_time', 'paid_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['amount'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            // ===== UTILITIES =====
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
                }
            },

            onAmountInput(e) {
                let raw = e.target.value.replace(/[^\d]/g, '');
                let val = Number(raw);
                if (Number.isNaN(val)) val = 0;
                this.form.amount = val;
                this.form.amountFormatted = val.toLocaleString('en-US');
            },

            // ===== VALIDATION =====
            validateField(field) {
                this.errors[field] = '';

                if (field === 'supplier_id' && !this.form.supplier_id) {
                    this.errors.supplier_id = 'Vui lòng chọn nhà cung cấp';
                }
                if (field === 'purchase_order_id' && !this.form.purchase_order_id) {
                    this.errors.purchase_order_id = 'Vui lòng chọn phiếu nhập';
                }
                if (field === 'method' && !this.form.method) {
                    this.errors.method = 'Vui lòng chọn phương thức thanh toán';
                }
                if (field === 'paid_at' && !this.form.paid_at) {
                    this.errors.paid_at = 'Vui lòng chọn ngày chi';
                }
                if (field === 'amount') {
                    if (!this.form.amount || this.form.amount <= 0) {
                        this.errors.amount = 'Số tiền phải lớn hơn 0';
                    } else if (this.form.amount > MAX_AMOUNT) {
                        this.errors.amount = 'Số tiền quá lớn';
                    } else if (this.selectedPurchaseOrderDebt > 0 && this.form.amount > this.selectedPurchaseOrderDebt) {
                        this.errors.amount = 'Số tiền không được lớn hơn công nợ còn lại (' + this.formatCurrency(this.selectedPurchaseOrderDebt) + ')';
                    }
                }
                if (field === 'paid_by' && !this.form.paid_by) {
                    this.errors.paid_by = 'Vui lòng chọn người chi';
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['supplier_id', 'purchase_order_id', 'method', 'paid_at', 'amount', 'paid_by'];
                for (const f of fields) this.validateField(f);
                return Object.values(this.errors).every(v => !v);
            },

            resetForm() {
                this.form = {
                    id: null,
                    code: '',
                    supplier_id: '',
                    purchase_order_id: '',
                    method: '',
                    txn_ref: '',
                    amount: 0,
                    amountFormatted: '',
                    paid_by: '',
                    paid_at: '',
                    bank_time: '',
                    note: '',
                };
                this.errors = {};
                this.touched = {};
            },

            async fetchSuppliers() {
                try {
                    const res = await fetch('/admin/api/suppliers');
                    const data = await res.json();
                    this.suppliers = data.items || [];
                } catch (e) {
                    this.showToast('Không thể tải danh sách nhà cung cấp');
                }
            },

            async fetchPurchaseOrders() {
                try {
                    const res = await fetch('/admin/api/purchase-orders/unpaid');
                    if (res.ok) {
                        const data = await res.json();
                        this.purchaseOrders = data.items || [];
                    } else {
                        this.purchaseOrders = [];
                    }
                } catch {
                    this.purchaseOrders = [];
                }
            },

            async fetchUsers() {
                try {
                    const res = await fetch('/admin/api/staff');
                    const data = await res.json();
                    this.users = (data.items || []).map(u => ({
                        id: u.user_id,
                        name: u.full_name
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách nhân viên');
                }
            },

            // ===== FILTERED PURCHASE ORDERS THEO NHÀ CUNG CẤP =====
            get filteredPurchaseOrders() {
                if (!this.form.supplier_id || !this.purchaseOrders.length) return this.purchaseOrders;
                return this.purchaseOrders.filter(po => String(po.supplier_id) === String(this.form.supplier_id));
            },

            // ===== CÔNG NỢ CỦA PHIẾU NHẬP ĐƯỢC CHỌN =====
            get selectedPurchaseOrderDebt() {
                if (!this.form.purchase_order_id) return 0;
                const selected = this.purchaseOrders.find(po => String(po.id) === String(this.form.purchase_order_id));
                return selected ? (selected.remaining_debt || 0) : 0;
            },

            // ===== CRUD =====
            async openCreate() {
                this.resetForm();
                await Promise.all([
                    this.fetchSuppliers(),
                    this.fetchPurchaseOrders(),
                    this.fetchUsers(),
                    this.fetchNextCode()
                ]);
                this.openAdd = true;
            },

            async fetchNextCode() {
                try {
                    const res = await fetch(api.nextCode);
                    if (res.ok) {
                        const data = await res.json();
                        this.form.code = data.code;
                    } else {
                        this.form.code = '';
                    }
                } catch {
                    this.form.code = '';
                }
            },

            async openEditModal(e) {
                this.resetForm();
                await Promise.all([
                    this.fetchSuppliers(),
                    this.fetchPurchaseOrders(),
                    this.fetchUsers()
                ]);
                this.form = {
                    ...e,
                    supplier_id: e.supplier_id ? String(e.supplier_id) : '',
                    purchase_order_id: e.purchase_order_id ? String(e.purchase_order_id) : '',
                    paid_by: e.paid_by ? String(e.paid_by) : '',
                    amountFormatted: e.amount ? e.amount.toLocaleString('en-US') : '',
                };
                this.openEdit = true;
            },

            async submitCreate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        const data = await res.json();
                        // Cập nhật items từ response thay vì fetch lại
                        if (data.items) {
                            this.items = data.items;
                        } else {
                            await this.fetchAll();
                        }
                        this.showToast('Thêm phiếu chi thành công!', 'success');
                        this.openAdd = false;
                    } else {
                        const error = await res.json();
                        console.error('Server error:', error);
                        this.showToast('Không thể thêm phiếu chi');
                    }
                } catch (e) {
                    console.error('Request error:', e);
                    this.showToast('Không thể thêm phiếu chi');
                } finally {
                    this.submitting = false;
                }
            },

            async submitUpdate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const res = await fetch(api.update(this.form.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        this.showToast('Cập nhật phiếu chi thành công!', 'success');
                        this.openEdit = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể cập nhật phiếu chi');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật phiếu chi');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Bạn có chắc muốn xóa phiếu chi này?')) return;
                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(e => e.id !== id);
                        this.showToast('Xóa phiếu chi thành công!', 'success');
                    } else {
                        const error = await res.json();
                        this.showToast(error.error || 'Không thể xóa phiếu chi');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa phiếu chi');
                }
            },

            // ===== TOAST =====
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
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
                </svg>
                <div class="flex-1">${msg}</div>
            `;

                box.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            },

            exportExcel() {
                const data = this.filtered().map(e => ({
                    code: e.code || '',
                    purchase_order_code: e.purchase_order_code || '',
                    supplier_name: e.supplier_name || '',
                    method: this.getPaymentMethodText(e.method),
                    amount: e.amount || 0,
                    txn_ref: e.txn_ref || '',
                    bank_time: e.bank_time || '',
                    paid_by_name: e.paid_by_name || '',
                    paid_at: e.paid_at || '',
                    note: e.note || '',
                    created_at: e.created_at || '',
                    created_by_name: e.created_by_name || ''
                }));

                const now = new Date();
                const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
                const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
                const filename = `Phieu_chi_${dateStr}_${timeStr}.xlsx`;

                fetch('/admin/api/expense_vouchers/export', {
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
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>