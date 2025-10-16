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
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm lô</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:1200px; min-width:800px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 whitespace-nowrap text-center">Thao tác</th>
                        <?= textFilterPopover('product_name', 'Sản phẩm') ?>
                        <?= textFilterPopover('batch_code', 'Mã lô') ?>
                        <?= textFilterPopover('exp_date', 'HSD') ?>
                        <?= numberFilterPopover('current_qty', 'Tồn hiện tại') ?>
                        <?= numberFilterPopover('unit_cost', 'Giá nhập') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in paginated()" :key="b.id">
                        <tr class="border-t">
                            <td class="py-2 px-4 text-center">
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
                            </td>
                            <td class="py-2 px-4" x-text="b.product_name || b.product_sku"></td>
                            <td class="py-2 px-4" x-text="b.batch_code"></td>
                            <td class="py-2 px-4" :class="(b.exp_date || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="b.exp_date || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="b.current_qty"></td>
                            <td class="py-2 px-4 text-right" x-text="formatCurrency(b.unit_cost)"></td>
                            <td class="py-2 px-4" :class="(b.note || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="b.note || '—'"></td>
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
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openForm"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openForm=false">
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

            // --- filters ---
            filtered() {
                let data = this.items;
                if (this.filters.product_name) {
                    data = data.filter(b => (b.product_name || '').toLowerCase().includes(this.filters.product_name.toLowerCase()));
                }
                if (this.filters.batch_code) {
                    data = data.filter(b => (b.batch_code || '').toLowerCase().includes(this.filters.batch_code.toLowerCase()));
                }
                if (this.filters.exp_date) {
                    data = data.filter(b => (b.exp_date || '').toLowerCase().includes(this.filters.exp_date.toLowerCase()));
                }
                if (this.filters.current_qty) {
                    const val = Number(this.filters.current_qty);
                    if (!isNaN(val)) data = data.filter(b => Number(b.current_qty) === val);
                }
                if (this.filters.unit_cost) {
                    const val = Number(this.filters.unit_cost);
                    if (!isNaN(val)) data = data.filter(b => Number(b.unit_cost) === val);
                }
                if (this.filters.note) {
                    data = data.filter(b => (b.note || '').toLowerCase().includes(this.filters.note.toLowerCase()));
                }
                return data;
            },

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                this.filters[key] = '';
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
                    return new Intl.NumberFormat('vi-VN', { 
                        style: 'currency', 
                        currency: 'VND' 
                    }).format(n || 0);
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