<?php
// views/admin/staff/staff.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Quản lý nhân viên</span>
</nav>

<div x-data="staffPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý nhân viên</h1>
        <div class="flex gap-2">
            <a href="/admin/import-history?table=staff"
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
                @click="openCreate()">+ Thêm nhân viên</button>
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
            <table style="width:180%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <th class="py-2 px-4 whitespace-nowrap text-center">Ảnh đại diện</th>
                        <?= textFilterPopover('username', 'Tài khoản') ?>
                        <?= textFilterPopover('full_name', 'Họ tên') ?>
                        <?= selectFilterPopover('staff_role', 'Vai trò', [
                            '' => '-- Tất cả --',
                            'Admin' => 'Quản trị viên',
                            'Kho' => 'Kho',
                            'Nhân viên bán hàng' => 'Nhân viên bán hàng',
                            'Hỗ trợ trực tuyến' => 'Hỗ trợ trực tuyến'
                        ]) ?>
                        <?= selectFilterPopover('gender', 'Giới tính', [
                            '' => '-- Tất cả --',
                            'Nam' => 'Nam',
                            'Nữ' => 'Nữ',
                        ]) ?>
                        <?= textFilterPopover('email', 'Email') ?>
                        <?= textFilterPopover('phone', 'Số điện thoại') ?>
                        <?= selectFilterPopover('is_active', 'Trạng thái', ['' => '-- Tất cả --', '1' => 'Hoạt động', '0' => 'Khóa']) ?>
                        <?= dateFilterPopover('hired_at', 'Ngày vào làm') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in paginated()" :key="s.user_id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center space-x-2">
                                <button @click="openEditModal(s)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Sửa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.65-1.65a1.875 1.875 0 112.652 2.652l-1.65 1.65M18.513 6.138L7.5 17.25H4.5v-3l11.013-11.112z" />
                                    </svg>
                                </button>
                                <button @click="openChangePasswordModal(s)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Đổi mật khẩu">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 10V7a4 4 0 00-8 0v3M5 10h14a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                                    </svg>
                                </button>
                                <button @click="remove(s.user_id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xóa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 7h12M9 7V4h6v3m-7 4v7m4-7v7m4-7v7M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="py-2 px-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <template x-if="s.avatar_url">
                                        <img :src="'/assets/images/avatar/' + s.avatar_url" :alt="s.full_name"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                                    </template>
                                    <template x-if="!s.avatar_url">
                                        <img src="/assets/images/avatar/default.png" :alt="s.full_name"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                                    </template>
                                </div>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line uppercase" x-text="s.username"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.full_name"></td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex justify-center items-center h-full">
                                    <span class="px-2 py-[3px] rounded text-xs font-medium" :class="{
                                        'bg-green-100 text-green-800': s.staff_role === 'Kho',
                                        'bg-red-100 text-orange-800': s.staff_role === 'Nhân viên bán hàng',
                                        'bg-blue-100 text-blue-800': s.staff_role === 'Hỗ trợ trực tuyến',
                                        'bg-purple-100 text-purple-800': s.staff_role === 'Admin',
                                    }" x-text="getStaffRoleText(s.staff_role)"></span>
                                </div>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="s.gender ? (s.gender === 'Nam' ? 'Nam' : 'Nữ') : '—'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.email"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.phone || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.phone || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="s.is_active ? 'Hoạt động' : 'Khóa'"
                                    :class="s.is_active ? 'text-green-600' : 'text-red-600'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="formatDate(s.hired_at) || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.note || '—') === '—' ? 'text-center' : 'text-right'" x-text="s.note || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="s.created_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.created_by_name || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.created_by_name || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                :class="(s.updated_at || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.updated_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line"
                                :class="(s.updated_by_name || '—') === '—' ? 'text-center' : 'text-right'"
                                x-text="s.updated_by_name || '—'">
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered().length===0">
                        <td colspan="14" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Không có dữ liệu nhân viên</div>
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
        <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openAdd=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Thêm nhân viên</h3>
                <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600 
                  hover:bg-red-600 hover:text-white transition-colors" @click="openAdd=false">Hủy</button>
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
        <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openEdit=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Sửa nhân viên</h3>
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

    <!-- MODAL: Đổi mật khẩu -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openChangePassword" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-md rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="openChangePassword=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Đổi mật khẩu</h3>
                <button class="text-slate-500 absolute right-5" @click="openChangePassword=false">✕</button>
            </div>
            <form class="p-5 space-y-4" @submit.prevent="submitChangePassword()">
                <div>
                    <label class="block text-sm text-black font-semibold mb-1">Mật khẩu mới <span
                            class="text-red-500">*</span></label>
                    <div class="flex gap-2 items-center">
                        <div class="relative flex-1 min-w-0">
                            <input :type="showChangePassword ? 'text' : 'password'"
                                x-model="formChangePassword.password" class="border rounded px-3 py-2 w-full pr-10"
                                placeholder="Nhập mật khẩu mới" minlength="8" maxlength="50" autocomplete="new-password"
                                required @blur="validateChangePasswordField('password')">
                            <button type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                                @click="showChangePassword = !showChangePassword" tabindex="-1">
                                <i :class="showChangePassword ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                            </button>
                        </div>
                        <button type="button"
                            class="px-4 py-2 border rounded text-sm font-semibold text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white flex-shrink-0"
                            style="min-width:64px;" @click="generateChangePassword()">Tạo</button>
                    </div>
                    <p x-show="changePasswordErrors.password" x-text="changePasswordErrors.password"
                        class="text-red-500 text-xs mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm text-black font-semibold mb-1">Xác nhận mật khẩu <span
                            class="text-red-500">*</span></label>
                    <div class="relative flex-1 min-w-0">
                        <input :type="showChangePasswordConfirm ? 'text' : 'password'"
                            x-model="formChangePassword.password_confirm" class="border rounded px-3 py-2 w-full pr-10"
                            placeholder="Nhập lại mật khẩu" minlength="8" maxlength="50" autocomplete="new-password"
                            required @blur="validateChangePasswordField('password_confirm')">
                        <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                            @click="showChangePasswordConfirm = !showChangePasswordConfirm" tabindex="-1">
                            <i :class="showChangePasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                        </button>
                    </div>
                    <p x-show="changePasswordErrors.password_confirm" x-text="changePasswordErrors.password_confirm"
                        class="text-red-500 text-xs mt-1"></p>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md border"
                        @click="openChangePassword=false">Đóng</button>
                    <button type="submit"
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting?'Đang lưu...':'Đổi mật khẩu'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Import Excel -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="showImportModal" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
            @click.outside="showImportModal=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Nhập dữ liệu nhân viên từ Excel</h3>
                <button class="text-slate-500 absolute right-5" @click="showImportModal=false">✕</button>
            </div>

            <div class="p-5 space-y-4">
                <!-- Chọn file -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" @change="handleFileSelect($event)" accept=".xlsx,.xls" class="hidden"
                        x-ref="fileInput">
                    <div x-show="!importFile" @click="$refs.fileInput.click()" class="cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-[#002975] mb-3"></i>
                        <p class="text-slate-600 mb-1">Nhấn để chọn file Excel</p>
                        <p class="text-sm text-slate-400">Hỗ trợ định dạng .xlsx, .xls</p>
                    </div>
                    <div x-show="importFile" class="space-y-3">
                        <div class="flex items-center justify-center gap-2 text-[#002975]">
                            <i class="fa-solid fa-file-excel text-2xl"></i>
                            <span x-text="importFile?.name" class="font-medium"></span>
                        </div>
                        <button type="button" @click="clearFile()" class="text-sm text-red-600 hover:underline">
                            Xóa file
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
                                <li>• Tài khoản: Chỉ chữ cái không dấu, dấu chấm, gạch dưới (tối thiểu 6 ký tự)</li>
                                <li>• Vai trò: <code class="bg-gray-200 px-1 rounded">Admin</code>, <code
                                        class="bg-gray-200 px-1 rounded">Kho</code>, <code
                                        class="bg-gray-200 px-1 rounded">Nhân viên bán hàng</code> hoặc <code
                                        class="bg-gray-200 px-1 rounded">Hỗ trợ trực tuyến</code></li>
                                <li>• Email: Phải đúng định dạng email</li>
                                <li>• Số điện thoại: Bắt đầu bằng 0 và có 10 chữ số</li>
                                <li>• Ngày vào làm: Định dạng <code class="bg-gray-200 px-1 rounded">dd/mm/yyyy</code>
                                </li>
                                <li>• File phải có định dạng .xls hoặc .xlsx</li>
                                <li>• File tối đa 10MB, không quá 10,000 dòng</li>
                            </ul>
                            <button type="button" @click="downloadTemplate()"
                                class="text-sm text-red-400 hover:text-red-600 hover:underline font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-download"></i>
                                Tải file mẫu Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button"
                        class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white transition-colors"
                        @click="showImportModal=false">
                        Hủy
                    </button>
                    <button type="button" @click="submitImport()" :disabled="!importFile || importing"
                        class="px-4 py-2 rounded-md text-white bg-[#002975] hover:bg-[#001a56] disabled:opacity-50 disabled:cursor-not-allowed"
                        x-text="importing ? 'Đang nhập...' : 'Nhập dữ liệu'">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast lỗi nổi -->
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
    function staffPage() {
        return {
            openChangePassword: false,
            formChangePassword: { user_id: null, password: '', password_confirm: '' },
            showChangePassword: false,
            showChangePasswordConfirm: false,
            changePasswordErrors: {},
            changePasswordTouched: false,
            openChangePasswordModal(s) {
                this.formChangePassword = { user_id: s.user_id, password: '', password_confirm: '' };
                this.showChangePassword = false;
                this.showChangePasswordConfirm = false;
                this.changePasswordErrors = {};
                this.changePasswordTouched = false;
                this.openChangePassword = true;
            },

            generateChangePassword() {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                let len = Math.floor(Math.random() * 5) + 8; // 8-12 ký tự
                let pw = Array.from({ length: len }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
                this.formChangePassword.password = pw;
                this.formChangePassword.password_confirm = pw;
                this.showChangePassword = true;
                this.showChangePasswordConfirm = true;
                this.changePasswordTouched = true;
                this.changePasswordErrors = {};
            },

            validateChangePassword() {
                this.validateChangePasswordField('password');
                this.validateChangePasswordField('password_confirm');
                // Check if all error messages are empty
                const hasError = Object.values(this.changePasswordErrors).some(msg => msg !== '');
                return !hasError;
            },

            validateChangePasswordField(field) {
                const pw = (this.formChangePassword.password || '').trim();
                const pw2 = (this.formChangePassword.password_confirm || '').trim();
                if (field === 'password') {
                    if (!pw) this.changePasswordErrors.password = 'Mật khẩu không được để trống';
                    else if (pw.length < 8) this.changePasswordErrors.password = 'Mật khẩu phải ít nhất 8 ký tự';
                    else this.changePasswordErrors.password = '';
                    // Also revalidate confirm if already filled
                    if (pw2) this.validateChangePasswordField('password_confirm');
                } else if (field === 'password_confirm') {
                    if (!pw2) this.changePasswordErrors.password_confirm = 'Vui lòng nhập lại mật khẩu';
                    else if (pw !== pw2) this.changePasswordErrors.password_confirm = 'Mật khẩu không khớp';
                    else this.changePasswordErrors.password_confirm = '';
                }
            },

            async submitChangePassword() {
                this.changePasswordTouched = true;
                console.log('Form data:', this.formChangePassword);
                console.log('Errors:', this.changePasswordErrors);

                if (!this.validateChangePassword()) {
                    console.log('Validation failed');
                    return;
                }

                this.submitting = true;
                console.log('Submitting to:', `/admin/api/staff/${this.formChangePassword.user_id}/password`);

                try {
                    const res = await fetch(`/admin/api/staff/${this.formChangePassword.user_id}/password`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ password: this.formChangePassword.password })
                    });

                    console.log('Response status:', res.status);
                    const data = await res.json();
                    console.log('Response data:', data);

                    if (res.ok && data && data.ok) {
                        this.openChangePassword = false;
                        this.showToast('Đổi mật khẩu thành công!', 'success');
                    } else {
                        this.showToast((data && data.error) || 'Không thể đổi mật khẩu');
                    }
                } catch (e) {
                    console.error('Error:', e);
                    this.showToast('Không thể đổi mật khẩu');
                } finally {
                    this.submitting = false;
                }
            },
            items: [],
            staffRoles: [
                { name: 'Kho' },
                { name: 'Nhân viên bán hàng' },
                { name: 'Hỗ trợ trực tuyến' },
                { name: 'Admin' }
            ],
            loading: true,
            openAdd: false,
            openEdit: false,
            currentPage: 1,
            perPage: 10,
            perPageOptions: [10, 25, 50],
            submitting: false,
            form: {},
            errors: {},         // lưu lỗi từng trường
            touched: {},        // lưu trạng thái đã chạm (blur)

            showPassword: false,
            showPasswordConfirm: false,

            formatDate(d) {
                if (!d || d === '0000-00-00') return '';
                const parts = d.split('-');
                if (parts.length === 3) {
                    const [year, month, day] = parts;
                    return `${day}/${month}/${year}`;
                }
                return d;
            },

            generatePassword() {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                let len = Math.floor(Math.random() * 5) + 8; // 8-12 ký tự
                let pw = Array.from({ length: len }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
                this.form.password = pw;
                this.form.password_confirm = pw;
                this.showPassword = true;
                this.showPasswordConfirm = true;
                this.touched.password = true;
                this.touched.password_confirm = true;
                this.clearError('password');
                this.clearError('password_confirm');
                this.validateField('password');
                this.validateField('password_confirm');
            },

            getStaffRoleText(staff_role) {
                const map = {
                    'Admin': 'Quản trị viên',
                    'Kho': 'Kho',
                    'Nhân viên bán hàng': 'Nhân viên bán hàng',
                    'Hỗ trợ trực tuyến': 'Hỗ trợ trực tuyến'
                };
                return map[staff_role] || staff_role;
            },

            // ===== FILTERS =====
            openFilter: {
                username: false, full_name: false, staff_role: false, gender: false, email: false, phone: false, is_active: false, hired_at: false, note: false,
                created_at: false, created_by: false, updated_at: false, updated_by: false
            },

            filters: {
                username: '',
                full_name: '',
                staff_role: '',
                email: '',
                gender: '',
                phone_type: '', phone_value: '', phone_from: '', phone_to: '',
                is_active: '',
                hired_at_type: '', hired_at_value: '', hired_at_from: '', hired_at_to: '',
                note: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by: ''
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

                // --- Lọc theo chuỗi ---
                ['username', 'full_name', 'created_by', 'email', 'updated_by'].forEach(key => {
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
                ['is_active', 'staff_role', 'gender'].forEach(key => {
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
                ['phone'].forEach(key => {
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
                ['hired_at', 'created_at', 'updated_at'].forEach(key => {
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

            toggleFilter(key) {
                for (const k in this.openFilter) this.openFilter[k] = false;
                this.openFilter[key] = true;
            },
            closeFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (['hired_at', 'updated_at', 'created_at'].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else if (['phone'].includes(key)) {
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
                this.loading = true;
                try {
                    const staffRes = await fetch('/admin/api/staff');
                    const staffData = await staffRes.json();
                    this.items = staffData.items || [];
                } catch (e) {
                    console.error('Lỗi khi tải dữ liệu nhân viên:', e);
                } finally {
                    this.loading = false;
                }
            },

            validateField(field) {
                const value = (this.form[field] || '').trim();
                let msg = '';

                switch (field) {
                    case 'username':
                        if (!value) msg = 'Tài khoản không được để trống';
                        else if (value.length < 6) msg = 'Tài khoản phải có ít nhất 6 ký tự';
                        else if (!/^[a-zA-Z_.]+$/.test(value)) msg = 'Tài khoản chỉ được chứa chữ cái không dấu, dấu chấm hoặc gạch dưới';
                        break;
                    case 'full_name':
                        if (!value) msg = 'Họ tên không được để trống';
                        else if (value.length < 3) msg = 'Họ tên phải có ít nhất 3 ký tự';
                        else if (/[^a-zA-ZÀ-ỹà-ỹ\s'.-]/.test(value)) msg = 'Họ tên không được chứa số hoặc ký tự đặc biệt';
                        break;
                    case 'email':
                        if (!value) msg = 'Email không được để trống';
                        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
                            msg = 'Email không hợp lệ';
                        break;
                    case 'phone':
                        if (!value) msg = 'Số điện thoại không được để trống';
                        else if (value && !/^0\d{9}$/.test(value))
                            msg = 'Số điện thoại phải bắt đầu bằng số 0 và có 10 chữ số';
                        break;
                    case 'staff_role':
                        if (!value) msg = 'Vai trò không được để trống';
                        break;
                }

                this.errors[field] = msg;
                return msg === '';
            },

            // Xóa lỗi khi người dùng sửa lại
            clearError(field) {
                this.errors[field] = '';
            },

            // Validate toàn bộ form
            validateAll() {
                const requiredFields = ['username', 'full_name', 'staff_role', 'email'];
                let ok = true;
                requiredFields.forEach(f => {
                    if (!this.validateField(f)) ok = false;
                });
                if (!this.validateField('phone')) ok = false;
                return ok;
            },

            paginated() {
                const arr = this.filtered();
                if (!Array.isArray(arr)) return [];
                return arr.filter(s => s && s.user_id != null);
            },


            totalPages() {
                return Math.ceil(this.filtered().length / this.perPage) || 1;
            },

            goToPage(p) {
                if (p >= 1 && p <= this.totalPages()) this.currentPage = p;
            },

            // Khi bấm thêm nhân viên
            openCreate() {
                this.form = {
                    is_active: '1', // Mặc định là Hoạt động
                    hired_at: ''
                };
                this.errors = {};
                this.touched = {};
                this.showPassword = false;
                this.showPasswordConfirm = false;
                this.openAdd = true;
                // Khởi tạo lại datepicker sau khi modal mở
                this.$nextTick(() => {
                    this.initDatepicker();
                });
            },

            async submitCreate() {
                this.submitting = true;
                try {
                    const res = await fetch('/admin/api/staff', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();
                    if (res.ok && data && data.user_id) {
                        this.items.unshift(data);
                        this.openAdd = false;
                        this.showToast('Thêm nhân viên thành công!', 'success');
                    } else {
                        this.showToast((data && data.error) || 'Không thể thêm nhân viên');
                    }
                } catch (e) {
                    this.showToast('Không thể thêm nhân viên');
                } finally {
                    this.submitting = false;
                }
            },

            openEditModal(s) {
                this.form = {
                    ...s,
                    is_active: String(s.is_active ?? '1'),
                    gender: s.gender ?? '',
                };
                // Chuyển đổi ngày từ Y-m-d sang d/m/Y cho datepicker
                if (this.form.hired_at && this.form.hired_at !== '0000-00-00') {
                    this.form.hired_at = this.formatDate(this.form.hired_at);
                }
                this.errors = {};
                this.touched = {};
                this.showPassword = false;
                this.showPasswordConfirm = false;
                this.openEdit = true;
                // Khởi tạo lại datepicker sau khi modal mở
                this.$nextTick(() => {
                    this.initDatepicker();
                });
            },

            async submitUpdate() {
                this.submitting = true;
                try {
                    const res = await fetch(`/admin/api/staff/${this.form.user_id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();
                    if (res.ok && data && data.user_id) {
                        const idx = this.items.findIndex(i => i.user_id === this.form.user_id);
                        if (idx !== -1) this.items[idx] = data;
                        this.openEdit = false;
                        this.showToast('Cập nhật nhân viên thành công!', 'success');
                    } else {
                        this.showToast((data && data.error) || 'Không thể cập nhật nhân viên');
                    }
                } catch (e) {
                    this.showToast('Không thể cập nhật nhân viên');
                } finally {
                    this.submitting = false;
                }
            },

            async remove(id) {
                if (!confirm('Xóa nhân viên này?')) return;
                try {
                    const res = await fetch(`/admin/api/staff/${id}`, { method: 'DELETE' });
                    if (res.ok) {
                        this.items = this.items.filter(i => i.user_id !== id);
                        this.showToast('Xóa nhân viên thành công!', 'success');
                    } else {
                        const data = await res.json().catch(() => ({}));
                        this.showToast((data && data.error) || 'Không thể xóa nhân viên');
                    }
                } catch (e) {
                    this.showToast('Không thể xóa nhân viên');
                }
            },

            // Khởi tạo datepicker cho modal
            initDatepicker() {
                if (!window.flatpickr) return;

                const inputs = document.querySelectorAll('.staff-datepicker');
                inputs.forEach(input => {
                    // Destroy existing instance if any
                    if (input._flatpickr) {
                        input._flatpickr.destroy();
                    }

                    // Create new instance
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        allowInput: true,
                        static: true,
                        appendTo: input.parentElement,
                        onChange: (selectedDates, dateStr) => {
                            // Cập nhật giá trị vào form.hired_at
                            this.form.hired_at = dateStr;
                        }
                    });
                });
            },

            exportExcel() {
                const data = this.filtered().map(s => ({
                    username: s.username || '',
                    full_name: s.full_name || '',
                    staff_role: this.getStaffRoleText(s.staff_role),
                    email: s.email || '',
                    phone: s.phone || '',
                    is_active: s.is_active == 1 ? 'Hoạt động' : 'Không hoạt động',
                    hired_at: s.hired_at || '',
                    note: s.note || '',
                    created_at: s.created_at || '',
                    created_by_name: s.created_by_name || '',
                    updated_at: s.updated_at || '',
                    updated_by_name: s.updated_by_name || ''
                }));

                const now = new Date();
                const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
                const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
                const filename = `Nhan_vien_${dateStr}_${timeStr}.xlsx`;

                fetch('/admin/api/staff/export', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: data })
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

            // ===== toast =====
            showToast(msg, type = 'error') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');

                // Xác định màu sắc theo type
                let colorClasses = '';
                let iconColor = '';
                let iconSvg = '';

                if (type === 'success') {
                    colorClasses = 'text-green-700 border-green-400';
                    iconColor = 'text-green-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
                } else if (type === 'warning') {
                    colorClasses = 'text-yellow-700 border-yellow-400';
                    iconColor = 'text-yellow-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 12a7 7 0 1114 0 7 7 0 01-14 0z" />`;
                } else {
                    colorClasses = 'text-red-700 border-red-400';
                    iconColor = 'text-red-600';
                    iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`;
                }

                toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold ${colorClasses} bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
                        <svg class="flex-shrink-0 w-6 h-6 ${iconColor} mr-3" 
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${iconSvg}
                        </svg>
                        <div class="flex-1">${msg}</div>
                    `;

                box.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            },

            // ===== IMPORT EXCEL =====
            showImportModal: false,
            importFile: null,
            importing: false,

            openImportModal() {
                this.showImportModal = true;
                this.importFile = null;
            },

            handleFileSelect(e) {
                const file = e.target.files[0];
                if (!file) {
                    this.importFile = null;
                    return;
                }

                // 1. Kiểm tra định dạng file
                const allowedExtensions = ['.xls', '.xlsx'];
                const fileName = file.name.toLowerCase();
                const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

                if (!isValidExtension) {
                    this.showToast('Chỉ chấp nhận file Excel (.xls, .xlsx)');
                    e.target.value = '';
                    return;
                }

                // 2. Kiểm tra kích thước file (max 5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    this.showToast('File không được vượt quá 5MB');
                    e.target.value = '';
                    return;
                }

                // 3. Kiểm tra độ dài tên file
                if (file.name.length > 255) {
                    this.showToast('Tên file quá dài (tối đa 255 ký tự)');
                    e.target.value = '';
                    return;
                }

                // 4. Kiểm tra ký tự đặc biệt trong tên file
                const specialCharsRegex = /[<>:"|?*]/;
                if (specialCharsRegex.test(file.name)) {
                    this.showToast('Tên file chứa ký tự không hợp lệ (< > : " | ? *)');
                    e.target.value = '';
                    return;
                }

                this.importFile = file;
            },

            clearFile() {
                this.importFile = null;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            },

            downloadTemplate() {
                window.location.href = '/admin/api/staff/template';
            },

            async submitImport() {
                if (!this.importFile) {
                    this.showToast('Vui lòng chọn file Excel');
                    return;
                }

                this.importing = true;
                const formData = new FormData();
                formData.append('file', this.importFile);

                try {
                    const res = await fetch('/admin/api/staff/import', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();

                    if (data.success) {
                        // Xác định loại toast dựa trên status
                        let toastType = 'success';
                        if (data.status === 'failed') {
                            toastType = 'error'; // Đỏ
                        } else if (data.status === 'partial') {
                            toastType = 'warning'; // Vàng/Cam
                        } else {
                            toastType = 'success'; // Xanh
                        }

                        this.showToast(data.message || 'Nhập Excel thành công!', toastType);
                        this.showImportModal = false;
                        this.importFile = null;
                        await this.init();
                    } else {
                        this.showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('Lỗi kết nối server', 'error');
                } finally {
                    this.importing = false;
                }
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>