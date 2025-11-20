<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <!-- Avatar -->
                    <div class="flex flex-col items-center mb-6">
                        <?php
                        $avatarPath = !empty($customer['avatar_url'])
                            ? "/assets/images/avatar/" . htmlspecialchars($customer['avatar_url'])
                            : "/assets/images/avatar/default.png";
                        ?>
                        <img src="<?= $avatarPath ?>?v=<?= time() ?>" alt="Avatar"
                            class="w-32 h-32 rounded-full border-4 border-[#002975] object-cover mb-4">

                        <h3 class="text-xl font-bold text-gray-800 mb-1">
                            <?= htmlspecialchars($customer['full_name']) ?>
                        </h3>
                        <p class="text-gray-500 text-sm mb-2">
                            <?= htmlspecialchars($customer['email']) ?>
                        </p>

                        <!-- Điểm tích lũy -->
                        <div class="bg-gradient-to-r from-yellow-100 to-orange-100 rounded-lg px-4 py-3 mb-4">
                            <div class="flex items-center justify-center gap-2">
                                <i class="fa-solid fa-coins text-yellow-600 text-xl"></i>
                                <div class="text-center">
                                    <div class="text-xs text-gray-600">Điểm tích lũy</div>
                                    <div class="text-2xl font-bold text-[#002975]">
                                        <?= number_format($customer['loyalty_points'] ?? 0, 0, ',', '.') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form upload avatar -->
                        <form method="post" enctype="multipart/form-data" action="/profile/upload-avatar">
                            <input type="file" name="avatar" class="hidden" id="avatarInput" accept="image/*"
                                onchange="this.form.submit()">
                            <button type="button" onclick="document.getElementById('avatarInput').click()"
                                class="px-4 py-2 text-sm border-2 border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-colors">
                                <i class="fa-solid fa-camera mr-2"></i>
                                Đổi ảnh đại diện
                            </button>
                        </form>
                    </div>

                    <!-- Menu -->
                    <nav class="space-y-2">
                        <a href="/profile?tab=info"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= ($_GET['tab'] ?? 'info') == 'info' ? 'bg-[#002975] text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fa-solid fa-user"></i>
                            <span>Thông tin cá nhân</span>
                        </a>
                        <a href="/profile?tab=password"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= ($_GET['tab'] ?? 'info') == 'password' ? 'bg-[#002975] text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fa-solid fa-lock"></i>
                            <span>Đổi mật khẩu</span>
                        </a>
                        <a href="/profile?tab=orders"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= ($_GET['tab'] ?? 'info') == 'orders' ? 'bg-[#002975] text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fa-solid fa-box"></i>
                            <span>Đơn hàng</span>
                        </a>
                        <a href="/profile?tab=loyalty"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= ($_GET['tab'] ?? 'info') == 'loyalty' ? 'bg-[#002975] text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fa-solid fa-gift"></i>
                            <span>Điểm tích lũy</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <?php $currentTab = $_GET['tab'] ?? 'info'; ?>

                    <?php if ($currentTab == 'info'): ?>
                        <!-- Thông tin cá nhân -->
                        <h2 class="text-2xl font-bold mb-6 text-[#002975]">Thông tin cá nhân</h2>

                        <form method="post" action="/profile/update" class="space-y-4" x-data="profileForm()"
                            @submit.prevent="submitForm($event)">

                            <!-- Họ và tên -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Họ và tên <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="fullname" x-model="form.fullname"
                                    class="w-full border-2 rounded-lg px-4 py-2 focus:border-[#002975] focus:outline-none"
                                    required maxlength="250" @input="clearError('fullname')"
                                    @blur="validateField('fullname')">
                                <p x-show="errors.fullname" x-text="errors.fullname" class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" x-model="form.email"
                                    class="w-full border-2 rounded-lg px-4 py-2 focus:border-[#002975] focus:outline-none"
                                    required maxlength="250" @input="clearError('email')" @blur="validateField('email')">
                                <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Số điện thoại -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="phone" x-model="form.phone"
                                    class="w-full border-2 rounded-lg px-4 py-2 focus:border-[#002975] focus:outline-none"
                                    required maxlength="32" @input="clearError('phone')" @blur="validateField('phone')">
                                <p x-show="errors.phone" x-text="errors.phone" class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Giới tính -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Giới tính <span class="text-red-500">*</span>
                                </label>
                                <div class="relative"
                                    x-data="{ open: false, options: ['Nam', 'Nữ'], display: form.gender || 'Chọn giới tính' }"
                                    @click.away="open=false">
                                    <button type="button"
                                        class="w-full border-2 rounded-lg px-4 py-2 text-left bg-white focus:outline-none focus:border-[#002975] flex justify-between items-center"
                                        @click="open=!open">
                                        <span x-text="display" :class="form.gender ? '' : 'text-gray-400'"></span>
                                        <i class="fa-solid fa-chevron-down text-sm"></i>
                                    </button>
                                    <ul x-show="open"
                                        class="absolute left-0 mt-1 w-full bg-white border-2 rounded-lg shadow-lg z-10 max-h-60 overflow-auto">
                                        <template x-for="opt in options" :key="opt">
                                            <li @click="form.gender=opt; display=opt; open=false; clearError('gender'); validateField('gender')"
                                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer" x-text="opt">
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <input type="hidden" name="gender" :value="form.gender">
                                <p x-show="errors.gender" x-text="errors.gender" class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Ngày sinh -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Ngày sinh <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="date_of_birth"
                                    class="w-full border-2 rounded-lg px-4 py-2 focus:border-[#002975] focus:outline-none"
                                    placeholder="dd/mm/yyyy" x-model="form.date_of_birth" required autocomplete="off"
                                    @input="clearError('date_of_birth')" @blur="validateField('date_of_birth')">
                                <p x-show="errors.date_of_birth" x-text="errors.date_of_birth"
                                    class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-3 justify-end pt-4">
                                <button type="button"
                                    class="px-6 py-2 text-gray-600 rounded-lg border-2 border-gray-300 hover:bg-gray-100 transition-colors"
                                    @click="resetForm">
                                    Hủy
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors">
                                    <i class="fa-solid fa-save mr-2"></i>
                                    Lưu thay đổi
                                </button>
                            </div>
                        </form>

                    <?php elseif ($currentTab == 'orders'): ?>
                        <!-- Đơn hàng -->
                        <div x-data="ordersTab()" x-init="init()">
                            <h2 class="text-2xl font-bold mb-6 text-[#002975] flex items-center gap-3">
                                <i class="fa-solid fa-box"></i>
                                Đơn hàng của tôi
                            </h2>

                            <!-- Stats -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-shopping-cart text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Tổng đơn hàng</div>
                                            <div class="text-2xl font-bold text-blue-600" x-text="totalOrders"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-green-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-check-circle text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Hoàn thành</div>
                                            <div class="text-2xl font-bold text-green-600" x-text="completedCount"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-orange-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-clock text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Đang xử lý</div>
                                            <div class="text-2xl font-bold text-orange-600" x-text="processingCount"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading -->
                            <template x-if="loading">
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#002975] mb-3"></div>
                                    <p class="text-gray-600">Đang tải dữ liệu...</p>
                                </div>
                            </template>

                            <!-- Table -->
                            <template x-if="!loading">
                                <div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full mb-[150px]">
                                            <thead class="bg-gray-50">
                                                <tr class="text-left">
                                                    <th class="py-3 px-4 text-center">Thao tác</th>
                                                    <?= textFilterPopover('code', 'Mã đơn hàng') ?>
                                                    <?= dateFilterPopover('created_at', 'Ngày đặt') ?>
                                                    <?= selectFilterPopover('status', 'Trạng thái', [
                                                        '' => '-- Tất cả --',
                                                        'Chờ xử lý' => 'Chờ xử lý',
                                                        'Đang xử lý' => 'Đang xử lý',
                                                        'Đang giao' => 'Đang giao',
                                                        'Hoàn tất' => 'Hoàn tất',
                                                        'Đã hủy' => 'Đã hủy',
                                                    ]) ?>
                                                    <?= selectFilterPopover('payment_status', 'Thanh toán', [
                                                        '' => '-- Tất cả --',
                                                        'Đã thanh toán' => 'Đã thanh toán',
                                                        'Chưa thanh toán' => 'Chưa thanh toán',
                                                        'Thất bại' => 'Thất bại',
                                                        'Đã hoàn lại tiền' => 'Đã hoàn lại tiền',
                                                    ]) ?>
                                                    <?= numberFilterPopover('total_items', 'Sản phẩm')?>
                                                    <?= numberFilterPopover('grand_total', 'Tổng tiền')?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="order in paginated()" :key="order.id">
                                                    <tr class="border-t hover:bg-blue-50">
                                                        <td class="py-3 px-4 text-center">
                                                            <button @click="viewDetail(order.id)"
                                                                class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </button>
                                                        </td>
                                                        <td class="py-3 px-4">
                                                            <span class="font-mono font-semibold text-[#002975]"
                                                                x-text="order.code"></span>
                                                        </td>
                                                        <td class="py-3 px-4 text-sm text-gray-600">
                                                            <span x-text="formatDate(order.created_at)"></span>
                                                        </td>
                                                        <td class="py-3 px-4 text-center">
                                                            <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                                                :class="getStatusClass(order.status)"
                                                                x-text="order.status_label"></span>
                                                        </td>
                                                        <td class="py-3 px-4 text-center">
                                                            <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                                                :class="getPaymentStatusClass(order.payment_status)"
                                                                x-text="order.payment_status_label"></span>
                                                        </td>
                                                        <td class="py-3 px-4 text-center">
                                                            <span class="font-semibold" x-text="order.total_items"></span>
                                                        </td>
                                                        <td class="py-3 px-4 text-right font-bold text-[#002975]">
                                                            <span x-text="formatNumber(order.grand_total) + '₫'"></span>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <tr x-show="filtered().length === 0">
                                                    <td colspan="7" class="py-12 text-center text-gray-500">
                                                        <i class="fa-solid fa-inbox text-6xl text-gray-300 mb-3 block"></i>
                                                        <div class="text-lg">Chưa có đơn hàng nào</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="flex items-center justify-center mt-4 gap-4">
                                        <div class="text-sm text-gray-600">
                                            Tổng <span x-text="filtered().length"></span> đơn hàng
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button @click="currentPage--" :disabled="currentPage===1"
                                                class="px-3 py-1 border-2 rounded-lg disabled:opacity-50 hover:bg-gray-100">
                                                &lt;
                                            </button>
                                            <span class="px-3">
                                                <span x-text="currentPage"></span> / <span x-text="totalPages()"></span>
                                            </span>
                                            <button @click="currentPage++" :disabled="currentPage===totalPages()"
                                                class="px-3 py-1 border-2 rounded-lg disabled:opacity-50 hover:bg-gray-100">
                                                &gt;
                                            </button>
                                            <select x-model="perPage" class="border-2 rounded-lg px-3 py-1">
                                                <option value="5">5 / trang</option>
                                                <option value="10">10 / trang</option>
                                                <option value="20">20 / trang</option>
                                                <option value="50">50 / trang</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Modal xem chi tiết đơn hàng -->
                            <div x-show="viewOrderModal" x-cloak
                                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
                                @click.self="viewOrderModal = false">
                                <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
                                    @click.stop>
                                    <!-- Header -->
                                    <div
                                        class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
                                        <h3 class="text-xl font-bold text-[#002975]">
                                            <i class="fa-solid fa-file-invoice mr-2"></i>
                                            Chi tiết đơn hàng
                                        </h3>
                                        <button @click="viewOrderModal = false"
                                            class="text-gray-400 hover:text-gray-600 text-2xl">
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>

                                    <!-- Body -->
                                    <div class="p-6 space-y-6">
                                        <!-- Thông tin đơn hàng -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Mã đơn
                                                    hàng</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border font-mono font-semibold"
                                                    x-text="viewOrder.code"></div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày đặt</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border"
                                                    x-text="viewOrder.created_at ? formatDate(viewOrder.created_at) : '—'">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái đơn
                                                    hàng</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border">
                                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                                        :class="getStatusClass(viewOrder.status)"
                                                        x-text="viewOrder.status_label || '—'"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái thanh
                                                    toán</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border">
                                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                                        :class="getPaymentStatusClass(viewOrder.payment_status)"
                                                        x-text="viewOrder.payment_status_label || '—'"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức
                                                    thanh toán</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border"
                                                    x-text="viewOrder.payment_method_label || '—'"></div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên xử
                                                    lý</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border"
                                                    x-text="viewOrder.staff_name || '—'"></div>
                                            </div>
                                            <div class="col-span-2" x-show="viewOrder.note">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                                <div class="px-3 py-2 bg-gray-50 rounded border min-h-[60px]"
                                                    x-text="viewOrder.note || '—'"></div>
                                            </div>
                                        </div>

                                        <!-- Chi tiết sản phẩm -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Chi tiết sản
                                                phẩm</label>
                                            <div class="border rounded-lg overflow-hidden">
                                                <table class="w-full">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th
                                                                class="px-3 py-2 text-left text-sm font-semibold text-gray-700">
                                                                Sản phẩm</th>
                                                            <th
                                                                class="px-3 py-2 text-center text-sm font-semibold text-gray-700">
                                                                Số lượng</th>
                                                            <th
                                                                class="px-3 py-2 text-right text-sm font-semibold text-gray-700">
                                                                Đơn giá</th>
                                                            <th
                                                                class="px-3 py-2 text-right text-sm font-semibold text-gray-700">
                                                                Thành tiền</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(item, idx) in viewOrder.items || []" :key="idx">
                                                            <tr class="border-t">
                                                                <td class="px-3 py-2">
                                                                    <div class="font-medium" x-text="item.product_name">
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">SKU: <span
                                                                            x-text="item.product_sku || '—'"></span></div>
                                                                </td>
                                                                <td class="px-3 py-2 text-center font-semibold"
                                                                    x-text="item.quantity"></td>
                                                                <td class="px-3 py-2 text-right"
                                                                    x-text="formatNumber(item.unit_price) + '₫'"></td>
                                                                <td class="px-3 py-2 text-right font-semibold"
                                                                    x-text="formatNumber(item.quantity * item.unit_price) + '₫'">
                                                                </td>
                                                            </tr>
                                                        </template>
                                                        <tr x-show="!viewOrder.items || viewOrder.items.length === 0">
                                                            <td colspan="4" class="px-3 py-8 text-center text-gray-400">
                                                                Chưa có sản phẩm
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot class="bg-gray-50 border-t">
                                                        <tr>
                                                            <td colspan="3" class="px-3 py-2 text-right">Tạm tính:</td>
                                                            <td class="px-3 py-2 text-right font-semibold"
                                                                x-text="formatNumber(viewOrder.subtotal || 0) + '₫'"></td>
                                                        </tr>
                                                        <tr x-show="viewOrder.discount_amount > 0">
                                                            <td colspan="3" class="px-3 py-2 text-right text-red-600">Giảm
                                                                giá:</td>
                                                            <td class="px-3 py-2 text-right font-semibold text-red-600"
                                                                x-text="'- ' + formatNumber(viewOrder.discount_amount) + '₫'">
                                                            </td>
                                                        </tr>
                                                        <tr class="border-t-2">
                                                            <td colspan="3" class="px-3 py-2 text-right font-semibold">Tổng
                                                                cộng:</td>
                                                            <td class="px-3 py-2 text-right font-bold text-lg text-[#002975]"
                                                                x-text="formatNumber(viewOrder.grand_total || 0) + '₫'">
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="border-t px-6 py-4 flex justify-end">
                                        <button @click="viewOrderModal = false"
                                            class="px-6 py-2 border-2 border-[#002975] text-[#002975] rounded-lg hover:bg-[#002975] hover:text-white transition-colors">
                                            Đóng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End ordersTab -->

                    <?php elseif ($currentTab == 'loyalty'): ?>
                        <!-- Điểm tích lũy -->
                        <div x-data="loyaltyTab()" x-init="init()">
                            <h2 class="text-2xl font-bold mb-6 text-[#002975] flex items-center gap-3">
                                <i class="fa-solid fa-coins text-yellow-500"></i>
                                Điểm tích lũy
                            </h2>

                            <!-- Stats Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-green-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-plus-circle text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Tổng đã tích</div>
                                            <div class="text-2xl font-bold text-green-600" x-text="stats.totalEarned"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-red-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-minus-circle text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Đã sử dụng</div>
                                            <div class="text-2xl font-bold text-red-600" x-text="stats.totalRedeemed"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-500 p-3 rounded-lg">
                                            <i class="fa-solid fa-shopping-bag text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">Đơn hàng</div>
                                            <div class="text-2xl font-bold text-blue-600" x-text="stats.orderCount"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading -->
                            <template x-if="loading">
                                <div class="flex flex-col items-center justify-center py-12">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#002975] mb-3"></div>
                                    <p class="text-gray-600">Đang tải dữ liệu...</p>
                                </div>
                            </template>

                            <!-- Table -->
                            <template x-if="!loading">
                                <div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full mb-[150px]">
                                            <thead class="bg-gray-50">
                                                <tr class="text-left">
                                                    <?= textFilterPopover('order_code', 'Mã đơn') ?>
                                                    <?= numberFilterPopover('points_change', 'Điểm') ?>
                                                    <?= numberFilterPopover('total_amount', 'Giá trị') ?>
                                                    <?= textFilterPopover('description', 'Mô tả') ?>
                                                    <?= dateFilterPopover('created_at', 'Thời gian') ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="t in paginated()" :key="t.id">
                                                    <tr class="border-t hover:bg-blue-50">
                                                        <td class="py-3 px-4 font-mono" x-text="t.order_code"></td>
                                                        <td class="py-3 px-4 text-right font-bold"
                                                            :class="t.points_change > 0 ? 'text-green-600' : 'text-red-600'"
                                                            x-text="(t.points_change > 0 ? '+' : '') + formatNumber(t.points_change)">
                                                        </td>
                                                        <td class="py-3 px-4 text-right"
                                                            x-text="t.total_amount > 0 ? formatNumber(t.total_amount) + '₫' : '—'">
                                                        </td>
                                                        <td class="py-3 px-4 text-gray-600" x-text="t.description || '—'">
                                                        </td>
                                                        <td class="py-3 px-4 text-right text-sm text-gray-500"
                                                            x-text="t.created_at"></td>
                                                    </tr>
                                                </template>

                                                <tr x-show="filtered().length === 0">
                                                    <td colspan="6" class="py-12 text-center text-gray-500">
                                                        <i class="fa-solid fa-inbox text-6xl text-gray-300 mb-3 block"></i>
                                                        <div class="text-lg">Chưa có giao dịch nào</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="flex items-center justify-center mt-4 gap-4">
                                        <div class="text-sm text-gray-600">
                                            Tổng <span x-text="filtered().length"></span> giao dịch
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button @click="currentPage--" :disabled="currentPage===1"
                                                class="px-3 py-1 border-2 rounded-lg disabled:opacity-50 hover:bg-gray-100">
                                                &lt;
                                            </button>
                                            <span class="px-3">
                                                <span x-text="currentPage"></span> / <span x-text="totalPages()"></span>
                                            </span>
                                            <button @click="currentPage++" :disabled="currentPage===totalPages()"
                                                class="px-3 py-1 border-2 rounded-lg disabled:opacity-50 hover:bg-gray-100">
                                                &gt;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                    <?php elseif ($currentTab == 'password'): ?>
                        <!-- Đổi mật khẩu -->
                        <h2 class="text-2xl font-bold mb-6 text-[#002975]">Đổi mật khẩu</h2>

                        <form method="post" action="/profile/change-password" class="space-y-4"
                            x-data="changePasswordForm()" @submit.prevent="submitForm($event)">

                            <!-- Mật khẩu hiện tại -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Mật khẩu hiện tại <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="show.old_password ? 'text' : 'password'" name="old_password"
                                        class="w-full border-2 rounded-lg px-4 py-2 pr-12 focus:border-[#002975] focus:outline-none"
                                        placeholder="Nhập mật khẩu hiện tại" x-model="form.old_password"
                                        @input="clearError('old_password')" @blur="validateField('old_password')">
                                    <button type="button" tabindex="-1" @click="show.old_password = !show.old_password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975]">
                                        <i :class="show.old_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                                    </button>
                                </div>
                                <p x-show="errors.old_password" x-text="errors.old_password"
                                    class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Mật khẩu mới -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Mật khẩu mới <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="show.new_password ? 'text' : 'password'" name="new_password"
                                        class="w-full border-2 rounded-lg px-4 py-2 pr-12 focus:border-[#002975] focus:outline-none"
                                        placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" x-model="form.new_password"
                                        @input="clearError('new_password')" @blur="validateField('new_password')">
                                    <button type="button" tabindex="-1" @click="show.new_password = !show.new_password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975]">
                                        <i :class="show.new_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                                    </button>
                                </div>
                                <p x-show="errors.new_password" x-text="errors.new_password"
                                    class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Xác nhận mật khẩu -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Xác nhận mật khẩu <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="show.confirm_password ? 'text' : 'password'" name="confirm_password"
                                        class="w-full border-2 rounded-lg px-4 py-2 pr-12 focus:border-[#002975] focus:outline-none"
                                        placeholder="Nhập lại mật khẩu mới" x-model="form.confirm_password"
                                        @input="clearError('confirm_password')" @blur="validateField('confirm_password')">
                                    <button type="button" tabindex="-1"
                                        @click="show.confirm_password = !show.confirm_password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975]">
                                        <i
                                            :class="show.confirm_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                                    </button>
                                </div>
                                <p x-show="errors.confirm_password" x-text="errors.confirm_password"
                                    class="text-red-500 text-sm mt-1"></p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-3 justify-end pt-4">
                                <button type="button"
                                    class="px-6 py-2 text-gray-600 rounded-lg border-2 border-gray-300 hover:bg-gray-100 transition-colors"
                                    @click="resetForm">
                                    Hủy
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors">
                                    <i class="fa-solid fa-key mr-2"></i>
                                    Đổi mật khẩu
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

    <script>
        // Toast notification
        function showToast(msg, type = 'success') {
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

        // Profile form
        function profileForm() {
            return {
                form: {
                    fullname: <?= json_encode($customer['full_name'] ?? '') ?>,
                    email: <?= json_encode($customer['email'] ?? '') ?>,
                    phone: <?= json_encode($customer['phone'] ?? '') ?>,
                    gender: <?= json_encode($customer['gender'] ?? '') ?>,
                    date_of_birth: <?php
                    $dob = $customer['date_of_birth'] ?? '';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                        $parts = explode('-', $dob);
                        $dob = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                    }
                    echo json_encode($dob);
                    ?>
                },
                errors: {},
                clearError(f) { this.errors[f] = '' },
                validateField(f) {
                    if (f === 'fullname' && !this.form.fullname.trim()) {
                        this.errors.fullname = 'Họ và tên không được bỏ trống';
                    }
                    if (f === 'email') {
                        const email = this.form.email.trim();
                        if (!email) this.errors.email = 'Email không được bỏ trống';
                        else if (!/^\S+@\S+\.\S+$/.test(email)) this.errors.email = 'Email không hợp lệ';
                        else this.errors.email = '';
                    }
                    if (f === 'phone') {
                        const value = this.form.phone.trim();
                        if (!value) {
                            this.errors.phone = 'Số điện thoại không được bỏ trống';
                        } else if (!/^0\d{9}$/.test(value)) {
                            this.errors.phone = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0';
                        } else {
                            this.errors.phone = '';
                        }
                    }
                    if (f === 'gender' && !this.form.gender) {
                        this.errors.gender = 'Vui lòng chọn giới tính';
                    }
                    if (f === 'date_of_birth' && !this.form.date_of_birth) {
                        this.errors.date_of_birth = 'Vui lòng chọn ngày sinh';
                    }
                },
                validateForm() {
                    ['fullname', 'email', 'phone', 'gender', 'date_of_birth'].forEach(f => this.validateField(f));
                    return Object.values(this.errors).every(v => !v);
                },
                submitForm(e) {
                    if (!this.validateForm()) {
                        showToast('Vui lòng điền đầy đủ thông tin hợp lệ!', 'error');
                        return;
                    }
                    e.target.submit();
                },
                resetForm() {
                    this.form = {
                        fullname: <?= json_encode($customer['full_name'] ?? '') ?>,
                        email: <?= json_encode($customer['email'] ?? '') ?>,
                        phone: <?= json_encode($customer['phone'] ?? '') ?>,
                        gender: <?= json_encode($customer['gender'] ?? '') ?>,
                        date_of_birth: <?php echo json_encode($dob); ?>
                    };
                    this.errors = {};
                }
            }
        }

        // Change password form
        function changePasswordForm() {
            return {
                form: { old_password: '', new_password: '', confirm_password: '' },
                errors: {},
                show: { old_password: false, new_password: false, confirm_password: false },
                clearError(f) { this.errors[f] = '' },
                validateField(f) {
                    if (f === 'old_password' && !this.form.old_password.trim()) {
                        this.errors.old_password = 'Vui lòng nhập mật khẩu hiện tại';
                    }
                    if (f === 'new_password') {
                        const val = this.form.new_password;
                        if (!val) this.errors.new_password = 'Vui lòng nhập mật khẩu mới';
                        else if (val.length < 6) this.errors.new_password = 'Mật khẩu phải có ít nhất 6 ký tự';
                        else this.errors.new_password = '';
                    }
                    if (f === 'confirm_password') {
                        const val = this.form.confirm_password;
                        if (!val) this.errors.confirm_password = 'Vui lòng xác nhận mật khẩu';
                        else if (val !== this.form.new_password) this.errors.confirm_password = 'Mật khẩu xác nhận không khớp';
                        else this.errors.confirm_password = '';
                    }
                },
                validateForm() {
                    ['old_password', 'new_password', 'confirm_password'].forEach(f => this.validateField(f));
                    return Object.values(this.errors).every(v => !v);
                },
                submitForm(e) {
                    if (!this.validateForm()) {
                        showToast('Vui lòng điền đầy đủ thông tin!', 'error');
                        return;
                    }
                    e.target.submit();
                },
                resetForm() {
                    this.form = { old_password: '', new_password: '', confirm_password: '' };
                    this.errors = {};
                }
            }
        }

        // Loyalty tab (for profile page)
        function loyaltyTab() {
            return {
                transactions: [],
                loading: false,
                search: '',
                currentPage: 1,
                perPage: 10,
                stats: {
                    totalEarned: <?= number_format($loyaltyStats['totalEarned'] ?? 0, 0, '', '') ?>,
                    totalRedeemed: <?= number_format($loyaltyStats['totalRedeemed'] ?? 0, 0, '', '') ?>,
                    orderCount: <?= $loyaltyStats['orderCount'] ?? 0 ?>
                },

                // Filter states
                openFilter: {
                    order_code: false,
                    points_change: false,
                    total_amount: false,
                    description: false,
                    created_at: false
                },

                filters: {
                    order_code: '',
                    points_change_type: '', points_change_value: '', points_change_from: '', points_change_to: '',
                    total_amount_type: '', total_amount_value: '', total_amount_from: '', total_amount_to: '',
                    description: '',
                    created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: ''
                },

                async init() {
                    await this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/profile/loyalty/transactions');
                        const data = await res.json();

                        if (data.success) {
                            this.transactions = data.data;
                            if (data.stats) {
                                this.stats = {
                                    totalEarned: this.formatNumber(data.stats.totalEarned),
                                    totalRedeemed: this.formatNumber(data.stats.totalRedeemed),
                                    orderCount: data.stats.orderCount
                                };
                            }
                        }
                    } catch (err) {
                        console.error('Fetch error:', err);
                        showToast('Không thể tải dữ liệu', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                applyFilter(val, type, { value, from, to, dataType }) {
                    if (val == null) return false;

                    // TEXT
                    if (dataType === 'text') {
                        const normalize = (str) => String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                        const raw = String(val || '').toLowerCase();
                        const str = normalize(val);
                        const query = String(value || '').toLowerCase();
                        const queryNoAccent = normalize(value);

                        if (!query) return true;
                        if (type === 'eq') return raw === query || str === queryNoAccent;
                        if (type === 'contains' || type === 'like') return raw.includes(query) || str.includes(queryNoAccent);
                        return true;
                    }

                    // NUMBER
                    if (dataType === 'number') {
                        const parseNum = (v) => {
                            if (v === '' || v === null || v === undefined) return null;
                            const n = Number(String(v).replace(/[^\d.-]/g, ''));
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
                    let result = this.transactions;

                    // Text filters
                    if (this.filters.order_code) {
                        result = result.filter(t => this.applyFilter(t.order_code, 'contains', {
                            value: this.filters.order_code,
                            dataType: 'text'
                        }));
                    }

                    if (this.filters.description) {
                        result = result.filter(t => this.applyFilter(t.description, 'contains', {
                            value: this.filters.description,
                            dataType: 'text'
                        }));
                    }

                    // Number filter: points_change
                    if (this.filters.points_change_type) {
                        result = result.filter(t => this.applyFilter(t.points_change, this.filters.points_change_type, {
                            value: this.filters.points_change_value,
                            from: this.filters.points_change_from,
                            to: this.filters.points_change_to,
                            dataType: 'number'
                        }));
                    }

                    // Number filter: points_change
                    if (this.filters.total_amount_type) {
                        result = result.filter(t => this.applyFilter(t.total_amount, this.filters.total_amount_type, {
                            value: this.filters.total_amount_value,
                            from: this.filters.total_amount_from,
                            to: this.filters.total_amount_to,
                            dataType: 'number'
                        }));
                    }

                    // Date filter: created_at
                    if (this.filters.created_at_type) {
                        result = result.filter(t => this.applyFilter(t.created_at, this.filters.created_at_type, {
                            value: this.filters.created_at_value,
                            from: this.filters.created_at_from,
                            to: this.filters.created_at_to,
                            dataType: 'date'
                        }));
                    }

                    return result;
                },

                paginated() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filtered().slice(start, start + this.perPage);
                },

                totalPages() {
                    return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
                },

                toggleFilter(key) {
                    for (const k in this.openFilter) this.openFilter[k] = false;
                    this.openFilter[key] = true;
                },

                closeFilter(key) {
                    this.openFilter[key] = false;
                },

                resetFilter(key) {
                    if (['created_at', 'points_change', 'total_amount'].includes(key)) {
                        this.filters[`${key}_type`] = '';
                        this.filters[`${key}_value`] = '';
                        this.filters[`${key}_from`] = '';
                        this.filters[`${key}_to`] = '';
                    } else {
                        this.filters[key] = '';
                    }
                    this.openFilter[key] = false;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('vi-VN').format(num);
                }
            }
        }

        // Orders tab (for profile page)
        function ordersTab() {
            return {
                orders: [],
                loading: false,
                search: '',
                statusFilter: '',
                currentPage: 1,
                perPage: 10,
                totalOrders: <?= $totalOrders ?? 0 ?>,
                viewOrderModal: false,
                viewOrder: {},

                // Filter states
                openFilter: {
                    code: false,
                    created_at: false,
                    status: false,
                    payment_status: false,
                    total_items: false,
                    grand_total: false
                },

                filters: {
                    code: '',
                    created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                    status: '',
                    payment_status: '',
                    total_items_type: '', total_items_value: '', total_items_from: '', total_items_to: '',
                    grand_total_type: '', grand_total_value: '', grand_total_from: '', grand_total_to: ''
                },

                async init() {
                    await this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/profile/orders');
                        const data = await res.json();

                        if (data.success) {
                            this.orders = data.data;
                            this.totalOrders = data.total;
                        }
                    } catch (err) {
                        console.error('Fetch error:', err);
                        showToast('Không thể tải dữ liệu', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                get completedCount() {
                    return this.orders.filter(o => o.status === 'completed').length;
                },

                get processingCount() {
                    return this.orders.filter(o => ['pending', 'confirmed', 'shipping'].includes(o.status)).length;
                },

                applyFilter(val, type, { value, from, to, dataType }) {
                    if (val == null) return false;

                    // TEXT
                    if (dataType === 'text') {
                        const normalize = (str) => String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                        const raw = String(val || '').toLowerCase();
                        const str = normalize(val);
                        const query = String(value || '').toLowerCase();
                        const queryNoAccent = normalize(value);

                        if (!query) return true;
                        if (type === 'eq') return raw === query || str === queryNoAccent;
                        if (type === 'contains' || type === 'like') return raw.includes(query) || str.includes(queryNoAccent);
                        return true;
                    }

                    // NUMBER
                    if (dataType === 'number') {
                        const parseNum = (v) => {
                            if (v === '' || v === null || v === undefined) return null;
                            const n = Number(String(v).replace(/[^\d.-]/g, ''));
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
                    let result = this.orders;

                    // Text filter: code
                    if (this.filters.code) {
                        result = result.filter(o => this.applyFilter(o.code, 'contains', {
                            value: this.filters.code,
                            dataType: 'text'
                        }));
                    }

                    // Select filter: status
                    if (this.filters.status) {
                        result = result.filter(o => this.applyFilter(o.status_label, 'eq', {
                            value: this.filters.status,
                            dataType: 'text'
                        }));
                    }

                    // Select filter: payment_status
                    if (this.filters.payment_status) {
                        result = result.filter(o => this.applyFilter(o.payment_status_label, 'eq', {
                            value: this.filters.payment_status,
                            dataType: 'text'
                        }));
                    }

                    // Number filter: total_items
                    if (this.filters.total_items_type) {
                        result = result.filter(o => this.applyFilter(o.total_items, this.filters.total_items_type, {
                            value: this.filters.total_items_value,
                            from: this.filters.total_items_from,
                            to: this.filters.total_items_to,
                            dataType: 'number'
                        }));
                    }

                    // Number filter: grand_total
                    if (this.filters.grand_total_type) {
                        result = result.filter(o => this.applyFilter(o.grand_total, this.filters.grand_total_type, {
                            value: this.filters.grand_total_value,
                            from: this.filters.grand_total_from,
                            to: this.filters.grand_total_to,
                            dataType: 'number'
                        }));
                    }

                    // Date filter: created_at
                    if (this.filters.created_at_type) {
                        result = result.filter(o => this.applyFilter(o.created_at, this.filters.created_at_type, {
                            value: this.filters.created_at_value,
                            from: this.filters.created_at_from,
                            to: this.filters.created_at_to,
                            dataType: 'date'
                        }));
                    }

                    return result;
                },

                paginated() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filtered().slice(start, start + this.perPage);
                },

                totalPages() {
                    return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
                },

                clearFilters() {
                    this.search = '';
                    this.statusFilter = '';
                    this.currentPage = 1;
                },

                async viewDetail(orderId) {
                    try {
                        const res = await fetch(`/api/profile/orders/${orderId}`);
                        const data = await res.json();

                        if (data.success) {
                            this.viewOrder = data.data;
                            this.viewOrderModal = true;
                        } else {
                            showToast(data.message || 'Không thể tải chi tiết đơn hàng', 'error');
                        }
                    } catch (err) {
                        console.error('Fetch order detail error:', err);
                        showToast('Không thể tải chi tiết đơn hàng', 'error');
                    }
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('vi-VN').format(num);
                },

                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleString('vi-VN', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                getStatusClass(status) {
                    const classes = {
                        'Chờ xử lý': 'bg-yellow-100 text-yellow-700',
                        'Đang xử lý': 'bg-blue-100 text-blue-700',
                        'Đang giao': 'bg-purple-100 text-purple-700',
                        'Hoàn tất': 'bg-indigo-100 text-indigo-700',
                        'Đã hủy': 'bg-green-100 text-green-700'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-700';
                },

                getPaymentStatusClass(status) {
                    const classes = {
                        'Đã thanh toán': 'bg-orange-100 text-orange-700',
                        'Chưa thanh toán': 'bg-green-100 text-green-700',
                        'Thất bại': 'bg-red-100 text-red-700',
                        'Đã hoàn lại tiền': 'bg-gray-100 text-gray-700'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-700';
                },

                toggleFilter(key) {
                    for (const k in this.openFilter) this.openFilter[k] = false;
                    this.openFilter[key] = true;
                },

                closeFilter(key) {
                    this.openFilter[key] = false;
                },

                resetFilter(key) {
                    if (['created_at', 'total_items', 'grand_total'].includes(key)) {
                        this.filters[`${key}_type`] = '';
                        this.filters[`${key}_value`] = '';
                        this.filters[`${key}_from`] = '';
                        this.filters[`${key}_to`] = '';
                    } else {
                        this.filters[key] = '';
                    }
                    this.openFilter[key] = false;
                }
            }
        }

        // Flatpickr date picker
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr('input[name="date_of_birth"]', {
                dateFormat: 'd/m/Y',
                locale: 'vn',
                maxDate: 'today'
            });
        });

        // Flatpickr init cho filter popover
        window.__initFlatpickr = function(el) {
            const inputs = el.querySelectorAll('input.flatpickr');
            inputs.forEach(input => {
                if (input._flatpickr) return; // Đã init rồi

                const filterKey = input.dataset.filterKey;
                const filterField = input.dataset.filterField;

                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    locale: 'vn',
                    onChange: function(selectedDates, dateStr) {
                        // Cập nhật filter value vào Alpine.js component
                        const component = Alpine.$data(el.closest('[x-data]'));
                        if (component && component.filters) {
                            component.filters[`${filterKey}_${filterField}`] = dateStr;
                        }
                    }
                });
            });
        };

        // Helper: mở flatpickr khi click icon calendar
        window.openFlatpickr = function(iconEl) {
            const input = iconEl.closest('.relative').querySelector('input.flatpickr');
            if (input && input._flatpickr) {
                input._flatpickr.open();
            }
        };

        // Show toast messages from session
        <?php if (!empty($_SESSION['profile_success'])): ?>
            showToast(<?= json_encode($_SESSION['profile_success']) ?>, 'success');
            <?php unset($_SESSION['profile_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            showToast(<?= json_encode($_SESSION['flash_error']) ?>, 'error');
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            const errors = {
                'old': 'Mật khẩu hiện tại không đúng',
                'same': 'Mật khẩu mới không được trùng với mật khẩu hiện tại',
                'confirm': 'Mật khẩu xác nhận không khớp',
                'weak': 'Mật khẩu phải có ít nhất 6 ký tự',
                'empty': 'Vui lòng điền đầy đủ thông tin'
            };
            const error = '<?= $_GET['error'] ?>';
            if (errors[error]) showToast(errors[error], 'error');
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            showToast('Đổi mật khẩu thành công!', 'success');
        <?php endif; ?>

        <?php if (isset($_GET['avatar-updated'])): ?>
            showToast('Cập nhật ảnh đại diện thành công!', 'success');
            // Update avatar in header
            const newAvatar = '<?= $avatarPath ?>?v=' + Date.now();
            document.querySelectorAll('img[alt="avatar"]').forEach(img => {
                img.src = newAvatar;
            });
        <?php endif; ?>

    </script>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>