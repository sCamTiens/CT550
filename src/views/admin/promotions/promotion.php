<?php
// views/admin/promotions/promotion.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý khuyến mãi / <span class="text-slate-800 font-medium">Chương trình khuyến mãi</span>
</nav>

<div x-data="promotionPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý chương trình khuyến mãi</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm chương trình</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:200%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('name', 'Tên chương trình') ?>
                        <?= textFilterPopover('description', 'Mô tả') ?>
                        <?= selectFilterPopover('discount_type', 'Loại giảm giá', [
                            '' => '-- Tất cả --',
                            'percentage' => 'Phần trăm',
                            'fixed' => 'Số tiền cố định'
                        ]) ?>
                        <?= numberFilterPopover('discount_value', 'Giá trị giảm') ?>
                        <?= selectFilterPopover('apply_to', 'Áp dụng cho', [
                            '' => '-- Tất cả --',
                            'all' => 'Toàn bộ sản phẩm',
                            'category' => 'Theo danh mục',
                            'product' => 'Sản phẩm cụ thể'
                        ]) ?>
                        <?= numberFilterPopover('priority', 'Độ ưu tiên') ?>
                        <?= dateFilterPopover('starts_at', 'Ngày bắt đầu') ?>
                        <?= dateFilterPopover('ends_at', 'Ngày kết thúc') ?>
                        <?= selectFilterPopover('is_active', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            '1' => 'Kích hoạt',
                            '0' => 'Vô hiệu hóa'
                        ]) ?>
                        <?= dateFilterPopover('created_at', 'Ngày tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in paginated()" :key="p.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEdit(p)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="remove(p.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="py-2 px-4 font-semibold text-[#002975]" x-text="p.name"></td>
                            <td class="py-2 px-4" x-text="p.description || '—'"></td>
                            <td class="py-2 px-4"
                                x-text="p.discount_type === 'percentage' ? 'Phần trăm' : 'Số tiền cố định'"></td>
                            <td class="py-2 px-4 text-right"
                                x-text="p.discount_type === 'percentage' ? (p.discount_value + '%') : formatCurrency(p.discount_value)">
                            </td>
                            <td class="py-2 px-4" x-text="getApplyToText(p.apply_to)"></td>
                            <td class="py-2 px-4 text-center" x-text="p.priority || 0"></td>
                            <td class="py-2 px-4 text-right" x-text="p.starts_at || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="p.ends_at || '—'"></td>
                            <td class="py-2 px-4 text-center">
                                <span :class="p.is_active == 1 ? 'text-green-600' : 'text-red-600'"
                                    x-text="p.is_active == 1 ? 'Kích hoạt' : 'Vô hiệu hóa'"></span>
                            </td>
                            <td class="py-2 px-4 text-right" x-text="p.created_at || '—'"></td>
                            <td class="py-2 px-4" x-text="p.created_by_name || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="filtered().length===0">
                        <td colspan="12" class="py-12 text-center text-slate-500">
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
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openForm" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-5xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openForm=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]"
                        x-text="form.id ? 'Sửa chương trình khuyến mãi' : 'Thêm chương trình khuyến mãi'"></h3>
                    <button class="text-slate-500 absolute right-5" @click="openForm=false">✕</button>
                </div>

                <form class="flex flex-col flex-1 overflow-hidden" @submit.prevent="submit()">
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <?php require __DIR__ . '/form.php'; ?>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end gap-3 flex-shrink-0 bg-white">
                        <button type="button" @click="openForm=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded text-sm"
                            :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : (form.id ? 'Cập nhật' : 'Tạo')"></button>
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
    function promotionPage() {
        const api = {
            list: '/admin/api/promotions',
            create: '/admin/promotions',
            update: (id) => `/admin/promotions/${id}`,
            remove: (id) => `/admin/promotions/${id}`,
            products: '/admin/api/products',
            categories: '/admin/api/categories'
        };

        return {
            // State
            items: <?= json_encode($items ?? [], JSON_UNESCAPED_UNICODE) ?>,
            products: [],
            categories: [],
            openForm: false,
            submitting: false,
            form: {},
            errors: {},
            touched: {},

            // Pagination
            currentPage: 1,
            perPage: 20,
            perPageOptions: [5, 10, 20, 50, 100],

            // Filters
            openFilter: {
                name: false, description: false, discount_type: false, discount_value: false,
                apply_to: false, priority: false, starts_at: false, ends_at: false,
                is_active: false, created_at: false, created_by: false
            },
            filters: {
                name: '',
                description: '',
                discount_type: '',
                discount_value_type: '', discount_value_value: '', discount_value_from: '', discount_value_to: '',
                apply_to: '',
                priority_type: '', priority_value: '', priority_from: '', priority_to: '',
                starts_at_type: '', starts_at_value: '', starts_at_from: '', starts_at_to: '',
                ends_at_type: '', ends_at_value: '', ends_at_from: '', ends_at_to: '',
                is_active: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: ''
            },

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
                return this.items.filter(p => {
                    if (f.name && !fn(p.name).includes(fn(f.name))) return false;
                    if (f.description && !fn(p.description).includes(fn(f.description))) return false;
                    if (f.discount_type && !fn(p.discount_type).includes(fn(f.discount_type))) return false;
                    if (f.apply_to && !fn(p.apply_to).includes(fn(f.apply_to))) return false;
                    if (f.is_active !== '' && f.is_active !== undefined && String(p.is_active) !== String(f.is_active)) return false;
                    if (f.created_by && !fn(p.created_by_name || '').includes(fn(f.created_by))) return false;
                    if (!this.applyNumberFilter(p.discount_value, f.discount_value_type, f.discount_value_value, f.discount_value_from, f.discount_value_to)) return false;
                    if (!this.applyNumberFilter(p.priority, f.priority_type, f.priority_value, f.priority_from, f.priority_to)) return false;
                    if (!this.applyDateFilter(p.starts_at, f.starts_at_type, f.starts_at_value, f.starts_at_from, f.starts_at_to)) return false;
                    if (!this.applyDateFilter(p.ends_at, f.ends_at_type, f.ends_at_value, f.ends_at_from, f.ends_at_to)) return false;
                    if (!this.applyDateFilter(p.created_at, f.created_at_type, f.created_at_value, f.created_at_from, f.created_at_to)) return false;
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
                if (['starts_at', 'ends_at', 'created_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['discount_value', 'priority'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            async init() {
                await this.fetchAll();
            },

            async fetchAll() {
                try {
                    const r = await fetch(api.list);
                    if (r.ok) {
                        const data = await r.json();
                        this.items = data.items || [];
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            async fetchProducts() {
                try {
                    const r = await fetch(api.products);
                    if (r.ok) {
                        const data = await r.json();
                        this.products = data.items || [];
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            async fetchCategories() {
                try {
                    const r = await fetch(api.categories);
                    if (r.ok) {
                        const data = await r.json();
                        this.categories = data.items || [];
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            async openCreate() {
                this.form = {
                    id: null,
                    name: '',
                    description: '',
                    discount_type: 'percentage',
                    discount_value: 0,
                    apply_to: 'all',
                    category_ids: [],
                    product_ids: [],
                    priority: 0,
                    starts_at: '',
                    ends_at: '',
                    is_active: 1
                };
                this.errors = {};
                this.touched = {};
                await Promise.all([this.fetchProducts(), this.fetchCategories()]);
                this.openForm = true;
            },

            async openEdit(p) {
                this.form = {
                    ...p,
                    category_ids: p.category_ids || [],
                    product_ids: p.product_ids || []
                };
                this.errors = {};
                this.touched = {};
                await Promise.all([this.fetchProducts(), this.fetchCategories()]);
                this.openForm = true;
            },

            validateField(field) {
                this.errors[field] = '';

                if (field === 'name' && (!this.form.name || this.form.name.trim() === '')) {
                    this.errors.name = 'Vui lòng nhập tên chương trình';
                }

                if (field === 'discount_value') {
                    if (this.form.discount_value === '' || this.form.discount_value === null) {
                        this.errors.discount_value = 'Vui lòng nhập giá trị giảm';
                    } else if (this.form.discount_value <= 0) {
                        this.errors.discount_value = 'Giá trị giảm phải lớn hơn 0';
                    } else if (this.form.discount_type === 'percentage' && this.form.discount_value > 100) {
                        this.errors.discount_value = 'Phần trăm giảm không được vượt quá 100%';
                    }
                }

                if (field === 'starts_at' && (!this.form.starts_at || this.form.starts_at.trim() === '')) {
                    this.errors.starts_at = 'Vui lòng chọn ngày bắt đầu';
                }

                if (field === 'ends_at' && (!this.form.ends_at || this.form.ends_at.trim() === '')) {
                    this.errors.ends_at = 'Vui lòng chọn ngày kết thúc';
                }
            },

            validateAll() {
                this.validateField('name');
                this.validateField('discount_value');
                this.validateField('starts_at');
                this.validateField('ends_at');

                this.touched = {
                    name: true,
                    discount_value: true,
                    starts_at: true,
                    ends_at: true
                };

                return !Object.values(this.errors).some(err => err !== '');
            },

            async submit() {
                if (!this.validateAll()) {
                    this.showToast('Vui lòng kiểm tra lại thông tin!', 'error');
                    return;
                }

                if (this.submitting) return;
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
                    this.showToast(e.message || 'Lỗi', 'error');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Xóa chương trình khuyến mãi này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!r.ok) throw new Error('Lỗi server');
                    await this.fetchAll();
                    this.showToast('Xóa thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message || 'Lỗi', 'error');
                }
            },

            getApplyToText(applyTo) {
                const map = {
                    'all': 'Toàn bộ sản phẩm',
                    'category': 'Theo danh mục',
                    'product': 'Sản phẩm cụ thể'
                };
                return map[applyTo] || applyTo;
            },

            // --- utils ---
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
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