<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Phiếu nhập</span>
</nav>

<div x-data="purchaseOrdersPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý phiếu nhập</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm phiếu</button>
    </div>

    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:1200px; min-width:800px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4">Thao tác</th>
                        <th class="py-2 px-4">Nhà cung cấp</th>
                        <th class="py-2 px-4">Ngày nhập</th>
                        <th class="py-2 px-4">Tổng tiền</th>
                        <th class="py-2 px-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="o in items" :key="o.id">
                        <tr class="border-t">
                            <td class="py-2 px-4 text-center">
                                <button @click="openEdit(o)"
                                    class="p-2 rounded hover:bg-gray-100 text-[#002975]">Sửa</button>
                                <button @click="remove(o.id)"
                                    class="p-2 rounded hover:bg-gray-100 text-[#002975]">Xóa</button>
                            </td>
                            <td class="py-2 px-4" x-text="o.supplier_name"></td>
                            <td class="py-2 px-4" x-text="o.created_at"></td>
                            <td class="py-2 px-4" x-text="formatCurrency(o.total_amount)"></td>
                            <td class="py-2 px-4" x-text="o.note || ''"></td>
                        </tr>
                    </template>
                    <tr x-show="items.length===0">
                        <td colspan="10" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <!-- Icon hộp trống -->
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Trống</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Create/Edit -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openForm"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-4xl rounded-xl shadow" @click.outside="openForm=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]"
                        x-text="form.id? 'Sửa phiếu nhập':'Thêm phiếu nhập'"></h3>
                    <button class="text-slate-500 absolute right-5" @click="openForm=false">✕</button>
                </div>
                <?php require __DIR__ . '/form.php'; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function purchaseOrdersPage() {
        const api = {
            list: '/admin/api/purchase-orders',
            create: '/admin/api/purchase-orders',
            update: (id) => `/admin/api/purchase-orders/${id}`,
            remove: (id) => `/admin/api/purchase-orders/${id}`,
            suppliers: '/admin/api/suppliers',
            products: '/admin/api/products'
        };
        return {
            items: [],
            suppliers: [],
            products: [],
            openForm: false,
            submitting: false,
            form: {},
            supplier_id: '',
            lines: [{ product_id: '', qty: 1, unit_cost: 0 }],
            init() {
                this.fetchAll();
                this.fetchSuppliers();
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
            async fetchSuppliers() {
                try {
                    const r = await fetch(api.suppliers);
                    if (r.ok) {
                        const data = await r.json();
                        this.suppliers = data.items || [];
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
            openCreate() {
                this.form = {};
                this.supplier_id = '';
                this.lines = [{ product_id: '', qty: 1, unit_cost: 0 }];
                this.openForm = true;
            },
            openEdit(o) {
                this.form = Object.assign({}, o);
                this.supplier_id = o.supplier_id;
                this.lines = o.lines ? JSON.parse(JSON.stringify(o.lines)) : [{ product_id: '', qty: 1, unit_cost: 0 }];
                this.openForm = true;
            },
            async submit() {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id ? api.update(this.form.id) : api.create;
                    const payload = {
                        ...this.form,
                        supplier_id: this.supplier_id,
                        lines: this.lines,
                        total_amount: this.lines.reduce((s, l) => s + (l.qty * l.unit_cost), 0)
                    };
                    const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                    if (!r.ok) throw new Error('Lỗi server');
                    const res = await r.json();
                    await this.fetchAll();
                    this.openForm = false;
                } catch (e) { this.showToast(e.message || 'Lỗi'); }
                finally { this.submitting = false; }
            },
            async remove(id) {
                if (!confirm('Xác nhận xoá phiếu nhập này?')) return;
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