<!-- Flatpickr CSS -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">

<?php
// views/admin/promotions/promotion.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Khuyến mãi</span>
</nav>

<div x-data="promotionPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý khuyến mãi</h1>
        <div class="flex items-center gap-2">
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
                @click="exportExcel()">
                <i class="fa-solid fa-file-excel"></i>
                Xuất Excel
            </button>
            <button
                class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
                @click="openCreate()">+ Thêm khuyến mãi</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:200%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 whitespace-nowrap text-center">Thao tác</th>
                        <?= textFilterPopover('name', 'Tên CT KM') ?>
                        <?= textFilterPopover('description', 'Mô tả') ?>
                        <th class="py-2 px-4 whitespace-nowrap text-center">Loại KM</th>
                        <?= selectFilterPopover('discount_type', 'Loại giảm giá', [
                            '' => '-- Tất cả --',
                            'percentage' => 'Phần trăm',
                            'fixed' => 'Số tiền cố định'
                        ]) ?>
                        <?= numberFilterPopover('discount_value', 'Giá trị giảm') ?>
                        <?= numberFilterPopover('priority', 'Độ ưu tiên') ?>
                        <?= dateFilterPopover('starts_at', 'Ngày bắt đầu') ?>
                        <?= dateFilterPopover('ends_at', 'Ngày kết thúc') ?>
                        <?= selectFilterPopover('status', 'Trạng thái', [
                            '' => '-- Tất cả --',
                            '1' => 'Đang hoạt động',
                            '0' => 'Tạm dừng'
                        ]) ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="p in paginated()" :key="p.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 space-x-2 text-center">
                                <button @click="openEditModal(p)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button @click="openDetailModal(p)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xem chi tiết">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button @click="remove(p.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="p.name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="(p.description || '—')"
                                    :class="(p.description || '—') === '—' ? '' : 'text-left'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span class="px-2 py-0.5 rounded text-xs" :class="{
                                        'bg-blue-100 text-blue-700': p.promo_type === 'discount',
                                        'bg-purple-100 text-purple-700': p.promo_type === 'bundle',
                                        'bg-green-100 text-green-700': p.promo_type === 'gift',
                                        'bg-orange-100 text-orange-700': p.promo_type === 'combo'
                                    }" x-text="{
                                        'discount': 'Giảm giá',
                                        'bundle': 'Bundle',
                                        'gift': 'Tặng quà',
                                        'combo': 'Combo'
                                    }[p.promo_type] || p.promo_type"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <template x-if="p.promo_type === 'discount'">
                                    <span class="px-2 py-0.5 rounded text-xs"
                                        :class="p.discount_type === 'percentage' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                        x-text="p.discount_type === 'percentage' ? 'Phần trăm' : 'Số tiền'"></span>
                                </template>
                                <template x-if="p.promo_type !== 'discount'">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right">
                                <template x-if="p.promo_type === 'discount'">
                                    <span
                                        x-text="p.discount_type === 'percentage' ? (p.discount_value + '%') : formatCurrency(p.discount_value)"></span>
                                </template>
                                <template x-if="p.promo_type !== 'discount'">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center" x-text="p.priority"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="p.starts_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="p.ends_at || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span class="px-2 py-0.5 rounded text-xs"
                                    :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    x-text="p.is_active ? 'Hoạt động' : 'Tạm dừng'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="p.created_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="p.created_by_name || '—'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="p.updated_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="p.updated_by_name || '—'"></span>
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
            <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="openAdd=false" style="max-height: 90vh; overflow-y: auto;">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Thêm khuyến mãi</h3>
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

        <!-- MODAL: Detail -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openDetail" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="openDetail=false" style="max-height: 90vh; overflow-y: auto;">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết khuyến mãi</h3>
                    <button class="text-slate-500 absolute right-5" @click="openDetail=false">✕</button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Thông tin cơ bản -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên chương trình</label>
                            <p class="text-gray-900" x-text="form.name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại khuyến mãi</label>
                            <span class="px-2 py-0.5 rounded text-xs" :class="{
                                    'bg-blue-100 text-blue-700': form.promo_type === 'discount',
                                    'bg-purple-100 text-purple-700': form.promo_type === 'bundle',
                                    'bg-green-100 text-green-700': form.promo_type === 'gift',
                                    'bg-orange-100 text-orange-700': form.promo_type === 'combo'
                                }" x-text="{
                                    'discount': 'Giảm giá',
                                    'bundle': 'Bundle',
                                    'gift': 'Tặng quà',
                                    'combo': 'Combo'
                                }[form.promo_type]"></span>
                        </div>
                    </div>

                    <div x-show="form.description">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <p class="text-gray-900" x-text="form.description"></p>
                    </div>

                    <!-- Chi tiết theo loại -->
                    <div x-show="form.promo_type === 'discount'" class="border-t pt-4">
                        <h4 class="font-semibold text-lg mb-3">Chi tiết giảm giá</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loại giảm giá</label>
                                <span class="px-2 py-0.5 rounded text-xs"
                                    :class="form.discount_type === 'percentage' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                    x-text="form.discount_type === 'percentage' ? 'Phần trăm' : 'Số tiền'"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị giảm</label>
                                <p class="text-gray-900 font-semibold"
                                    x-text="form.discount_type === 'percentage' ? (form.discount_value + '%') : formatCurrency(form.discount_value)">
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Áp dụng cho</label>
                            <p class="text-gray-900" x-text="getApplyToText(form.apply_to)"></p>
                        </div>
                        <div x-show="form.category_ids && form.category_ids.length > 0" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="catId in form.category_ids" :key="catId">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-sm"
                                        x-text="categories.find(c => c.id == catId)?.name || catId"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="form.product_ids && form.product_ids.length > 0" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="prodId in form.product_ids" :key="prodId">
                                    <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-sm"
                                        x-text="products.find(p => p.id == prodId)?.name || prodId"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="form.promo_type === 'bundle'" class="border-t pt-4">
                        <h4 class="font-semibold text-lg mb-3">Chi tiết Bundle</h4>
                        <template x-if="form.bundle_rules && form.bundle_rules.length > 0">
                            <div>
                                <template x-for="(rule, idx) in form.bundle_rules" :key="idx">
                                    <div class="p-3 bg-gray-50 rounded mb-2">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-600">Sản phẩm:</span>
                                                <strong class="text-sm"
                                                    x-text="products.find(p => p.id == rule.product_id)?.name || 'Không xác định'"></strong>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm text-gray-700">Mua <strong class="text-blue-600"
                                                        x-text="rule.buy_quantity"></strong> sản phẩm</span>
                                                <span class="text-sm text-gray-700">→ Tặng <strong
                                                        class="text-green-600" x-text="rule.free_quantity"></strong> sản
                                                    phẩm</span>
                                            </div>
                                            <div class="text-sm text-gray-700">
                                                Giá bundle: <strong class="text-orange-600"
                                                    x-text="formatCurrency(rule.price)"></strong>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!form.bundle_rules || form.bundle_rules.length === 0">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                                Chưa có quy tắc Bundle nào được thiết lập
                            </div>
                        </template>
                    </div>

                    <div x-show="form.promo_type === 'gift'" class="border-t pt-4">
                        <h4 class="font-semibold text-lg mb-3">Chi tiết quà tặng</h4>
                        <template x-if="form.gift_rules && form.gift_rules.length > 0">
                            <div>
                                <template x-for="(rule, idx) in form.gift_rules" :key="idx">
                                    <div class="p-3 bg-gray-50 rounded mb-2">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-600">Điều kiện:</span>
                                                <span class="text-sm">Mua <strong class="text-blue-600"
                                                        x-text="rule.trigger_qty"></strong>
                                                    <strong
                                                        x-text="products.find(p => p.id == rule.trigger_product_id)?.name || 'Không xác định'"></strong></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-600">Quà tặng:</span>
                                                <span class="text-sm">Tặng <strong class="text-green-600"
                                                        x-text="rule.gift_qty"></strong>
                                                    <strong
                                                        x-text="products.find(p => p.id == rule.gift_product_id)?.name || 'Không xác định'"></strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!form.gift_rules || form.gift_rules.length === 0">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                                ⚠️ Chưa có quy tắc quà tặng nào được thiết lập
                            </div>
                        </template>
                    </div>

                    <div x-show="form.promo_type === 'combo'" class="border-t pt-4">
                        <h4 class="font-semibold text-lg mb-3">Chi tiết Combo</h4>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giá combo</label>
                            <p class="text-gray-900 font-semibold text-lg"
                                x-text="formatCurrency(form.combo_price || 0)"></p>
                        </div>
                        <template x-if="form.combo_items && form.combo_items.length > 0">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sản phẩm trong combo</label>
                                <template x-for="(item, idx) in form.combo_items" :key="idx">
                                    <div class="p-3 bg-gray-50 rounded mb-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">
                                                <strong
                                                    x-text="products.find(p => p.id == item.product_id)?.name || 'Không xác định'"></strong>
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                Số lượng: <strong class="text-blue-600" x-text="item.qty"></strong>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!form.combo_items || form.combo_items.length === 0">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                                ⚠️ Chưa có sản phẩm nào trong combo
                            </div>
                        </template>
                    </div>

                    <!-- Thông tin khác -->
                    <div class="border-t pt-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Độ ưu tiên</label>
                            <p class="text-gray-900" x-text="form.priority"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                            <span class="px-2 py-0.5 rounded text-xs"
                                :class="form.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                x-text="form.is_active ? 'Hoạt động' : 'Tạm dừng'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                            <p class="text-gray-900" x-text="form.starts_at"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                            <p class="text-gray-900" x-text="form.ends_at"></p>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="button" class="px-4 py-2 rounded-md border"
                            @click="openDetail=false">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Edit -->
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
            x-show="openEdit" x-transition.opacity style="display:none">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
                @click.outside="openEdit=false" style="max-height: 90vh; overflow-y: auto;">
                <div class="px-5 py-3 border-b flex justify-center items-center relative">
                    <h3 class="font-semibold text-2xl text-[#002975]">Sửa khuyến mãi</h3>
                    <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
                    <?php require __DIR__ . '/form.php'; ?>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
                        <button
                            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                            :disabled="submitting" x-text="submitting?'Đang lưu...':'Cập nhật'"></button>
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
            categories: '/admin/api/categories',
            products: '/admin/api/products',
            allProducts: '/admin/api/products/all-including-inactive' // Cho quà tặng
        };

        return {
            // State
            loading: true,
            submitting: false,
            openAdd: false,
            openEdit: false,
            openDetail: false,
            items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
            categories: [],
            products: [],
            giftProducts: [], // Tất cả sản phẩm (bao gồm cả không bán) cho quà tặng

            // Pagination
            currentPage: 1,
            perPage: 20,
            perPageOptions: [5, 10, 20, 50, 100],

            // Form
            form: {
                id: null,
                name: '',
                description: '',
                promo_type: 'discount',
                discount_type: 'percentage',
                discount_value: 0,
                apply_to: 'all',
                priority: 0,
                starts_at: '',
                ends_at: '',
                is_active: 1,
                category_ids: [],
                product_ids: [],
                bundle_rules: [],
                gift_rules: [],
                combo_price: 0,
                combo_items: []
            },

            errors: {
                name: '', discount_value: '', starts_at: '', ends_at: ''
            },

            touched: {
                name: false, discount_value: false, starts_at: false, ends_at: false
            },

            // ===== FILTERS =====
            openFilter: {
                name: false, description: false, discount_type: false, discount_value: false,
                apply_to: false, priority: false, starts_at: false, ends_at: false,
                status: false, created_at: false, created_by: false,
                updated_at: false, updated_by: false,
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
                status: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by: '',
            },

            // ------------------------------------------------------------------
            // Hàm lọc tổng quát — hỗ trợ TEXT, NUMBER, DATE
            // ------------------------------------------------------------------
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
                        return hasAccent(query)
                            ? raw.includes(query)
                            : str.includes(queryNoAccent);
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
                        return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
                    }
                    if (type === 'lte') {
                        if (!v) return true;
                        const nextDay = new Date(v);
                        nextDay.setDate(v.getDate() + 1);
                        return d < nextDay; // cộng thêm 1 ngày
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
                let data = this.items;

                // --- Lọc theo text ---
                // Bao gồm các trường hiển thị tên như brand_name, category_name, created_by_name, updated_by_name
                ['name', 'description', 'created_by', 'updated_by'].forEach(key => {
                    if (this.filters[key]) {
                        data = data.filter(o =>
                            this.applyFilter(o[key], 'contains', {
                                value: this.filters[key],
                                dataType: 'text'
                            })
                        );
                    }
                });

                // --- Lọc theo select ---
                ['discount_type'].forEach(key => {
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
                ['discount_value', 'priority'].forEach(key => {
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
                ['starts_at', 'ends_at', 'created_at', 'updated_at'].forEach(key => {
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

            // ------------------------------------------------------------------
            // Mở / đóng / reset filter
            // ------------------------------------------------------------------
            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['starts_at', 'ends_at', 'created_at', 'updated_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['discount_value_type', 'priority'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },

            exportExcel() {
                const data = this.filtered();

                if (data.length === 0) {
                    this.showToast('Không có dữ liệu để xuất', 'error');
                    return;
                }   

                const now = new Date();
                const dateStr = now.toLocaleDateString('vi-VN').replace(/\//g, '-');
                const timeStr = now.toLocaleTimeString('vi-VN', { hour12: false }).replace(/:/g, '-');
                const filename = `Chuong_trinh_khuyen_mai_${dateStr}_${timeStr}.xlsx`;

                const exportData = {
                    items: data.map(item => ({
                        name: item.name || '',
                        description: item.description || '',
                        promo_type: item.promo_type || '',
                        discount_type: item.discount_type || '',
                        discount_value: item.discount_value || 0,
                        priority: item.priority || 0,
                        starts_at: item.starts_at || '',
                        ends_at: item.ends_at || '',
                        is_active: item.is_active || 0,
                        created_at: item.created_at || '',
                        created_by_name: item.created_by_name || '',
                        updated_at: item.updated_at || '',
                        updated_by_name: item.updated_by_name || '',
                    })),
                    export_date: now.toLocaleDateString('vi-VN'),
                    filename: filename
                };

                fetch('/admin/api/promotions/export', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(exportData)
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Export failed');
                        return response.blob();
                    })
                    .then(blob => {
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

            // Lifecycle
            async init() {
                await this.fetchOptions();
                await this.fetchAll();
            },

            // API calls
            async fetchAll() {
                try {
                    this.loading = true;
                    const res = await fetch(api.list);
                    if (!res.ok) throw new Error('Không thể tải danh sách');
                    const data = await res.json();

                    // API trả về { items: [...] } nên cần lấy items
                    this.items = data.items || data || [];

                } catch (err) {
                    this.showToast(err.message, 'error');
                } finally {
                    this.loading = false;
                }
            },

            async fetchOptions() {
                try {
                    const [catRes, prodRes, giftProdRes] = await Promise.all([
                        fetch(api.categories),
                        fetch(api.products),
                        fetch(api.allProducts) // Lấy tất cả sản phẩm cho quà tặng
                    ]);
                    if (catRes.ok) {
                        const catData = await catRes.json();
                        this.categories = catData.items || catData || [];
                    }
                    if (prodRes.ok) {
                        const prodData = await prodRes.json();
                        this.products = prodData.items || prodData || [];
                    }
                    if (giftProdRes.ok) {
                        const giftData = await giftProdRes.json();
                        this.giftProducts = giftData.items || giftData || [];
                    }
                } catch (err) {
                    console.error('Lỗi load options:', err);
                }
            },

            // CRUD operations
            openCreate() {
                this.resetForm();
                this.openAdd = true;
            },

            openDetailModal(item) {
                console.log('=== OPEN DETAIL MODAL ===');
                console.log('Item data:', item);

                this.resetForm();
                this.form.id = item.id;
                this.form.name = item.name || '';
                this.form.description = item.description || '';
                this.form.promo_type = item.promo_type || 'discount';
                this.form.discount_type = item.discount_type || 'percentage';
                this.form.discount_value = item.discount_value || 0;
                this.form.apply_to = item.apply_to || 'all';
                this.form.priority = item.priority || 0;

                // Convert ngày từ YYYY-MM-DD HH:MM:SS sang DD/MM/YYYY HH:MM
                this.form.starts_at = this.convertDateToDisplay(item.starts_at);
                this.form.ends_at = this.convertDateToDisplay(item.ends_at);

                this.form.is_active = item.is_active ? 1 : 0;
                this.form.category_ids = item.category_ids || [];
                this.form.product_ids = item.product_ids || [];

                // Load data theo loại khuyến mãi
                this.form.bundle_rules = item.bundle_rules || [];
                this.form.gift_rules = item.gift_rules || [];
                this.form.combo_price = item.combo_price || 0;
                this.form.combo_items = item.combo_items || [];

                console.log('Form after load:', this.form);
                this.openDetail = true;
            },

            openEditModal(item) {
                // Reset form trước
                this.resetForm();

                // Sau đó gán dữ liệu
                this.form.id = item.id;
                this.form.name = item.name || '';
                this.form.description = item.description || '';
                this.form.promo_type = item.promo_type || 'discount';
                this.form.discount_type = item.discount_type || 'percentage';

                // Format discount_value theo loại
                if (item.discount_type === 'fixed') {
                    this.form.discount_value = new Intl.NumberFormat('en-US').format(item.discount_value || 0);
                } else {
                    this.form.discount_value = item.discount_value || 0;
                }

                this.form.apply_to = item.apply_to || 'all';
                this.form.priority = item.priority || 0;

                // Convert ngày từ YYYY-MM-DD HH:MM:SS sang DD/MM/YYYY
                this.form.starts_at = this.convertDateToDisplay(item.starts_at);
                this.form.ends_at = this.convertDateToDisplay(item.ends_at);

                this.form.is_active = item.is_active ? 1 : 0;
                this.form.category_ids = item.category_ids || [];
                this.form.product_ids = item.product_ids || [];

                // Format bundle_rules prices
                this.form.bundle_rules = (item.bundle_rules || []).map(rule => ({
                    ...rule,
                    price: new Intl.NumberFormat('en-US').format(rule.price || 0)
                }));

                this.form.gift_rules = item.gift_rules || [];

                // Format combo_price
                this.form.combo_price = new Intl.NumberFormat('en-US').format(item.combo_price || 0);

                this.form.combo_items = item.combo_items || [];

                console.log('=== OPEN EDIT MODAL ===');
                console.log('Form after load:', this.form);

                this.openEdit = true;
            },

            async submitCreate() {
                if (!this.validateForm()) return;

                try {
                    this.submitting = true;

                    // Convert discount_value về số (xóa dấu phẩy) - chỉ khi promo_type là discount
                    const discountValue = this.form.promo_type === 'discount'
                        ? (typeof this.form.discount_value === 'string'
                            ? parseFloat(this.form.discount_value.replace(/,/g, ''))
                            : this.form.discount_value)
                        : 0;

                    // Helper function để convert giá trị có dấu phẩy thành số
                    const parseFormattedNumber = (val) => {
                        if (typeof val === 'string') {
                            return parseFloat(val.replace(/,/g, '')) || 0;
                        }
                        return parseFloat(val) || 0;
                    };

                    // Convert bundle_rules prices
                    const bundleRules = (this.form.bundle_rules || []).map(rule => ({
                        ...rule,
                        price: parseFormattedNumber(rule.price)
                    }));

                    // Convert combo_price
                    const comboPrice = parseFormattedNumber(this.form.combo_price);

                    // Convert ngày từ DD/MM/YYYY sang YYYY-MM-DD HH:MM:SS
                    const formData = {
                        ...this.form,
                        discount_value: discountValue,
                        starts_at: this.convertDateToSQL(this.form.starts_at),
                        ends_at: this.convertDateToSQL(this.form.ends_at),
                        bundle_rules: bundleRules,
                        gift_rules: this.form.gift_rules || [],
                        combo_price: comboPrice,
                        combo_items: this.form.combo_items || []
                    };

                    console.log('=== SUBMIT CREATE DEBUG ===');
                    console.log('Form data:', formData);

                    const res = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });

                    console.log('Response status:', res.status);
                    const responseData = await res.json();
                    console.log('Response data:', responseData);

                    if (!res.ok) {
                        const errorMsg = responseData.error || 'Không thể tạo mới';
                        throw new Error(errorMsg);
                    }

                    this.showToast('Thêm khuyến mãi thành công!', 'success');
                    this.openAdd = false;
                    await this.fetchAll();
                } catch (err) {
                    console.error('Create error:', err);
                    this.showToast(err.message, 'error');
                } finally {
                    this.submitting = false;
                }
            },

            async submitUpdate() {
                if (!this.validateForm()) return;

                try {
                    this.submitting = true;

                    // Convert discount_value về số (xóa dấu phẩy) - chỉ khi promo_type là discount
                    const discountValue = this.form.promo_type === 'discount'
                        ? (typeof this.form.discount_value === 'string'
                            ? parseFloat(this.form.discount_value.replace(/,/g, ''))
                            : this.form.discount_value)
                        : 0;

                    // Helper function để convert giá trị có dấu phẩy thành số
                    const parseFormattedNumber = (val) => {
                        if (typeof val === 'string') {
                            return parseFloat(val.replace(/,/g, '')) || 0;
                        }
                        return parseFloat(val) || 0;
                    };

                    // Convert bundle_rules prices
                    const bundleRules = (this.form.bundle_rules || []).map(rule => ({
                        ...rule,
                        price: parseFormattedNumber(rule.price)
                    }));

                    // Convert combo_price
                    const comboPrice = parseFormattedNumber(this.form.combo_price);

                    // Convert ngày từ DD/MM/YYYY HH:MM sang YYYY-MM-DD HH:MM:SS
                    const formData = {
                        ...this.form,
                        discount_value: discountValue,
                        starts_at: this.convertDateToSQL(this.form.starts_at),
                        ends_at: this.convertDateToSQL(this.form.ends_at),
                        bundle_rules: bundleRules,
                        gift_rules: this.form.gift_rules || [],
                        combo_price: comboPrice,
                        combo_items: this.form.combo_items || []
                    };

                    console.log('=== SUBMIT UPDATE DEBUG ===');
                    console.log('Form data:', formData);

                    const res = await fetch(api.update(this.form.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });

                    console.log('Response status:', res.status);
                    const responseData = await res.json();
                    console.log('Response data:', responseData);

                    if (!res.ok) {
                        const errorMsg = responseData.error || 'Không thể cập nhật';
                        throw new Error(errorMsg);
                    }

                    this.showToast('Cập nhật thành công!', 'success');
                    this.openEdit = false;
                    await this.fetchAll();
                } catch (err) {
                    console.error('Update error:', err);
                    this.showToast(err.message, 'error');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Xác nhận xóa khuyến mãi này?')) return;

                try {
                    const res = await fetch(api.remove(id), { method: 'DELETE' });
                    if (!res.ok) throw new Error('Không thể xóa');

                    this.showToast('Xóa thành công!', 'success');
                    await this.fetchAll();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            },

            // Validation
            validateField(field) {
                this.errors[field] = '';
                const val = this.form[field];

                if (field === 'name' && !val) {
                    this.errors[field] = 'Vui lòng nhập tên chương trình';
                }

                if (field === 'discount_value') {
                    // Chuyển string có dấu phấy thành số
                    const numVal = typeof val === 'string'
                        ? parseFloat(val.replace(/,/g, ''))
                        : parseFloat(val);

                    if (!numVal || numVal <= 0) {
                        this.errors[field] = 'Giá trị giảm phải lớn hơn 0';
                    } else if (this.form.discount_type === 'percentage' && numVal > 100) {
                        this.errors[field] = 'Phần trăm giảm không được vượt quá 100%';
                    } else if (this.form.discount_type === 'fixed' && numVal > 9999999999) {
                        this.errors[field] = 'Số tiền giảm không được vượt quá 9,999,999,999đ';
                    }
                }

                if (field === 'starts_at' && !val) {
                    this.errors[field] = 'Vui lòng chọn ngày bắt đầu';
                }
                if (field === 'ends_at' && !val) {
                    this.errors[field] = 'Vui lòng chọn ngày kết thúc';
                }
                if (field === 'ends_at' && val && this.form.starts_at && val <= this.form.starts_at) {
                    this.errors[field] = 'Ngày kết thúc phải sau ngày bắt đầu';
                }
            },

            validateForm() {
                this.touched = { name: true, discount_value: true, starts_at: true, ends_at: true };
                this.validateField('name');

                // Chỉ validate discount_value khi promo_type là 'discount'
                if (this.form.promo_type === 'discount') {
                    this.validateField('discount_value');
                }

                this.validateField('starts_at');
                this.validateField('ends_at');

                // Validate theo loại khuyến mãi
                if (this.form.promo_type === 'bundle') {
                    if (!this.form.bundle_rules || this.form.bundle_rules.length === 0) {
                        this.showToast('Vui lòng thêm ít nhất 1 quy tắc Bundle', 'error');
                        return false;
                    }
                    // Kiểm tra bundle rules đã đầy đủ chưa
                    for (let rule of this.form.bundle_rules) {
                        if (!rule.product_id || !rule.qty || !rule.price) {
                            this.showToast('Vui lòng điền đầy đủ thông tin cho tất cả quy tắc Bundle', 'error');
                            return false;
                        }
                    }
                }

                if (this.form.promo_type === 'gift') {
                    if (!this.form.gift_rules || this.form.gift_rules.length === 0) {
                        this.showToast('Vui lòng thêm ít nhất 1 quy tắc Tặng quà', 'error');
                        return false;
                    }
                    // Kiểm tra gift rules đã đầy đủ chưa
                    for (let rule of this.form.gift_rules) {
                        if (!rule.trigger_product_id || !rule.trigger_qty || !rule.gift_product_id || !rule.gift_qty) {
                            this.showToast('Vui lòng điền đầy đủ thông tin cho tất cả quy tắc Tặng quà', 'error');
                            return false;
                        }
                    }
                }

                if (this.form.promo_type === 'combo') {
                    if (!this.form.combo_price || this.form.combo_price === '0' || this.form.combo_price === 0) {
                        this.showToast('Vui lòng nhập giá combo', 'error');
                        return false;
                    }
                    if (!this.form.combo_items || this.form.combo_items.length === 0) {
                        this.showToast('Vui lòng thêm ít nhất 2 sản phẩm vào combo', 'error');
                        return false;
                    }
                    if (this.form.combo_items.length < 2) {
                        this.showToast('Combo phải có ít nhất 2 sản phẩm', 'error');
                        return false;
                    }
                    // Kiểm tra combo items đã đầy đủ chưa
                    for (let item of this.form.combo_items) {
                        if (!item.product_id || !item.qty) {
                            this.showToast('Vui lòng điền đầy đủ thông tin cho tất cả sản phẩm trong combo', 'error');
                            return false;
                        }
                    }
                }

                return !Object.values(this.errors).some(e => e);
            },

            resetForm() {
                this.form = {
                    id: null,
                    name: '',
                    description: '',
                    promo_type: 'discount',
                    discount_type: 'percentage',
                    discount_value: 0,
                    apply_to: 'all',
                    priority: 0,
                    starts_at: '',
                    ends_at: '',
                    is_active: 1,
                    category_ids: [],
                    product_ids: [],
                    bundle_rules: [],
                    gift_rules: [],
                    combo_price: 0,
                    combo_items: []
                };
                this.errors = { name: '', discount_value: '', starts_at: '', ends_at: '' };
                this.touched = { name: false, discount_value: false, starts_at: false, ends_at: false };
            },

            // Pagination
            paginated() {
                const arr = this.filtered();
                if (!Array.isArray(arr)) return [];

                // Lấy dữ liệu theo trang
                const start = (this.currentPage - 1) * this.perPage;
                const end = start + this.perPage;
                return arr.slice(start, end);
            },


            totalPages() {
                return Math.ceil(this.filtered().length / this.perPage) || 1;
            },

            goToPage(p) {
                if (p >= 1 && p <= this.totalPages()) this.currentPage = p;
            },

            // Utilities
            formatCurrency(n) {
                try {
                    // Format với dấu phẩy thay vì dấu chấm
                    const formatted = new Intl.NumberFormat('en-US').format(n || 0);
                    return formatted + 'đ';
                } catch {
                    return (n || 0) + 'đ';
                }
            },

            // Format discount value khi nhập
            formatDiscountValue() {
                const val = this.form.discount_value;

                // Nếu là percentage thì không format (để nhập số thập phân)
                if (this.form.discount_type === 'percentage') {
                    // Chỉ cho phép số và dấu chấm
                    this.form.discount_value = String(val).replace(/[^\d.]/g, '');
                    return;
                }

                // Nếu là fixed thì format với dấu phẩy
                if (this.form.discount_type === 'fixed') {
                    // Xóa tất cả ký tự không phải số
                    let num = String(val).replace(/[^\d]/g, '');

                    if (num) {
                        // Format với dấu phẩy (en-US style)
                        this.form.discount_value = new Intl.NumberFormat('en-US').format(parseInt(num));
                    }
                }
            },

            // Format number input với dấu phẩy
            formatNumberInput(val) {
                if (!val) return '';
                // Xóa tất cả ký tự không phải số
                let num = String(val).replace(/[^\d]/g, '');

                if (num) {
                    // Format với dấu phẩy (en-US style: 500,000)
                    return new Intl.NumberFormat('en-US').format(parseInt(num));
                }
                return '';
            },

            // Convert YYYY-MM-DD HH:MM:SS -> DD/MM/YYYY HH:MM
            convertDateToDisplay(dateStr) {
                if (!dateStr) return '';
                const match = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/);
                if (match) {
                    const [, year, month, day, hour, minute] = match;
                    return `${day}/${month}/${year} ${hour}:${minute}`;
                }
                return dateStr;
            },

            // Convert DD/MM/YYYY HH:MM -> YYYY-MM-DD HH:MM:SS (for backend)
            // hoặc DD/MM/YYYY -> YYYY-MM-DD 00:00:00
            convertDateToSQL(dateStr) {
                if (!dateStr) return '';

                // Thử match với giờ: DD/MM/YYYY HH:MM
                let match = dateStr.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/);
                if (match) {
                    const [, day, month, year, hour, minute] = match;
                    return `${year}-${month}-${day} ${hour}:${minute}:00`;
                }

                // Thử match không có giờ: DD/MM/YYYY
                match = dateStr.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (match) {
                    const [, day, month, year] = match;
                    return `${year}-${month}-${day} 00:00:00`;
                }

                return dateStr;
            },

            getApplyToText(type) {
                const map = {
                    'category': 'Theo danh mục',
                    'product': 'Sản phẩm cụ thể'
                };
                return map[type] || type;
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
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>