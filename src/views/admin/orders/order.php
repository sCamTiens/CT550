<?php
// views/admin/orders/order.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý bán hàng / <span class="text-slate-800 font-medium">Đơn hàng</span>
</nav>

<div x-data="orderPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý đơn hàng</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm đơn hàng</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:180%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã đơn hàng') ?>
                        <?= textFilterPopover('customer_name', 'Khách hàng') ?>
                        <?= selectFilterPopover('status', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            'pending' => 'Chờ xử lý',
                            'confirmed' => 'Đã xác nhận',
                            'preparing' => 'Đang chuẩn bị',
                            'shipping' => 'Đang giao',
                            'delivered' => 'Đã giao',
                            'cancelled' => 'Đã hủy',
                            'returned' => 'Đã trả'
                        ]) ?>
                        <?= numberFilterPopover('subtotal', 'Tạm tính') ?>
                        <?= numberFilterPopover('discount_amount', 'Giảm giá') ?>
                        <?= numberFilterPopover('shipping_fee', 'Phí vận chuyển') ?>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= textFilterPopover('payment_method', 'PT thanh toán') ?>
                        <?= selectFilterPopover('payment_status', 'TT thanh toán', [
                            '' => '-- Tất cả --',
                            'pending' => 'Chờ thanh toán',
                            'paid' => 'Đã thanh toán',
                            'failed' => 'Thất bại',
                            'refunded' => 'Đã hoàn'
                        ]) ?>
                        <?= textFilterPopover('shipping_address', 'Địa chỉ giao') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(o, idx) in paginated()" :key="o.id">
                        <tr>
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEditModal(o)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="remove(o.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.code"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.customer_name || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line">
                                <span :class="{
                                    'px-2 py-1 rounded text-xs': true,
                                    'bg-yellow-100 text-yellow-800': o.status === 'pending',
                                    'bg-blue-100 text-blue-800': o.status === 'confirmed',
                                    'bg-purple-100 text-purple-800': o.status === 'preparing',
                                    'bg-orange-100 text-orange-800': o.status === 'shipping',
                                    'bg-green-100 text-green-800': o.status === 'delivered',
                                    'bg-red-100 text-red-800': o.status === 'cancelled',
                                    'bg-gray-100 text-gray-800': o.status === 'returned'
                                }" x-text="getStatusText(o.status)"></span>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.subtotal || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.discount_amount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.shipping_fee || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right font-semibold"
                                x-text="formatCurrency(o.total_amount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.payment_method || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line">
                                <span :class="{
                                    'px-2 py-1 rounded text-xs': true,
                                    'bg-yellow-100 text-yellow-800': o.payment_status === 'pending',
                                    'bg-green-100 text-green-800': o.payment_status === 'paid',
                                    'bg-red-100 text-red-800': o.payment_status === 'failed',
                                    'bg-gray-100 text-gray-800': o.payment_status === 'refunded'
                                }" x-text="getPaymentStatusText(o.payment_status)"></span>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.shipping_address || '—'">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.note || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="o.created_at ? o.created_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="o.created_by_name || '—'">
                            </td>
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

    <!-- MODAL: Create -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow max-h-[90vh] flex flex-col" @click.outside="openAdd=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                <h3 class="font-semibold text-2xl text-[#002975]">Thêm đơn hàng</h3>
                <button type="button" class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
            </div>

            <form class="flex-1 overflow-y-auto" @submit.prevent="submitCreate()">
                <div class="p-5 space-y-4">
                    <?php require __DIR__ . '/form.php'; ?>
                </div>
                <div class="px-5 pb-5 pt-2 flex justify-end gap-3 border-t bg-white sticky bottom-0">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow max-h-[90vh] flex flex-col" @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa đơn hàng</h3>
                <button type="button" class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
            </div>
            <form class="flex-1 overflow-y-auto" @submit.prevent="submitUpdate()">
                <div class="p-5 space-y-4">
                    <?php require __DIR__ . '/form.php'; ?>
                </div>
                <div class="px-5 pb-5 pt-2 flex justify-end gap-3 border-t bg-white sticky bottom-0">
                    <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
                    <button
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting?'Đang lưu...':'Cập nhật'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-container" class="z-[60]"></div>
</div>

<script>
    function orderPage() {
        const api = {
            list: '/admin/api/orders',
            create: '/admin/orders',
            update: (id) => `/admin/orders/${id}`,
            remove: (id) => `/admin/orders/${id}`,
            nextCode: '/admin/api/orders/next-code',
            customers: '/admin/api/customers',
            products: '/admin/api/products',
        };

        const MAX_AMOUNT = 1_000_000_000;
        const MAXLEN = 255;

        return {
            // ===== STATE =====
            loading: true,
            submitting: false,
            openAdd: false,
            openEdit: false,
            customers: [],
            products: [],
            orderItems: [],
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
                customer_id: null,
                payment_method: 'cash',
                payment_status: 'paid',
                subtotal: 0,
                subtotalFormatted: '',
                discount_amount: 0,
                discount_amountFormatted: '',
                shipping_fee: 0,
                shipping_feeFormatted: '',
                tax_amount: 0,
                tax_amountFormatted: '',
                total_amount: 0,
                total_amountFormatted: '',
                shipping_address: '',
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
                    this.showToast('Không thể tải dữ liệu đơn hàng');
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

                    if (['subtotal', 'discount_amount', 'shipping_fee', 'total_amount'].includes(key)) {
                        data = data.filter(r => Number(r[key]) === Number(val));
                    } else if (['created_at'].includes(key)) {
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

            onAmountInput(field, e) {
                let raw = e.target.value.replace(/[^\d]/g, '');
                let val = Number(raw);
                if (Number.isNaN(val)) val = 0;
                this.form[field] = val;
                this.form[field + 'Formatted'] = val.toLocaleString('en-US');
                this.calculateTotal();
            },

            calculateTotal() {
                // Tính tổng tiền từ danh sách sản phẩm
                const subtotal = this.orderItems.reduce((sum, item) => {
                    return sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
                }, 0);
                
                this.form.subtotal = subtotal;
                this.form.subtotalFormatted = subtotal.toLocaleString('en-US');
                
                const discount = Number(this.form.discount_amount) || 0;
                const total = subtotal - discount;
                
                this.form.total_amount = total;
                this.form.total_amountFormatted = total.toLocaleString('en-US');
            },

            addItem() {
                this.orderItems.push({
                    product_id: '',
                    quantity: 1,
                    unit_price: 0
                });
            },

            removeItem(idx) {
                this.orderItems.splice(idx, 1);
                this.calculateTotal();
            },

            getStatusText(status) {
                const map = {
                    'pending': 'Chờ xử lý',
                    'confirmed': 'Đã xác nhận',
                    'preparing': 'Đang chuẩn bị',
                    'shipping': 'Đang giao',
                    'delivered': 'Đã giao',
                    'cancelled': 'Đã hủy',
                    'returned': 'Đã trả'
                };
                return map[status] || status;
            },

            getPaymentStatusText(status) {
                const map = {
                    'pending': 'Chờ thanh toán',
                    'paid': 'Đã thanh toán',
                    'failed': 'Thất bại',
                    'refunded': 'Đã hoàn'
                };
                return map[status] || status;
            },

            // ===== VALIDATION =====
            validateField(field) {
                this.errors[field] = '';

                if (field === 'total_amount') {
                    if (!this.form.total_amount || this.form.total_amount <= 0)
                        this.errors.total_amount = 'Tổng tiền phải lớn hơn 0';
                    else if (this.form.total_amount > MAX_AMOUNT)
                        this.errors.total_amount = 'Tổng tiền quá lớn';
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['total_amount'];
                for (const f of fields) this.validateField(f);
                
                // Kiểm tra phải có ít nhất 1 sản phẩm
                if (this.orderItems.length === 0) {
                    this.showToast('Vui lòng chọn ít nhất một sản phẩm');
                    return false;
                }
                
                // Kiểm tra tất cả sản phẩm đã được chọn
                for (let i = 0; i < this.orderItems.length; i++) {
                    const item = this.orderItems[i];
                    if (!item.product_id) {
                        this.showToast(`Vui lòng chọn sản phẩm ở dòng ${i + 1}`);
                        return false;
                    }
                    if (!item.quantity || item.quantity <= 0) {
                        this.showToast(`Số lượng phải lớn hơn 0 ở dòng ${i + 1}`);
                        return false;
                    }
                    if (!item.unit_price || item.unit_price <= 0) {
                        this.showToast(`Đơn giá phải lớn hơn 0 ở dòng ${i + 1}`);
                        return false;
                    }
                }
                
                return Object.values(this.errors).every(v => !v);
            },

            resetForm() {
                this.form = {
                    id: null,
                    code: '',
                    customer_id: null,
                    payment_method: 'cash',
                    payment_status: 'paid',
                    subtotal: 0,
                    subtotalFormatted: '',
                    discount_amount: 0,
                    discount_amountFormatted: '',
                    shipping_fee: 0,
                    shipping_feeFormatted: '',
                    tax_amount: 0,
                    tax_amountFormatted: '',
                    total_amount: 0,
                    total_amountFormatted: '',
                    shipping_address: '',
                    note: '',
                };
                this.errors = {};
                this.touched = {};
                this.customers = [];
                this.orderItems = [];
            },



            async fetchProducts() {
                try {
                    const res = await fetch(api.products);
                    const data = await res.json();
                    this.products = (data.items || []).map(p => ({
                        id: p.id,
                        sku: p.sku,
                        name: p.name,
                        sale_price: p.sale_price
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách sản phẩm');
                }
            },

            // ===== CRUD =====
            async openCreate() {
                this.resetForm();
                await Promise.all([
                    this.fetchProducts(),
                    this.fetchNextCode()
                ]);
                // Thêm 1 dòng sản phẩm mặc định
                this.orderItems = [{
                    product_id: '',
                    quantity: 1,
                    unit_price: 0
                }];
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

            async openEditModal(o) {
                this.resetForm();
                await Promise.all([
                    this.fetchProducts(),
                    this.fetchOrderItems(o.id)
                ]);
                this.form = {
                    ...o,
                    customer_id: o.customer_id || null,
                    payment_status: 'paid',
                    subtotalFormatted: o.subtotal ? o.subtotal.toLocaleString('en-US') : '',
                    discount_amountFormatted: o.discount_amount ? o.discount_amount.toLocaleString('en-US') : '',
                    shipping_feeFormatted: o.shipping_fee ? o.shipping_fee.toLocaleString('en-US') : '',
                    tax_amountFormatted: o.tax_amount ? o.tax_amount.toLocaleString('en-US') : '',
                    total_amountFormatted: o.total_amount ? o.total_amount.toLocaleString('en-US') : '',
                };
                this.openEdit = true;
            },

            async fetchOrderItems(orderId) {
                try {
                    const res = await fetch(`/admin/api/orders/${orderId}/items`);
                    const data = await res.json();
                    this.orderItems = (data.items || []).map(item => ({
                        product_id: String(item.product_id),
                        quantity: item.qty,
                        unit_price: item.unit_price
                    }));
                    if (this.orderItems.length === 0) {
                        this.orderItems = [{
                            product_id: '',
                            quantity: 1,
                            unit_price: 0
                        }];
                    }
                } catch (e) {
                    this.showToast('Không thể tải danh sách sản phẩm của đơn hàng');
                    this.orderItems = [{
                        product_id: '',
                        quantity: 1,
                        unit_price: 0
                    }];
                }
            },

            async submitCreate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const payload = {
                        ...this.form,
                        items: this.orderItems.map(item => ({
                            product_id: item.product_id,
                            qty: item.quantity,
                            unit_price: item.unit_price
                        }))
                    };
                    
                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    if (res.ok) {
                        this.showToast('Thêm đơn hàng thành công!', 'success');
                        this.openAdd = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể thêm đơn hàng');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm đơn hàng');
                } finally {
                    this.submitting = false;
                }
            },

            async submitUpdate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const payload = {
                        ...this.form,
                        items: this.orderItems.map(item => ({
                            product_id: item.product_id,
                            qty: item.quantity,
                            unit_price: item.unit_price
                        }))
                    };
                    
                    const res = await fetch(api.update(this.form.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    if (res.ok) {
                        this.showToast('Cập nhật đơn hàng thành công!', 'success');
                        this.openEdit = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể cập nhật đơn hàng');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật đơn hàng');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Bạn có chắc muốn xóa đơn hàng này?')) return;
                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(r => r.id !== id);
                        this.showToast('Xóa đơn hàng thành công!', 'success');
                    } else {
                        this.showToast('Không thể xóa đơn hàng');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa đơn hàng');
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