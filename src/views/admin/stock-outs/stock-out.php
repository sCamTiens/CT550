<?php
// views/admin/stock-outs/stock-out.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Quản lý kho / <span class="text-slate-800 font-medium">Phiếu xuất kho</span>
</nav>

<div x-data="stockOutPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý phiếu xuất kho</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm phiếu xuất</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:200%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã phiếu') ?>
                        <?= selectFilterPopover('type', 'Loại xuất', [
                            '' => '-- Tất cả --',
                            'sale' => 'Bán hàng',
                            'return' => 'Trả hàng NCC',
                            'damage' => 'Hư hỏng',
                            'other' => 'Khác'
                        ]) ?>
                        <?= textFilterPopover('order_code', 'Mã đơn hàng') ?>
                        <?= textFilterPopover('customer_name', 'Khách hàng') ?>
                        <th class="py-2 px-4 text-center align-top" style="min-width: 500px; width: 500px;">
                            <div class="mb-2 text-base font-bold">Chi tiết xuất kho (theo lô)</div>

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

                                    <div x-show="openFilter.item_product" x-transition
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

                                    <div x-show="openFilter.item_qty" x-transition
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

                                    <div x-show="openFilter.item_price" x-transition
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
                        <?= selectFilterPopover('status', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            'pending' => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy'
                        ]) ?>
                        <?= dateFilterPopover('out_date', 'Ngày xuất') ?>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(s, idx) in paginated()" :key="s.id">
                        <tr>
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openViewModal(s)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button x-show="s.status !== 'completed'" @click="remove(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button x-show="s.status === 'pending'" @click="approveStockOut(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-green-600"
                                    title="Duyệt">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button x-show="s.status === 'approved'" @click="completeStockOut(s.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-blue-600"
                                    title="Hoàn thành">
                                    <i class="fa-solid fa-check-double"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.code"></td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-blue-100 text-blue-800': s.type === 'sale',
                                        'bg-orange-100 text-orange-800': s.type === 'return',
                                        'bg-red-100 text-red-800': s.type === 'damage',
                                        'bg-gray-100 text-gray-800': s.type === 'other'
                                    }" x-text="getTypeText(s.type)">
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.order_code || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.customer_name || '—'"></td>

                            <!-- Cột Chi tiết xuất kho theo lô -->
                            <td class="px-3 py-2 align-top" style="min-width: 800px; width: 800px;">
                                <div class="space-y-2">
                                    <!-- Hiển thị danh sách sản phẩm theo lô -->
                                    <template x-if="s.items && s.items.length > 0">
                                        <div class="space-y-2">
                                            <template x-for="(item, itemIdx) in s.items" :key="itemIdx">
                                                <div class="grid grid-cols-[2.5fr_1fr_1fr] gap-3 p-2">
                                                    <!-- Tên sản phẩm - Mã lô -->
                                                    <div>
                                                        <div>
                                                            <span x-text="item.product_name || '—'"></span>
                                                            <span> - </span>
                                                            <span x-text="item.batch_code || '—'"></span>
                                                        </div>
                                                    </div>

                                                    <!-- Số lượng -->
                                                    <div class="text-center">
                                                        <div x-text="item.qty || 0"></div>
                                                    </div>

                                                    <!-- Đơn giá -->
                                                    <div class="text-right">
                                                        <div x-text="formatCurrency(item.unit_price || 0)"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Trạng thái rỗng -->
                                    <template x-if="!s.items || s.items.length === 0">
                                        <div
                                            class="text-center text-gray-400 text-sm py-4 bg-gray-50 rounded border border-dashed">
                                            Chưa có sản phẩm
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center align-middle">
                                <div class="px-2 py-[3px] rounded text-xs font-medium"
                                    class="flex justify-center items-center h-full">
                                    <span :class="{
                                    'px-2 py-1 rounded text-xs': true,
                                    'bg-yellow-100 text-yellow-800': s.status === 'pending',
                                    'bg-blue-100 text-blue-800': s.status === 'approved',
                                    'bg-green-100 text-green-800': s.status === 'completed',
                                    'bg-red-100 text-red-800': s.status === 'cancelled'
                                }" x-text="getStatusText(s.status)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="s.out_date ? s.out_date.substring(0,10) : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right font-semibold"
                                x-text="formatCurrency(s.total_amount)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.note || '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="s.created_at ? s.created_at : '—'"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line" x-text="s.created_by_name || '—'">
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

        <!-- MODAL: Create -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openAdd" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn animate__faster"
                @click.outside="openAdd=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative sticky top-0 bg-white z-10">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm phiếu xuất kho</h3>
                    <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3 sticky bottom-0 bg-white border-t py-3">
                        <button type="button" @click="openAdd=false"
                            class="px-4 py-2 border rounded text-sm">Hủy</button>
                        <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
                            x-text="submitting ? 'Đang lưu...' : 'Tạo'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: View (Chi tiết) -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openView"
            x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-4xl rounded-xl shadow max-h-[90vh] flex flex-col"
                @click.outside="openView=false">
                <div class="px-5 py-3 border-b flex justify-center items-center relative flex-shrink-0">
                    <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết phiếu xuất kho</h3>
                    <button class="text-slate-500 absolute right-5" @click="openView=false">✕</button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <!-- Thông tin phiếu xuất -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Mã phiếu</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewItem.code"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Loại xuất</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border" x-text="getTypeText(viewItem.type)"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Mã đơn hàng</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewItem.order_code || '—'"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Khách hàng</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewItem.customer_name || '—'">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Trạng thái</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="{
                                    'bg-yellow-100 text-yellow-800': viewItem.status === 'pending',
                                    'bg-blue-100 text-blue-800': viewItem.status === 'approved',
                                    'bg-green-100 text-green-800': viewItem.status === 'completed',
                                    'bg-red-100 text-red-800': viewItem.status === 'cancelled'
                                }" x-text="getStatusText(viewItem.status)"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Ngày xuất</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border"
                                x-text="viewItem.out_date ? viewItem.out_date.substring(0, 10) : '—'"></div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-900 mb-1">Ghi chú</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border min-h-[60px]" x-text="viewItem.note || '—'">
                            </div>
                        </div>
                    </div>

                    <!-- Chi tiết sản phẩm theo lô -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">Chi tiết sản phẩm</label>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">Sản phẩm -
                                            Mã lô</th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-gray-700">NSX/HSD
                                        </th>
                                        <th class="px-3 py-2 text-center text-sm font-semibold text-gray-700">Số lượng
                                        </th>
                                        <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">Đơn giá
                                        </th>
                                        <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">Thành tiền
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="viewItem.items && viewItem.items.length > 0">
                                        <template x-for="(item, idx) in viewItem.items" :key="idx">
                                            <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                                                <td class="px-3 py-2">
                                                    <div class="font-medium" x-text="item.product_name"></div>
                                                    <div class="text-xs">Lô: <span x-text="item.batch_code"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-center text-xs text-gray-600">
                                                    <div x-show="item.mfg_date">NSX: <span
                                                            x-text="item.mfg_date"></span></div>
                                                    <div x-show="item.exp_date">
                                                        HSD: <span x-text="item.exp_date"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-center font-semibold" x-text="item.qty"></td>
                                                <td class="px-3 py-2 text-right"
                                                    x-text="formatCurrency(item.unit_price)"></td>
                                                <td class="px-3 py-2 text-right font-semibold"
                                                    x-text="formatCurrency(item.qty * item.unit_price)"></td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template x-if="!viewItem.items || viewItem.items.length === 0">
                                        <tr>
                                            <td colspan="5" class="px-3 py-8 text-center text-gray-400">
                                                Chưa có sản phẩm
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-gray-50 border-t-2">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right font-semibold">Tổng cộng:</td>
                                        <td class="px-3 py-2 text-right font-bold text-lg text-[#002975]"
                                            x-text="formatCurrency(viewItem.total_amount)"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Thông tin người tạo -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người tạo</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border text-sm"
                                x-text="viewItem.created_by_name || '—'"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian tạo</label>
                            <div class="px-3 py-2 bg-gray-50 rounded border text-sm"
                                x-text="viewItem.created_at || '—'"></div>
                        </div>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-2 flex justify-end gap-3 border-t bg-white flex-shrink-0">
                    <button type="button" class="px-4 py-2 border rounded text-sm" @click="openView=false">Đóng</button>
                </div>
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
                        <div @click="perPage=opt; open=false" class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                            x-text="opt + ' / trang'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function stockOutPage() {
        const api = {
            list: '/admin/api/stock-outs',
            create: '/admin/api/stock-outs',
            update: (id) => `/admin/api/stock-outs/${id}`,
            remove: (id) => `/admin/api/stock-outs/${id}`,
            approve: (id) => `/admin/api/stock-outs/${id}/approve`,
            complete: (id) => `/admin/api/stock-outs/${id}/complete`,
            nextCode: '/admin/api/stock-outs/next-code',
            orders: '/admin/api/orders',
        };

        const MAX_AMOUNT = 1_000_000_000;
        const MAXLEN = 255;

        return {
            // ===== STATE =====
            loading: true,
            submitting: false,
            openAdd: false,
            openView: false,
            viewItem: {},
            order_id: null,
            orders: [],
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
                type: 'sale',
                order_id: '',
                status: 'pending',
                out_date: '',
                total_amount: 0,
                total_amountFormatted: '',
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

                    // Load chi tiết lô hàng cho mỗi phiếu xuất
                    await this.loadStockOutItems();
                } catch (e) {
                    this.showToast('Không thể tải dữ liệu phiếu xuất kho');
                } finally {
                    this.loading = false;
                }
            },

            async loadStockOutItems() {
                // Load danh sách sản phẩm và lô hàng cho tất cả phiếu xuất
                const promises = this.items.map(async (stockOut) => {
                    try {
                        console.log('Fetching items for stock-out ID:', stockOut.id);
                        const res = await fetch(`/admin/api/stock-outs/${stockOut.id}/items`);
                        console.log('Response status:', res.status);

                        if (res.ok) {
                            const data = await res.json();
                            console.log('Items data for stock-out', stockOut.id, ':', data);

                            stockOut.items = (data.items || []).map(item => {
                                // Kiểm tra xem lô có gần hết hạn không (còn 30 ngày)
                                const today = new Date();
                                const expDate = item.exp_date ? new Date(item.exp_date) : null;
                                const daysUntilExpiry = expDate ? Math.floor((expDate - today) / (1000 * 60 * 60 * 24)) : null;

                                return {
                                    ...item,
                                    is_near_expiry: daysUntilExpiry !== null && daysUntilExpiry <= 30 && daysUntilExpiry >= 0,
                                    is_oldest_batch: item.is_oldest_batch || false,
                                    days_until_expiry: daysUntilExpiry
                                };
                            });
                            console.log('Processed items:', stockOut.items);
                        } else {
                            const errorText = await res.text();
                            console.error('Failed to fetch items:', errorText);
                            stockOut.items = [];
                        }
                    } catch (e) {
                        console.error('Error loading items for stock-out', stockOut.id, ':', e);
                        stockOut.items = [];
                    }
                });

                await Promise.all(promises);
                console.log('All stock-outs with items:', this.items);
            },

            // ===== FILTERS =====
            openFilter: {
                code: false, customer_name: false, item_product: false,
                item_qty: false, item_price: false,
                order_code: false, type: false,
                status: false, out_date: false,
                total_amount: false, note: false, created_at: false, created_by_name: false
            },

            filters: {
                code: '', customer_name: '', type: '', note: '', status: '', created_by_name: '', order_code: '',

                item_product: '',

                item_qty_type: '', item_qty_value: '', item_qty_from: '', item_qty_to: '',
                item_price_type: '', item_price_value: '', item_price_from: '', item_price_to: '',

                total_amount_type: '', total_amount_value: '', total_amount_from: '', total_amount_to: '',
                out_date_type: '', out_date_value: '', out_date_from: '', out_date_to: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: ''
            },

            // ------------------------------------------------------------------
            // Hàm lọc tổng quát — hỗ trợ TEXT, NUMBER, DATE
            // ------------------------------------------------------------------
            applyFilter(val, type, { value, from, to, dataType }) {
                if (val == null) return false;

                // -------- TEXT --------
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
                        return hasAccent(query)
                            ? raw.includes(query)
                            : str.includes(queryNoAccent);
                    }

                    return true;
                }

                // -------- NUMBER --------
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

                // -------- DATE --------
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
                        return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
                    }
                    if (type === 'lte') {
                        if (!v) return true;
                        const nextDay = new Date(v);
                        nextDay.setDate(v.getDate() + 1);
                        return d < nextDay;
                    }
                    if (type === 'gte') return v ? d >= v : true;
                    if (type === 'between') return f && t ? d >= f && d <= t : true;

                    return true;
                }

                return true;
            },

            // ------------------------------------------------------------------
            // Áp dụng filter cho toàn bộ bảng
            // ------------------------------------------------------------------
            filtered() {
                let data = this.items; // đây là mảng danh sách phiếu xuất (s)

                // --- TEXT: các cột cấp phiếu ---
                ['code', 'customer_name', 'type', 'note', 'created_by_name', 'order_code'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(s =>
                            this.applyFilter(s[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- STATUS ---
                if (this.filters.status) {
                    data = data.filter(s =>
                        this.applyFilter(s.status, 'eq', {
                            value: this.filters.status,
                            dataType: 'text'
                        })
                    );
                }

                // Sản phẩm hoặc Mã lô
                if (this.filters.item_product) {
                    const keyword = this.filters.item_product.toLowerCase();
                    data = data.filter(s => s.items?.some(it =>
                        (it.product_name && it.product_name.toLowerCase().includes(keyword)) ||
                        (it.batch_code && it.batch_code.toLowerCase().includes(keyword))
                    ));
                }

                // Số lượng
                if (this.filters.item_qty_type) {
                    data = data.filter(s => s.items?.some(it =>
                        this.applyFilter(it.qty, this.filters.item_qty_type, {
                            value: this.filters.item_qty_value,
                            from: this.filters.item_qty_from,
                            to: this.filters.item_qty_to,
                            dataType: 'number'
                        })
                    ));
                }

                // Đơn giá
                if (this.filters.item_price_type) {
                    data = data.filter(s => s.items?.some(it =>
                        this.applyFilter(it.unit_price, this.filters.item_price_type, {
                            value: this.filters.item_price_value,
                            from: this.filters.item_price_from,
                            to: this.filters.item_price_to,
                            dataType: 'number'
                        })
                    ));
                }

                // Tổng tiền
                if (this.filters.total_amount_type) {
                    data = data.filter(s =>
                        this.applyFilter(s.total_amount, this.filters.total_amount_type, {
                            value: this.filters.total_amount_value,
                            from: this.filters.total_amount_from,
                            to: this.filters.total_amount_to,
                            dataType: 'number'
                        })
                    );
                }

                // Ngày xuất, Ngày tạo
                ['out_date', 'created_at'].forEach(key => {
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

                return data;
            },

            // ------------------------------------------------------------------
            // Mở / đóng / reset filter
            // ------------------------------------------------------------------
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                // --- Date type ---
                if (['created_at', 'out_date'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                }

                // --- Number type ---
                else if (['price', 'total_amount', 'qty', 'item_qty', 'item_price'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                }

                // --- Select type (status) ---
                else if (key === 'status') {
                    this.filters.status = '';
                }

                // --- Text type (code, customer_name, product_name, batch_code, type, note, created_by_name) ---
                else {
                    this.filters[key] = '';
                }

                // --- Close dropdown ---
                this.openFilter[key] = false;
            },

            // --- utils ---
            formatCurrency(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(n || 0);
                } catch {
                    return n;
                }
            },

            onAmountInput(e) {
                let raw = e.target.value.replace(/[^\d]/g, '');
                let val = Number(raw);
                if (Number.isNaN(val)) val = 0;
                this.form.total_amount = val;
                this.form.total_amountFormatted = val.toLocaleString('en-US');
            },

            getTypeText(type) {
                const map = {
                    'sale': 'Bán hàng',
                    'return': 'Trả hàng NCC',
                    'damage': 'Hư hỏng',
                    'other': 'Khác'
                };
                return map[type] || type;
            },

            getStatusText(status) {
                const map = {
                    'pending': 'Chờ duyệt',
                    'approved': 'Đã duyệt',
                    'completed': 'Hoàn thành',
                    'cancelled': 'Đã hủy'
                };
                return map[status] || status;
            },

            // ===== VALIDATION =====
            validateField(field) {
                this.errors[field] = '';

                if (field === 'total_amount') {
                    if (!this.form.total_amount || this.form.total_amount < 0)
                        this.errors.total_amount = 'Tổng tiền không hợp lệ';
                    else if (this.form.total_amount > MAX_AMOUNT)
                        this.errors.total_amount = 'Tổng tiền quá lớn';
                }
            },

            validateForm() {
                this.errors = {};
                const fields = ['total_amount'];
                for (const f of fields) this.validateField(f);
                return Object.values(this.errors).every(v => !v);
            },

            async fetchOrders() {
                try {
                    const res = await fetch(api.orders);
                    const data = await res.json();
                    this.orders = (data.items || []).map(o => ({
                        id: o.id,
                        code: o.code,
                        customer_name: o.customer_name
                    }));
                } catch (e) {
                    this.showToast('Không thể tải danh sách đơn hàng');
                }
            },

            resetForm() {
                this.form = {
                    id: null,
                    code: '',
                    type: 'sale',
                    order_id: '',
                    status: 'pending',
                    out_date: new Date().toISOString().substring(0, 10),
                    total_amount: 0,
                    total_amountFormatted: '',
                    note: '',
                };
                this.errors = {};
                this.touched = {};
                this.order_id = null;
                this.orders = [];
            },

            async fetchOrders() {
                try {
                    const res = await fetch('/admin/api/orders/unpaid');
                    if (res.ok) {
                        const data = await res.json();
                        this.orders = data.items || [];
                    } else {
                        this.orders = [];
                    }
                } catch {
                    this.orders = [];
                }
            },

            // ===== CRUD =====
            async openCreate() {
                this.resetForm();
                await Promise.all([
                    this.fetchOrders(),
                    this.fetchNextCode()
                ]);
                this.openAdd = true;
            },

            async fetchNextCode() {
                try {
                    const res = await fetch(api.nextCode);
                    if (res.ok) {
                        const data = await res.json();
                        this.form.code = data.code;
                    } else {
                        this.form.code = '';
                    }
                } catch {
                    this.form.code = '';
                }
            },

            async openViewModal(s) {
                // Copy dữ liệu và đảm bảo items được load
                this.viewItem = {
                    ...s,
                    items: s.items || []
                };

                // Nếu chưa có items, load lại
                if (!this.viewItem.items || this.viewItem.items.length === 0) {
                    try {
                        const res = await fetch(`/admin/api/stock-outs/${s.id}/items`);
                        if (res.ok) {
                            const data = await res.json();
                            this.viewItem.items = (data.items || []).map(item => {
                                const today = new Date();
                                const expDate = item.exp_date ? new Date(item.exp_date) : null;
                                const daysUntilExpiry = expDate ? Math.floor((expDate - today) / (1000 * 60 * 60 * 24)) : null;

                                return {
                                    ...item,
                                    is_near_expiry: daysUntilExpiry !== null && daysUntilExpiry <= 30 && daysUntilExpiry >= 0,
                                    is_oldest_batch: item.is_oldest_batch || false,
                                    days_until_expiry: daysUntilExpiry
                                };
                            });
                        }
                    } catch (e) {
                        this.viewItem.items = [];
                    }
                }

                this.openView = true;
            },

            async submitCreate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        this.showToast('Thêm phiếu xuất kho thành công!', 'success');
                        this.openAdd = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể thêm phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm phiếu xuất kho');
                } finally {
                    this.submitting = false;
                }
            },

            async submitUpdate() {
                if (!this.validateForm()) return;
                this.submitting = true;
                try {
                    const res = await fetch(api.update(this.form.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    if (res.ok) {
                        this.showToast('Cập nhật phiếu xuất kho thành công!', 'success');
                        this.openEdit = false;
                        await this.fetchAll();
                    } else {
                        this.showToast('Không thể cập nhật phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật phiếu xuất kho');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Bạn có chắc muốn xóa phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(r => r.id !== id);
                        this.showToast('Xóa phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể xóa phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa phiếu xuất kho');
                }
            },

            async approveStockOut(id) {
                if (!confirm('Bạn có chắc muốn duyệt phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.approve(id), { method: 'POST' });
                    if (res.ok) {
                        await this.fetchAll();
                        this.showToast('Duyệt phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể duyệt phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể duyệt phiếu xuất kho');
                }
            },

            async completeStockOut(id) {
                if (!confirm('Bạn có chắc muốn hoàn thành phiếu xuất kho này?')) return;
                try {
                    const res = await fetch(api.complete(id), { method: 'POST' });
                    if (res.ok) {
                        await this.fetchAll();
                        this.showToast('Hoàn thành phiếu xuất kho thành công!', 'success');
                    } else {
                        this.showToast('Không thể hoàn thành phiếu xuất kho');
                    }
                } catch (e) {
                    this.showToast('Không thể hoàn thành phiếu xuất kho');
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