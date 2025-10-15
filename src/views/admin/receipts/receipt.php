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
            <table style="width:180%; min-width:1250px; border-collapse:collapse;">
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
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(r, idx) in paginated()" :key="r.id">
                        <tr>
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
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="r.code"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="r.payer_user_name"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.order_id || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="r.order_id || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="r.method"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(r.amount)">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.payment_id || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.payment_id || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(r.received_by || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="r.received_by || '—'"></td>
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
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openAdd=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu thu</h3>
                    <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="openAdd=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : 'Tạo'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Edit -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openEdit=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu thu</h3>
                    <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3">
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
                } catch (e) {
                    this.showToast('Không thể tải dữ liệu phiếu thu');
                } finally {
                    this.loading = false;
                }
            },

            // ===== FILTERS =====
            openFilter: {},
            filters: {},

            filtered() {
                let data = this.items;

                for (const key in this.filters) {
                    const val = this.filters[key];
                    if (!val) continue;

                    if (['amount'].includes(key)) {
                        data = data.filter(r => Number(r.amount) === Number(val));
                    } else if (['received_at', 'created_at', 'bank_time'].includes(key)) {
                        data = data.filter(r => (r[key] || '').startsWith(val));
                    } else {
                        data = data.filter(r => (r[key] || '').toLowerCase().includes(val.toLowerCase()));
                    }
                }

                return data;
            },

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                delete this.filters[key];
                this.openFilter[key] = false;
            },

            // ===== UTILITIES =====
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0);
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

                if (field === 'received_at' && !this.form.received_at) {
                    this.errors.received_at = 'Vui lòng chọn ngày thu';
                }
                if (field === 'amount') {
                    if (!this.form.amount || this.form.amount <= 0)
                        this.errors.amount = 'Số tiền phải lớn hơn 0';
                    else if (this.form.amount > MAX_AMOUNT)
                        this.errors.amount = 'Số tiền quá lớn';
                }
                if (field === 'received_by_name') {
                    if (!this.form.received_by_name?.trim())
                        this.errors.received_by_name = 'Vui lòng nhập tên người thu';
                    else if (this.form.received_by_name.length > MAXLEN)
                        this.errors.received_by_name = 'Tên người thu quá dài';
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['received_at', 'amount', 'received_by_name'];
                for (const f of fields) this.validateField(f);
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
                if (!this.validateForm()) return;
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
                if (!this.validateForm()) return;
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