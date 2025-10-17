<?php
// views/admin/stock-outs/stock-out.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Phiếu xuất kho</span>
</nav>

<div x-data="stockOutPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý phiếu xuất kho</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm phiếu xuất</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:200%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu') ?>
                        <?= selectFilterPopover('type', 'Loại xuất', [
                            '' => '-- Tất cả --',
                            'sale' => 'Bán hàng',
                            'return' => 'Trả hàng NCC',
                            'damage' => 'Hư hỏng',
                            'other' => 'Khác'
                        ]) ?>
                        <?= textFilterPopover('order_code', 'Mã đơn hàng') ?>
                        <?= textFilterPopover('customer_name', 'Khách hàng') ?>
                        <?= selectFilterPopover('status', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            'pending' => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy'
                        ]) ?>
                        <?= dateFilterPopover('out_date', 'Ngày xuất') ?>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Ngày tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(s, idx) in paginated()" :key="s.id">
                        <tr>
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEditModal(s)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="remove(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button x-show="s.status === 'pending'" @click="approveStockOut(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-green-600"
                                    title="Duyệt">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button x-show="s.status === 'approved'" @click="completeStockOut(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-blue-600"
                                    title="Hoàn thành">
                                    <i class="fa-solid fa-check-double"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.code"></td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-blue-100 text-blue-800': s.type === 'sale',
                                        'bg-orange-100 text-orange-800': s.type === 'return',
                                        'bg-red-100 text-red-800': s.type === 'damage',
                                        'bg-gray-100 text-gray-800': s.type === 'other'
                                    }" x-text="getTypeText(s.type)">
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.order_code || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.customer_name || '—'"></td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="px-2 py-[3px] rounded text-xs font-medium" class="flex justify-center items-center h-full">
                                    <span :class="{
                                    'px-2 py-1 rounded text-xs': true,
                                    'bg-yellow-100 text-yellow-800': s.status === 'pending',
                                    'bg-blue-100 text-blue-800': s.status === 'approved',
                                    'bg-green-100 text-green-800': s.status === 'completed',
                                    'bg-red-100 text-red-800': s.status === 'cancelled'
                                }" x-text="getStatusText(s.status)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="s.out_date ? s.out_date.substring(0,10) : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right font-semibold"
                                x-text="formatCurrency(s.total_amount)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.note || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="s.created_at ? s.created_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.created_by_name || '—'">
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="11" class="py-12 text-center text-slate-500">
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
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] overflow-y-auto"
                @click.outside="openAdd=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative sticky top-0 bg-white z-10">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu xuất kho</h3>
                    <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3 sticky bottom-0 bg-white border-t py-3">
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
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] overflow-y-auto"
                @click.outside="openEdit=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative sticky top-0 bg-white z-10">
                    <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu xuất kho</h3>
                    <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3 sticky bottom-0 bg-white border-t py-3">
                        <button type="button" @click="openEdit=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : 'Cập nhật'"></button>
                    </div>
                </form>
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
                        <div @click="perPage=opt; open=false" class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                            x-text="opt + ' / trang'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function stockOutPage() {
        const api = {
            list: '/admin/api/stock-outs',
            create: '/admin/api/stock-outs',
            update: (id) => `/admin/api/stock-outs/${id}`,
            remove: (id) => `/admin/api/stock-outs/${id}`,
            approve: (id) => `/admin/api/stock-outs/${id}/approve`,
            complete: (id) => `/admin/api/stock-outs/${id}/complete`,
            nextCode: '/admin/api/stock-outs/next-code',
            orders: '/admin/api/orders',
        };

        const MAX_AMOUNT = 1_000_000_000;
        const MAXLEN = 255;

        return {
            // ===== STATE =====
            loading: true,
            submitting: false,
            openAdd: false,
            openEdit: false,
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
                type: 'sale',
                order_id: '',
                status: 'pending',
                out_date: '',
                total_amount: 0,
                total_amountFormatted: '',
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
                    const data = await res.json();
                    this.items = data.items || [];
                } catch (e) {
                    this.showToast('Không thể tải dữ liệu phiếu xuất kho');
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

                    if (['total_amount'].includes(key)) {
                        data = data.filter(r => Number(r[key]) === Number(val));
                    } else if (['created_at', 'out_date'].includes(key)) {
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
                this.form.total_amount = val;
                this.form.total_amountFormatted = val.toLocaleString('en-US');
            },

            getTypeText(type) {
                const map = {
                    'sale': 'Bán hàng',
                    'return': 'Trả hàng NCC',
                    'damage': 'Hư hỏng',
                    'other': 'Khác'
                };
                return map[type] || type;
            },

            getStatusText(status) {
                const map = {
                    'pending': 'Chờ duyệt',
                    'approved': 'Đã duyệt',
                    'completed': 'Hoàn thành',
                    'cancelled': 'Đã hủy'
                };
                return map[status] || status;
            },

            // ===== VALIDATION =====
            validateField(field) {
                this.errors[field] = '';

                if (field === 'total_amount') {
                    if (!this.form.total_amount || this.form.total_amount < 0)
                        this.errors.total_amount = 'Tổng tiền không hợp lệ';
                    else if (this.form.total_amount > MAX_AMOUNT)
                        this.errors.total_amount = 'Tổng tiền quá lớn';
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['total_amount'];
                for (const f of fields) this.validateField(f);
                return Object.values(this.errors).every(v => !v);
            },

            async fetchOrders() {
                try {
                    const res = await fetch(api.orders);
                    const data = await res.json();
                    this.orders = (data.items || []).map(o => ({
                        id: o.id,
                        code: o.code,
                        customer_name: o.customer_name
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách đơn hàng');
                }
            },

            resetForm() {
                this.form = {
                    id: null,
                    code: '',
                    type: 'sale',
                    order_id: '',
                    status: 'pending',
                    out_date: new Date().toISOString().substring(0, 10),
                    total_amount: 0,
                    total_amountFormatted: '',
                    note: '',
                };
                this.errors = {};
                this.touched = {};
                this.order_id = null;
                this.orders = [];
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

            // ===== CRUD =====
            async openCreate() {
                this.resetForm();
                await Promise.all([
                    this.fetchOrders(),
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

            async openEditModal(s) {
                this.resetForm();
                await this.fetchOrders();
                this.form = {
                    ...s,
                    order_id: s?.order_id ? String(s.order_id) : '',  // thêm dấu ? để tránh undefined
                    out_date: s.out_date ? s.out_date.substring(0, 10) : '',
                    total_amountFormatted: s.total_amount ? s.total_amount.toLocaleString('en-US') : '',
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
                        this.showToast('Thêm phiếu xuất kho thành công!', 'success');
                        this.openAdd = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể thêm phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm phiếu xuất kho');
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
                        this.showToast('Cập nhật phiếu xuất kho thành công!', 'success');
                        this.openEdit = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể cập nhật phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật phiếu xuất kho');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Bạn có chắc muốn xóa phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(r => r.id !== id);
                        this.showToast('Xóa phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể xóa phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa phiếu xuất kho');
                }
            },

            async approveStockOut(id) {
                if (!confirm('Bạn có chắc muốn duyệt phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.approve(id), { method: 'POST' });
                    if (res.ok) {
                        await this.fetchAll();
                        this.showToast('Duyệt phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể duyệt phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể duyệt phiếu xuất kho');
                }
            },

            async completeStockOut(id) {
                if (!confirm('Bạn có chắc muốn hoàn thành phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.complete(id), { method: 'POST' });
                    if (res.ok) {
                        await this.fetchAll();
                        this.showToast('Hoàn thành phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể hoàn thành phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể hoàn thành phiếu xuất kho');
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