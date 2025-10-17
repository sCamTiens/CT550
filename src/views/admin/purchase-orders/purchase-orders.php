<?php
// views/admin/purchase-orders/purchase-orders.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Phiếu nhập kho</span>
</nav>

<div x-data="purchaseOrdersPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Phiếu nhập kho</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm phiếu nhập kho</button>
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
                        <?= textFilterPopover('paid_amount', 'Số tiền đã thanh toán') ?>
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
                                <!-- Debug: hiện giá trị payment_status -->
                                <!-- <span x-text="'Status: ' + po.payment_status" class="text-xs"></span> -->

                                <!-- Hiện nút sửa/xóa nếu KHÔNG phải đã thanh toán (0 hoặc 2) -->
                                <template x-if="po.payment_status != '0' && po.payment_status != '2'">
                                    <div class="inline-flex space-x-2">
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
                                    </div>
                                </template>
                                <template x-if="po.payment_status == '0' || po.payment_status == '2'">
                                    <span class="text-slate-400 text-sm">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.code"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.supplier_name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="po.total_amount ? formatCurrency(po.total_amount) : '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="po.paid_amount ? formatCurrency(po.paid_amount) : '0'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="po.due_date || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="po.note || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="statusLabel(po.payment_status)" :class="statusLabel(po.payment_status) === 'Đã thanh toán hết' 
                                    ? 'text-green-600 font-semibold'
                                    : (statusLabel(po.payment_status) === 'Đã thanh toán một phần' 
                                        ? 'text-orange-600 font-semibold' 
                                        : 'text-red-600 font-semibold')">
                                </span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.received_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="po.received_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="po.created_by_name || '—'">
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
        <div class="bg-white w-full max-w-5xl rounded-xl shadow" @click.outside="openAdd=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu nhập</h3>
                <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                <?php require __DIR__ . '/form.php'; ?>
            </form>
        </div>
    </div>
    <!-- MODAL: Edit -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow" @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu nhập</h3>
                <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                <?php require __DIR__ . '/form.php'; ?>
            </form>
        </div>
    </div>

    <div id="toast-container" class="z-[60]"></div>

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
    function purchaseOrdersPage() {
        return {
            items: [],
            loading: true,
            openAdd: false,
            openEdit: false,
            submitting: false,
            touched: {},
            errors: {},
            form: {},
            currentPage: 1,
            perPage: 20,
            perPageOptions: [10, 20, 50, 100],

            // thêm mấy biến/hàm frontend bị thiếu
            supplier_id: null,
            suppliers: [],
            product_id: null,
            products: [],
            search: '',
            lines: [],
            touchedLines: [],
            reset() {
                this.form = {
                    payment_status: 'Chưa đối soát',
                    paid_amount: 0,
                    due_date: null
                };
                this.supplier_id = null;
                this.product_id = null;
                this.search = '';
                this.lines = [];
                this.touchedLines = [];
                this.touched = {};
                this.errors = {};
                // Không reset suppliers và products vì chúng đã được fetch
            },

            formatCurrency(n) {
                try { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0) }
                catch { return n }
            },

            calculateTotal() {
                return this.lines.reduce((sum, line) => {
                    const qty = parseInt(line.qty) || 0;
                    const cost = parseInt(line.unit_cost) || 0;
                    return sum + (qty * cost);
                }, 0);
            },

            async init() {
                this.loading = true;
                try {
                    const res = await fetch('/admin/api/purchase-orders');
                    const data = await res.json();
                    this.items = data.items || [];
                    // gọi API lấy suppliers luôn khi load trang
                    this.fetchSuppliers();
                    this.fetchProducts();
                    // reset các lỗi và trạng thái touch
                    this.touched = {};
                    this.errors = {};
                } catch (e) {
                    this.showToast('Không thể tải danh sách phiếu nhập');
                } finally {
                    this.loading = false;
                }
            },

            async fetchProducts() {
                try {
                    const res = await fetch('/admin/api/products');
                    const data = await res.json();
                    this.products = data.items || [];
                } catch (e) {
                    this.showToast('Không thể tải danh sách sản phẩm');
                }
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

            validateField(field, index = null) {
                if (!this.errors) this.errors = {};
                this.errors[field] = '';

                // Nhà cung cấp
                if (field === 'supplier_id' && !this.form.supplier_id) {
                    this.errors[field] = 'Vui lòng chọn nhà cung cấp';
                }

                // Ngày nhập
                if (field === 'created_at' && !this.form.created_at) {
                    this.errors[field] = 'Vui lòng chọn ngày nhập';
                }

                // Số tiền thanh toán
                if (field === 'paid_amount') {
                    const total = this.calculateTotal();
                    if (this.form.payment_status === 'Đã thanh toán một phần') {
                        if (!this.form.paid_amount || this.form.paid_amount <= 0) {
                            this.errors[field] = 'Số tiền phải lớn hơn 0';
                        } else if (this.form.paid_amount >= total) {
                            this.errors[field] = 'Số tiền phải nhỏ hơn tổng tiền (' + total.toLocaleString('vi-VN') + ' đ)';
                        }
                    } else if (this.form.payment_status === 'Đã thanh toán hết') {
                        this.form.paid_amount = total;
                    }
                }

                // Dòng sản phẩm
                if (field === 'lines' && this.lines.length === 0) {
                    this.errors[field] = 'Vui lòng chọn ít nhất một mặt hàng';
                }

                // Kiểm tra từng dòng sản phẩm
                if (['product', 'qty', 'unit_cost'].includes(field) && index !== null) {
                    const line = this.lines[index];

                    if (field === 'product' && !line.product_id) {
                        // Chỉ hiện lỗi nếu đã submit hoặc đã blur input này
                        if (this.touchedLines[index] || this.submitting) {
                            this.errors[`product_${index}`] = 'Vui lòng chọn sản phẩm';
                        } else {
                            this.errors[`product_${index}`] = '';
                        }
                    } else {
                        this.errors[`product_${index}`] = '';
                    }

                    if (field === 'qty') {
                        if ((!line.qty || line.qty < 1) && (this.touchedLines[index] || this.submitting)) {
                            this.errors[`qty_${index}`] = 'Số lượng phải lớn hơn hoặc bằng 1';
                        } else if (line.qty > 999 && (this.touchedLines[index] || this.submitting)) {
                            this.errors[`qty_${index}`] = 'Số lượng không vượt quá 999';
                        } else {
                            this.errors[`qty_${index}`] = '';
                        }
                    }

                    if (field === 'unit_cost') {
                        if ((line.unit_cost < 0) && (this.touchedLines[index] || this.submitting)) {
                            this.errors[`unit_cost_${index}`] = 'Giá nhập không được âm';
                        } else if (line.unit_cost > 999999999 && (this.touchedLines[index] || this.submitting)) {
                            this.errors[`unit_cost_${index}`] = 'Giá nhập không vượt quá 999,999,999';
                        } else {
                            this.errors[`unit_cost_${index}`] = '';
                        }
                    }
                }
            },

            // filters
            openFilter: {},
            filters: {},

            statusLabel(s) {
                // NULL hoặc undefined = Chưa đối soát
                if (s === null || s === undefined || s === '' || s === '1') {
                    return 'Chưa đối soát';
                }

                switch (String(s)) {
                    case '0': return 'Đã thanh toán một phần';
                    case '2': return 'Đã thanh toán hết';
                    default: return 'Chưa đối soát';
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
                // Reset form trước
                this.reset();

                // Nếu suppliers hoặc products chưa được fetch, fetch chúng
                const fetchSuppliersPromise = (this.suppliers.length === 0) ? this.fetchSuppliers() : Promise.resolve();
                const fetchProductsPromise = (this.products.length === 0) ? this.fetchProducts() : Promise.resolve();

                Promise.all([fetchSuppliersPromise, fetchProductsPromise]).then(() => {
                    // Thêm dòng đầu tiên nếu chưa có
                    if (!this.lines || this.lines.length === 0) {
                        this.lines.push({ product_id: '', qty: 1, unit_cost: 0, mfg_date: '', exp_date: '' });
                    }

                    // Mở modal
                    this.openAdd = true;

                    // Khởi tạo flatpickr sau khi modal mở
                    setTimeout(() => {
                        if (typeof window.initAllDatePickers === 'function') {
                            window.initAllDatePickers();
                        }
                    }, 100);
                });
            },

            openEditModal(po) {
                // Reset form trước
                this.reset();

                // Gọi API để lấy chi tiết phiếu nhập kèm các dòng sản phẩm
                fetch(`/admin/api/purchase-orders/${po.id}`)
                    .then(res => res.json())
                    .then(data => {
                        // Fill form với dữ liệu từ API
                        this.form = {
                            id: data.id,
                            supplier_id: data.supplier_id,
                            created_at: data.created_at, // đã được convert sang d/m/Y từ backend
                            payment_status: data.payment_status, // đã được convert sang text
                            paid_amount: data.paid_amount || 0,
                            due_date: data.due_date || null, // đã được convert sang d/m/Y
                            note: data.note || ''
                        };

                        // Fill lines với dữ liệu từ API
                        this.lines = data.lines.map(line => ({
                            product_id: line.product_id,
                            qty: line.qty,
                            unit_cost: line.unit_cost,
                            mfg_date: line.mfg_date || '', // đã được convert sang d/m/Y
                            exp_date: line.exp_date || ''  // đã được convert sang d/m/Y
                        }));

                        // Nếu suppliers hoặc products chưa được fetch, fetch chúng
                        const fetchSuppliersPromise = (this.suppliers.length === 0) ? this.fetchSuppliers() : Promise.resolve();
                        const fetchProductsPromise = (this.products.length === 0) ? this.fetchProducts() : Promise.resolve();

                        Promise.all([fetchSuppliersPromise, fetchProductsPromise]).then(() => {
                            // Mở modal
                            this.openEdit = true;

                            // Khởi tạo flatpickr sau khi modal mở
                            setTimeout(() => {
                                if (typeof window.initAllDatePickers === 'function') {
                                    window.initAllDatePickers();
                                }
                            }, 100);
                        });
                    })
                    .catch(e => {
                        console.error(e);
                        this.showToast('Không thể tải chi tiết phiếu nhập');
                    });
            },

            async submitCreate() {
                this.submitting = true;
                try {
                    // Validate trước khi submit
                    this.validateField('supplier_id');
                    this.validateField('created_at');

                    if (!this.form.supplier_id || !this.form.created_at) {
                        this.showToast('Vui lòng điền đầy đủ thông tin bắt buộc');
                        this.submitting = false;
                        return;
                    }

                    if (this.lines.length === 0) {
                        this.showToast('Vui lòng chọn ít nhất một mặt hàng');
                        this.submitting = false;
                        return;
                    }

                    // Validate lines
                    for (let i = 0; i < this.lines.length; i++) {
                        const line = this.lines[i];
                        if (!line.product_id) {
                            this.showToast(`Dòng ${i + 1}: Chưa chọn sản phẩm`);
                            this.submitting = false;
                            return;
                        }
                        if (!line.qty || line.qty < 1) {
                            this.showToast(`Dòng ${i + 1}: Số lượng không hợp lệ`);
                            this.submitting = false;
                            return;
                        }
                        if (line.unit_cost < 0) {
                            this.showToast(`Dòng ${i + 1}: Giá nhập không hợp lệ`);
                            this.submitting = false;
                            return;
                        }
                    }

                    // Validate số tiền thanh toán
                    if (this.form.payment_status === 'Đã thanh toán một phần') {
                        const total = this.calculateTotal();
                        if (!this.form.paid_amount || this.form.paid_amount <= 0 || this.form.paid_amount >= total) {
                            this.showToast('Số tiền thanh toán phải lớn hơn 0 và nhỏ hơn tổng tiền');
                            this.submitting = false;
                            return;
                        }
                    } else if (this.form.payment_status === 'Đã thanh toán hết') {
                        this.form.paid_amount = this.calculateTotal();
                    }

                    // Chuẩn bị data để gửi
                    const payload = {
                        supplier_id: this.form.supplier_id,
                        created_at: this.convertToYmd(this.form.created_at),
                        payment_status: this.form.payment_status,
                        paid_amount: this.form.paid_amount || 0,
                        due_date: this.convertToYmd(this.form.due_date) || null,
                        note: this.form.note || '',
                        lines: this.lines.map(line => ({
                            product_id: line.product_id,
                            qty: line.qty,
                            unit_cost: line.unit_cost,
                            batch_code: line.batch_code || `BATCH-${Date.now()}`,
                            mfg_date: this.convertToYmd(line.mfg_date),
                            exp_date: this.convertToYmd(line.exp_date)
                        }))
                    };

                    const res = await fetch('/admin/api/purchase-orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (res.ok && data.id) {
                        // Reload danh sách để lấy data mới nhất
                        await this.init();
                        this.openAdd = false;
                        this.showToast('Thêm phiếu nhập thành công!', 'success');
                    } else {
                        this.showToast(data.message || data.error || 'Không thể thêm phiếu nhập');
                        console.error('Server error:', data);
                    }
                } catch (e) {
                    console.error(e);
                    this.showToast('Không thể thêm phiếu nhập');
                } finally {
                    this.submitting = false;
                }
            },
            // Thêm hàm convert date
            convertToYmd(dateStr) {
                if (!dateStr) return null;

                // Nếu đã đúng format Y-m-d thì giữ nguyên
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                    return dateStr;
                }

                // Convert từ d/m/Y sang Y-m-d
                const match = dateStr.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (match) {
                    return `${match[3]}-${match[2]}-${match[1]}`;
                }

                return null;
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
                        // Reload danh sách để lấy data mới nhất
                        await this.init();
                        this.openEdit = false;
                        this.showToast('Cập nhật phiếu nhập thành công!', 'success');
                    } else {
                        this.showToast(data.error || 'Không thể cập nhật phiếu nhập');
                    }
                } catch (e) {
                    console.error(e);
                    this.showToast('Không thể cập nhật phiếu nhập');
                } finally {
                    this.submitting = false;
                }
            },
            async remove(id) {
                if (!confirm('Xóa phiếu nhập này? Hành động này sẽ xóa tất cả dữ liệu liên quan (lô hàng, biến động kho, phiếu chi).')) return;
                try {
                    const res = await fetch(`/admin/api/purchase-orders/${id}`, { method: 'DELETE' });
                    const data = await res.json();

                    if (res.ok) {
                        // Xóa khỏi danh sách
                        this.items = this.items.filter(i => i.id !== id);
                        this.showToast('Xóa phiếu nhập thành công!', 'success');
                    } else {
                        // Hiển thị lỗi từ server
                        this.showToast(data.error || 'Không thể xóa phiếu nhập');
                    }
                } catch (e) {
                    console.error(e);
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