<?php
// views/admin/supplier-debts/supplier-debts.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<style>
    /* Cải thiện hiển thị filter trong bảng modal */
    .modal-table thead th {
        white-space: nowrap;
        vertical-align: middle;
        text-align: center;
        padding: 0.6rem 0.85rem;
    }

    .modal-table th .filter-trigger {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        font-weight: 500;
        color: #334155;
    }

    .modal-table th .filter-popover {
        width: 220px !important;
        right: 0 !important;
    }

    .modal-table td {
        white-space: nowrap;
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
    }
</style>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý công nợ / <span class="text-slate-800 font-medium">Công nợ nhà cung cấp</span>
</nav>

<div x-data="supplierDebts()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Công nợ nhà cung cấp</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Tổng số NCC có nợ -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Tổng số NCC có nợ</h3>
                    <div class="text-3xl font-bold mt-2" x-text="items.length"></div>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h13v10H3V7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 13h4l1 2v2h-5v-4z" />
                        <circle cx="7.5" cy="17.5" r="1.5" />
                        <circle cx="17.5" cy="17.5" r="1.5" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tổng công nợ -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Tổng công nợ</h3>
                    <div class="text-3xl font-bold mt-2" x-text="formatMoney(getTotalDebt())"></div>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 16v-4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tổng số phiếu nhập nợ -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Tổng phiếu nhập nợ</h3>
                    <div class="text-3xl font-bold mt-2" x-text="getTotalDebtOrders()"></div>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div x-show="!loading" class="bg-white rounded-xl shadow pb-4">
        <!-- Loading overlay bên trong bảng -->
        <template x-if="loading">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
            </div>
        </template>
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:120%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('name', 'Tên') ?>
                        <?= textFilterPopover('phone', 'SĐT') ?>
                        <?= textFilterPopover('email', 'Email') ?>
                        <?= textFilterPopover('debt_orders_count', 'Số phiếu nợ') ?>
                        <?= textFilterPopover('total_debt', 'Tổng công nợ') ?>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="supplier in paginated()" :key="supplier.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 space-x-2 text-center">
                                <!-- Nút Xem chi tiết - luôn hiển thị -->
                                <button @click="viewDetail(supplier)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="supplier.name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                :class="(supplier.phone || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="supplier.phone || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(supplier.email || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="supplier.email || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center"
                                x-text="supplier.debt_orders_count + ' phiếu'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="formatMoney(supplier.total_debt)"></td>
                        </tr>
                    </template>

                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="6" class="py-12 text-center text-slate-500">
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

    <!-- Modal Chi tiết -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="showModal" x-transition.opacity style="display:none" @keydown.escape.window="showModal = false">
        <div class="bg-white w-full max-w-6xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="showModal = false">
            <!-- Header -->
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết công nợ</h3>
                <button class="text-slate-500 absolute right-5" @click="showModal=false">✕</button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
                <!-- Thông tin NCC -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Thông tin cơ bản -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Thông tin nhà cung cấp</h3>
                        <div class="space-y-2">
                            <div class="flex">
                                <span class="text-gray-600 w-24">Tên NCC:</span>
                                <span class="font-medium text-gray-900" x-text="selectedSupplier?.name"></span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">SĐT:</span>
                                <span class="text-gray-900" x-text="selectedSupplier?.phone || '-'"></span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Email:</span>
                                <span class="text-gray-900" x-text="selectedSupplier?.email || '-'"></span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Địa chỉ:</span>
                                <span class="text-gray-900" x-text="selectedSupplier?.address || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tổng quan công nợ -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3">Tổng quan công nợ</h3>
                        <div class="text-3xl font-bold mb-2" x-text="formatMoney(selectedSupplier?.total_debt)">
                        </div>
                        <div class="text-sm opacity-90">
                            <span x-text="debtOrders.length"></span> phiếu nhập còn nợ
                        </div>
                    </div>
                </div>

                <!-- Loading orders -->
                <div x-show="loadingOrders" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600">Đang tải danh sách phiếu nhập...</p>
                </div>

                <!-- Danh sách phiếu nhập -->
                <div x-show="!loadingOrders" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Danh sách phiếu nhập còn nợ</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table style="width:140%; min-width:1200px; border-collapse:collapse;">
                            <thead class="bg-gray-50">
                                <tr class="text-slate-600 text-xs font-semibold uppercase tracking-wide">
                                    <th class="px-4 py-3 text-center">Thao tác</th>
                                    <?= textFilterPopover('code', 'Mã phiếu nhập') ?>
                                    <?= DateFilterPopover('date', 'Ngày nhập') ?>
                                    <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                                    <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                                    <?= numberFilterPopover('paid_amount', 'Đã thanh toán') ?>
                                    <?= numberFilterPopover('remaining_amount', 'Còn nợ') ?>
                                    <?= selectFilterPopover('status', 'Trạng thái', [
                                        '' => '-- Tất cả --',
                                        'Chưa đối soát' => 'Tiền mặt',
                                        'Đã thanh toán một phần' => 'Chuyển khoản'
                                    ]) ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="order in filteredDetail()" :key="order.id">
                                    <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                                        <td class="px-4 py-3 text-center">
                                            <button @click.stop="openPaymentModal(order)"
                                                class="px-3 py-1 border border-[#002975] hover:bg-[#002975] hover:text-white text-[#002975] text-xs font-medium rounded transition-colors"
                                                title="Thanh toán">
                                                Thanh toán
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-blue-600"
                                            x-text="order.order_code"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"
                                            x-text="formatDate(order.order_date)"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"
                                            x-text="order.created_by_name || '-'"></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900"
                                            x-text="formatMoney(order.total_amount)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-green-600 font-medium"
                                            x-text="formatMoney(order.paid_amount)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-red-600 font-bold"
                                            x-text="formatMoney(order.remaining_debt)"></td>
                                        <td class="px-3 py-2 text-center align-middle">
                                            <div class="flex justify-center items-center h-full">
                                                <span class="px-2 py-[3px] rounded text-xs font-medium" :class="[
                                                        getPaymentStatus(order) === 'Chưa đối soát' && 'bg-red-100 text-red-800',
                                                        getPaymentStatus(order) === 'Đã thanh toán một phần' && 'bg-yellow-100 text-yellow-800',
                                                        getPaymentStatus(order) === 'Đã thanh toán hết' && 'bg-green-100 text-green-800'
                                                    ]" x-text="getPaymentStatus(order)">
                                                </span>

                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="debtOrders.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        Không có phiếu nhập nào còn nợ
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <button @click="showModal = false"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Thêm Phiếu Chi -->
    <div class="fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4" x-show="showPaymentModal"
        x-transition.opacity style="display:none" @keydown.escape.window="showPaymentModal = false"
        @click="showPaymentModal = false">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg" @click.stop>
            <!-- Header -->
            <div class="px-5 py-3 border-b flex justify-center items-center relative text-[#002975] rounded-t-xl">
                <h3 class="font-semibold text-xl">Thêm Phiếu Chi - Thanh Toán Công Nợ</h3>
                <button class="absolute right-5 hover:text-gray-200" @click="showPaymentModal=false">✕</button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitPayment" class="p-6 space-y-4">
                <!-- Nhà cung cấp & Phiếu nhập (2 cột) -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Nhà cung cấp -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nhà cung cấp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" :value="selectedSupplier?.name" readonly
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                    </div>

                    <!-- Phiếu nhập -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Phiếu nhập <span class="text-red-500">*</span>
                        </label>
                        <input type="text" :value="selectedOrder?.order_code" readonly
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                    </div>
                </div>

                <!-- Thông tin công nợ -->
                <div class="grid grid-cols-2 gap-4 p-4 bg-red-50 rounded-lg border border-red-200">
                    <div>
                        <div class="text-sm text-gray-600">Tổng tiền phiếu nhập:</div>
                        <div class="text-lg font-bold text-gray-900" x-text="formatMoney(selectedOrder?.total_amount)">
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Đã thanh toán:</div>
                        <div class="text-lg font-bold text-green-600" x-text="formatMoney(selectedOrder?.paid_amount)">
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-sm text-gray-600">Còn nợ:</div>
                        <div class="text-2xl font-bold text-red-600"
                            x-text="formatMoney(selectedOrder?.remaining_debt)"></div>
                    </div>
                </div>

                <!-- Số tiền thanh toán -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Số tiền thanh toán <span class="text-red-500">*</span>
                    </label>

                    <!-- Input hiển thị định dạng -->
                    <input type="text" x-model="paymentForm.amountFormatted" @input="formatAmountInput"
                        inputmode="decimal" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        placeholder="Nhập số tiền thanh toán">

                    <p class="text-xs text-gray-500 mt-1">
                        Số tiền tối đa:
                        <span x-text="formatCurrency(selectedOrder?.remaining_debt)"></span>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Phương thức thanh toán -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Phương thức thanh toán <span class="text-red-500">*</span>
                        </label>
                        <select x-model="paymentForm.method" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">-- Chọn phương thức --</option>
                            <option value="Tiền mặt">Tiền mặt</option>
                            <option value="Chuyển khoản">Chuyển khoản</option>
                            <option value="Thẻ">Thẻ</option>
                        </select>
                    </div>

                    <!-- Ngày thanh toán -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ngày thanh toán <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="paymentForm.date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <!-- Ghi chú -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea x-model="paymentForm.note" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        placeholder="Nhập ghi chú (không bắt buộc)"></textarea>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="showPaymentModal = false"
                        class="px-4 py-2 bg-gray-200 hover:bg-red-600 hover:text-white text-gray-800 rounded-lg transition-colors">
                        Hủy
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="px-4 py-2 border border-[#002975] hover:bg-[#002975] hover:text-white text-[#002975] rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">
                            Xác nhận thanh toán
                        </span>
                        <span x-show="submitting">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                            Đang xử lý...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function supplierDebts() {
        const api = {
            list: '/admin/api/supplier-debts/suppliers',
            orders: id => `/admin/api/supplier-debts/orders?id=${id}`
        };

        return {
            loading: true,
            items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
            showModal: false,
            loadingOrders: false,
            selectedSupplier: null,
            debtOrders: [],

            // Modal thanh toán
            showPaymentModal: false,
            selectedOrder: null,
            submitting: false,
            paymentForm: {
                amount: 0,
                amountFormatted: '',
                method: 'Tiền mặt', // Mặc định là tiền mặt
                date: new Date().toISOString().split('T')[0],
                note: ''
            },

            // ===== PHÂN TRANG =====
            currentPage: 1,
            perPage: 20,
            perPageOptions: [5, 10, 20, 50, 100],

            // ===== FILTERS =====
            openFilter: {
                name: false,
                phone: false,
                email: false,
                debt_orders_count: false,
                total_debt: false
            },

            filters: {
                name: '',
                phone: '',
                email: '',
                debt_orders_count: '',
                total_debt: '',
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
                        .replace(/[\u0300-\u036f]/g, '')
                        .trim();

                    const raw = String(val || '').toLowerCase();
                    const str = normalize(val);
                    const query = String(value || '').toLowerCase();
                    const queryNoAccent = normalize(value);

                    if (!query) return true;

                    if (type === 'eq') return hasAccent(query)
                        ? raw === query
                        : str === queryNoAccent;

                    if (type === 'contains' || type === 'like') {
                        if (hasAccent(query)) {
                            return raw.includes(query);
                        } else {
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

                    if (type === 'like') {
                        const raw = String(val).replace(/[^\d]/g, '');
                        const query = String(value || '').replace(/[^\d]/g, '');
                        return raw.includes(query);
                    }

                    return true;
                }

                return true;
            },

            // ===== Lọc dữ liệu =====
            filtered() {
                let data = this.items || [];

                // --- Lọc theo text ---
                ['name', 'phone', 'email'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo số ---
                ['debt_orders_count', 'total_debt'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'number'
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
                if (['debt_orders_count', 'total_debt'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            getPaymentStatus(order) {
                const paid = parseFloat(order.paid_amount || 0);
                const total = parseFloat(order.total_amount || 0);

                if (paid === 0) return 'Chưa đối soát';
                if (paid >= total) return 'Đã thanh toán hết';
                return 'Đã thanh toán một phần';
            },

            openFilterDetail: {
                code: false,
                date: false,
                total_amount: false,
                paid_amount: false,
                remaining_amount: false,
                created_by_name: false,
                status: false,
            },

            filtersDetail: {
                code: '',
                date: '',
                total_amount_type: '', total_amount_value: '', total_amount_from: '', total_amount_to: '',
                paid_amount_type: '', paid_amount_value: '', paid_amount_from: '', paid_amount_to: '',
                remaining_amount_type: '', remaining_amount_value: '', remaining_amount_from: '', remaining_amount_to: '',
                created_by_name: '',
                status: '',
            },

            filteredDetail() {
                let data = this.debtOrders || [];

                // --- Lọc theo text (mã phiếu, ngày) ---
                ['code'].forEach(key => {
                    if (this.filtersDetail[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'contains', {
                                value: this.filtersDetail[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo trạng thái ---
                if (this.filtersDetail.status) {
                    data = data.filter(o =>
                        this.getPaymentStatus(o) === this.filtersDetail.status
                    );
                }

                // --- Lọc theo ngày ---
                ['date'].forEach(key => {
                    if (this.filters[`${key}_type`]) {
                        data = data.filter(s =>
                            this.applyFilter(s[key], this.filters[`${key}_type`], {
                                value: this.filters[`${key}_value`],
                                from: this.filters[`${key}_from`],
                                to: this.filters[`${key}_to`],
                                dataType: 'date'
                            })
                        );
                    }
                });

                // --- Lọc theo số ---
                ['total_amount', 'paid_amount', 'remaining_amount'].forEach(key => {
                    if (this.filtersDetail[`${key}_type`]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], this.filtersDetail[`${key}_type`], {
                                value: this.filtersDetail[`${key}_value`],
                                from: this.filtersDetail[`${key}_from`],
                                to: this.filtersDetail[`${key}_to`],
                                dataType: 'number'
                            })
                        );
                    }
                });

                return data;
            },

            toggleFilterDetail(key) {
                for (const k in this.openFilterDetail) this.openFilterDetail[k] = false;
                this.openFilterDetail[key] = true;
            },
            closeFilterDetail(key) { this.openFilterDetail[key] = false; },
            resetFilterDetail(key) {
                if (['total_amount', 'paid_amount', 'remaining_amount'].includes(key)) {
                    this.filtersDetail[`${key}_type`] = '';
                    this.filtersDetail[`${key}_value`] = '';
                    this.filtersDetail[`${key}_from`] = '';
                    this.filtersDetail[`${key}_to`] = '';
                } else if (['date'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filtersDetail[key] = '';
                }
                this.openFilterDetail[key] = false;
            },

            paginated() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filtered().slice(start, start + this.perPage);
            },
            totalPages() {
                return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
            },
            goToPage(p) {
                if (p < 1) p = 1;
                if (p > this.totalPages()) p = this.totalPages();
                this.currentPage = p;
            },

            // ===== UTILITIES =====
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('en-US').format(n || 0); // dùng dấu phẩy
                } catch {
                    return n;
                }
            },

            // ===== Xử lý định dạng khi nhập =====
            formatAmountInput(e) {
                let raw = e.target.value.replace(/,/g, ''); // bỏ dấu phẩy cũ
                let num = parseFloat(raw);
                if (!isNaN(num)) {
                    this.paymentForm.amount = num;
                    this.paymentForm.amountFormatted = new Intl.NumberFormat('en-US').format(num);
                } else {
                    this.paymentForm.amount = 0;
                    this.paymentForm.amountFormatted = '';
                }
            },

            async init() {
                await this.loadSuppliers();
            },

            async loadSuppliers() {
                this.loading = true;
                try {
                    const response = await fetch(api.list);
                    const data = await response.json();

                    if (data.success) {
                        this.items = data.data || [];
                    } else {
                        this.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showToast('Không thể tải dữ liệu', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async viewDetail(supplier) {
                this.selectedSupplier = supplier;
                this.showModal = true;
                this.debtOrders = [];
                this.loadingOrders = true;

                try {
                    const response = await fetch(api.orders(supplier.id));
                    const data = await response.json();

                    if (data.success) {
                        this.debtOrders = data.data || [];
                    } else {
                        this.showToast(data.message || 'Không thể tải danh sách phiếu nhập', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showToast('Không thể tải danh sách phiếu nhập', 'error');
                } finally {
                    this.loadingOrders = false;
                }
            },

            // Reload orders without opening modal (dùng sau khi thanh toán)
            async reloadOrders(supplierId) {
                this.loadingOrders = true;
                try {
                    const response = await fetch(api.orders(supplierId));
                    const data = await response.json();

                    if (data.success) {
                        this.debtOrders = data.data || [];
                    }
                } catch (error) {
                    console.error('Error:', error);
                } finally {
                    this.loadingOrders = false;
                }
            },

            getTotalDebt() {
                return this.items.reduce((sum, s) => sum + parseFloat(s.total_debt || 0), 0);
            },

            getTotalDebtOrders() {
                return this.items.reduce((sum, s) => sum + parseInt(s.debt_orders_count || 0), 0);
            },

            openPaymentModal(order) {
                this.selectedOrder = order;
                this.paymentForm = {
                    amount: order.remaining_debt,
                    amountFormatted: this.formatCurrency(order.remaining_debt),
                    method: 'Tiền mặt', // Mặc định là tiền mặt
                    date: new Date().toISOString().split('T')[0],
                    note: `Thanh toán công nợ phiếu nhập ${order.order_code}`
                };
                this.showPaymentModal = true;
            },

            async submitPayment() {
                if (this.submitting) return;

                // Validate
                if (!this.paymentForm.amount || this.paymentForm.amount <= 0) {
                    this.showToast('Vui lòng nhập số tiền thanh toán', 'error');
                    return;
                }

                if (this.paymentForm.amount > this.selectedOrder.remaining_debt) {
                    this.showToast('Số tiền thanh toán không được lớn hơn số tiền còn nợ', 'error');
                    return;
                }

                this.submitting = true;

                try {
                    // Bước 1: Lấy mã phiếu chi tự động
                    const codeResponse = await fetch('/admin/api/expense_vouchers/next-code');
                    const codeData = await codeResponse.json();

                    if (!codeData.success || !codeData.code) {
                        this.showToast('Không thể tạo mã phiếu chi', 'error');
                        this.submitting = false;
                        return;
                    }

                    // Bước 2: Tạo phiếu chi
                    const response = await fetch('/admin/api/expense_vouchers', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            code: codeData.code,
                            supplier_id: this.selectedSupplier.id,
                            purchase_order_id: this.selectedOrder.id,
                            amount: this.paymentForm.amount,
                            method: this.paymentForm.method,
                            voucher_date: this.paymentForm.date,
                            note: this.paymentForm.note,
                            payment_type: 'supplier_debt'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showToast('Thêm phiếu chi thành công!', 'success');

                        // Lưu lại supplier ID trước khi reload
                        const currentSupplierId = this.selectedSupplier.id;

                        // Đóng modal thanh toán
                        this.showPaymentModal = false;

                        // Reload lại danh sách suppliers (cập nhật tổng nợ)
                        await this.loadSuppliers();

                        // Tìm lại supplier từ danh sách mới và cập nhật selectedSupplier
                        const updatedSupplier = this.items.find(s => s.id === currentSupplierId);
                        if (updatedSupplier) {
                            this.selectedSupplier = updatedSupplier;
                        }

                        // Reload lại danh sách orders trong modal chi tiết
                        await this.reloadOrders(currentSupplierId);
                    } else {
                        this.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showToast('Không thể thêm phiếu chi', 'error');
                } finally {
                    this.submitting = false;
                }
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount || 0);
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN');
            },

            showToast(msg, type = 'success') {
                const box = document.getElementById('toast-container');
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className =
                    `fixed top-5 right-5 z-[9999] flex items-center w-[400px] p-4 mb-4 text-base font-semibold
                    ${type === 'success'
                        ? 'text-green-700 border-green-400'
                        : type === 'warning'
                            ? 'text-yellow-700 border-yellow-400'
                            : 'text-red-700 border-red-400'}
                    bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                    <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : type === 'warning' ? 'text-yellow-600' : 'text-red-600'} mr-3" 
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${type === 'success'
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />`
                        : type === 'warning'
                            ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />`
                            : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
                    </svg>
                    <div class="flex-1">${msg}</div>
                    `;

                box.appendChild(toast);

                setTimeout(() => toast.remove(), 5000);
            }
        }
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>