<?php
// views/admin/orders/order.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>
<style>
    [x-cloak] {
        display: none !important;
    }
</style>

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
            <table style="width:280%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center" style="min-width: 100px;">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã đơn hàng', minWidth: 130) ?>
                        <?= textFilterPopover('customer_name', 'Khách hàng', minWidth: 150) ?>
                        <th class="py-2 px-4 text-center align-top" style="min-width: 500px; width: 500px;">
                            <div class="mb-2 text-base font-bold">Chi tiết đơn hàng</div>
                            <div class="grid grid-cols-3 gap-3 border-t pt-2">
                                <!-- Tên sản phẩm -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Tên sản phẩm</span>
                                        <button @click.stop="toggleFilter('product_name')"
                                            class="p-1 rounded hover:bg-gray-100" title="Tìm theo Tên sản phẩm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="openFilter.product_name" x-transition
                                        @click.outside="openFilter.product_name=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Tên sản phẩm"</div>
                                        <input x-model.trim="filters.product_name"
                                            class="w-full border rounded px-3 py-2" placeholder="Nhập tên sản phẩm">
                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="applyFilter('product_name')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('product_name')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.product_name=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Số lượng -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Số lượng</span>
                                        <button @click.stop="toggleFilter('qty')" class="p-1 rounded hover:bg-gray-100"
                                            title="Tìm theo Số lượng">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="openFilter.qty" x-transition @click.outside="openFilter.qty=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Số lượng"</div>
                                        <input type="number" x-model.number="filters.qty"
                                            class="w-full border rounded px-3 py-2" placeholder="Nhập số lượng">
                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="applyFilter('qty')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('qty')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.qty=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Đơn giá -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Đơn giá</span>
                                        <button @click.stop="toggleFilter('unit_price')"
                                            class="p-1 rounded hover:bg-gray-100" title="Tìm theo Đơn giá">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="openFilter.unit_price" x-transition
                                        @click.outside="openFilter.unit_price=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left right-0">
                                        <div class="font-semibold mb-2">Tìm theo "Đơn giá"</div>
                                        <input type="number" x-model.number="filters.unit_price"
                                            class="w-full border rounded px-3 py-2" placeholder="Nhập đơn giá">
                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="applyFilter('unit_price')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('unit_price')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.unit_price=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <?= selectFilterPopover('status', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'preparing' => 'Đang chuẩn bị',
                            'shipping' => 'Đang giao',
                            'delivered' => 'Hoàn tất',
                            'cancelled' => 'Đã hủy',
                            'returned' => 'Hoàn trả'
                        ]) ?>
                        <?= numberFilterPopover('subtotal', 'Tạm tính') ?>
                        <?= numberFilterPopover('discount_amount', 'Giảm giá') ?>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= textFilterPopover('payment_method', 'PT thanh toán') ?>
                        <?= textFilterPopover('shipping_address', 'Địa chỉ giao') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(o, idx) in paginated()" :key="o.id">
                        <tr>
                            <td class="py-2 px-4 text-center space-x-2">
                                <!-- Nút Xem chi tiết -->
                                <button @click.stop="openViewModal(o)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <!-- Nút In hóa đơn -->
                                <button @click="printInvoice(o)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="In hóa đơn">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                                <!-- Nút Xóa (ẩn nếu trạng thái Hoàn tất) -->
                                <button x-show="o.status !== 'Hoàn tất'" @click="remove(o.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(o.code || '—') === '—' ? 'text-center' : 'text-left'" x-text="o.code || '—'">
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(o.customer_name || 'Khách vãng lai') === 'Khách vãng lai' ? 'text-left' : 'text-left'"
                                x-text="o.customer_name || 'Khách vãng lai'"></td>

                            <!-- Cột Chi tiết đơn hàng -->
                            <td class="px-3 py-2 align-top" style="min-width: 500px; width: 500px;">
                                <div class="space-y-2">
                                    <!-- Hiển thị danh sách sản phẩm -->
                                    <template x-if="o.items && o.items.length > 0">
                                        <div class="space-y-2">
                                            <template x-for="(item, itemIdx) in o.items" :key="itemIdx">
                                                <div class="p-3">
                                                    <div class="grid grid-cols-3 gap-3">
                                                        <!-- Tên sản phẩm -->
                                                        <div>
                                                            <div :title="item.product_name"
                                                                x-text="item.product_name || '—'"></div>
                                                        </div>
                                                        <!-- Số lượng -->
                                                        <div>
                                                            <div class="text-right" x-text="item.qty || 0"></div>
                                                        </div>
                                                        <!-- Đơn giá -->
                                                        <div>
                                                            <div class="text-right"
                                                                x-text="formatCurrency(item.unit_price || 0)"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Trạng thái rỗng -->
                                    <template x-if="!o.items || o.items.length === 0">
                                        <div
                                            class="text-center text-gray-400 text-sm py-4 border rounded-md bg-gray-50">
                                            Chưa có sản phẩm
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-yellow-100 text-yellow-800': o.status === 'Chờ xác nhận',
                                        'bg-blue-100 text-blue-800': o.status === 'Đã xác nhận',
                                        'bg-purple-100 text-purple-800': o.status === 'Đang chuẩn bị',
                                        'bg-orange-100 text-orange-800': o.status === 'Đang giao',
                                        'bg-green-100 text-green-800': o.status === 'Hoàn tất',
                                        'bg-red-100 text-red-800': o.status === 'Đã hủy',
                                        'bg-gray-100 text-gray-800': o.status === 'Hoàn trả'
                                    }" x-text="getStatusText(o.status)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.subtotal || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.discount_amount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right font-semibold"
                                x-text="formatCurrency(o.total_amount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(o.payment_method || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="o.payment_method || '—'"></td>
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
    <template x-if="openAdd">
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            @click.self="openAdd=false">
            <div class="bg-white w-full max-w-5xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openAdd=false">
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
    </template>

    <!-- MODAL: View -->
    <template x-if="openView">
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            @click.self="openView=false">
            <div class="bg-white w-full max-w-5xl rounded-xl shadow max-h-[90vh] flex flex-col animate__animated animate__zoomIn animate__faster"
                @click.outside="openView=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết đơn hàng</h3>
                    <button type="button" class="text-slate-500 absolute right-5" @click="openView=false">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <?php require __DIR__ . '/modal-view.php'; ?>
                </div>

                <div class="px-5 pb-5 pt-2 flex justify-end gap-3 border-t bg-white flex-shrink-0">
                    <button @click="printInvoice(viewOrder)"
                        class="px-4 py-2 rounded-md bg-[#002975] text-white hover:opacity-90 flex items-center gap-2">
                        <i class="fa-solid fa-print"></i>
                        In hóa đơn
                    </button>
                    <button type="button" class="px-4 py-2 rounded-md border" @click="openView=false">Đóng</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Toast -->
    <div id="toast-container" class="z-[60]"></div>
</div>

<script>
    function orderPage() {
        const api = {
            list: '/admin/api/orders',
            create: '/admin/orders',
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
            openView: false,
            viewOrder: {},
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

                    // Load danh sách sản phẩm cho mỗi đơn hàng
                    await this.loadOrderItems();
                } catch (e) {
                    this.showToast('Không thể tải dữ liệu đơn hàng');
                } finally {
                    this.loading = false;
                }
            },

            async loadOrderItems() {
                // Load danh sách sản phẩm cho tất cả đơn hàng
                const promises = this.items.map(async (order) => {
                    try {
                        const res = await fetch(`/admin/api/orders/${order.id}/items`);
                        if (res.ok) {
                            const data = await res.json();
                            // Set items cho order
                            order.items = data.items || [];
                        } else {
                            order.items = [];
                        }
                    } catch (e) {
                        order.items = [];
                    }
                });

                await Promise.all(promises);
                // Trigger reactivity by reassigning the array
                this.items = [...this.items];
            },

            // ===== FILTERS =====
            openFilter: {},
            filters: {},

            filtered() {
                let data = this.items;

                for (const key in this.filters) {
                    const val = this.filters[key];
                    if (!val) continue;

                    // Filter theo các trường trong order items (product_name, qty, unit_price)
                    if (['product_name', 'qty', 'unit_price'].includes(key)) {
                        data = data.filter(order => {
                            if (!order.items || order.items.length === 0) return false;

                            return order.items.some(item => {
                                if (key === 'product_name') {
                                    return (item.product_name || '').toLowerCase().includes(val.toLowerCase());
                                } else if (key === 'qty') {
                                    return Number(item.qty) === Number(val);
                                } else if (key === 'unit_price') {
                                    return Number(item.unit_price) === Number(val);
                                }
                                return false;
                            });
                        });
                    }
                    // Filter theo các trường số tiền
                    else if (['subtotal', 'discount_amount', 'shipping_fee', 'total_amount'].includes(key)) {
                        data = data.filter(r => Number(r[key]) === Number(val));
                    }
                    // Filter theo ngày
                    else if (['created_at'].includes(key)) {
                        data = data.filter(r => (r[key] || '').startsWith(val));
                    }
                    // Filter theo text
                    else {
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
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
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

            validateQuantity(item) {
                if (!item.product_id) {
                    item.quantity = 1;
                    return;
                }

                const prod = this.products.find(p => p.id == item.product_id);
                const maxStock = prod ? prod.stock : 0;

                if (item.quantity > maxStock) {
                    this.showToast(
                        'Sản phẩm "' + (prod?.name || 'này') + '" chỉ còn ' + maxStock + ' trong kho!',
                        'error'
                    );
                    item.quantity = maxStock;
                }

                if (item.quantity < 0) {
                    item.quantity = 0;
                }

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

                    // Kiểm tra tồn kho
                    const product = this.products.find(p => p.id == item.product_id);
                    if (product && item.quantity > product.stock) {
                        this.showToast(
                            `Sản phẩm "${product.name}" không đủ tồn kho. ` +
                            `Tồn kho hiện tại: ${product.stock}, yêu cầu: ${item.quantity}`,
                            'error'
                        );
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
                        sale_price: p.sale_price,
                        stock: p.stock_qty || 0  // Map từ stock_qty sang stock
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách sản phẩm');
                }
            },

            async fetchCustomers() {
                try {
                    const res = await fetch(api.customers);
                    const data = await res.json();
                    this.customers = (data.items || []).map(c => ({
                        id: c.id,
                        name: c.full_name,
                        phone: c.phone
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách khách hàng');
                }
            },

            // ===== CRUD =====
            async openCreate() {
                console.log('➕ Opening create modal');
                this.resetForm();

                // Fetch next code trước
                await this.fetchNextCode();

                // Sau đó fetch products và customers
                await Promise.all([
                    this.fetchProducts(),
                    this.fetchCustomers()
                ]);

                // Thêm 1 dòng sản phẩm mặc định
                this.orderItems = [{
                    product_id: '',
                    quantity: 1,
                    unit_price: 0
                }];

                console.log('✅ Setting openAdd to true');
                this.openAdd = true;

                setTimeout(() => {
                    console.log('🔎 Current openAdd state:', this.openAdd);
                }, 100);
            },

            async fetchNextCode() {
                try {
                    const res = await fetch(api.nextCode);
                    if (res.ok) {
                        const text = await res.text();

                        try {
                            const data = JSON.parse(text);
                            this.form.code = data.code || data.next_code || '';
                        } catch (parseError) {
                            // Fallback: tạo mã tự động
                            this.form.code = this.generateOrderCode();
                        }
                    } else {
                        this.form.code = this.generateOrderCode();
                    }
                } catch (e) {
                    this.form.code = this.generateOrderCode();
                }
            },

            generateOrderCode() {
                // Tạo mã đơn hàng tự động: DH + timestamp
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const date = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');

                return `DH${year}${month}${date}${hours}${minutes}${seconds}`;
            },

            async openViewModal(o) {

                // Copy order data và đảm bảo items được load
                this.viewOrder = {
                    ...o,
                    items: o.items || []
                };


                // Nếu chưa có items, load lại
                if (!this.viewOrder.items || this.viewOrder.items.length === 0) {
                    try {
                        const res = await fetch(`/admin/api/orders/${o.id}/items`);
                        if (res.ok) {
                            const data = await res.json();
                            this.viewOrder.items = data.items || [];
                        }
                    } catch (e) {
                        this.viewOrder.items = [];
                    }
                }

                // Load customers và products trước để hiển thị đúng tên
                await Promise.all([
                    this.customers.length === 0 ? this.fetchCustomers() : Promise.resolve(),
                    this.products.length === 0 ? this.fetchProducts() : Promise.resolve()
                ]);

                // Map dữ liệu từ viewOrder sang form để hiển thị trong form.php
                this.form = {
                    ...this.viewOrder,
                    customer_id: this.viewOrder.payer_user_id || null,
                    payment_method: this.viewOrder.payment_method || 'cash',
                    payment_status: 'paid',
                    subtotalFormatted: this.viewOrder.subtotal ? this.viewOrder.subtotal.toLocaleString('en-US') : '0',
                    discount_amountFormatted: this.viewOrder.discount_amount ? this.viewOrder.discount_amount.toLocaleString('en-US') : '0',
                    shipping_feeFormatted: this.viewOrder.shipping_fee ? this.viewOrder.shipping_fee.toLocaleString('en-US') : '0',
                    tax_amountFormatted: this.viewOrder.tax_amount ? this.viewOrder.tax_amount.toLocaleString('en-US') : '0',
                    total_amountFormatted: this.viewOrder.total_amount ? this.viewOrder.total_amount.toLocaleString('en-US') : '0',
                };

                // Map items từ viewOrder sang orderItems để hiển thị trong form.php
                this.orderItems = (this.viewOrder.items || []).map(item => ({
                    product_id: String(item.product_id),
                    quantity: item.qty,
                    unit_price: item.unit_price
                }));

                this.openView = true;
            },

            printInvoice(order) {
                this.openView = false;

                const printWindow = window.open(
                    `/admin/orders/${order.id}/print`,
                    '_blank'
                );

                if (printWindow) {
                    printWindow.onload = function () {
                        printWindow.print();
                    };
                }
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

                    const data = await res.json();

                    if (res.ok) {
                        this.showToast('Thêm đơn hàng thành công!', 'success');
                        this.openAdd = false;
                        await this.fetchAll();
                    } else {
                        // Hiển thị thông báo lỗi từ server
                        const errorMsg = data.error || 'Không thể thêm đơn hàng';
                        this.showToast(errorMsg, 'error');
                    }
                } catch (e) {
                    this.showToast('Không thể kết nối đến server', 'error');
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