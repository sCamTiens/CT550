<?php // views/admin/staff/form.php ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Họ tên -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Họ tên <span class="text-red-500">*</span></label>
        <input x-model="form.full_name" @input="clearError('full_name'); validateField('full_name')"
            @blur="touched.full_name = true; validateField('full_name')" class="border rounded px-3 py-2 w-full"
            placeholder="Nhập họ tên" maxlength="250" required>
        <p x-show="touched.full_name && errors.full_name" x-text="errors.full_name" class="text-red-500 text-xs mt-1">
        </p>
    </div>

    <!-- Email -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">
            Email <span class="text-red-500">*</span>
        </label>
        <input type="email" x-model="form.email" @input="clearError('email'); validateField('email')"
            @blur="touched.email = true; validateField('email')" class="border rounded px-3 py-2 w-full"
            placeholder="Nhập email" maxlength="250" required>
        <p x-show="touched.email && errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Số điện thoại -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Số điện thoại</label>
        <input type="text" x-model="form.phone" @input="clearError('phone'); validateField('phone')"
            @blur="touched.phone = true; validateField('phone')" class="border rounded px-3 py-2 w-full"
            placeholder="Nhập số điện thoại" maxlength="32">
        <p x-show="touched.phone && errors.phone" x-text="errors.phone" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Vai trò -->
    <div class="relative" x-data="{
        open: false,
        search: '',
        filtered: [],
        highlight: -1,
        choose(role) {
            form.staff_role = role.name;
            this.search = role.name;
            this.open = false;
            touched.staff_role = true;
            validateField('staff_role');
        },
        clear() {
            form.staff_role = '';
            this.search = '';
            this.filtered = staffRoles;
            this.open = false;
        },
        reset() {
            const selected = staffRoles.find(r => r.name == form.staff_role);
            this.search = selected ? selected.name : '';
            this.filtered = staffRoles;
            this.highlight = -1;
        }
    }" x-effect="reset()" @click.away="open = false">

        <label class="block text-sm text-black font-semibold mb-1">
            Vai trò <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = staffRoles"
                @input="open = true; filtered = staffRoles.filter(r => r.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.staff_role = true; validateField('staff_role')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.staff_role && errors.staff_role) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn vai trò --" />

            <!-- nút xóa -->
            <button x-show="form.staff_role" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <!-- icon dropdown -->
            <svg x-show="!form.staff_role"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- dropdown -->
        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(role, i) in filtered" :key="role.name">
                <div @click="choose(role)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[ 
                highlight === i ? 'bg-[#002975] text-white' 
                : (form.staff_role == role.name ? 'bg-[#002975] text-white' 
                : 'hover:bg-[#002975] hover:text-white text-black'),
                'px-3 py-2 cursor-pointer transition-colors text-sm'
            ]" x-text="role.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy vai trò
            </div>
        </div>

        <p x-show="staffRoles.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p x-show="touched.staff_role && errors.staff_role" x-text="errors.staff_role"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Ngày vào làm -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ngày vào làm</label>
        <div class="relative">
            <input type="text" x-model="form.hired_at" class="border rounded px-3 py-2 w-full staff-datepicker"
                placeholder="Chọn ngày vào làm" autocomplete="off">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fa-regular fa-calendar"></i>
            </span>
        </div>
    </div>

    <!-- Tài khoản -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Tài khoản <span class="text-red-500">*</span></label>
        <input x-model="form.username" @input="clearError('username'); validateField('username')"
            @blur="touched.username = true; validateField('username')" class="border rounded px-3 py-2 w-full"
            placeholder="Nhập tài khoản" maxlength="50" required :disabled="!!form.user_id">
        <p x-show="touched.username && errors.username" x-text="errors.username" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Mật khẩu (chỉ khi tạo mới) -->
    <template x-if="!form.user_id">
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Mật khẩu <span
                    class="text-red-500">*</span></label>
            <div class="flex gap-2 items-center">
                <div class="relative flex-1 min-w-0">
                    <input :type="showPassword ? 'text' : 'password'" x-model="form.password"
                        @input="clearError('password'); validateField('password')"
                        @blur="touched.password = true; validateField('password'); if (!form.password) { errors.password = 'Mật khẩu không được để trống' } else if (form.password.length < 6) { errors.password = 'Mật khẩu phải ít nhất 6 ký tự' }"
                        class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập mật khẩu" minlength="6"
                        maxlength="50" autocomplete="new-password" required>
                    <button type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                        @click="showPassword = !showPassword" tabindex="-1">
                        <i :class="showPassword ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                    </button>
                </div>
                <button type="button"
                    class="px-4 py-2 border rounded text-sm font-semibold text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white flex-shrink-0"
                    style="min-width:64px;" @click="generatePassword()">Tạo</button>
            </div>
            <p x-show="touched.password && errors.password" x-text="errors.password" class="text-red-500 text-xs mt-1">
            </p>
        </div>
    </template>

    <!-- Xác nhận mật khẩu (chỉ khi tạo mới) -->
    <template x-if="!form.user_id">
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Xác nhận mật khẩu <span
                    class="text-red-500">*</span></label>
            <div class="flex gap-2 items-center relative">
                <input :type="showPasswordConfirm ? 'text' : 'password'" x-model="form.password_confirm"
                    @input="clearError('password_confirm'); validateField('password_confirm')"
                    @blur="touched.password_confirm = true; validateField('password_confirm'); if (!form.password_confirm) { errors.password_confirm = 'Vui lòng nhập lại mật khẩu' } else if (form.password !== form.password_confirm) { errors.password_confirm = 'Mật khẩu không khớp' }"
                    class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập lại mật khẩu" minlength="6"
                    maxlength="50" autocomplete="new-password" required>
                <button type="button"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                    @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1">
                    <i :class="showPasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
                </button>
            </div>
            <p x-show="touched.password_confirm && errors.password_confirm" x-text="errors.password_confirm"
                class="text-red-500 text-xs mt-1"></p>
        </div>
    </template>

    <!-- Trạng thái -->
    <div class="relative" x-data="{
            open: false,
            options: [
                { value: '1',   label: 'Hoạt động' },
                { value: '0',  label: 'Khóa' }
            ],
            highlight: -1,
            search: '',
            choose(opt) {
                form.is_active = opt.value;
                this.search = opt.label;
                this.open = false;
                this.touched.is_active = true;
                validateField('is_active');
                errors.is_active = '';
            },
            reset() {
                const selected = this.options.find(o => o.value === form.is_active);
                this.search = selected ? selected.label : '';
            }
        }" x-effect="reset()" @click.away="open = false">

        <label class="block text-sm text-black font-semibold mb-1">
            Trạng thái <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true" readonly @blur="
                    touched.is_active = true; 
                    if (!form.is_active) {
                        errors.is_active = 'Vui lòng chọn trạng thái';
                    } else {
                        errors.is_active = '';
                    }
                " placeholder="-- Chọn trạng thái --"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />

            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <p x-show="touched.is_active && errors.is_active" x-text="errors.is_active" class="text-red-500 text-xs mt-1">
        </p>

        <!-- Dropdown -->
        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(opt, i) in options" :key="opt.value">
                <div @click="choose(opt)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white' :
                    (form.is_active === opt.value ? 'bg-[#002975] text-white' :
                    'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer text-sm transition-colors'
                 ]" x-text="opt.label">
                </div>
            </template>
        </div>
    </div>

    <!-- Ghi chú -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <textarea x-model="form.note" maxlength="255" class="border rounded px-3 py-2 w-full"
            placeholder="Ghi chú thêm về nhân viên"></textarea>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            document.querySelectorAll('.staff-datepicker').forEach(function (input) {
                flatpickr(input, {
                    dateFormat: 'd/m/Y',
                    locale: 'vn',
                    allowInput: true,
                    static: true,
                    appendTo: input.parentElement
                });
            });
        }
    });
</script>