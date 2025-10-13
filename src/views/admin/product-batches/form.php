<form x-data="productBatchForm()" class="p-5 space-y-4" @submit.prevent="validateForm() && submit()">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Sản phẩm -->
        <div>
            <label class="block text-sm font-semibold mb-1">Sản phẩm <span class="text-red-500">*</span></label>
            <select x-model="form.product_id" @input="touched.product_id && validateField('product_id')"
                @blur="touched.product_id = true; validateField('product_id')"
                class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]">
                <option value="">-- Chọn sản phẩm --</option>
                <template x-for="p in products" :key="p.id">
                    <option :value="p.id" x-text="p.name + ' (' + p.sku + ')'"></option>
                </template>
            </select>

            <template x-if="touched.product_id && errors.product_id">
                <div class="text-xs text-red-500 mt-1" x-text="errors.product_id"></div>
            </template>
        </div>

        <!-- Mã lô -->
        <div>
            <label class="block text-sm font-semibold mb-1">Mã lô <span class="text-red-500">*</span></label>
            <input x-model="form.batch_code" @input="touched.batch_code && validateField('batch_code')"
                @blur="touched.batch_code = true; validateField('batch_code')" type="text"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                placeholder="Mã lô" />
            <template x-if="touched.batch_code && errors.batch_code">
                <div class="text-xs text-red-500 mt-1" x-text="errors.batch_code"></div>
            </template>
        </div>

        <!-- Ngày sản xuất -->
        <div>
            <label class="block text-sm font-semibold mb-1">Ngày sản xuất <span class="text-red-500">*</span></label>
            <input x-model="form.mfg_date" @input="touched.mfg_date && validateField('mfg_date')"
                @blur="touched.mfg_date = true; validateField('mfg_date')" type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />
            <template x-if="touched.mfg_date && errors.mfg_date">
                <div class="text-xs text-red-500 mt-1" x-text="errors.mfg_date"></div>
            </template>
        </div>

        <!-- Hạn sử dụng -->
        <div>
            <label class="block text-sm font-semibold mb-1">Hạn sử dụng <span class="text-red-500">*</span></label>
            <input x-model="form.exp_date" @input="touched.exp_date && validateField('exp_date')"
                @blur="touched.exp_date = true; validateField('exp_date')" type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />
            <template x-if="touched.exp_date && errors.exp_date">
                <div class="text-xs text-red-500 mt-1" x-text="errors.exp_date"></div>
            </template>
        </div>

        <!-- Số lượng ban đầu -->
        <div>
            <label class="block text-sm font-semibold mb-1">Số lượng ban đầu <span class="text-red-500">*</span></label>
            <input x-model.number="form.initial_qty" @input="touched.initial_qty && validateField('initial_qty')"
                @blur="touched.initial_qty = true; validateField('initial_qty')" type="number"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />
            <template x-if="touched.initial_qty && errors.initial_qty">
                <div class="text-xs text-red-500 mt-1" x-text="errors.initial_qty"></div>
            </template>
        </div>

        <!-- Tồn hiện tại -->
        <div>
            <label class="block text-sm font-semibold mb-1">Tồn hiện tại <span class="text-red-500">*</span></label>
            <input x-model.number="form.current_qty" @input="touched.current_qty && validateField('current_qty')"
                @blur="touched.current_qty = true; validateField('current_qty')" type="number"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />
            <template x-if="touched.current_qty && errors.current_qty">
                <div class="text-xs text-red-500 mt-1" x-text="errors.current_qty"></div>
            </template>
        </div>

        <!-- Giá nhập -->
        <div>
            <label class="block text-sm font-semibold mb-1">Giá nhập <span class="text-red-500">*</span></label>
            <input x-model.number="form.unit_cost" @input="touched.unit_cost && validateField('unit_cost')"
                @blur="touched.unit_cost = true; validateField('unit_cost')" type="number" step="1"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />
            <template x-if="touched.unit_cost && errors.unit_cost">
                <div class="text-xs text-red-500 mt-1" x-text="errors.unit_cost"></div>
            </template>
        </div>

        <!-- Ghi chú -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold mb-1">Ghi chú</label>
            <input x-model="form.note" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                placeholder="Ghi chú">
        </div>
    </div>

    <!-- Nút -->
    <div class="flex justify-end gap-3 pt-4">
        <button type="button" @click="openForm = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
        <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
            x-text="submitting ? 'Đang lưu...' : (form.id ? 'Cập nhật' : 'Tạo')"></button>
    </div>
</form>

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

            // --- filters ---
            filtered() {
                let data = this.items;
                if (this.filters.product_name) {
                    data = data.filter(b => (b.product_name || '').toLowerCase().includes(this.filters.product_name.toLowerCase()));
                }
                if (this.filters.batch_code) {
                    data = data.filter(b => (b.batch_code || '').toLowerCase().includes(this.filters.batch_code.toLowerCase()));
                }
                return data;
            },
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) { this.filters[key] = ''; this.openFilter[key] = false; },

            // --- form control ---
            openCreate() {
                this.form = { id: null, product_id: '', batch_code: '', mfg_date: '', exp_date: '', initial_qty: 0, current_qty: 0, note: '', unit_cost: 0 };
                this.openForm = true;
            },
            openEdit(b) {
                this.form = Object.assign({}, b);
                this.openForm = true;
            },

            async submit() {
                if (this.submitting) return;
                this.submitting = true;
                try {
                    const method = this.form.id ? 'PUT' : 'POST';
                    const url = this.form.id ? api.update(this.form.id) : api.create;
                    const r = await fetch(url, {
                        method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form)
                    });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                    this.openForm = false;
                } catch (e) {
                    this.showToast(e.message || 'Lỗi');
                } finally { this.submitting = false; }
            },

            async remove(id) {
                if (!confirm('Xóa lô này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                } catch (e) { this.showToast(e.message || 'Lỗi'); }
            },

            // --- utils ---
            formatCurrency(n) {
                try { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0) }
                catch { return n }
            },
            showToast(msg) {
                const box = document.getElementById('toast-container'); if (!box) return;
                box.innerHTML = '';
                const toast = document.createElement('div');
                toast.className = `fixed top-5 right-5 z-[60] p-4 bg-white rounded shadow`;
                toast.innerText = msg;
                box.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        };
    }
</script>