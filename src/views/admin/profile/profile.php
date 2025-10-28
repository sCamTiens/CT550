<!-- Flatpickr CSS -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">

<?php
require __DIR__ . '/../partials/layout-start.php';

// Lấy id người dùng hiện tại từ session
$currentUserId = $_SESSION['user']['id'] ?? ($_SESSION['admin_user']['id'] ?? null);

// Lấy thông tin người dùng
$user = $_SESSION['user'] ?? $_SESSION['admin_user'] ?? [];
$avatarPath = !empty($user['avatar_url'])
    ? "/assets/images/avatar/" . htmlspecialchars($user['avatar_url'])
    : "/assets/images/avatar/default.png";
?>

<style>
    /* Ẩn biểu tượng con mắt mặc định của Chrome / Edge */
    input::-ms-reveal,
    input::-ms-clear,
    input::-webkit-credentials-auto-fill-button,
    input::-webkit-password-toggle-button {
        display: none !important;
    }

    /* Dành cho trình duyệt mới (Chrome, Edge) */
    input::-webkit-textfield-decoration-container {
        display: none !important;
    }
</style>

<div class="flex w-full min-h-screen p-6">
    <!-- Cột trái -->
    <div class="w-1/4 bg-white rounded-xl shadow p-4">
        <div class="flex flex-col items-center">
            <!-- Avatar -->
            <img src="<?= $avatarPath ?>?v=<?= time() ?>" alt="Avatar" class="w-28 h-28 rounded-full border mb-3">

            <!-- Form upload avatar -->
            <form method="post" enctype="multipart/form-data" action="/admin/profile/upload-avatar">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($currentUserId) ?>">
                <input type="file" name="avatar" class="hidden" id="avatarInput" onchange="this.form.submit()">
                <button type="button" onclick="document.getElementById('avatarInput').click()"
                    class="px-3 py-1 text-sm bg-[#002975] text-white rounded-lg hover:bg-[#002975]/90">
                    Đổi ảnh
                </button>
            </form>
        </div>

        <!-- Tabs -->
        <div class="mt-6">
            <ul class="space-y-2">
                <li>
                    <a href="?tab=info" class="block px-3 py-2 rounded-lg 
                        <?= ($_GET['tab'] ?? 'info') == 'info'
                            ? 'bg-[#002975] text-white'
                            : 'hover:bg-[#002975] hover:text-white text-[#002975]' ?>">
                        Thông tin cá nhân
                    </a>
                </li>
                <li>
                    <a href="?tab=password" class="block px-3 py-2 rounded-lg 
                        <?= ($_GET['tab'] ?? 'info') == 'password'
                            ? 'bg-[#002975] text-white'
                            : 'hover:bg-[#002975] hover:text-white text-[#002975]' ?>">
                        Đổi mật khẩu
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Cột phải -->
    <div class="flex-1 bg-white rounded-xl shadow p-6 ml-6 relative">

        <?php if (($_GET['tab'] ?? 'info') == 'info'): ?>

            <!-- Thông báo toast -->
            <div id="toast-container"></div>

            <?php if (!empty($_SESSION['profile_success'])): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const msg = <?= json_encode($_SESSION['profile_success']) ?>;
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-green-700 border-green-400 bg-green-50 border-l-4 rounded-lg shadow";
                            toast.innerHTML = `<svg class=\"flex-shrink-0 w-6 h-6 text-green-600 mr-3\"
                                xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\" /></svg><div class=\"flex-1\">${msg}</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3000);
                        }
                    });
                </script>
                <?php unset($_SESSION['profile_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const msg = <?= json_encode($_SESSION['flash_error']) ?>;
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-red-700 border-red-400 bg-white border-2 rounded-lg shadow";
                            toast.innerHTML = `<svg class=\"flex-shrink-0 w-6 h-6 text-red-600 mr-3\"
                                xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z\" /></svg><div class=\"flex-1\">${msg}</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3000);
                        }
                    });
                </script>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <h2 class="text-3xl font-bold mb-4 text-[#002975]">Thông tin cá nhân</h2>
            <!-- Form cập nhật profile -->
            <form method="post" action="/admin/profile/update-profile" class="space-y-4" x-data="profileForm()"
                @submit.prevent="submitForm($event)">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($currentUserId) ?>">

                <!-- Họ và tên -->
                <div>
                    <label class="block text-sm font-medium">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" name="fullname" x-model="form.fullname" class="w-full border rounded-lg px-3 py-2"
                        required maxlength="250" @input="clearError('fullname')" @blur="validateField('fullname')">
                    <p x-show="errors.fullname" x-text="errors.fullname" class="text-red-500 text-xs mt-1"></p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" x-model="form.email" class="w-full border rounded-lg px-3 py-2"
                        required maxlength="250" @input="clearError('email')" @blur="validateField('email')">
                    <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1"></p>
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block text-sm font-medium">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" x-model="form.phone" class="w-full border rounded-lg px-3 py-2" required
                        maxlength="32" @input="clearError('phone')" @blur="validateField('phone')">
                    <p x-show="errors.phone" x-text="errors.phone" class="text-red-500 text-xs mt-1"></p>
                </div>

                <!-- Giới tính -->
                <div>
                    <label class="block text-sm font-medium">Giới tính <span class="text-red-500">*</span></label>
                    <div class="relative"
                        x-data="{ open: false, options: ['Nam', 'Nữ'], display: form.gender || 'Chọn giới tính' }"
                        @click.away="open=false">
                        <button type="button"
                            class="w-full border rounded-lg px-3 py-2 text-left bg-white focus:outline-none flex justify-between items-center"
                            @click="open=!open">
                            <span x-text="display" :class="form.gender ? '' : 'text-gray-400'"></span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <ul x-show="open" class="absolute left-0 mt-1 w-full bg-white border rounded-lg shadow z-10">
                            <template x-for="opt in options" :key="opt">
                                <li @click="form.gender=opt; display=opt; open=false; clearError('gender'); validateField('gender')"
                                    :class="form.gender===opt ? 'bg-[#002975] text-white' : 'text-[#002975] hover:bg-[#002975] hover:text-white'"
                                    class="px-4 py-2 cursor-pointer transition-colors select-none">
                                    <span x-text="opt"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <input type="hidden" name="gender" :value="form.gender">
                    <p x-show="errors.gender" x-text="errors.gender" class="text-red-500 text-xs mt-1"></p>
                </div>

                <!-- Ngày sinh -->
                <div>
                    <label class="block text-sm font-medium">Ngày sinh <span class="text-red-500">*</span></label>
                    <input type="text" name="date_of_birth" class="w-full border rounded-lg px-3 py-2"
                        placeholder="dd/mm/yyyy" x-model="form.date_of_birth" required autocomplete="off"
                        @input="clearError('date_of_birth')" @blur="validateField('date_of_birth')">
                    <p x-show="errors.date_of_birth" x-text="errors.date_of_birth" class="text-red-500 text-xs mt-1"></p>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 justify-end">
                    <button type="button"
                        class="px-4 py-2 text-[#002975] rounded-lg border border-[#002975] bg-white hover:bg-[#002975] hover:text-white"
                        @click="resetForm">
                        Hủy
                    </button>
                    <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#002975]/90">
                        Lưu thay đổi
                    </button>
                </div>
            </form>

        <?php else: ?>
            <!-- Toast hiển thị lỗi hoặc thành công khi đổi mật khẩu -->
            <div id="toast-container"></div>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'old'): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-red-700 border-red-400 bg-white border-2 rounded-lg shadow";
                            toast.innerHTML = `
                            <svg class="flex-shrink-0 w-6 h-6 text-red-600 mr-3"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                            </svg>
                            <div class="flex-1">Mật khẩu hiện tại không đúng.</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3500);
                        }
                    });
                </script>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'same'): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-red-700 border-red-400 bg-white border-2 rounded-lg shadow";
                            toast.innerHTML = `<svg class=\"flex-shrink-0 w-6 h-6 text-red-600 mr-3\"
                                    xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z\" /></svg><div class=\"flex-1\">Mật khẩu mới không được trùng với mật khẩu hiện tại.</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3000);
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'confirm'): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-red-700 border-red-400 bg-white border-2 rounded-lg shadow";
                            toast.innerHTML = `
                            <svg class="flex-shrink-0 w-6 h-6 text-red-600 mr-3"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                            </svg>
                            <div class="flex-1">Mật khẩu xác nhận không khớp.</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3500);
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const box = document.getElementById('toast-container');
                        if (box) {
                            const toast = document.createElement('div');
                            toast.className = "fixed top-5 right-5 z-[60] flex items-center w-[400px] p-4 mb-4 text-base font-semibold text-green-700 border-green-400 bg-green-50 border-l-4 rounded-lg shadow";
                            toast.innerHTML = `
                            <svg class="flex-shrink-0 w-6 h-6 text-green-600 mr-3"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <div class="flex-1">Đổi mật khẩu thành công!</div>`;
                            box.appendChild(toast);
                            setTimeout(() => toast.remove(), 3500);
                        }
                    });
                </script>
            <?php endif; ?>

            <h2 class="text-3xl font-bold mb-4 text-[#002975]">Đặt lại mật khẩu</h2>
            <form method="post" action="/admin/profile/change-password" class="space-y-4" x-data="changePasswordForm()"
                @submit.prevent="submitForm($event)">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($currentUserId) ?>">
                <div>
                    <label class="block text-sm font-medium">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="show.old_password ? 'text' : 'password'" name="old_password"
                            class="w-full border rounded-lg px-3 py-2 pr-10" placeholder="Nhập mật khẩu hiện tại"
                            x-model="form.old_password" @input="clearError('old_password')"
                            @blur="validateField('old_password')">
                        <button type="button" tabindex="-1" @click="show.old_password = !show.old_password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975] focus:outline-none">
                            <i :class="show.old_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                        </button>
                    </div>
                    <p x-show="errors.old_password" x-text="errors.old_password" class="text-red-500 text-xs mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium">Mật khẩu mới <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="show.new_password ? 'text' : 'password'" name="new_password"
                            class="w-full border rounded-lg px-3 py-2 pr-10" placeholder="Nhập mật khẩu mới"
                            x-model="form.new_password" @input="clearError('new_password')"
                            @blur="validateField('new_password')">
                        <button type="button" tabindex="-1" @click="show.new_password = !show.new_password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975] focus:outline-none">
                            <i :class="show.new_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                        </button>
                    </div>
                    <p x-show="errors.new_password" x-text="errors.new_password" class="text-red-500 text-xs mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="show.confirm_password ? 'text' : 'password'" name="confirm_password"
                            class="w-full border rounded-lg px-3 py-2 pr-10" placeholder="Nhập lại mật khẩu mới"
                            x-model="form.confirm_password" @input="clearError('confirm_password')"
                            @blur="validateField('confirm_password')">
                        <button type="button" tabindex="-1" @click="show.confirm_password = !show.confirm_password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002975] focus:outline-none">
                            <i :class="show.confirm_password ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                        </button>
                    </div>
                    <p x-show="errors.confirm_password" x-text="errors.confirm_password" class="text-red-500 text-xs mt-1">
                    </p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button"
                        class="px-4 py-2 text-[#002975] rounded-lg border border-[#002975] bg-white hover:bg-[#002975] hover:text-white"
                        @click="resetForm">
                        Hủy
                    </button>
                    <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#002975]/90">
                        Đổi mật khẩu
                    </button>
                </div>
            </form>
            <script>
                function changePasswordForm() {
                    return {
                        form: { old_password: '', new_password: '', confirm_password: '' },
                        errors: {},
                        show: { old_password: false, new_password: false, confirm_password: false },
                        clearError(f) { this.errors[f] = '' },
                        validateField(f) {
                            if (f === 'old_password' && !this.form.old_password.trim()) this.errors.old_password = 'Vui lòng nhập mật khẩu hiện tại';
                            if (f === 'new_password') {
                                const v = this.form.new_password;
                                if (!v) this.errors.new_password = 'Vui lòng nhập mật khẩu mới';
                                else if (v.length < 8) this.errors.new_password = 'Mật khẩu phải từ 8 ký tự trở lên';
                                else if (!/[A-Z]/.test(v)) this.errors.new_password = 'Mật khẩu phải có chữ hoa';
                                else if (!/[a-z]/.test(v)) this.errors.new_password = 'Mật khẩu phải có chữ thường';
                                else if (!/[0-9]/.test(v)) this.errors.new_password = 'Mật khẩu phải có số';
                                else if (!/[^A-Za-z0-9]/.test(v)) this.errors.new_password = 'Mật khẩu phải có ký tự đặc biệt';
                                else this.errors.new_password = '';
                            }
                            if (f === 'confirm_password') {
                                if (!this.form.confirm_password) this.errors.confirm_password = 'Vui lòng nhập lại mật khẩu mới';
                                else if (this.form.confirm_password !== this.form.new_password) this.errors.confirm_password = 'Mật khẩu xác nhận không khớp';
                                else this.errors.confirm_password = '';
                            }
                        },
                        validateForm() {
                            ['old_password', 'new_password', 'confirm_password'].forEach(f => this.validateField(f));
                            return Object.values(this.errors).every(v => !v);
                        },
                        submitForm(e) {
                            if (!this.validateForm()) return;
                            e.target.submit();
                        },
                        resetForm() {
                            this.form.old_password = '';
                            this.form.new_password = '';
                            this.form.confirm_password = '';
                            this.errors = {};
                        }
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<script>
    function profileForm() {
        return {
            form: {
                fullname: <?= json_encode($user['full_name'] ?? '') ?>,
                email: <?= json_encode($user['email'] ?? '') ?>,
                phone: <?= json_encode($user['phone'] ?? '') ?>,
                gender: <?= json_encode($user['gender'] ?? '') ?>,
                date_of_birth: <?php
                $dob = $user['date_of_birth'] ?? '';
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

                // Thay mới phần số điện thoại
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
            showToast(msg, type = 'success') {
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
            submitForm(e) {
                if (!this.validateForm()) {
                    this.showToast('Vui lòng điền đầy đủ thông tin hợp lệ!', 'error');
                    return;
                }
                e.target.submit();
            },
            resetForm() {
                this.form.fullname = <?= json_encode($user['full_name'] ?? '') ?>;
                this.form.email = <?= json_encode($user['email'] ?? '') ?>;
                this.form.phone = <?= json_encode($user['phone'] ?? '') ?>;
                this.form.gender = <?= json_encode($user['gender'] ?? '') ?>;
                this.form.date_of_birth = <?= json_encode($user['date_of_birth'] ?? '') ?>;
                this.errors = {};
            }
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.has('avatar-updated')) {
            const imgPath = <?= json_encode("/assets/images/avatar/" . ($_SESSION['admin_user']['avatar_url'] ?? $_SESSION['user']['avatar_url'] ?? 'default.png')) ?>;
            window.dispatchEvent(new CustomEvent('avatar-updated', {
                detail: { url: imgPath }
            }));
            params.delete('avatar-updated');
            const cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', cleanUrl);
        }
    });
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>