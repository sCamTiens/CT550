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
    Admin / <span class="text-slate-800 font-medium">Quản lý đơn hàng</span>
</nav>

<div x-data="orderPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý đơn hàng</h1>
        <div class="flex gap-2">
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="exportExcel()">
                <i class="fa-solid fa-file-excel"></i>
                Xuất Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
                @click="openCreate()">+ Thêm đơn hàng</button>
        </div>
    </div>

    <!-- Thống kê tổng quan -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng số đơn hàng</div>
            <div class="text-2xl font-bold text-blue-600" x-text="getTotalOrders()"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng doanh thu</div>
            <div class="text-lg font-bold text-green-600" x-text="formatCurrency(getTotalRevenue())"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đơn hàng hoàn thành</div>
            <div class="text-2xl font-bold text-purple-600" x-text="countByStatus('Đã giao')"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đơn hàng chờ xử lý</div>
            <div class="text-2xl font-bold text-orange-600" x-text="countByStatus('Chờ xử lý')"></div>
        </div>
    </section>

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
            <table style="width:280%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center" style="min-width: 100px;">Thao tác</th>
                        <?= textFilterPopover('code', 'Mã đơn hàng', minWidth: 130) ?>
                        <?= textFilterPopover('customer_name', 'Khách hàng', minWidth: 150) ?>
                        <?= selectFilterPopover('order_type', 'Loại đơn', [
                            '' => '-- Tất cả --',
                            'Online' => 'Online',
                            'Offline' => 'Offline',
                        ], minWidth: 100) ?>
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
                                    <div x-cloak x-show="openFilter.product_name" x-transition
                                        @click.outside="openFilter.product_name=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Tên sản phẩm"</div>
                                        <input x-model.trim="filters.product_name"
                                            class="w-full border rounded px-3 py-2" placeholder="Nhập tên sản phẩm">
                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('product_name')"
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
                                    <div x-cloak x-show="openFilter.qty" x-transition
                                        @click.outside="openFilter.qty=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left left-0">
                                        <div class="font-semibold mb-2">Tìm theo "Số lượng"</div>

                                        <div class="flex flex-col gap-2">
                                            <!-- Dòng chọn kiểu lọc -->
                                            <select x-model="filters.qty_type"
                                                class="border rounded px-3 py-2 text-sm w-full">
                                                <option value="">-- Chọn kiểu lọc --</option>
                                                <option value="eq">Bằng</option>
                                                <option value="gt">Lớn hơn</option>
                                                <option value="lt">Nhỏ hơn</option>
                                                <option value="gte">Lớn hơn hoặc bằng</option>
                                                <option value="lte">Nhỏ hơn hoặc bằng</option>
                                                <option value="between">Trong khoảng</option>
                                            </select>

                                            <!-- Dòng nhập giá trị -->
                                            <div class="flex gap-2 items-center">
                                                <input type="number" x-model.number="filters.qty_value"
                                                    x-show="filters.qty_type && filters.qty_type !== 'between'"
                                                    class="flex-1 border rounded px-3 py-2" placeholder="Nhập giá trị">

                                                <template x-if="filters.qty_type === 'between'">
                                                    <div class="flex gap-2 w-full">
                                                        <input type="number" x-model.number="filters.qty_from"
                                                            class="w-1/2 border rounded px-3 py-2" placeholder="Từ">
                                                        <input type="number" x-model.number="filters.qty_to"
                                                            class="w-1/2 border rounded px-3 py-2" placeholder="Đến">
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('qty')"
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

                                    <!-- Popup lọc -->
                                    <div x-cloak x-show="openFilter.unit_price" x-transition
                                        @click.outside="openFilter.unit_price=false"
                                        class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow-lg border p-3 text-left right-0">
                                        <div class="font-semibold mb-2">Tìm theo "Đơn giá"</div>

                                        <div class="flex flex-col gap-2">
                                            <!-- Dòng chọn kiểu lọc -->
                                            <select x-model="filters.unit_price_type"
                                                class="border rounded px-3 py-2 text-sm w-full">
                                                <option value="">-- Chọn kiểu lọc --</option>
                                                <option value="eq">Bằng</option>
                                                <option value="gt">Lớn hơn</option>
                                                <option value="lt">Nhỏ hơn</option>
                                                <option value="gte">Lớn hơn hoặc bằng</option>
                                                <option value="lte">Nhỏ hơn hoặc bằng</option>
                                                <option value="between">Trong khoảng</option>
                                            </select>

                                            <!-- Dòng nhập giá trị -->
                                            <div class="flex gap-2 items-center">
                                                <input type="number" x-model.number="filters.unit_price_value"
                                                    x-show="filters.unit_price_type && filters.unit_price_type !== 'between'"
                                                    class="flex-1 border rounded px-3 py-2" placeholder="Nhập giá trị">

                                                <template x-if="filters.unit_price_type === 'between'">
                                                    <div class="flex gap-2 w-full">
                                                        <input type="number" x-model.number="filters.unit_price_from"
                                                            class="w-1/2 border rounded px-3 py-2" placeholder="Từ">
                                                        <input type="number" x-model.number="filters.unit_price_to"
                                                            class="w-1/2 border rounded px-3 py-2" placeholder="Đến">
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex gap-2 justify-end">
                                            <button @click="closeFilter('unit_price')"
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
                            'Chờ xử lý' => 'Chờ xử lý',
                            'Đang xử lý' => 'Đang xử lý',
                            'Đang giao' => 'Đang giao',
                            'Đã giao' => 'Đã giao',
                            'Đã hủy' => 'Đã hủy',
                        ]) ?>
                        <?= numberFilterPopover('subtotal', 'Tạm tính') ?>
                        <?= numberFilterPopover('promotion_discount', 'CT khuyến mãi') ?>
                        <?= numberFilterPopover('discount_amount', 'Giảm giá') ?>
                        <?= numberFilterPopover('total_amount', 'Tổng tiền') ?>
                        <?= selectFilterPopover('payment_method', 'PT thanh toán', [
                            '' => '-- Tất cả --',
                            'Tiền mặt' => 'Tiền mặt',
                            'Chuyển khoản' => 'Chuyển khoản',
                        ]) ?>
                        <?= textFilterPopover('shipping_address', 'Địa chỉ giao') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(o, idx) in paginated()" :key="o.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
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

                                <!-- Manual Actions Modal Trigger -->
                                <template x-if="o.status === 'Đang giao hàng'">
                                    <button @click.stop="openManualAction(o)"
                                        class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                        title="Cập nhật trạng thái thủ công">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </template>

                                <!-- Nút Xử lý đơn (chỉ hiện khi Chờ xử lý) -->
                                <button x-show="o.status === 'Chờ xử lý'" @click="processOrder(o.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xử lý đơn">
                                    <i class="fa-solid fa-play"></i>
                                </button>

                                <!-- Nút Giao GHN (chỉ hiện khi Đang xử lý) -->
                                <button x-show="o.status === 'Đang xử lý'" @click="shipWithGHN(o.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Giao cho GHN">
                                    <i class="fa-solid fa-truck"></i>
                                </button>

                                <!-- Nút Tracking GHN (chỉ hiện khi có ghn_order_code) -->
                                <button x-show="o.ghn_order_code" @click="viewTracking(o.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-purple-100 text-purple-600"
                                    title="Tracking GHN">
                                    <i class="fa-solid fa-map-marker-alt"></i>
                                </button>

                                <!-- Nút Xóa (ẩn nếu trạng thái Hoàn tất) -->
                                <button x-show="o.status !== 'Đã giao'" @click="remove(o.id)"
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

                            <!-- Cột Loại đơn -->
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px]rounded text-xs font-medium" :class="{
                                        'bg-blue-100 text-blue-800': o.order_type === 'Online',
                                        'bg-green-100 text-green-800': o.order_type === 'Offline',
                                    }" x-text="o.order_type || 'Online'"></span>
                                </div>
                            </td>

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
                                                            <div class="text-right" x-text="item.quantity || 0"></div>
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
                                        'bg-yellow-100 text-yellow-800': o.status === 'Chờ xử lý',
                                        'bg-blue-100 text-blue-800': o.status === 'Đang xử lý',
                                        'bg-orange-100 text-orange-800': o.status === 'Đang giao hàng',
                                        'bg-green-100 text-green-800': o.status === 'Đã giao',
                                        'bg-red-100 text-red-800': o.status === 'Đã hủy',
                                    }" x-text="getStatusText(o.status)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.subtotal || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.promotion_discount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right"
                                x-text="formatCurrency(o.discount_amount || 0)"></td>
                            <td class="px-3 py-2 break-words whitespace-pre-line text-right font-semibold"
                                x-text="formatCurrency(o.total_amount || 0)"></td>
                            <!-- <td class="px-3 py-2 break-words whitespace-pre-line"
                                :class="(o.payment_method || '—') === '—' ? 'text-center' : 'text-left'"
                                x-text="o.payment_method || '—'"></td> -->
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-green-100 text-green-800': o.payment_method === 'Tiền mặt',
                                        'bg-red-100 text-orange-800': o.payment_method === 'Chuyển khoản',
                                    }" x-text="getPaymentMethodText(o.payment_method)"></span>
                                </div>
                            </td>
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
                        <td colspan="14" class="py-12 text-center text-slate-500">
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

    <!-- Confirm Dialog -->
    <div x-show="confirmDialog.show"
        class="fixed inset-0 bg-black/40 z-[70] flex items-center justify-center p-5 mt-[-200px]"
        style="display: none;">
        <div class="bg-white w-full max-w-md rounded-xl shadow-lg" @click.outside="confirmDialog.show = false">
            <div class="px-5 py-4 border-b">
                <h3 class="text-xl font-bold text-[#002975]" x-text="confirmDialog.title"></h3>
            </div>
            <div class="p-5">
                <p class="text-gray-600 whitespace-pre-line" x-text="confirmDialog.message"></p>
            </div>
            <div class="px-5 py-4 border-t flex gap-2 justify-end">
                <button @click="confirmDialog.show = false; confirmDialog.onCancel()"
                    class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-600 hover:text-white">
                    Hủy
                </button>
                <button @click="confirmDialog.show = false; confirmDialog.onConfirm()"
                    class="px-4 py-2 border border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>

    <!-- Manual Action Modal -->
    <div x-show="manualActionDialog.show"
        class="fixed inset-0 bg-black/40 z-[80] flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        style="display: none;"
        @click.self="manualActionDialog.show = false">
        <div class="bg-white w-full max-w-md rounded-xl shadow-2xl flex flex-col animate__animated animate__zoomIn animate__faster overflow-hidden"
            @click.outside="manualActionDialog.show = false">

            <!-- Header -->
            <div class="px-5 py-4 border-b flex justify-center items-center relative flex-shrink-0 bg-white">
                <h3 class="font-bold text-2xl text-[#002975]">Cập nhật trạng thái</h3>
                <button type="button" class="text-slate-400 hover:text-red-500 absolute right-5 text-xl transition-colors"
                    @click="manualActionDialog.show = false">✕</button>
            </div>

            <!-- Body -->
            <div class="p-6 flex flex-col gap-6 bg-white">
                <div class="text-center text-gray-600">
                    Đơn hàng <span class="font-bold text-[#002975] text-lg" x-text="manualActionDialog.orderCode"></span><br>đang trong quá trình giao hàng.
                    <div class="mt-2 text-sm text-gray-500">Vui lòng xác nhận kết quả giao hàng thực tế:</div>
                </div>

                <div class="flex flex-col gap-3">
                    <!-- Nút Hoàn tất -->
                    <button @click="manualActionDialog.show = false; manualComplete(manualActionDialog.orderId)"
                        class="relative flex items-center p-4 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 transition-all duration-200 group text-left shadow-sm hover:shadow-md">
                        <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-check text-xl"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="font-bold text-green-800 text-lg group-hover:text-green-900">Giao thành công</div>
                            <div class="text-sm text-green-600 opacity-90 group-hover:opacity-100">Xác nhận hoàn tất & tạo phiếu thu</div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-green-400 opacity-0 group-hover:opacity-100 transition-opacity absolute right-4"></i>
                    </button>

                    <!-- Nút Hủy -->
                    <button @click="manualActionDialog.show = false; manualCancel(manualActionDialog.orderId)"
                        class="relative flex items-center p-4 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-all duration-200 group text-left shadow-sm hover:shadow-md">
                        <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="font-bold text-red-800 text-lg group-hover:text-red-900">Giao thất bại / Hủy</div>
                            <div class="text-sm text-red-600 opacity-90 group-hover:opacity-100">Hủy đơn hàng & hoàn kho sản phẩm</div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-red-400 opacity-0 group-hover:opacity-100 transition-opacity absolute right-4"></i>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                <button @click="manualActionDialog.show = false"
                    class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-800 text-sm font-medium transition-colors shadow-sm">
                    Đóng
                </button>
            </div>
        </div>
    </div>

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
            checkPromotions: '/admin/api/promotions/check',
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

            // ===== PROMOTIONS =====
            appliedPromotions: [],
            promotionDiscount: 0,
            checkingPromotions: false,

            manualActionDialog: {
                show: false,
                orderId: null,
                orderCode: ''
            },

            confirmDialog: {
                show: false,
                title: '',
                message: '',
                onConfirm: () => {},
                onCancel: () => {}
            },

            // ===== ACTIONS =====
            openManualAction(order) {
                this.manualActionDialog = {
                    show: true,
                    orderId: order.id,
                    orderCode: order.code
                };
            },

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
                loyalty_points_used: 0,
                coupon_code: '',
            },

            errors: {},
            touched: {},
            customerLoyaltyPoints: 0,

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
            openFilter: {
                id: false,
                code: false,
                customer_name: false,
                order_type: false,
                product_name: false,
                qty: false,
                unit_price: false,
                status: false,
                subtotal: false,
                promotion_discount: false,
                discount_amount: false,
                total_amount: false,
                payment_method: false,
                shipping_address: false,
                note: false,
                created_at: false,
                created_by: false
            },
            filters: {
                id: '',
                code: '',
                customer_name: '',
                order_type: '',
                product_name: '',
                qty_type: '',
                qty_value: '',
                qty_from: '',
                qty_to: '',
                unit_price_type: '',
                unit_price_value: '',
                unit_price_from: '',
                unit_price_to: '',
                status: '',
                subtotal_type: '',
                subtotal_value: '',
                subtotal_from: '',
                subtotal_to: '',
                promotion_discount_type: '',
                promotion_discount_value: '',
                promotion_discount_from: '',
                promotion_discount_to: '',
                discount_amount_type: '',
                discount_amount_value: '',
                discount_amount_from: '',
                discount_amount_to: '',
                total_amount_type: '',
                total_amount_value: '',
                total_amount_from: '',
                total_amount_to: '',
                payment_method: '',
                shipping_address: '',
                note: '',
                created_at_type: '',
                created_at_value: '',
                created_at_from: '',
                created_at_to: '',
                created_by: ''
            },

            // -------------------------------------------
            // Hàm lọc tổng quát, hỗ trợ text / number / date
            // -------------------------------------------
            applyFilter(val, type, {
                value,
                from,
                to,
                dataType
            }) {
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

                    if (type === 'eq') return hasAccent(query) ?
                        raw === query // có dấu → so đúng dấu
                        :
                        str === queryNoAccent; // không dấu → so không dấu

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

                // --- Lọc theo chuỗi ---
                ['code', 'customer_name', 'shipping_address', 'note', 'created_by'].forEach(key => {
                    if (this.filters[key]) {
                        const field = key === 'created_by' ? 'created_by_name' : key;
                        data = data.filter(o =>
                            this.applyFilter(o[field], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo select ---
                ['status', 'payment_method', 'order_type'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'eq', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo số ---
                if (this.filters.qty_type) {
                    data = data.filter(o => {
                        const allQty = o.items?.map(i => Number(i.quantity) || 0) || [];
                        const totalQty = allQty.reduce((a, b) => a + b, 0);
                        return this.applyFilter(totalQty, this.filters.qty_type, {
                            value: this.filters.qty_value,
                            from: this.filters.qty_from,
                            to: this.filters.qty_to,
                            dataType: 'number'
                        });
                    });
                }

                // --- Lọc các trường số thông thường ---
                ['subtotal', 'promotion_discount', 'discount_amount', 'total_amount'].forEach(key => {
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

                // --- Lọc theo đơn giá trong từng sản phẩm của đơn ---
                if (this.filters.unit_price_type) {
                    data = data.filter(o =>
                        o.items?.some(i =>
                            this.applyFilter(i.unit_price, this.filters.unit_price_type, {
                                value: this.filters.unit_price_value,
                                from: this.filters.unit_price_from,
                                to: this.filters.unit_price_to,
                                dataType: 'number'
                            })
                        )
                    );
                }


                // --- Lọc theo ngày ---
                if (this.filters.created_at_type) {
                    data = data.filter(o =>
                        this.applyFilter(o.created_at, this.filters.created_at_type, {
                            value: this.filters.created_at_value,
                            from: this.filters.created_at_from,
                            to: this.filters.created_at_to,
                            dataType: 'date'
                        })
                    );
                }

                // --- Lọc theo tên sản phẩm trong items ---
                if (this.filters.product_name) {
                    const keyword = this.filters.product_name.toLowerCase();
                    data = data.filter(o =>
                        o.items?.some(i => (i.product_name || '').toLowerCase().includes(keyword))
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
                } else if (['discount_amount', 'total_amount', 'qty', 'unit_price', 'subtotal', 'promotion_discount'].includes(key)) {
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
            getTotalOrders() {
                return this.filtered().length;
            },

            getTotalRevenue() {
                return this.filtered().reduce((sum, order) => {
                    return sum + (parseFloat(order.total_amount) || 0);
                }, 0);
            },

            countByStatus(status) {
                if (status === 'Hoàn thành') {
                    // Đếm các đơn hàng có trạng thái "Hoàn tất"
                    return this.filtered().filter(o => o.status === 'Đã giao').length;
                } else if (status === 'Chờ xử lý') {
                    // Đếm tất cả các đơn hàng có trạng thái chờ xử lý
                    return this.filtered().filter(o => o.status === 'Chờ xử lý').length;
                }
                return this.filtered().filter(o => o.status === status).length;
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

                // Cập nhật model
                this.form[field] = val;

                // Format số
                const formatted = val.toLocaleString('en-US');
                this.form[field + 'Formatted'] = formatted;

                // Cập nhật lại hiển thị trong input
                e.target.value = formatted;

                // Force Alpine cập nhật model (vì input đang x-model đến discount_amountFormatted)
                this.$nextTick(() => {
                    this.form[field + 'Formatted'] = formatted;
                });

                this.calculateTotal();
            },


            // Cập nhật điểm tích lũy khách hàng
            async updateCustomerLoyaltyPoints() {
                if (!this.form.customer_id) {
                    this.customerLoyaltyPoints = 0;
                    this.form.loyalty_points_used = 0;
                    return;
                }

                try {
                    const customer = this.customers.find(c => c.id == this.form.customer_id);
                    if (customer) {
                        this.customerLoyaltyPoints = Number(customer.loyalty_points) || 0;
                    }
                } catch (e) {
                    console.error('Error loading customer loyalty points:', e);
                    this.customerLoyaltyPoints = 0;
                }
            },

            calculateTotal(shouldCheckPromotions = false) {
                // Tính tổng tiền từ danh sách sản phẩm
                const subtotal = this.orderItems.reduce((sum, item) => {
                    return sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
                }, 0);

                this.form.subtotal = subtotal;
                this.form.subtotalFormatted = subtotal.toLocaleString('en-US');

                // Tính giảm giá: Khuyến mãi tự động + Giảm giá thủ công + Điểm tích lũy
                const manualDiscount = Number(this.form.discount_amount) || 0;
                const loyaltyDiscount = Number(this.form.loyalty_points_used) || 0; // 1 điểm = 1đ
                const totalDiscount = this.promotionDiscount + manualDiscount + loyaltyDiscount;

                const total = Math.max(0, subtotal - totalDiscount);

                this.form.total_amount = total;
                this.form.total_amountFormatted = total.toLocaleString('en-US');

                // Kiểm tra khuyến mãi sau khi tính toán (chỉ khi cần)
                if (shouldCheckPromotions) {
                    this.checkPromotions();
                }
            },

            // Kiểm tra khuyến mãi
            async checkPromotions() {
                if (this.orderItems.length === 0 || this.checkingPromotions) {
                    this.appliedPromotions = [];
                    this.promotionDiscount = 0;
                    this.calculateTotal();
                    return;
                }

                this.checkingPromotions = true;
                try {
                    const items = this.orderItems
                        .filter(item => !item.is_gift) // Không tính quà tặng
                        .map(item => ({
                            id: item.product_id,
                            price: Number(item.unit_price) || 0,
                            quantity: Number(item.quantity) || 0
                        }))
                        .filter(item => item.id && item.quantity > 0);

                    if (items.length === 0) {
                        this.appliedPromotions = [];
                        this.promotionDiscount = 0;
                        this.calculateTotal();
                        return;
                    }

                    console.log('=== Checking promotions (NEW API) ===');
                    console.log('Items sent:', items);

                    const res = await fetch('/admin/api/orders/calculate-with-promotions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            items
                        })
                    });

                    if (!res.ok) {
                        console.warn('Promotion check failed with status:', res.status);
                        this.appliedPromotions = [];
                        this.promotionDiscount = 0;
                        this.calculateTotal();
                        return;
                    }

                    const result = await res.json();
                    console.log('Promotion response:', result);

                    if (result.success && result.data) {
                        const data = result.data;

                        // Xóa toàn bộ quà tặng cũ trước
                        this.orderItems = this.orderItems.filter(item => !item.is_gift);

                        // Tạo mảng khuyến mãi để hiển thị
                        this.appliedPromotions = [];
                        this.promotionDiscount = data.total_discount || 0;

                        // Xử lý DISCOUNT promotions
                        data.item_details.forEach((detail) => {
                            if (detail.applied_promotion) {
                                const promo = detail.applied_promotion;

                                this.appliedPromotions.push({
                                    name: promo.promo_name,
                                    type: promo.promo_type,
                                    description: `${this.getPromoTypeLabel(promo.promo_type)}: -${detail.discount_amount.toLocaleString('en-US')}đ`,
                                    discount_amount: detail.discount_amount || 0
                                });
                            }
                        });

                        // Xử lý GIFT promotions
                        if (data.gift_items && data.gift_items.length > 0) {
                            data.gift_items.forEach(gift => {
                                // Thêm promotion vào danh sách
                                this.appliedPromotions.push({
                                    name: gift.promo_name,
                                    type: 'gift',
                                    description: '🎁 Tặng quà',
                                    discount_amount: 0
                                });

                                // Thêm quà tặng vào orderItems
                                const giftProduct = this.products.find(p => p.id == gift.product_id);
                                if (giftProduct) {
                                    this.orderItems.push({
                                        product_id: gift.product_id,
                                        product_name: giftProduct.name,
                                        quantity: gift.quantity,
                                        unit_price: 0,
                                        is_gift: true
                                    });
                                } else {
                                    console.warn('Gift product not found:', gift.product_id);
                                }
                            });
                        }

                        // Cập nhật tổng tiền
                        this.form.subtotal = data.subtotal;
                        this.form.subtotalFormatted = data.subtotal.toLocaleString('en-US');
                        this.calculateTotal();

                        console.log('✅ Promotions applied:', this.appliedPromotions);
                        console.log('✅ Gift items:', data.gift_items);
                    } else {
                        this.appliedPromotions = [];
                        this.promotionDiscount = 0;
                        this.calculateTotal();
                    }
                } catch (e) {
                    console.error('Error checking promotions:', e);
                    this.appliedPromotions = [];
                    this.promotionDiscount = 0;
                    this.calculateTotal();
                } finally {
                    this.checkingPromotions = false;
                }

            },

            async applyCoupon() {
                if (!this.form.coupon_code || !this.form.coupon_code.trim()) {
                    this.showToast('Vui lòng nhập mã giảm giá', 'error');
                    return;
                }

                if (this.form.subtotal <= 0) {
                    this.showToast('Vui lòng chọn sản phẩm trước khi áp dụng mã giảm giá', 'error');
                    return;
                }

                try {
                    const res = await fetch(`/admin/api/coupons/validate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            code: this.form.coupon_code.toUpperCase(),
                            order_amount: this.form.subtotal,
                            user_id: this.form.customer_id || null
                        })
                    });

                    const data = await res.json();

                    if (res.ok && data.valid) {
                        // Làm tròn trước khi format
                        const discount = Math.round(Number(data.discount_amount)) || 0;

                        this.form.discount_amount = discount;
                        this.form.discount_amountFormatted = discount.toLocaleString('en-US');

                        // ép Alpine cập nhật lại input
                        this.$nextTick(() => {
                            this.form.discount_amountFormatted = discount.toLocaleString('en-US');
                        });

                        this.calculateTotal();
                        this.showToast(
                            `Áp dụng mã giảm giá thành công! Giảm ${this.formatCurrency(discount)}`,
                            'success'
                        );
                    } else {
                        this.showToast(data.message || 'Mã giảm giá không hợp lệ', 'error');
                        this.form.coupon_code = '';
                    }
                } catch (e) {
                    this.showToast('Không thể kiểm tra mã giảm giá', 'error');
                }
            },

            addItem() {
                this.orderItems.push({
                    product_id: '',
                    product_name: '',
                    quantity: 1,
                    unit_price: 0,
                    is_gift: false,
                    bundle_applied: false
                });
            },

            removeItem(idx) {
                // Kiểm tra xem có phải quà tặng không
                const item = this.orderItems[idx];
                if (item.is_gift) {
                    this.showToast('Không thể xóa quà tặng. Hãy xóa sản phẩm kích hoạt khuyến mãi.', 'error');
                    return;
                }

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

                this.calculateTotal(false); // Không check promotion tự động
            },

            getStatusText(status) {
                const map = {
                    'Chờ xử lý': 'Chờ xử lý',
                    'Đang xử lý': 'Đang xử lý',
                    'Đang giao': 'Đang giao',
                    'Đã giao': 'Đã giao',
                    'Đã hủy': 'Đã hủy',
                };
                return map[status] || status;
            },

            getPaymentMethodText(payment_method) {
                const map = {
                    'Tiền mặt': 'Tiền mặt',
                    'Chuyển khoản': 'Chuyển khoản',
                };
                return map[payment_method] || payment_method;
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

            getPromoTypeLabel(type) {
                const map = {
                    'discount': 'Giảm giá',
                    'gift': 'Tặng quà',
                    'combo': 'Combo',
                    'bundle': 'Mua kèm'
                };
                return map[type] || 'Khuyến mãi';
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
                    coupon_code: '',
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
                        stock: p.stock_qty || 0 // Map từ stock_qty sang stock
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

                this.openAdd = true;
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

            async fetchCustomers() {
                try {
                    const res = await fetch(api.customers);
                    if (res.ok) {
                        const data = await res.json();
                        this.customers = data.items || data.customers || [];
                        console.log('Customers loaded:', this.customers.length);
                    }
                } catch (e) {
                    console.error('Error loading customers:', e);
                    this.customers = [];
                }
            },

            async fetchProducts() {
                try {
                    const res = await fetch(api.products);
                    if (res.ok) {
                        const data = await res.json();
                        this.products = data.items || data.products || [];
                        console.log('Products loaded:', this.products.length);
                    }
                } catch (e) {
                    console.error('Error loading products:', e);
                    this.products = [];
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

                // Copy order data
                this.viewOrder = {
                    ...o,
                    items: []
                };

                // LUÔN load lại items từ API để đảm bảo có đầy đủ dữ liệu qty
                try {
                    const res = await fetch(`/admin/api/orders/${o.id}/items`);
                    if (res.ok) {
                        const data = await res.json();
                        console.log('Items loaded from API:', data.items);
                        this.viewOrder.items = data.items || [];
                    } else {
                        console.error('Failed to load items:', res.status);
                    }
                } catch (e) {
                    console.error('Error loading order items:', e);
                    this.viewOrder.items = [];
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
                    quantity: item.quantity,
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
                    printWindow.onload = function() {
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
                        quantity: item.quantity,
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
                        promotion_discount: this.promotionDiscount,
                        items: this.orderItems.map(item => ({
                            product_id: item.product_id,
                            qty: item.quantity,
                            unit_price: item.unit_price
                        }))
                    };

                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
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
                const order = this.items.find(o => o.id === id);
                const code = order ? order.code : 'đơn hàng này';

                this.showConfirm(
                    'Xác nhận xóa',
                    `Bạn có chắc chắn muốn xóa đơn hàng "${code}"?`,
                    async () => {
                        try {
                            const res = await fetch(api.remove(id), {
                                method: 'DELETE'
                            });
                            if (res.ok) {
                                this.items = this.items.filter(r => r.id !== id);
                                this.showToast('Xóa đơn hàng thành công!', 'success');
                            } else {
                                this.showToast('Không thể xóa đơn hàng');
                            }
                        } catch (e) {
                            this.showToast('Không thể xóa đơn hàng');
                        }
                    }
                );
            },

            showConfirm(title, message, onConfirm, onCancel = () => {}) {
                this.confirmDialog = {
                    show: true,
                    title,
                    message,
                    onConfirm,
                    onCancel
                };
            },

            // ===== EXPORT EXCEL =====
            exportExcel() {
                // Lấy dữ liệu đã lọc
                const data = this.filtered();

                if (data.length === 0) {
                    this.showToast('Không có dữ liệu để xuất', 'error');
                    return;
                }

                // Tạo tên file với ngày giờ hiện tại
                const now = new Date();
                const dateStr = now.toLocaleDateString('vi-VN').replace(/\//g, '-');
                const timeStr = now.toLocaleTimeString('vi-VN', {
                    hour12: false
                }).replace(/:/g, '-');
                const filename = `Don_hang_${dateStr}_${timeStr}.xlsx`;

                // Tìm khoảng thời gian của dữ liệu
                const dates = data.map(d => new Date(d.created_at)).filter(d => !isNaN(d));
                const fromDate = dates.length > 0 ? new Date(Math.min(...dates)) : now;
                const toDate = dates.length > 0 ? new Date(Math.max(...dates)) : now;

                // Chuẩn bị dữ liệu để gửi lên server
                const exportData = {
                    orders: data.map(order => ({
                        code: order.code || '',
                        customer_name: order.customer_name || 'Khách vãng lai',
                        items: (order.items || []).map(item => ({
                            product_name: item.product_name || '',
                            qty: item.qty || 0,
                            unit_price: item.unit_price || 0
                        })),
                        status: order.status || '',
                        subtotal: order.subtotal || 0,
                        promotion_discount: order.promotion_discount || 0,
                        discount_amount: order.discount_amount || 0,
                        total_amount: order.total_amount || 0,
                        payment_method: order.payment_method || '',
                        shipping_address: order.shipping_address || '',
                        note: order.note || '',
                        created_at: order.created_at || '',
                        created_by_name: order.created_by_name || ''
                    })),
                    from_date: fromDate.toLocaleDateString('vi-VN'),
                    to_date: toDate.toLocaleDateString('vi-VN'),
                    export_date: now.toLocaleDateString('vi-VN'),
                    filename: filename
                };

                // Gửi request đến server để tạo file Excel
                fetch('/admin/api/orders/export', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(exportData)
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Export failed');
                        return response.blob();
                    })
                    .then(blob => {
                        // Tạo link download
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        this.showToast('Xuất file Excel thành công!', 'success');
                    })
                    .catch(e => {
                        console.error('Export error:', e);
                        this.showToast('Không thể xuất file Excel', 'error');
                    });
            },

            // ===== GHN ORDER PROCESSING =====
            async processOrder(orderId) {
                this.showConfirm(
                    'Xử lý đơn hàng',
                    'Bắt đầu xử lý đơn hàng này?\n\nTrạng thái sẽ chuyển từ "Chờ xử lý" sang "Đang xử lý".',
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch(`/admin/api/orders/${orderId}/process`, {
                                method: 'POST'
                            });
                            const data = await res.json();

                            if (data.success) {
                                this.showToast(data.message || 'Đã chuyển trạng thái sang "Đang xử lý"', 'success');
                                await this.fetchAll();
                            } else {
                                this.showToast(data.error || 'Lỗi khi xử lý đơn hàng', 'error');
                            }
                        } catch (e) {
                            console.error('Process order error:', e);
                            this.showToast('Lỗi kết nối server', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async manualComplete(orderId) {
                this.showConfirm(
                    'Xác nhận hoàn tất',
                    'Bạn có chắc chắn muốn chuyển đơn hàng này sang HOÀN TẤT? \n(Hệ thống sẽ tự động tạo PHIẾU THU).',
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch(`/admin/api/orders/${orderId}/manual-complete`, {
                                method: 'POST'
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.showToast(data.message, 'success');
                                await this.fetchAll();
                            } else {
                                this.showToast(data.error, 'error');
                            }
                        } catch (e) {
                            this.showToast('Lỗi máy chủ', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async manualCancel(orderId) {
                this.showConfirm(
                    'Xác nhận hủy đơn',
                    'Bạn có chắc chắn muốn HỦY đơn hàng này? \n(Hệ thống sẽ HOÀN TRẢ TỒN KHO sản phẩm).',
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch(`/admin/api/orders/${orderId}/manual-cancel`, {
                                method: 'POST'
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.showToast(data.message, 'success');
                                await this.fetchAll();
                            } else {
                                this.showToast(data.error, 'error');
                            }
                        } catch (e) {
                            this.showToast('Lỗi máy chủ', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async shipWithGHN(orderId) {
                this.showConfirm(
                    'Giao hàng với GHN',
                    'Tạo vận đơn GHN và giao hàng?\n\nVui lòng đảm bảo:\n• Đã đóng gói hàng xong\n• Địa chỉ giao hàng chính xác\n\nSau khi tạo vận đơn, bạn có thể in nhãn để dán lên kiện hàng.',
                    async () => {
                        this.loading = true;
                        try {
                            const res = await fetch(`/admin/api/orders/${orderId}/ship-with-ghn`, {
                                method: 'POST'
                            });
                            const data = await res.json();

                            if (data.success) {
                                const msg = data.message + '\nMã vận đơn: ' + data.ghn_order_code;
                                this.showToast(msg, 'success');

                                // Mở label để in (nếu có)
                                if (data.label_url) {
                                    setTimeout(() => {
                                        window.open(data.label_url, '_blank');
                                    }, 500);
                                }

                                await this.fetchAll();
                            } else {
                                // Nếu thất bại, hỏi user có muốn chuyển manual không
                                const errorMsg = data.error || 'Lỗi khi tạo vận đơn GHN';
                                this.showToast(errorMsg, 'error');

                                setTimeout(() => {
                                    this.showConfirm(
                                        'Chuyển thủ công?',
                                        `GHN báo lỗi: "${errorMsg}"\n\nBạn có muốn chuyển trạng thái sang "Đang giao" THỦ CÔNG để tiếp tục quy trình không?`,
                                        async () => {
                                            this.loading = true;
                                            try {
                                                const res2 = await fetch(`/admin/api/orders/${orderId}/manual-ship`, {
                                                    method: 'POST'
                                                });
                                                const data2 = await res2.json();
                                                if (data2.success) {
                                                    this.showToast(data2.message, 'success');
                                                    await this.fetchAll();
                                                } else {
                                                    this.showToast(data2.error, 'error');
                                                }
                                            } catch (e2) {
                                                console.error(e2);
                                                this.showToast('Lỗi khi chuyển thủ công', 'error');
                                            } finally {
                                                this.loading = false;
                                            }
                                        }
                                    );
                                }, 1000); // Delay 1s để user đọc lỗi
                            }
                        } catch (e) {
                            console.error('Ship with GHN error:', e);
                            this.showToast('Lỗi kết nối server', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                );
            },

            async viewTracking(orderId) {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/api/orders/${orderId}/tracking`);
                    const data = await res.json();

                    if (data && data.order_code) {
                        const info = `
                            THÔNG TIN VẬN ĐƠN GHN

                            Mã vận đơn: ${data.order_code || 'N/A'}
                            Trạng thái: ${data.status || 'N/A'}
                            Thời gian dự kiến giao: ${data.expected_delivery_time || 'Chưa có'}

                            Log chi tiết:
                            ${data.log ? data.log.map(l => `- ${l.status}: ${l.updated_date}`).join('\n') : 'Không có log'}
                        `.trim();
                        alert(info);
                    } else {
                        this.showToast('Không có thông tin tracking', 'error');
                    }
                } catch (e) {
                    console.error('Get tracking error:', e);
                    this.showToast('Lỗi khi lấy thông tin tracking', 'error');
                } finally {
                    this.loading = false;
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