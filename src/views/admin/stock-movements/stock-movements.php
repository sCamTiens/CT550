<?php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Lịch sử thay đổi tồn kho</span>
</nav>

<div x-data="stockMovementsPage()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Lịch sử thay đổi tồn kho</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
            @click="exportExcel()">
            <i class="fa-solid fa-file-excel"></i> Xuất Excel
        </button>
    </div>

    <!-- Thống kê tổng quan -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng số giao dịch</div>
            <div class="text-2xl font-bold text-blue-600" x-text="getTotalMovements()"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Nhập kho</div>
            <div class="text-2xl font-bold text-green-600" x-text="countByType('Nhập kho')"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Xuất kho</div>
            <div class="text-2xl font-bold text-red-600" x-text="countByType('Xuất kho')"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Điều chỉnh</div>
            <div class="text-2xl font-bold text-orange-600" x-text="countByType('Điều chỉnh')"></div>
        </div>
    </section>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <!-- Loading overlay -->
        <template x-if="loading">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
            </div>
        </template>

        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:180%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <?= textFilterPopover('product_name', 'Sản phẩm') ?>
                        <?= textFilterPopover('product_sku', 'Mã SKU') ?>
                        <?= selectFilterPopover('movement_type', 'Loại', [
                            '' => '-- Tất cả --',
                            'Nhập kho' => 'Nhập kho',
                            'Xuất kho' => 'Xuất kho',
                            'Điều chỉnh' => 'Điều chỉnh',
                            'Bán hàng' => 'Bán hàng',
                            'Trả hàng' => 'Trả hàng'
                        ]) ?>
                        <?= numberFilterPopover('quantity', 'Số lượng') ?>
                        <?= textFilterPopover('reference_type', 'Nguồn gốc') ?>
                        <?= numberFilterPopover('reference_id', 'Mã tham chiếu') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in paginated()" :key="item.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="item.product_name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center" x-text="item.product_sku"></td>
                            <td class="py-2 px-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="{
                                    'bg-green-100 text-green-800': ['Nhập kho', 'Trả hàng'].includes(item.movement_type),
                                    'bg-red-100 text-red-800': ['Xuất kho', 'Bán hàng'].includes(item.movement_type),
                                    'bg-orange-100 text-orange-800': item.movement_type === 'Điều chỉnh'
                                }" x-text="item.movement_type"></span>
                            </td>
                            <td class="py-2 px-4 font-semibold text-center" :class="{
                                'text-green-600': ['Nhập kho', 'Trả hàng'].includes(item.movement_type),
                                'text-red-600': ['Xuất kho', 'Bán hàng'].includes(item.movement_type),
                                'text-orange-600': item.movement_type === 'Điều chỉnh'
                            }">
                                <span x-text="(['Nhập kho', 'Trả hàng'].includes(item.movement_type) ? '+' : (['Xuất kho', 'Bán hàng'].includes(item.movement_type) ? '-' : '')) + formatNumber(item.quantity)"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center" x-text="item.reference_type || '—'"></td>
                            <td class="py-2 px-4 text-center" x-text="item.reference_id || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(item.note || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="item.note || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="item.created_at || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="8" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Không có dữ liệu</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
    function stockMovementsPage() {
        return {
            // State
            loading: true,
            items: [],
            filters: {},
            openFilter: {},

            // Pagination
            currentPage: 1,
            perPage: 20,
            perPageOptions: [10, 20, 50, 100],

            // ===== LIFECYCLE =====
            async init() {
                await this.fetchAll();
            },

            // ===== API =====
            async fetchAll() {
                this.loading = true;
                try {
                    const r = await fetch('/admin/api/stock-movements');
                    if (r.ok) {
                        const data = await r.json();
                        this.items = data.items || [];
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            // ===== FILTERS =====
            openFilter: {
                product_name: false,
                product_sku: false,
                movement_type: false,
                quantity: false,
                reference_type: false,
                reference_id: false,
                note: false,
                created_at: false
            },

            filters: {
                product_name: '',
                product_sku: '',
                movement_type: '',
                quantity_type: '',
                quantity_value: '',
                quantity_from: '',
                quantity_to: '',
                reference_type: '',
                reference_id_type: '',
                reference_id_value: '',
                reference_id_from: '',
                reference_id_to: '',
                note: '',
                created_at_type: '',
                created_at_value: '',
                created_at_from: '',
                created_at_to: ''
            },

            applyFilter(val, type, {
                value,
                from,
                to,
                dataType
            }) {
                if (val == null) return false;

                // TEXT
                if (dataType === 'text') {
                    const normalize = (str) => String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                    const str = normalize(val);
                    const query = String(value || '').toLowerCase();
                    const queryNoAccent = normalize(value);

                    if (!query) return true;
                    if (type === 'eq') return str === queryNoAccent;
                    if (type === 'contains' || type === 'like') return str.includes(queryNoAccent);
                    return true;
                }

                // NUMBER
                if (dataType === 'number') {
                    const num = parseFloat(val);
                    const v = parseFloat(value);
                    const f = parseFloat(from);
                    const t = parseFloat(to);

                    if (isNaN(num)) return false;
                    if (!type) return true;

                    if (type === 'eq') return isNaN(v) ? true : num === v;
                    if (type === 'lt') return isNaN(v) ? true : num < v;
                    if (type === 'gt') return isNaN(v) ? true : num > v;
                    if (type === 'lte') return isNaN(v) ? true : num <= v;
                    if (type === 'gte') return isNaN(v) ? true : num >= v;
                    if (type === 'between') return (isNaN(f) || isNaN(t)) ? true : num >= f && num <= t;

                    return true;
                }

                // DATE
                if (dataType === 'date') {
                    if (!val) return false;
                    const d = new Date(val);
                    const v = value ? new Date(value) : null;
                    const f = from ? new Date(from) : null;
                    const t = to ? new Date(to) : null;

                    if (type === 'eq') return v ? d.toDateString() === v.toDateString() : true;
                    if (type === 'lt') return v ? d < v : true;
                    if (type === 'gt') return v ? d > v : true;
                    if (type === 'lte') return v ? d <= v : true;
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                return true;
            },

            filtered() {
                let data = this.items;

                // TEXT filters
                ['product_name', 'product_sku', 'reference_type', 'note'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(item =>
                            this.applyFilter(item[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // SELECT filter (movement_type)
                if (this.filters.movement_type) {
                    data = data.filter(item => item.movement_type === this.filters.movement_type);
                }

                // NUMBER filters
                if (this.filters.quantity_type) {
                    data = data.filter(item =>
                        this.applyFilter(item.quantity, this.filters.quantity_type, {
                            value: this.filters.quantity_value,
                            from: this.filters.quantity_from,
                            to: this.filters.quantity_to,
                            dataType: 'number'
                        })
                    );
                }

                if (this.filters.reference_id_type) {
                    data = data.filter(item =>
                        this.applyFilter(item.reference_id, this.filters.reference_id_type, {
                            value: this.filters.reference_id_value,
                            from: this.filters.reference_id_from,
                            to: this.filters.reference_id_to,
                            dataType: 'number'
                        })
                    );
                }

                // DATE filter
                if (this.filters.created_at_type) {
                    data = data.filter(item =>
                        this.applyFilter(item.created_at, this.filters.created_at_type, {
                            value: this.filters.created_at_value,
                            from: this.filters.created_at_from,
                            to: this.filters.created_at_to,
                            dataType: 'date'
                        })
                    );
                }

                return data;
            },

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },

            closeFilter(key) {
                this.openFilter[key] = false;
            },

            resetFilter(key) {
                if (['created_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['quantity', 'reference_id'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            // ===== THỐNG KÊ =====
            getTotalMovements() {
                return this.filtered().length;
            },

            countByType(type) {
                return this.filtered().filter(item => item.movement_type === type).length;
            },

            // ===== UTILS =====
            getMovementTypeLabel(type) {
                // Database đã lưu tiếng Việt rồi, return luôn
                return type || '';
            },

            formatNumber(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
                }
            },

            // ===== PAGINATION =====
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

            // ===== EXPORT =====
            exportExcel() {
                const data = this.filtered().map(item => ({
                    product_name: item.product_name || '',
                    product_sku: item.product_sku || '',
                    movement_type: item.movement_type || '',
                    quantity: item.quantity || 0,
                    reference_type: item.reference_type || '',
                    reference_id: item.reference_id || '',
                    note: item.note || '',
                    created_at: item.created_at || ''
                }));

                const now = new Date();
                const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
                const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
                const filename = `Lich_su_ton_kho_${dateStr}_${timeStr}.xlsx`;

                // TODO: Implement export endpoint
                alert('Chức năng xuất Excel đang được phát triển');
            },

            showToast(msg, type = 'error') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
                    ${type === 'success' ? 'text-green-700 border-green-400' : 'text-red-700 border-red-400'}
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
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>