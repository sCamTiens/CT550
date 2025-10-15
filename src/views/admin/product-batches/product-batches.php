<?php
$items = $items ?? [];
$products = $products ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Lô sản phẩm</span>
</nav>

<div x-data="productBatchesPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý lô sản phẩm</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm lô</button>
    </div>

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
                    <template x-for="b in filtered()" :key="b.id">
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
                                <!-- Icon hộp trống -->
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

                <div x-data="productBatchForm({
                        form: form,
                        products: products,
                        submitting: submitting
                    })">
                    <?php require __DIR__ . '/form.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function productBatchForm(parent) {
        const api = {
            list: '/admin/api/product-batches',
            create: '/admin/api/product-batches',
            update: (id) => `/admin/api/product-batches/${id}`,
            remove: (id) => `/admin/api/product-batches/${id}`,
            products: '/admin/api/products'
        };

        return {
            form: parent.form,
            products: parent.products,
            submitting: parent.submitting,
            touched: {},
            errors: {
                product_id: '', batch_code: '', mfg_date: '', exp_date: '',
                initial_qty: '', current_qty: '', unit_cost: ''
            },

            // filter state
            openFilter: {},
            filters: {},

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

            // toggle popup filter
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                this.filters[key] = '';
                this.openFilter[key] = false;
            },

            init() {
                this.fetchAll();
                this.fetchProducts();
            },

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

            openCreate() { this.form = { id: null, product_id: '', batch_code: '', mfg_date: '', exp_date: '', initial_qty: 0, current_qty: 0, note: '', unit_cost: 0 }; this.openForm = true; },
            openEdit(b) { this.form = Object.assign({}, b); this.openForm = true; },

            async submit() {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id ? api.update(this.form.id) : api.create;
                    const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                    if (!r.ok) throw new Error('Lỗi server');
                    const res = await r.json();
                    await this.fetchAll();
                    this.openForm = false;
                } catch (e) { this.showToast(e.message || 'Lỗi'); }
                finally { this.submitting = false; }
            },

            async remove(id) {
                if (!confirm('Xác nhận khoá lô này (archive)?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                } catch (e) { this.showToast(e.message || 'Lỗi'); }
            },

            formatCurrency(n) { try { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0) } catch { return n } },

            showToast(msg) {
                const box = document.getElementById('toast-container'); if (!box) return; box.innerHTML = '';
                const toast = document.createElement('div'); toast.className = `fixed top-5 right-5 z-[60] p-4 bg-white rounded shadow`;
                toast.innerText = msg; box.appendChild(toast); setTimeout(() => toast.remove(), 3000);
            }
        }
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>