<?php
// views/admin/suppliers/supplier.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / Danh mục sản phẩm / <span class="text-slate-800 font-medium">Nhà cung cấp</span>
</nav>

<div x-data="supplierPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý nhà cung cấp</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm nhà cung cấp</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:120%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('name', 'Tên') ?>
                        <?= textFilterPopover('phone', 'SĐT') ?>
                        <?= textFilterPopover('email', 'Email') ?>
                        <?= textFilterPopover('address', 'Địa chỉ') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by_name', 'Người cập nhật') ?>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="s in paginated()" :key="s.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 space-x-2 text-center">
                                <button @click="openEditModal(s)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.65-1.65a1.875 1.875 0 112.652 2.652l-1.65 1.65M18.513 6.138L7.5 17.25H4.5v-3l11.013-11.112z" />
                                    </svg>
                                </button>
                                <button @click="remove(s.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 7h12M9 7V4h6v3m-7 4v7m4-7v7m4-7v7M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                :class="(s.phone || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.phone || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.email || '—') === '—' ? 'text-center' : 'text-left'" x-text="s.email || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.address || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="s.address || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                :class="(s.created_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.created_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="s.created_by_name || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                :class="(s.updated_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.updated_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.updated_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="s.updated_by_name || '—'">
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="9" class="py-12 text-center text-slate-500">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openAdd" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openAdd=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Thêm nhà cung cấp</h3>
                <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="pt-2 flex justify-end gap-3">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openEdit" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa nhà cung cấp</h3>
                <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
                    <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.name"></td>
                    <button
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting ? 'Đang lưu...' : 'Cập nhật'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast -->
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
    function supplierPage() {
        const api = {
            list: '/admin/api/suppliers',
            create: '/admin/suppliers',
            update: id => `/admin/suppliers/${id}`,
            remove: id => `/admin/suppliers/${id}`,
        };

        return {
            loading: true, submitting: false,
            items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
            openAdd: false, openEdit: false,
            form: { id: null, name: '', phone: '', email: '', address: '' },

            currentPage: 1, perPage: 20, perPageOptions: [5, 10, 20, 50, 100],

            async init() { await this.fetchAll(); },

            resetForm() { this.form = { id: null, name: '', phone: '', email: '', address: '' }; },

            openCreate() { this.resetForm(); this.openAdd = true; },
            openEditModal(s) { this.form = { ...s }; this.openEdit = true; },

            // ===== FILTERS =====
            openFilter: {
                name: false,
                phone: false,
                email: false,
                address: false,
                created_at: false,
                created_by_name: false,
                updated_at: false,
                updated_by_name: false
            },

            filters: {
                name: '',
                phone: '',
                email: '',
                address: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by_name: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by_name: ''
            },

            // -------------------------------------------
            // Hàm lọc tổng quát, hỗ trợ text / number / date
            // -------------------------------------------
            applyFilter(val, type, { value, from, to, dataType }) {
                if (val == null) return false;

                // ---------------- TEXT ----------------
                if (dataType === 'text') {
                    const hasAccent = (s) => /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(s);

                    const normalize = (str) => String(str || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // xóa dấu
                        .trim();

                    const raw = String(val || '').toLowerCase();
                    const str = normalize(val);
                    const query = String(value || '').toLowerCase();
                    const queryNoAccent = normalize(value);

                    if (!query) return true;

                    if (type === 'eq') return hasAccent(query)
                        ? raw === query  // có dấu → so đúng dấu
                        : str === queryNoAccent; // không dấu → so không dấu

                    if (type === 'contains' || type === 'like') {
                        if (hasAccent(query)) {
                            // Có dấu → tìm chính xác theo dấu
                            return raw.includes(query);
                        } else {
                            // Không dấu → tìm theo không dấu
                            return str.includes(queryNoAccent);
                        }
                    }

                    return true;
                }

                // ---------------- NUMBER ----------------
                if (dataType === 'number') {
                    const parseNum = (v) => {
                        if (v === '' || v === null || v === undefined) return null;
                        const s = String(v).replace(/[^\d.-]/g, '');
                        const n = Number(s);
                        return isNaN(n) ? null : n;
                    };

                    const num = parseNum(val);
                    const v = parseNum(value);
                    const f = parseNum(from);
                    const t = parseNum(to);

                    if (num === null) return false;
                    if (!type) return true;

                    if (type === 'eq') return v === null ? true : num === v;
                    if (type === 'lt') return v === null ? true : num < v;
                    if (type === 'gt') return v === null ? true : num > v;
                    if (type === 'lte') return v === null ? true : num <= v;
                    if (type === 'gte') return v === null ? true : num >= v;
                    if (type === 'between') return f === null || t === null ? true : num >= f && num <= t;

                    // --- Lọc “mờ” theo chuỗi số ---
                    if (type === 'like') {
                        const raw = String(val).replace(/[^\d]/g, '');
                        const query = String(value || '').replace(/[^\d]/g, '');
                        return raw.includes(query);
                    }

                    return true;
                }

                // ---------------- DATE ----------------
                if (dataType === 'date') {
                    if (!val) return false;
                    const d = new Date(val);
                    const v = value ? new Date(value) : null;
                    const f = from ? new Date(from) : null;
                    const t = to ? new Date(to) : null;

                    if (type === 'eq') return v ? d.toDateString() === v.toDateString() : true;
                    if (type === 'lt') return v ? d < v : true;
                    if (type === 'gt') {
                        if (!v) return true;
                        // So sánh chỉ theo ngày, bỏ qua giờ phút giây
                        return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
                    }
                    if (type === 'lte') {
                        if (!v) return true;
                        const nextDay = new Date(v);
                        nextDay.setDate(v.getDate() + 1);
                        return d < nextDay; // <= nghĩa là nhỏ hơn ngày kế tiếp
                    }
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                return true;
            },

            // ===== Lọc dữ liệu =====
            filtered() {
                let data = this.items;

                // --- Lọc theo text ---
                ['name', 'phone', 'email', 'address', 'created_by_name', 'updated_by_name'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo ngày ---
                ['created_at', 'updated_at'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'date'
                            })
                        );
                    }
                });

                return data;
            },

            // ===== Mở / đóng / reset filter =====
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['created_at', 'updated_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            paginated() { const start = (this.currentPage - 1) * this.perPage; return this.filtered().slice(start, start + this.perPage); },
            totalPages() { return Math.max(1, Math.ceil(this.filtered().length / this.perPage)); },
            goToPage(p) { if (p < 1) p = 1; if (p > this.totalPages()) p = this.totalPages(); this.currentPage = p; },

            errors: {},
            touched: {},
            markTouched(field) {
                                this.touched[field] = true;
                this.validateField(field);
            },

            validateField(field) {
                let val = (this.form[field] || '').trim();
                this.errors[field] = '';

                if (field === 'name' && !val) {
                    this.errors[field] = 'Tên nhà cung cấp là bắt buộc';
                }

                if (field === 'phone' && val && !/^0\d{9}$/.test(val)) {
                    this.errors[field] = 'Số điện thoại không hợp lệ. Số điện thoại phải gồm 10 số và bắt đầu bằng 0.';
                }

                if (field === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    this.errors[field] = 'Email không hợp lệ';
                }

                if (field === 'address' && val.length > 255) {
                    this.errors[field] = 'Địa chỉ tối đa 255 ký tự';
                }
            },

            validateAll() {
                ['name', 'phone', 'email', 'address'].forEach(f => this.validateField(f));
                return Object.values(this.errors).every(e => !e);
            },

            async fetchAll() {
                this.loading = true;
                try { const r = await fetch(api.list); if (r.ok) { const d = await r.json(); this.items = d.items || []; } }
                finally { this.loading = false; }
            },

            async submitCreate() {
                this.submitting = true;
                try {
                    const r = await fetch(api.create, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                    if (!r.ok) throw new Error('Không thể thêm Nhà cung cấp');
                    const item = await r.json(); this.items.unshift(item); this.openAdd = false;
                    this.showToast('Thêm thành công!', 'success');
                } catch (e) { this.showToast(e.message, 'error'); } finally { this.submitting = false; }
            },

            async submitUpdate() {
                if (!this.form.id) return;
                this.submitting = true;
                try {
                    const r = await fetch(api.update(this.form.id), { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                    const res = await r.json();
                    if (!r.ok) throw new Error(res.error || 'Không thể cập nhật');
                    const i = this.items.findIndex(x => x.id == res.id); if (i > -1) this.items[i] = res; else this.items.unshift(res);
                    this.openEdit = false;
                    this.showToast('Cập nhật thành công!', 'success');
                } catch (e) { this.showToast(e.message, 'error'); } finally { this.submitting = false; }
            },

            async remove(id) {
                if (!confirm('Xóa nhà cung cấp này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' }); const res = await r.json();
                    if (!r.ok) throw new Error(res.error || 'Lỗi xóa');
                    this.items = this.items.filter(x => x.id != id);
                    this.showToast('Đã xóa thành công!', 'success');
                } catch (e) { this.showToast(e.message, 'error'); }
            },

            showToast(msg, type = 'success') {
                const box = document.getElementById('toast-container');
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className =
                    `fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold
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

        }
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>