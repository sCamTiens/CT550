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
        <div class="flex gap-2">
            <a href="/admin/import-history?table=purchase_orders"
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử nhập
            </a>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="openImportModal()">
                <i class="fa-solid fa-file-import"></i> Nhập Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="exportExcel()">
                <i class="fa-solid fa-file-excel"></i> Xuất Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
                @click="openCreate()">+ Thêm phiếu nhập kho</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <!-- Loading overlay bên trong bảng -->
        <template x-if="loading">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
            </div>
        </template>
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:230%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu') ?>
                        <?= textFilterPopover('supplier_name', 'Nhà cung cấp') ?>
                        <th class="py-2 px-4 text-center align-top" style="min-width: 500px; width: 500px;">
                            <div class="mb-2 text-base font-bold">Chi tiết nhập kho (theo lô)</div>

                            <div class="grid grid-cols-3 gap-3 border-t pt-2">
                                <!-- Tên sản phẩm - Mã lô -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Sản phẩm - Mã lô</span>
                                        <button @click.stop="toggleFilter('item_product')"
                                            class="p-1 rounded hover:bg-gray-100" title="Tìm theo Sản phẩm hoặc Mã lô">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div x-cloak x-show="openFilter.item_product" x-transition
                                        @click.outside="openFilter.item_product=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Sản phẩm hoặc Mã lô"</div>
                                        <input x-model.trim="filters.item_product"
                                            class="w-full border rounded px-3 py-2" placeholder="Nhập tên hoặc mã lô">
                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('item_product')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('item_product')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.item_product=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Số lượng -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Số lượng</span>
                                        <button @click.stop="toggleFilter('item_qty')"
                                            class="p-1 rounded hover:bg-gray-100" title="Tìm theo Số lượng">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div x-cloak x-show="openFilter.item_qty" x-transition
                                        @click.outside="openFilter.item_qty=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Số lượng"</div>
                                        <select x-model="filters.item_qty_type"
                                            class="border rounded px-3 py-2 text-sm w-full">
                                            <option value="">-- Chọn kiểu lọc --</option>
                                            <option value="eq">Bằng</option>
                                            <option value="gt">Lớn hơn</option>
                                            <option value="lt">Nhỏ hơn</option>
                                            <option value="gte">Lớn hơn hoặc bằng</option>
                                            <option value="lte">Nhỏ hơn hoặc bằng</option>
                                            <option value="between">Trong khoảng</option>
                                        </select>

                                        <div class="flex flex-col gap-2 mt-2">
                                            <input type="number" x-model.number="filters.item_qty_value"
                                                x-show="filters.item_qty_type && filters.item_qty_type !== 'between'"
                                                class="w-full border rounded px-3 py-2" placeholder="Nhập giá trị">

                                            <template x-if="filters.item_qty_type === 'between'">
                                                <div class="flex gap-2">
                                                    <input type="number" x-model.number="filters.item_qty_from"
                                                        class="w-1/2 border rounded px-3 py-2" placeholder="Từ">
                                                    <input type="number" x-model.number="filters.item_qty_to"
                                                        class="w-1/2 border rounded px-3 py-2" placeholder="Đến">
                                                </div>
                                            </template>
                                        </div>

                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('item_qty')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('item_qty')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.item_qty=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Đơn giá -->
                                <div class="relative">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-semibold text-gray-700">Đơn giá</span>
                                        <button @click.stop="toggleFilter('item_price')"
                                            class="p-1 rounded hover:bg-gray-100" title="Tìm theo Đơn giá">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div x-cloak x-show="openFilter.item_price" x-transition
                                        @click.outside="openFilter.item_price=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Đơn giá"</div>
                                        <select x-model="filters.item_price_type"
                                            class="border rounded px-3 py-2 text-sm w-full">
                                            <option value="">-- Chọn kiểu lọc --</option>
                                            <option value="eq">Bằng</option>
                                            <option value="gt">Lớn hơn</option>
                                            <option value="lt">Nhỏ hơn</option>
                                            <option value="gte">Lớn hơn hoặc bằng</option>
                                            <option value="lte">Nhỏ hơn hoặc bằng</option>
                                            <option value="between">Trong khoảng</option>
                                        </select>

                                        <div class="flex flex-col gap-2 mt-2">
                                            <input type="number" x-model.number="filters.item_price_value"
                                                x-show="filters.item_price_type && filters.item_price_type !== 'between'"
                                                class="w-full border rounded px-3 py-2" placeholder="Nhập giá trị">

                                            <template x-if="filters.item_price_type === 'between'">
                                                <div class="flex gap-2">
                                                    <input type="number" x-model.number="filters.item_price_from"
                                                        class="w-1/2 border rounded px-3 py-2" placeholder="Từ">
                                                    <input type="number" x-model.number="filters.item_price_to"
                                                        class="w-1/2 border rounded px-3 py-2" placeholder="Đến">
                                                </div>
                                            </template>
                                        </div>

                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('item_price')"
                                                class="px-3 py-1 text-xs rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
                                            <button @click="resetFilter('item_price')"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm
                                                mới</button>
                                            <button @click="openFilter.item_price=false"
                                                class="px-3 py-1 text-xs rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= numberFilterPopover('paid_amount', 'Số tiền đã thanh toán') ?>
                        <?= dateFilterPopover('due_date', 'Ngày hẹn thanh toán') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= selectFilterPopover('payment_status', 'Trạng thái thanh toán', [
                            '' => '-- Tất cả --',
                            'Chưa đối soát' => 'Chưa đối soát',
                            'Đã thanh toán một phần' => 'Đã thanh toán một phần',
                            'Đã thanh toán hết' => 'Đã thanh toán hết'
                        ]) ?>
                        <?= dateFilterPopover('received_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="po in paginated()" :key="po.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <div class="inline-flex space-x-2">
                                    <!-- Nút Sửa - chỉ hiện khi Chưa đối soát -->
                                    <template x-if="statusLabel(po) === 'Chưa đối soát'">
                                        <button @click="openEditModal(po)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Sửa">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </template>

                                    <!-- Nút Xem chi tiết - luôn hiển thị -->
                                    <button @click="openViewModal(po)"
                                        class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                        title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <!-- Nút Xóa - chỉ hiện khi Chưa đối soát -->
                                    <template x-if="statusLabel(po) === 'Chưa đối soát'">
                                        <button @click="remove(po.id)"
                                            class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                            title="Xóa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.code"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="po.supplier_name"></td>

                            <!-- Cột Chi tiết nhập kho theo lô -->
                            <td class="px-3 py-2 align-top" style="min-width: 800px; width: 800px;">
                                <div class="space-y-2">
                                    <!-- Hiển thị danh sách sản phẩm theo lô -->
                                    <template x-if="po.items && po.items.length > 0">
                                        <div class="space-y-1">
                                            <template x-for="(item, itemIdx) in po.items" :key="itemIdx">
                                                <div class="grid grid-cols-3 gap-2 text-xs pb-1"
                                                    :class="{ 'border-b': itemIdx < po.items.length - 1 }">
                                                    <!-- Cột 1: Tên sản phẩm - Mã lô -->
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-800"
                                                            x-text="item.product_name"></span>
                                                        <span class="text-gray-500 text-[10px]"
                                                            x-text="'Lô: ' + (item.batch_code || '—')"></span>
                                                    </div>
                                                    <!-- Cột 2: Số lượng -->
                                                    <div class="text-center">
                                                        <span class="font-semibold"
                                                            x-text="formatCurrency(item.quantity || 0)"></span>
                                                    </div>
                                                    <!-- Cột 3: Đơn giá -->
                                                    <div class="text-right">
                                                        <span x-text="formatCurrency(item.unit_cost || 0)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Trạng thái rỗng -->
                                    <template x-if="!po.items || po.items.length === 0">
                                        <div class="text-center text-gray-400 text-xs py-2">
                                            Đang tải...
                                        </div>
                                    </template>
                                </div>
                            </td>

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
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-yellow-100 text-yellow-800': statusLabel(po) === 'Chưa đối soát',
                                        'bg-orange-100 text-orange-800': statusLabel(po) === 'Đã thanh toán một phần',
                                        'bg-green-100 text-green-800': statusLabel(po) === 'Đã thanh toán hết',
                                    }" x-text="statusLabel(po)">
                                    </span>
                                </div>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.received_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="po.received_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="po.created_by_name || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.updated_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="po.updated_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(po.updated_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="po.updated_by_name || '—'">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openAdd" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openAdd=false">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openEdit" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa phiếu nhập</h3>
                <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                <?php require __DIR__ . '/form.php'; ?>
            </form>
        </div>
    </div>

    <!-- MODAL: View (Chi tiết) -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openView"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-4xl rounded-xl shadow max-h-[90vh] flex flex-col"
            @click.outside="openView=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết phiếu nhập kho</h3>
                <button class="text-slate-500 absolute right-5" @click="openView=false">✕</button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <!-- Thông tin phiếu nhập -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Mã phiếu</label>
                        <p class="mt-1 text-base" x-text="viewItem.code || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nhà cung cấp</label>
                        <p class="mt-1 text-base" x-text="viewItem.supplier_name || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Tổng tiền</label>
                        <p class="mt-1 text-base font-semibold text-[#002975]"
                            x-text="formatCurrency(viewItem.total_amount || 0)"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Đã thanh toán</label>
                        <p class="mt-1 text-base font-semibold text-green-600"
                            x-text="formatCurrency(viewItem.paid_amount || 0)"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Trạng thái</label>
                        <p class="mt-1">
                            <span class="px-2 py-1 rounded text-xs font-medium" :class="{
                                'bg-yellow-100 text-yellow-800': statusLabel(viewItem) === 'Chưa đối soát',
                                'bg-orange-100 text-orange-800': statusLabel(viewItem) === 'Đã thanh toán một phần',
                                'bg-green-100 text-green-800': statusLabel(viewItem) === 'Đã thanh toán hết'
                            }" x-text="statusLabel(viewItem)"></span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Hạn thanh toán</label>
                        <p class="mt-1 text-base" x-text="viewItem.due_date || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Ngày nhập</label>
                        <p class="mt-1 text-base" x-text="viewItem.received_at || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Ghi chú</label>
                        <p class="mt-1 text-base" x-text="viewItem.note || '—'"></p>
                    </div>
                </div>

                <!-- Chi tiết sản phẩm theo lô -->
                <div>
                    <h4 class="font-semibold text-lg text-[#002975] mb-3">Danh sách sản phẩm</h4>
                    <div class="border rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-3 text-left font-medium text-slate-600">STT</th>
                                    <th class="py-2 px-3 text-left font-medium text-slate-600">Sản phẩm</th>
                                    <th class="py-2 px-3 text-left font-medium text-slate-600">Mã lô</th>
                                    <th class="py-2 px-3 text-right font-medium text-slate-600">Số lượng</th>
                                    <th class="py-2 px-3 text-right font-medium text-slate-600">Đơn giá</th>
                                    <th class="py-2 px-3 text-right font-medium text-slate-600">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="!viewItem.items || viewItem.items.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-slate-500">Đang tải dữ liệu...</td>
                                    </tr>
                                </template>
                                <template x-if="viewItem.items && viewItem.items.length > 0">
                                    <template x-for="(item, idx) in viewItem.items" :key="idx">
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="py-2 px-3" x-text="idx + 1"></td>
                                            <td class="py-2 px-3 break-words"
                                                style="max-width: 200px; word-wrap: break-word; white-space: normal;"
                                                x-text="item.product_name || '—'"></td>
                                            <td class="py-2 px-3 break-words"
                                                style="max-width: 150px; word-wrap: break-word; white-space: normal;"
                                                x-text="item.batch_code || '—'"></td>
                                            <td class="py-2 px-3 text-right"
                                                x-text="formatCurrency(item.quantity || 0)">
                                            </td>
                                            <td class="py-2 px-3 text-right"
                                                x-text="formatCurrency(item.unit_cost || 0)">
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium"
                                                x-text="formatCurrency((item.quantity || 0) * (item.unit_cost || 0))">
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Thông tin người tạo -->
                <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Người tạo</label>
                        <p class="mt-1 text-base" x-text="viewItem.created_by_name || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Người cập nhật</label>
                        <p class="mt-1 text-base" x-text="viewItem.updated_by_name || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Thời gian tạo</label>
                        <p class="mt-1 text-base" x-text="viewItem.created_at || '—'"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Thời gian cập nhật</label>
                        <p class="mt-1 text-base" x-text="viewItem.updated_at || '—'"></p>
                    </div>
                </div>
            </div>
            <div class="px-5 pb-5 pt-2 flex justify-end gap-3 border-t bg-white flex-shrink-0">
                <button type="button" class="px-4 py-2 border rounded text-sm" @click="openView=false">Đóng</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Import Excel -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="showImportModal" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="showImportModal=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Nhập dữ liệu từ Excel</h3>
                <button class="text-slate-500 absolute right-5" @click="showImportModal=false">✕</button>
            </div>
            <div class="p-5 space-y-4">
                <!-- Chọn file -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" id="importPOFile" accept=".xls,.xlsx" @change="handleFileSelect($event)"
                        class="hidden">
                    <label for="importPOFile" class="cursor-pointer">
                        <div class="flex flex-col items-center">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-[#002975] mb-2"></i>
                            <span class="text-slate-600">Chọn file Excel hoặc kéo thả vào đây</span>
                            <span class="text-xs text-slate-400 mt-1">Hỗ trợ: .xls, .xlsx (Tối đa 10MB)</span>
                        </div>
                    </label>
                    <div x-show="importFile" class="mt-3 text-sm text-slate-700">
                        <i class="fa-solid fa-file-excel text-green-600"></i>
                        <span x-text="importFile?.name"></span>
                        <button @click="clearFile()" class="ml-2 text-red-500 hover:text-red-700">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Tải file mẫu -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-[#002975] text-xl mt-0.5"></i>
                        <div class="flex-1">
                            <h4 class="font-semibold text-blue-900 mb-2">Hướng dẫn nhập file:</h4>
                            <ul class="text-sm text-blue-800 space-y-1 mb-3">
                                <li>• Dòng đầu là tiêu đề, dữ liệu bắt đầu từ dòng 2</li>
                                <li>• Trường có dấu <span class="text-red-600 font-bold">*</span> là bắt buộc</li>
                                <li>• Các dòng có cùng NCC, Ngày nhập, Trạng thái TT sẽ gộp thành 1 phiếu</li>
                                <li>• Ngày tháng phải theo định dạng dd/mm/yyyy</li>
                                <li>• File phải có định dạng .xls hoặc .xlsx</li>
                                <li>• File tối đa 10MB, không quá 10,000 dòng</li>
                            </ul>
                            <button @click="downloadTemplate()"
                                class="text-sm text-red-400 hover:text-red-600 hover:underline font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-download"></i>
                                Tải file mẫu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button"
                        class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white transition-colors"
                        @click="openImport=false">Hủy</button>
                    <button type="button" :disabled="!selectedFile || importing"
                        class="px-4 py-2 rounded-md bg-[#002975] text-white hover:bg-[#001850] disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="submitImport()">
                        <span x-show="!importing">Nhập dữ liệu</span>
                        <span x-show="importing"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang nhập...</span>
                    </button>
                </div>
            </div>
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
                            x-text="opt + ' / trang'">
                        </div>
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
            openView: false,
            viewItem: {},
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

            // Import Excel
            showImportModal: false,
            importFile: null,
            importing: false,
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

            // --- utils ---
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
                }
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
                    // Load chi tiết items cho mỗi phiếu nhập
                    await this.loadPurchaseOrderItems();
                    // reset các lỗi và trạng thái touch
                    this.touched = {};
                    this.errors = {};
                } catch (e) {
                    this.showToast('Không thể tải danh sách phiếu nhập');
                } finally {
                    this.loading = false;
                }
            },

            async loadPurchaseOrderItems() {
                // Load danh sách sản phẩm và lô hàng cho tất cả phiếu nhập
                const promises = this.items.map(async (po) => {
                    try {
                        const res = await fetch(`/admin/api/purchase-orders/${po.id}`);
                        if (!res.ok) {
                            console.warn(`Cannot load items for PO ${po.id}: ${res.status}`);
                            po.items = [];
                            return;
                        }
                        const data = await res.json();
                        po.items = data.lines || [];
                    } catch (e) {
                        console.error(`Cannot load items for PO ${po.id}:`, e);
                        po.items = [];
                    }
                });

                await Promise.all(promises);
            },

            async fetchProducts() {
                try {
                    const res = await fetch('/admin/api/products/all-including-inactive');
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
            openFilter: {
                code: false, supplier_name: false, item_product: false, item_qty: false, item_price: false,
                total_amount: false, paid_amount: false,
                due_date: false, note: false, payment_status: false,
                received_at: false, created_by: false, updated_at: false, updated_by: false
            },
            filters: {
                code: '',
                supplier_name: '',
                item_product: '',
                item_qty_type: '', item_qty_value: '', item_qty_from: '', item_qty_to: '',
                item_price_type: '', item_price_value: '', item_price_from: '', item_price_to: '',
                total_amount_type: '', total_amount_value: '', total_amount_from: '', total_amount_to: '',
                paid_amount_type: '', paid_amount_value: '', paid_amount_from: '', paid_amount_to: '',
                due_date_type: '', due_date_value: '', due_date_from: '', due_date_to: '',
                note: '',
                payment_status: '',
                received_at_type: '', received_at_value: '', received_at_from: '', received_at_to: '',
                created_by: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by: '',
            },

            statusLabel(po) {
                // Tính toán trạng thái dựa trên paid_amount và total_amount
                const paidAmount = parseFloat(po.paid_amount || 0);
                const totalAmount = parseFloat(po.total_amount || 0);

                if (paidAmount === 0) {
                    return 'Chưa đối soát';
                } else if (paidAmount >= totalAmount) {
                    return 'Đã thanh toán hết';
                } else {
                    return 'Đã thanh toán một phần';
                }
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

            filtered() {
                let data = this.items;

                // --- Lọc theo text ---
                [
                    'code',
                    'supplier_name',
                    'note',
                    'created_by',
                    'updated_by',
                ].forEach(key => {
                    if (this.filters[key]) {
                        const field = key.endsWith('_by') ? `${key}_name` : key;
                        data = data.filter(o =>
                            this.applyFilter(o[field], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo Sản phẩm hoặc Mã lô ---
                if (this.filters.item_product) {
                    data = data.filter(po => {
                        if (!po.items || po.items.length === 0) return false;
                        return po.items.some(item =>
                            this.applyFilter(item.product_name, 'contains', {
                                value: this.filters.item_product,
                                dataType: 'text'
                            }) ||
                            this.applyFilter(item.batch_code, 'contains', {
                                value: this.filters.item_product,
                                dataType: 'text'
                            })
                        );
                    });
                }

                // --- Lọc theo Số lượng ---
                if (this.filters.item_qty_type) {
                    data = data.filter(po => {
                        if (!po.items || po.items.length === 0) return false;
                        return po.items.some(item =>
                            this.applyFilter(item.quantity, this.filters.item_qty_type, {
                                value: this.filters.item_qty_value,
                                from: this.filters.item_qty_from,
                                to: this.filters.item_qty_to,
                                dataType: 'number'
                            })
                        );
                    });
                }

                // --- Lọc theo Đơn giá ---
                if (this.filters.item_price_type) {
                    data = data.filter(po => {
                        if (!po.items || po.items.length === 0) return false;
                        return po.items.some(item =>
                            this.applyFilter(item.unit_cost, this.filters.item_price_type, {
                                value: this.filters.item_price_value,
                                from: this.filters.item_price_from,
                                to: this.filters.item_price_to,
                                dataType: 'number'
                            })
                        );
                    });
                }

                // --- Lọc theo số ---
                ['total_amount', 'paid_amount'].forEach(key => {
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

                // --- Lọc theo ngày ---
                ['due_date', 'received_at', 'updated_at'].forEach(key => {
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

                // --- Lọc theo trạng thái thanh toán ---
                if (this.filters.payment_status) {
                    const query = String(this.filters.payment_status).trim();
                    data = data.filter(o => {
                        // statusLabel(o) trả về chuỗi trạng thái dựa trên paid_amount / total_amount
                        const st = String(this.statusLabel(o)).trim();
                        return st === query;
                    });
                }

                return data;
            },

            // ==============================
            // BẬT/TẮT & RESET FILTER
            // ==============================
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['received_at', 'updated_at', 'due_date'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['paid_amount', 'total_amount', 'item_qty', 'item_price'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                }
                else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
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

            async openViewModal(po) {
                // Copy dữ liệu cơ bản
                this.viewItem = {
                    ...po,
                    items: [] // Khởi tạo mảng rỗng
                };

                // Mở modal ngay
                this.openView = true;

                // Gọi API lấy chi tiết sản phẩm theo lô
                try {
                    const res = await fetch(`/admin/api/purchase-orders/${po.id}`);
                    if (!res.ok) throw new Error('Không thể tải chi tiết');

                    const data = await res.json();

                    // Cập nhật items với dữ liệu chi tiết
                    this.viewItem = {
                        ...this.viewItem,
                        items: data.lines || []
                    };
                } catch (e) {
                    console.error(e);
                    this.showToast('Không thể tải chi tiết sản phẩm');
                }
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

            exportExcel() {
                const data = this.filtered().map(po => ({
                    code: po.code || '',
                    supplier_name: po.supplier_name || '',
                    total_amount: po.total_amount || 0,
                    paid_amount: po.paid_amount || 0,
                    payment_status: this.statusLabel(po),
                    due_date: po.due_date || '',
                    note: po.note || '',
                    received_at: po.received_at || '',
                    created_by_name: po.created_by_name || '',
                    updated_at: po.updated_at || '',
                    updated_by_name: po.updated_by_name || '',
                    items: po.items || [] // Bao gồm danh sách sản phẩm
                }));

                const now = new Date();
                const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
                const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
                const filename = `Phieu_nhap_kho_${dateStr}_${timeStr}.xlsx`;

                // Get date range from filters
                const fromDate = this.filters.received_from || '';
                const toDate = this.filters.received_to || '';

                fetch('/admin/api/purchase-orders/export', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: data,
                        from_date: fromDate,
                        to_date: toDate,
                        filename: filename
                    })
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Export failed');
                        return res.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        this.showToast('Xuất Excel thành công!', 'success');
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast('Không thể xuất Excel');
                    });
            },

            openImportModal() {
                this.importFile = null;
                this.showImportModal = true;
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (!file) return;

                // 1. Kiểm tra định dạng file
                const ext = file.name.split('.').pop().toLowerCase();
                if (!['xls', 'xlsx'].includes(ext)) {
                    this.showToast('File không đúng định dạng. Vui lòng chọn file Excel (.xls hoặc .xlsx)', 'error');
                    this.clearFile();
                    return;
                }

                // 2. Kiểm tra kích thước file (10MB)
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.showToast(`File quá lớn. Kích thước tối đa: 10MB. Kích thước file: ${(file.size / 1024 / 1024).toFixed(2)}MB`, 'error');
                    this.clearFile();
                    return;
                }

                // 3. Kiểm tra độ dài tên file
                if (file.name.length > 255) {
                    this.showToast(`Tên file quá dài (tối đa 255 ký tự). Độ dài hiện tại: ${file.name.length} ký tự`, 'error');
                    this.clearFile();
                    return;
                }

                // 4. Kiểm tra ký tự đặc biệt
                const fileName = file.name.split('.')[0];
                if (!/^[a-zA-Z0-9._\-\s()\[\]]+$/.test(fileName)) {
                    this.showToast('Tên file chứa ký tự đặc biệt không hợp lệ. Vui lòng chỉ sử dụng chữ cái, số, dấu gạch ngang, gạch dưới và khoảng trắng', 'error');
                    this.clearFile();
                    return;
                }

                this.importFile = file;
            },

            clearFile() {
                this.importFile = null;
                const input = document.getElementById('importPOFile');
                if (input) input.value = '';
            },

            downloadTemplate() {
                window.location.href = '/admin/api/purchase-orders/template';
            },

            async submitImport() {
                if (!this.importFile) {
                    this.showToast('Vui lòng chọn file', 'error');
                    return;
                }

                this.importing = true;

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);

                    const response = await fetch('/admin/api/purchase-orders/import', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (response.ok) {
                        // Đóng modal và reset file
                        this.showImportModal = false;
                        this.clearFile();

                        // Hiển thị kết quả
                        const successCount = result.success || 0;
                        const failedCount = result.failed || 0;
                        const status = result.status || 'success';

                        if (status === 'success') {
                            this.showToast(`Nhập thành công ${successCount} phiếu nhập kho`, 'success');
                        } else if (status === 'partial') {
                            this.showToast(`Nhập thành công ${successCount}/${successCount + failedCount} phiếu nhập kho. ${failedCount} phiếu thất bại. Xem chi tiết trong Lịch sử nhập.`, 'error');
                        } else {
                            this.showToast(result.message || `Nhập thất bại ${failedCount} phiếu nhập kho. Xem chi tiết trong Lịch sử nhập.`, 'error');
                        }

                        // Reload danh sách
                        await this.init();
                    } else {
                        this.showToast(result.error || 'Có lỗi xảy ra khi nhập file', 'error');
                    }

                } catch (error) {
                    console.error(error);
                    this.showToast(error.message || 'Có lỗi xảy ra khi nhập file', 'error');
                } finally {
                    this.importing = false;
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