<?php
// views/admin/purchase-orders/purchase-orders.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Quản lý đơn mua hàng</span>
</nav>

<div x-data="purchaseOrdersPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý đơn mua hàng</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm đơn mua</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:180%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu') ?>
                        <?= textFilterPopover('supplier_name', 'Nhà cung cấp') ?>
                        <?= textFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= textFilterPopover('paid_amount', 'Trạng thái thanh toán') ?>
                        <?= dateFilterPopover('due_date', 'Ngày hẹn thanh toán') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= selectFilterPopover('payment_status', 'Trạng thái thanh toán', [
                            '' => '-- Tất cả --',
                            '1' => 'Chưa đối soát',
                            '0' => 'Đã thanh toán một phần',
                            '2' => 'Đã thanh toán hết'
                        ]) ?>
                        <?= dateFilterPopover('received_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="po in paginated()" :key="po.id">
                        <tr class="border-t">
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEditModal(po)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="remove(po.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.code"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.supplier_name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="po.total_amount ? po.total_amount.toLocaleString('vi-VN'): '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="po.paid_amount ? po.paid_amount.toLocaleString('vi-VN'): '0'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.due_date || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.note || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="statusLabel(po.payment_status)" :class="statusLabel(po.payment_status) === 'Đã thanh toán hết' 
                                    ? 'text-green-600 font-semibold'
                                    : (statusLabel(po.payment_status) === 'Đã thanh toán một phần' 
                                        ? 'text-orange-600 font-semibold' 
                                        : 'text-red-600 font-semibold')">
                                </span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.received_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.created_by_name || '—'">
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="12" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Trống</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: Create -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openAdd=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu nhập</h3>
                <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600 
                  hover:bg-red-600 hover:text-white transition-colors" @click="openAdd=false">Hủy</button>
                    <button
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting?'Đang lưu...':'Lưu'"></button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL: Edit -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu nhập</h3>
                <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
                    <button
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting?'Đang lưu...':'Cập nhật'"></button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container" class="z-[60]"></div>
</div>

<script>
    function purchaseOrdersPage() {
        return {
            items: [],
            loading: true,
            openAdd: false,
            openEdit: false,
            submitting: false,
            form: {},
            currentPage: 1,
            perPage: 20,
            perPageOptions: [10, 20, 50, 100],

            // 🔹 thêm mấy biến/hàm frontend bị thiếu
            supplier_id: null,
            suppliers: [],
            search: '',
            lines: [],
            reset() {
                this.form = {};
                this.supplier_id = null;
                this.suppliers = [];
                this.search = '';
                this.lines = [];
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

            async init() {
                this.loading = true;
                try {
                    const res = await fetch('/admin/api/purchase-orders');
                    const data = await res.json();
                    this.items = data.items || [];
                    // gọi API lấy suppliers luôn khi load trang
                    this.fetchSuppliers();
                } catch (e) {
                    this.showToast('Không thể tải danh sách phiếu nhập');
                } finally {
                    this.loading = false;
                }
            },

            // filters
            openFilter: {},
            filters: {},

            statusLabel(s) {
                switch (String(s)) {
                    case '0': return 'Đã thanh toán một phần';
                    case '1': return 'Chưa đối soát';
                    case '2': return 'Đã thanh toán hết';
                    default: return 'Không rõ';
                }
            },

            // lọc client-side
            filtered() {
                let data = this.items;
                if (this.filters.code) {
                    data = data.filter(p => (p.code || '').toLowerCase().includes(this.filters.code.toLowerCase()));
                }
                if (this.filters.supplier_name) {
                    data = data.filter(p => (p.supplier_name || '').toLowerCase().includes(this.filters.supplier_name.toLowerCase()));
                }
                if (this.filters.total_amount) {
                    const val = Number(this.filters.total_amount);
                    if (!isNaN(val)) data = data.filter(p => Number(p.total_amount) === val);
                }
                if (this.filters.paid_amount) {
                    const val = Number(this.filters.paid_amount);
                    if (!isNaN(val)) data = data.filter(p => Number(p.paid_amount) === val);
                }
                if (this.filters.payment_status !== undefined && this.filters.payment_status !== '') {
                    data = data.filter(p => String(p.payment_status) === String(this.filters.payment_status));
                }
                if (this.filters.note) {
                    data = data.filter(p => (p.note || '').toLowerCase().includes(this.filters.note.toLowerCase()));
                }
                if (this.filters.created_by_name) {
                    data = data.filter(p => (p.created_by_name || '').toLowerCase().includes(this.filters.created_by_name.toLowerCase()));
                }

                // lọc ngày tạo
                if (this.filters.received_at_value && this.filters.received_at_type === 'eq') {
                    data = data.filter(p => (p.received_at || '').startsWith(this.filters.received_at_value));
                }
                if (this.filters.received_at_from && this.filters.received_at_to && this.filters.received_at_type === 'between') {
                    data = data.filter(p => p.received_at >= this.filters.received_at_from && p.received_at <= this.filters.received_at_to);
                }

                // lọc ngày hẹn thanh toán
                if (this.filters.due_date_value && this.filters.due_date_type === 'eq') {
                    data = data.filter(p => (p.due_date || '').startsWith(this.filters.due_date_value));
                }
                if (this.filters.due_date_from && this.filters.due_date_to && this.filters.due_date_type === 'between') {
                    data = data.filter(p => p.due_date >= this.filters.due_date_from && p.due_date <= this.filters.due_date_to);
                }

                return data;
            },

            paginated() {
                const arr = this.filtered();
                const start = (this.currentPage - 1) * this.perPage;
                return arr.slice(start, start + this.perPage);
            },
            totalPages() {
                return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
            },
            goToPage(p) {
                if (p < 1) p = 1;
                if (p > this.totalPages()) p = this.totalPages();
                this.currentPage = p;
            },
            openCreate() {
                this.reset();
                this.openAdd = true;
            },
            openEditModal(po) {
                this.form = { ...po };
                this.openEdit = true;
            },
            async submitCreate() {
                this.submitting = true;
                try {
                    const res = await fetch('/admin/api/purchase-orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();
                    if (res.ok && data.id) {
                        this.items.unshift({ ...this.form, id: data.id });
                        this.openAdd = false;
                        this.showToast('Thêm phiếu nhập thành công!', 'success');
                    } else {
                        this.showToast(data.error || 'Không thể thêm phiếu nhập');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm phiếu nhập');
                } finally {
                    this.submitting = false;
                }
            },
            async submitUpdate() {
                this.submitting = true;
                try {
                    const res = await fetch(`/admin/api/purchase-orders/${this.form.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();
                    if (res.ok && data.id) {
                        const idx = this.items.findIndex(i => i.id === data.id);
                        if (idx !== -1) this.items[idx] = { ...this.form, id: data.id };
                        this.openEdit = false;
                        this.showToast('Cập nhật phiếu nhập thành công!', 'success');
                    } else {
                        this.showToast(data.error || 'Không thể cập nhật phiếu nhập');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật phiếu nhập');
                } finally {
                    this.submitting = false;
                }
            },
            async remove(id) {
                if (!confirm('Xóa phiếu nhập này?')) return;
                try {
                    const res = await fetch(`/admin/api/purchase-orders/${id}`, { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(i => i.id !== id);
                        this.showToast('Xóa phiếu nhập thành công!', 'success');
                    } else {
                        this.showToast('Không thể xóa phiếu nhập');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa phiếu nhập');
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

            // Filter popover logic
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                this.filters[key] = '';
                this.filters[key + '_type'] = '';
                this.filters[key + '_value'] = '';
                this.filters[key + '_from'] = '';
                this.filters[key + '_to'] = '';
                this.openFilter[key] = false;
            },

        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>