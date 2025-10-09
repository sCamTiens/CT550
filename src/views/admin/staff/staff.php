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
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm nhân viên</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;">
            <table style="width:180%; min-width:1200px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600">
                        <th class="py-2 px-4 text-center">Thao tác</th>
                        <?= textFilterPopover('username', 'Tài khoản') ?>
                        <?= textFilterPopover('full_name', 'Họ tên') ?>
                        <?= textFilterPopover('staff_role', 'Vai trò') ?>
                        <?= textFilterPopover('email', 'Email') ?>
                        <?= textFilterPopover('phone', 'Số điện thoại') ?>
                        <?= selectFilterPopover('is_active', 'Trạng thái', ['' => '-- Tất cả --', '1' => 'Hoạt động', '0' => 'Khóa']) ?>
                        <?= dateFilterPopover('hired_at', 'Ngày vào làm') ?>
                        <?= textFilterPopover('note', 'Ghi chú') ?>
                        <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
                        <?= textFilterPopover('created_by_name', 'Người tạo') ?>
                        <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
                        <?= textFilterPopover('updated_by_name', 'Người cập nhật') ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in paginated()" :key="s.user_id">
                        <tr class="border-t">
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
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.username"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.full_name"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.staff_role"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.email"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="s.phone"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                                <span x-text="s.is_active ? 'Hoạt động' : 'Khóa'"
                                    :class="s.is_active ? 'text-green-600' : 'text-red-600'"></span>
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="s.hired_at"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.note"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="s.created_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.created_by_name || '—'">
                            </td>
                            <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                                x-text="s.updated_at || '—'"></td>
                            <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.updated_by_name || '—'">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openAdd=false">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openEdit=false">
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
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openChangePassword"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-md rounded-xl shadow" @click.outside="openChangePassword=false">
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
                                placeholder="Nhập mật khẩu mới" minlength="6" maxlength="50" autocomplete="new-password"
                                required>
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
                    <p x-show="changePasswordTouched && changePasswordErrors.password"
                        x-text="changePasswordErrors.password" class="text-red-500 text-xs mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm text-black font-semibold mb-1">Xác nhận mật khẩu <span
                            class="text-red-500">*</span></label>
                    <div class="relative flex-1 min-w-0">
                        <input :type="showChangePasswordConfirm ? 'text' : 'password'"
                            x-model="formChangePassword.password_confirm" class="border rounded px-3 py-2 w-full pr-10"
                            placeholder="Nhập lại mật khẩu" minlength="6" maxlength="50" autocomplete="new-password"
                            required>
                        <button type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                            @click="showChangePasswordConfirm = !showChangePasswordConfirm" tabindex="-1">
                            <i :class="showChangePasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                        </button>
                    </div>
                    <p x-show="changePasswordTouched && changePasswordErrors.password_confirm"
                        x-text="changePasswordErrors.password_confirm" class="text-red-500 text-xs mt-1"></p>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-md border"
                        @click="openChangePassword=false">Đóng</button>
                    <button
                        class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                        :disabled="submitting" x-text="submitting?'Đang lưu...':'Đổi mật khẩu'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast lỗi nổi -->
    <div id="toast-container" class="z-[60]"></div>

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
                const pw = (this.formChangePassword.password || '').trim();
                const pw2 = (this.formChangePassword.password_confirm || '').trim();
                let errors = {};
                if (!pw) errors.password = 'Mật khẩu không được để trống';
                else if (pw.length < 6) errors.password = 'Mật khẩu phải ít nhất 6 ký tự';
                if (!pw2) errors.password_confirm = 'Vui lòng nhập lại mật khẩu';
                else if (pw !== pw2) errors.password_confirm = 'Mật khẩu không khớp';
                this.changePasswordErrors = errors;
                return Object.keys(errors).length === 0;
            },

            async submitChangePassword() {
                this.changePasswordTouched = true;
                if (!this.validateChangePassword()) return;
                this.submitting = true;
                try {
                    const res = await fetch(`/admin/api/staff/${this.formChangePassword.user_id}/password`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ password: this.formChangePassword.password })
                    });
                    const data = await res.json();
                    if (res.ok && data && data.ok) {
                        this.openChangePassword = false;
                        this.showToast('Đổi mật khẩu thành công!', 'success');
                    } else {
                        this.showToast((data && data.error) || 'Không thể đổi mật khẩu');
                    }
                } catch (e) {
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

            init() {
                this.loading = true;
                fetch('/admin/api/staff')
                    .then(res => res.json())
                    .then(data => this.items = data.items || [])
                    .catch(e => console.error('Lỗi tải dữ liệu nhân viên:', e))
                    .finally(() => this.loading = false);

                // Kích hoạt flatpickr
                setTimeout(() => {
                    if (window.flatpickr) {
                        flatpickr('.staff-datepicker', {
                            dateFormat: 'Y-m-d',
                            locale: 'vi',
                            allowInput: true
                        });
                    }
                }, 300);
            },

            // Filter popover state
            openFilter: {
                username: false, full_name: false, staff_role: false, email: false, phone: false, is_active: false, hired_at: false, note: false,
                created_at: false, created_by_name: false, updated_at: false, updated_by_name: false
            },
            // Filter values
            filters: {
                username: '',
                full_name: '',
                staff_role: '',
                email: '',
                phone: '',
                is_active: '',
                hired_at_type: '', hired_at_value: '', hired_at_from: '', hired_at_to: '',
                note: '',
                created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
                created_by_name: '',
                updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
                updated_by_name: ''
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
                        else if (value.length < 3) msg = 'Tài khoản phải có ít nhất 3 ký tự';
                        break;
                    case 'full_name':
                        if (!value) msg = 'Họ tên không được để trống';
                        break;
                    case 'email':
                        if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
                            msg = 'Email không hợp lệ';
                        break;
                    case 'phone':
                        if (value && !/^0\d{9,10}$/.test(value))
                            msg = 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
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
                const requiredFields = ['username', 'full_name', 'staff_role'];
                let ok = true;
                requiredFields.forEach(f => {
                    if (!this.validateField(f)) ok = false;
                });
                if (!this.validateField('email')) ok = false;
                if (!this.validateField('phone')) ok = false;
                return ok;
            },

            paginated() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filtered().slice(start, start + this.perPage);
            },

            // Filter logic giống productPage
            filtered() {
                let data = this.items;
                const f = this.filters;
                const fn = v => (v ?? '').toString().toLowerCase();
                if (f.username) data = data.filter(s => fn(s.username).includes(fn(f.username)));
                if (f.full_name) data = data.filter(s => fn(s.full_name).includes(fn(f.full_name)));
                if (f.staff_role) data = data.filter(s => fn(s.staff_role).includes(fn(f.staff_role)));
                if (f.email) data = data.filter(s => fn(s.email).includes(fn(f.email)));
                if (f.phone) data = data.filter(s => fn(s.phone).includes(fn(f.phone)));
                if (f.is_active !== '' && f.is_active !== undefined) data = data.filter(s => String(s.is_active) === String(f.is_active));
                if (f.note) data = data.filter(s => fn(s.note).includes(fn(f.note)));
                if (f.created_by_name) data = data.filter(s => fn(s.created_by_name).includes(fn(f.created_by_name)));
                if (f.updated_by_name) data = data.filter(s => fn(s.updated_by_name).includes(fn(f.updated_by_name)));
                // Date filter: hired_at
                if (f.hired_at_type === 'eq' && f.hired_at_value) {
                    data = data.filter(s => (s.hired_at || '').startsWith(f.hired_at_value));
                }
                if (f.hired_at_type === 'between' && f.hired_at_from && f.hired_at_to) {
                    data = data.filter(s => s.hired_at >= f.hired_at_from && s.hired_at <= f.hired_at_to);
                }
                if (f.hired_at_type === 'lt' && f.hired_at_value) {
                    data = data.filter(s => s.hired_at < f.hired_at_value);
                }
                if (f.hired_at_type === 'gt' && f.hired_at_value) {
                    data = data.filter(s => s.hired_at > f.hired_at_value);
                }
                if (f.hired_at_type === 'lte' && f.hired_at_value) {
                    data = data.filter(s => s.hired_at <= f.hired_at_value);
                }
                if (f.hired_at_type === 'gte' && f.hired_at_value) {
                    data = data.filter(s => s.hired_at >= f.hired_at_value);
                }

                // Date filter: created_at
                if (f.created_at_type === 'eq' && f.created_at_value) {
                    data = data.filter(s => (s.created_at || '').startsWith(f.created_at_value));
                }
                if (f.created_at_type === 'between' && f.created_at_from && f.created_at_to) {
                    data = data.filter(s => s.created_at >= f.created_at_from && s.created_at <= f.created_at_to);
                }
                if (f.created_at_type === 'lt' && f.created_at_value) {
                    data = data.filter(s => s.created_at < f.created_at_value);
                }
                if (f.created_at_type === 'gt' && f.created_at_value) {
                    data = data.filter(s => s.created_at > f.created_at_value);
                }
                if (f.created_at_type === 'lte' && f.created_at_value) {
                    data = data.filter(s => s.created_at <= f.created_at_value);
                }
                if (f.created_at_type === 'gte' && f.created_at_value) {
                    data = data.filter(s => s.created_at >= f.created_at_value);
                }

                // Date filter: updated_at
                if (f.updated_at_type === 'eq' && f.updated_at_value) {
                    data = data.filter(s => (s.updated_at || '').startsWith(f.updated_at_value));
                }
                if (f.updated_at_type === 'between' && f.updated_at_from && f.updated_at_to) {
                    data = data.filter(s => s.updated_at >= f.updated_at_from && s.updated_at <= f.updated_at_to);
                }
                if (f.updated_at_type === 'lt' && f.updated_at_value) {
                    data = data.filter(s => s.updated_at < f.updated_at_value);
                }
                if (f.updated_at_type === 'gt' && f.updated_at_value) {
                    data = data.filter(s => s.updated_at > f.updated_at_value);
                }
                if (f.updated_at_type === 'lte' && f.updated_at_value) {
                    data = data.filter(s => s.updated_at <= f.updated_at_value);
                }
                if (f.updated_at_type === 'gte' && f.updated_at_value) {
                    data = data.filter(s => s.updated_at >= f.updated_at_value);
                }
                return data;
            },

            totalPages() {
                return Math.ceil(this.filtered().length / this.perPage) || 1;
            },

            goToPage(p) {
                if (p >= 1 && p <= this.totalPages()) this.currentPage = p;
            },

            // Khi bấm thêm nhân viên
            openCreate() {
                this.form = {};
                this.errors = {};
                this.touched = {};
                this.openAdd = true;
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
                this.form = { ...s };
                this.openEdit = true;
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

            // Filter popover logic
            toggleFilter(key) {
                Object.keys(this.openFilter).forEach(k => this.openFilter[k] = (k === key ? !this.openFilter[k] : false));
            },
            applyFilter(key) { this.openFilter[key] = false; },
            resetFilter(key) {
                if (["hired_at", "created_at", "updated_at"].includes(key)) {
                    this.filters[`${key}_type`] = '';
                    this.filters[`${key}_value`] = '';
                    this.filters[`${key}_from`] = '';
                    this.filters[`${key}_to`] = '';
                } else {
                    this.filters[key] = '';
                }
                this.openFilter[key] = false;
            },
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>