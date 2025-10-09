<?php // views/admin/customers/form.php ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <!-- Họ tên -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Họ tên <span class="text-red-500">*</span></label>
    <input x-model="form.full_name" class="border rounded px-3 py-2 w-full" placeholder="Nhập họ tên" maxlength="250"
      required>
  </div>

  <!-- Email -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Email</label>
    <input type="email" x-model="form.email" class="border rounded px-3 py-2 w-full" placeholder="Nhập email"
      maxlength="250">
  </div>

  <!-- Số điện thoại -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Số điện thoại</label>
    <input type="text" x-model="form.phone" class="border rounded px-3 py-2 w-full" placeholder="Nhập số điện thoại"
      maxlength="32">
  </div>

  <!-- Giới tính -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Giới tính</label>
    <select x-model="form.gender" class="border rounded px-3 py-2 w-full text-gray-700">
      <option value="">-- Chọn --</option>
      <option value="Nam">Nam</option>
      <option value="Nữ">Nữ</option>
      <option value="Khác">Khác</option>
    </select>
  </div>

  <!-- Ngày sinh -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Ngày sinh</label>
    <div class="relative">
      <input type="text" x-model="form.date_of_birth" class="border rounded px-3 py-2 w-full customer-datepicker"
        placeholder="Chọn ngày sinh" autocomplete="off">
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
      <label class="block text-sm text-black font-semibold mb-1">Mật khẩu <span class="text-red-500">*</span></label>
      <div class="flex gap-2 items-center">
        <div class="relative flex-1 min-w-0">
          <input :type="showPassword ? 'text' : 'password'" x-model="form.password"
            @input="clearError('password'); validateField('password')"
            @blur="touched.password = true; validateField('password')" class="border rounded px-3 py-2 w-full pr-10"
            placeholder="Nhập mật khẩu" minlength="6" maxlength="50" autocomplete="new-password" required>
          <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
            @click="showPassword = !showPassword" tabindex="-1">
            <i :class="showPassword ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
          </button>
        </div>
        <button type="button"
          class="px-4 py-2 border rounded text-sm font-semibold text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white flex-shrink-0"
          style="min-width:64px;" @click="generatePassword()">Tạo</button>
      </div>
      <p x-show="touched.password && errors.password" x-text="errors.password" class="text-red-500 text-xs mt-1"></p>
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
          @blur="touched.password_confirm = true; validateField('password_confirm')"
          class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập lại mật khẩu" minlength="6" maxlength="50"
          autocomplete="new-password" required>
        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
          @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1">
          <i :class="showPasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
        </button>
      </div>
      <p x-show="touched.password_confirm && errors.password_confirm" x-text="errors.password_confirm"
        class="text-red-500 text-xs mt-1"></p>
    </div>
  </template>

  <!-- Trạng thái -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Trạng thái</label>
    <select x-model="form.is_active" class="border rounded px-3 py-2 w-full">
      <option :value="1">Hoạt động</option>
      <option :value="0">Khóa</option>
    </select>
  </div>

  <!-- FontAwesome for eye icons (if not already included globally) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

  <!-- Flatpickr JS & CSS for date picker -->
  <link rel="stylesheet" href="/assets/css/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script type="module" src="/assets/js/flatpickr-vi.js"></script>
  <script>
    nt.addEventListener('DOMContentLoaded', function () {
      if (window.flatpickr) {
        flatpickr('.customer-datepicker', {
          dateFormat: 'Y-m-d',
          locale: 'vi',
          allowInput: true
        });
      }
    });
  </script>
</div>