<?php
// views/admin/receipts/receipt.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý thu / <span class="text-slate-800 font-medium">Phiếu thu</span>
</nav>

<div x-data="receiptPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý phiếu thu</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm phiếu thu</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:220%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu thu') ?>
                        <?= textFilterPopover('payer_user_name', 'Khách hàng') ?>
                        <?= textFilterPopover('order_id', 'Mã đơn hàng') ?>
                        <?= selectFilterPopover('method', 'Phương thức thanh toán', [
                            '' => '-- Tất cả --',
                            'Tiền mặt' => 'Tiền mặt',
                            'Chuyển khoản' => 'Chuyển khoản',
                            'Quẹt thẻ' => 'Quẹt thẻ',
                            'PayPal' => 'PayPal',
                            'Thanh toán khi nhận hàng (COD)' => 'Thanh toán khi nhận hàng (COD)'
                        ]) ?>
                        <?= numberFilterPopover('amount', 'Số tiền') ?>
                        <?= textFilterPopover('payment_id', 'Bản ghi thanh toán') ?>
                        <?= textFilterPopover('received_by', 'Người thu') ?>
                        <?= dateFilterPopover('received_at', 'Ngày thu') ?>
                        <?= textFilterPopover('txn_ref', 'Mã giao dịch') ?>
                        <?= dateFilterPopover('bank_time', 'Xác nhận giao dịch') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(r, idx) in paginated()" :key="r.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEditModal(r)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="remove(r.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.code || '—') === '—' ? 'text-center' : 'text-left'" x-text="r.code || '—'">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.payer_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.payer_name || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.order_id || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.order_id || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.method || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.method || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(r.amount)">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.payment_id || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.payment_id || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.created_by_name || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.received_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.received_at ? r.received_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.txn_ref || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.txn_ref || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.bank_time || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.bank_time ? r.bank_time : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="r.note || '—'">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.created_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.created_at ? r.created_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.created_by_name || '—'"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="14" class="py-12 text-center text-slate-500">
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
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openAdd=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu thu</h3>
                    <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
                </div>
                <form class="flex flex-col flex-1 overflow-hidden" @submit.prevent="submitCreate()">
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <?php require __DIR__ . '/form.php'; ?>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end gap-3 flex-shrink-0 bg-white">
                        <button type="button"
                            class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white"
                            @click="openAdd=false">Hủy</button>
                        <button
                            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                            :disabled="submitting" x-text="submitting?'Đang lưu...':'Lưu'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Edit -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openEdit" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openEdit=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu thu</h3>
                    <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
                </div>
                <form class="flex flex-col flex-1 overflow-hidden" @submit.prevent="submitUpdate()">
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <?php require __DIR__ . '/form.php'; ?>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end gap-3 flex-shrink-0 bg-white">
                        <button type="button" @click="openEdit=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : 'Cập nhật'"></button>
                    </div>
                </form>
            </div>
        </div>

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
                        <div @click="perPage=opt; open=false" class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                            x-text="opt + ' / trang'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function receiptPage() {
        const api = {
            list: '/admin/api/receipt_vouchers',
            create: '/admin/receipt_vouchers',
            update: (id) => `/admin/receipt_vouchers/${id}`,
            remove: (id) => `/admin/receipt_vouchers/${id}`,
            nextCode: '/admin/api/receipt_vouchers/next-code',
        };

        const MAX_AMOUNT = 1_000_000_000;
        const MAXLEN = 255;

        return {
            // ===== STATE =====
            loading: true,
            submitting: false,
            openAdd: false,
            openEdit: false,
            customer_id: null,
            customers: [],
            staffs: [],
            order_id: null,
            orders: [],

            items: <?= json_encode($items ?? [], JSON_UNESCAPED_UNICODE) ?>,

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
                customer_id: '',
                order_id: '',
                method: '',
                txn_ref: '',
                amount: 0,
                amountFormatted: '',
                received_by_name: '',
                received_at: '',
                note: '',
                is_active: 1,
                bank_time: '',
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
                    const data = await res.json();
                    this.items = data.items || [];
                    console.log('DATA ITEMS', this.items);
                } catch (e) {
                    this.showToast('Không thể tải dữ liệu phiếu thu');
                } finally {
                    this.loading = false;
                }
            },

            // ===== FILTERS =====
            openFilter: {
                code: false, payer_user_name: false, order_id: false, method: false,
                amount: false, payment_id: false, received_by: false, received_at: false,
                txn_ref: false, bank_time: false, note: false, created_at: false, created_by: false
            },
            filters: {
                code: '',
                payer_user_name: '',
                order_id: '',
                method: '',
                amount_type: '', amount_value: '', amount_from: '', amount_to: '',
                payment_id: '',
                received_by: '',
                received_at_type: '', received_at_value: '', received_at_from: '', received_at_to: '',
                txn_ref: '',
                bank_time_type: '', bank_time_value: '', bank_time_from: '', bank_time_to: '',
                note: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: ''
            },

            // Chuẩn hóa ngày cho so sánh (loại bỏ phần giờ)
            applyDateFilter(val, type, value, from, to) {
                if (!type) return true;
                const normalizeDate = (d) => {
                    if (!d) return null;
                    let s = String(d).trim();
                    if (/^\d{1,2}\/\d{1,2}\/\d{4}/.test(s)) {
                        const [dd, mm, yy] = s.split(/[\s\/]/);
                        s = `${yy}-${mm.padStart(2, '0')}-${dd.padStart(2, '0')}`;
                    }
                    if (/^\d{4}-\d{1,2}-\d{1,2}/.test(s)) {
                        s = s.substring(0, 10);
                    }
                    const parsed = new Date(s);
                    if (isNaN(parsed)) return null;
                    return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
                };
                const itemDate = normalizeDate(val);
                if (!itemDate) return false;
                if (type === 'eq') {
                    if (!value) return true;
                    const compareDate = normalizeDate(value);
                    if (!compareDate) return false;
                    return itemDate.getTime() === compareDate.getTime();
                }
                if (type === 'between') {
                    if (!from || !to) return true;
                    const fromDate = normalizeDate(from);
                    const toDate = normalizeDate(to);
                    if (!fromDate || !toDate) return false;
                    return itemDate >= fromDate && itemDate <= toDate;
                }
                if (type === 'lt') {
                    if (!value) return true;
                    const compareDate = normalizeDate(value);
                    if (!compareDate) return false;
                    return itemDate < compareDate;
                }
                if (type === 'gt') {
                    if (!value) return true;
                    const compareDate = normalizeDate(value);
                    if (!compareDate) return false;
                    return itemDate > compareDate;
                }
                if (type === 'lte') {
                    if (!value) return true;
                    const compareDate = normalizeDate(value);
                    if (!compareDate) return false;
                    return itemDate <= compareDate;
                }
                if (type === 'gte') {
                    if (!value) return true;
                    const compareDate = normalizeDate(value);
                    if (!compareDate) return false;
                    return itemDate >= compareDate;
                }
                return true;
            },

            applyNumberFilter(val, type, value, from, to) {
                const num = Number(val);
                if (isNaN(num)) return false;
                if (!type) return true;
                if (type === 'eq') {
                    if (!value && value !== 0) return true;
                    return num === Number(value);
                }
                if (type === 'between') {
                    if ((!from && from !== 0) || (!to && to !== 0)) return true;
                    return num >= Number(from) && num <= Number(to);
                }
                if (type === 'lt') {
                    if (!value && value !== 0) return true;
                    return num < Number(value);
                }
                if (type === 'gt') {
                    if (!value && value !== 0) return true;
                    return num > Number(value);
                }
                if (type === 'lte') {
                    if (!value && value !== 0) return true;
                    return num <= Number(value);
                }
                if (type === 'gte') {
                    if (!value && value !== 0) return true;
                    return num >= Number(value);
                }
                return true;
            },

            filtered() {
                const fn = (v) => (v ?? '').toString().toLowerCase();
                const f = this.filters;
                return this.items.filter(r => {
                    if (f.code && !fn(r.code).includes(fn(f.code))) return false;
                    if (f.payer_user_name && !fn(r.payer_user_name).includes(fn(f.payer_user_name))) return false;
                    if (f.order_id && !fn(r.order_id).includes(fn(f.order_id))) return false;
                    if (f.method && !fn(r.method).includes(fn(f.method))) return false;
                    if (f.payment_id && !fn(r.payment_id).includes(fn(f.payment_id))) return false;
                    if (f.received_by && !fn(r.received_by).includes(fn(f.received_by))) return false;
                    if (f.txn_ref && !fn(r.txn_ref).includes(fn(f.txn_ref))) return false;
                    if (f.note && !fn(r.note).includes(fn(f.note))) return false;
                    if (f.created_by && !fn(r.created_by_name || '').includes(fn(f.created_by))) return false;
                    if (!this.applyNumberFilter(r.amount, f.amount_type, f.amount_value, f.amount_from, f.amount_to)) return false;
                    if (!this.applyDateFilter(r.received_at, f.received_at_type, f.received_at_value, f.received_at_from, f.received_at_to)) return false;
                    if (!this.applyDateFilter(r.bank_time, f.bank_time_type, f.bank_time_value, f.bank_time_from, f.bank_time_to)) return false;
                    if (!this.applyDateFilter(r.created_at, f.created_at_type, f.created_at_value, f.created_at_from, f.created_at_to)) return false;
                    return true;
                });
            },

            toggleFilter(key) {
                Object.keys(this.openFilter).forEach(k => this.openFilter[k] = (k === key ? !this.openFilter[k] : false));
            },
            applyFilter(key) {
                this.openFilter[key] = false;
            },
            resetFilter(key) {
                if (['received_at', 'bank_time', 'created_at'].includes(key)) {
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

            // --- utils ---
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

                // Khách hàng - bắt buộc
                if (field === 'customer_id') {
                    if (!this.form.customer_id || this.form.customer_id === '') {
                        this.errors.customer_id = 'Vui lòng chọn khách hàng';
                    }
                }

                // Phương thức thanh toán - bắt buộc
                if (field === 'method') {
                    if (!this.form.method || this.form.method.trim() === '') {
                        this.errors.method = 'Vui lòng chọn phương thức thanh toán';
                    }
                }

                // Ngày thu - bắt buộc
                if (field === 'received_at') {
                    if (!this.form.received_at || this.form.received_at.trim() === '') {
                        this.errors.received_at = 'Vui lòng chọn ngày thu';
                    }
                }

                // Số tiền - bắt buộc, phải > 0
                if (field === 'amount') {
                    if (this.form.amount === '' || this.form.amount === null || this.form.amount === undefined) {
                        this.errors.amount = 'Vui lòng nhập số tiền';
                    } else if (this.form.amount <= 0) {
                        this.errors.amount = 'Số tiền phải lớn hơn 0';
                    } else if (this.form.amount > MAX_AMOUNT) {
                        this.errors.amount = 'Số tiền quá lớn (tối đa 1 tỷ)';
                    }
                }

                // Người thu - bắt buộc
                if (field === 'received_by') {
                    if (!this.form.received_by || this.form.received_by === '') {
                        this.errors.received_by = 'Vui lòng chọn người thu';
                    }
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['customer_id', 'method', 'received_at', 'amount', 'received_by'];
                for (const f of fields) this.validateField(f);

                // Mark all as touched
                this.touched = {
                    customer_id: true,
                    method: true,
                    received_at: true,
                    amount: true,
                    received_by: true
                };

                return Object.values(this.errors).every(v => !v);
            },

            resetForm() {
                this.form = {
                    id: null,
                    code: '',
                    customer_id: '',
                    order_id: '',
                    method: '',
                    txn_ref: '',
                    amount: 0,
                    amountFormatted: '',
                    received_by_name: '',
                    received_at: '',
                    note: '',
                    is_active: 1,
                    bank_time: '',
                };
                this.errors = {};
                this.touched = {};
                this.customer_id = null;
                this.customers = [];
                this.user_id = null;
                this.users = [];
                this.order_id = null;
                this.orders = [];
            },

            async fetchCustomers() {
                try {
                    const res = await fetch('/admin/api/customers');
                    const data = await res.json();
                    this.customers = (data.items || []).map(u => ({
                        id: u.id,
                        name: u.full_name
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách khách hàng');
                }
            },

            async fetchUsers() {
                try {
                    const res = await fetch('/admin/api/staff');
                    const data = await res.json();
                    this.staffs = (data.items || []).map(u => ({
                        id: u.user_id,
                        name: u.full_name
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách nhân viên');
                }
            },

            async fetchOrders() {
                try {
                    const res = await fetch('/admin/api/orders/unpaid');
                    if (res.ok) {
                        const data = await res.json();
                        this.orders = data.items || [];
                    } else {
                        this.orders = [];
                    }
                } catch {
                    this.orders = [];
                }
            },

            // ===== FILTERED ORDERS THEO KHÁCH HÀNG =====
            get filteredOrders() {
                if (!this.form.customer_id || !this.orders.length) return this.orders;
                return this.orders.filter(o => String(o.customer_id) === String(this.form.customer_id));
            },

            // ===== CRUD =====
            async openCreate() {
                this.resetForm();
                await Promise.all([
                    this.fetchCustomers(),
                    this.fetchNextCode(),
                    this.fetchUsers(),
                    this.fetchOrders()
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

            async openEditModal(r) {
                this.resetForm();
                await Promise.all([
                    this.fetchCustomers(),
                    this.fetchUsers(),
                    this.fetchOrders()
                ]);
                this.form = {
                    ...r,
                    customer_id: r.customer_id ? String(r.customer_id) : '',
                    order_id: r.order_id ? String(r.order_id) : '',
                    amountFormatted: r.amount ? r.amount.toLocaleString('en-US') : '',
                };
                this.openEdit = true;
            },

            async submitCreate() {
                if (!this.validateForm()) {
                    this.showToast('Vui lòng kiểm tra lại thông tin!', 'error');
                    return;
                }
                this.submitting = true;
                try {
                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        this.showToast('Thêm phiếu thu thành công!', 'success');
                        this.openAdd = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể thêm phiếu thu');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm phiếu thu');
                } finally {
                    this.submitting = false;
                }
            },

            async submitUpdate() {
                if (!this.validateForm()) {
                    this.showToast('Vui lòng kiểm tra lại thông tin!', 'error');
                    return;
                }
                this.submitting = true;
                try {
                    const res = await fetch(api.update(this.form.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        this.showToast('Cập nhật phiếu thu thành công!', 'success');
                        this.openEdit = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể cập nhật phiếu thu');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật phiếu thu');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Bạn có chắc muốn xóa phiếu thu này?')) return;
                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(r => r.id !== id);
                        this.showToast('Xóa phiếu thu thành công!', 'success');
                    } else {
                        this.showToast('Không thể xóa phiếu thu');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa phiếu thu');
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
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>