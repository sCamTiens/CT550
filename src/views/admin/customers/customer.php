<?php
// views/admin/staff/staff.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / <span class="text-slate-800 font-medium">Quản lý khách hàng</span>
</nav>
<div x-data="customerPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý khách hàng</h1>
    <button
      class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
      @click="openCreate()">+ Thêm khách hàng</button>
  </div>
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table style="width:170%; min-width:1200px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <th class="py-2 px-4 whitespace-nowrap text-center">Thao tác</th>
            <?= textFilterPopover('username', 'Tài khoản') ?>
            <?= textFilterPopover('full_name', 'Họ tên') ?>
            <?= textFilterPopover('email', 'Email') ?>
            <?= textFilterPopover('phone', 'SĐT') ?>
            <?= textFilterPopover('gender', 'Giới tính') ?>
            <?= textFilterPopover('date_of_birth', 'Ngày sinh') ?>
            <?= selectFilterPopover('is_active', 'Trạng thái', ['' => '-- Tất cả --', '1' => 'Hoạt động', '0' => 'Khóa']) ?>
            <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
            <?= textFilterPopover('created_by_name', 'Người tạo') ?>
            <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
            <?= textFilterPopover('updated_by_name', 'Người cập nhật') ?>
          </tr>
        </thead>
        <tbody>
          <template x-for="c in paginated()" :key="c.id">
            <tr class="border-t">
              <td class="py-2 px-4 space-x-2 text-center">
                <button @click="openEditModal(c)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Sửa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="openChangePasswordModal(c)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Đổi mật khẩu">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16 10V7a4 4 0 00-8 0v3M5 10h14a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                  </svg>
                </button>
                <button @click="remove(c.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line uppercase" x-text="c.username"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.full_name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.email"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.phone || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.phone || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.gender || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.gender || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.date_of_birth || '—') === '—' ? 'text-center' : 'text-right'"
                x-text="c.date_of_birth || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line">
                <span x-text="c.is_active ? 'Hoạt động' : 'Khóa'"
                  :class="c.is_active ? 'text-green-600' : 'text-red-600'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                :class="(c.created_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.created_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.created_by_name || '—') === '—' ? 'text-center' : 'text-right'"
                x-text="c.created_by_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                :class="(c.updated_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.updated_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.updated_by_name || '—') === '—' ? 'text-center' : 'text-right'"
                x-text="c.updated_by_name || '—'"></td>
            </tr>
          </template>
          <tr x-show="!loading && filtered().length===0">
            <td colspan="13" class="py-12 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center">
                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                <div class="text-lg text-slate-300">Không có dữ liệu khách hàng</div>
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
            <div @click="perPage=opt;open=false" class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
              x-text="opt + ' / trang'"></div>
          </template>
        </div>
      </div>
    </div>
  </div>
  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity
    style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm khách hàng</h3>
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
  <!-- MODAL: Edit -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
    x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openEdit=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Sửa khách hàng</h3>
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
              <input :type="showChangePassword ? 'text' : 'password'" x-model="formChangePassword.password"
                class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập mật khẩu mới" minlength="8"
                maxlength="50" autocomplete="new-password" required
                @blur="validateChangePasswordField('password')">
              <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
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
            <input :type="showChangePasswordConfirm ? 'text' : 'password'" x-model="formChangePassword.password_confirm"
              class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập lại mật khẩu" minlength="8" maxlength="50"
              autocomplete="new-password" required
              @blur="validateChangePasswordField('password_confirm')">
            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
              @click="showChangePasswordConfirm = !showChangePasswordConfirm" tabindex="-1">
              <i :class="showChangePasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
            </button>
          </div>
          <p x-show="changePasswordErrors.password_confirm"
            x-text="changePasswordErrors.password_confirm" class="text-red-500 text-xs mt-1"></p>
        </div>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md border" @click="openChangePassword=false">Đóng</button>
          <button
            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
            :disabled="submitting" x-text="submitting?'Đang lưu...':'Đổi mật khẩu'"></button>
        </div>
      </form>
    </div>
  </div>

  <div id="toast-container" class="z-[60]"></div>
</div>
<script>
  function customerPage() {
    const api = {
      list: '/admin/api/customers',
      create: '/admin/api/customers',
      update: (id) => `/admin/api/customers/${id}`,
      remove: (id) => `/admin/api/customers/${id}`
    };

    return {
      form: {
        id: null,
        username: '',
        full_name: '',
        email: '',
        phone: '',
        gender: '',
        date_of_birth: '',
        is_active: 1
      },
      errors: {
        username: '',
        full_name: '',
        email: '',
        phone: '',
        password: '',
        password_confirm: ''
      },
      touched: {
        username: false,
        full_name: false,
        email: false,
        phone: false,
        password: false,
        password_confirm: false
      },
      openChangePassword: false,
      loading: true,
      submitting: false,
      openAdd: false,
      openEdit: false,
      items: [],
      currentPage: 1,
      perPage: 20,
      perPageOptions: [5, 10, 20, 50, 100],
      openFilter: {
        username: false,
        full_name: false,
        email: false,
        phone: false,
        gender: false,
        date_of_birth: false,
        is_active: false,
        created_at: false,
        created_by_name: false,
        updated_at: false,
        updated_by_name: false
      },
      filters: {
        username: '',
        full_name: '',
        email: '',
        phone: '',
        gender: '',
        date_of_birth: '',
        is_active: '',
        created_at_type: '',
        created_at_value: '',
        created_at_from: '',
        created_at_to: '',
        created_by_name: '',
        updated_at_type: '',
        updated_at_value: '',
        updated_at_from: '',
        updated_at_to: '',
        updated_by_name: ''
      },
      openChangePasswordModal(c) {
        this.formChangePassword = { user_id: c.id, password: '', password_confirm: '' };
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
      filtered() {
        let data = this.items;
        if (this.filters.username) data = data.filter(x => (x.username || '').toLowerCase().includes(this.filters.username.toLowerCase()));
        if (this.filters.full_name) data = data.filter(x => (x.full_name || '').toLowerCase().includes(this.filters.full_name.toLowerCase()));
        if (this.filters.email) data = data.filter(x => (x.email || '').toLowerCase().includes(this.filters.email.toLowerCase()));
        if (this.filters.phone) data = data.filter(x => (x.phone || '').toLowerCase().includes(this.filters.phone.toLowerCase()));
        if (this.filters.gender) data = data.filter(x => (x.gender || '').toLowerCase().includes(this.filters.gender.toLowerCase()));
        if (this.filters.date_of_birth) data = data.filter(x => (x.date_of_birth || '').includes(this.filters.date_of_birth));
        if (this.filters.is_active !== '' && this.filters.is_active !== undefined) {
          data = data.filter(x => String(x.is_active) === String(this.filters.is_active));
        }
        if (this.filters.created_by_name) data = data.filter(x => (x.created_by_name || '').toLowerCase().includes(this.filters.created_by_name.toLowerCase()));
        if (this.filters.updated_by_name) data = data.filter(x => (x.updated_by_name || '').toLowerCase().includes(this.filters.updated_by_name.toLowerCase()));
        if (this.filters.created_at_value && this.filters.created_at_type === 'eq') {
          data = data.filter(x => (x.created_at || '').startsWith(this.filters.created_at_value));
        }
        if (this.filters.created_at_from && this.filters.created_at_to && this.filters.created_at_type === 'between') {
          data = data.filter(x => x.created_at >= this.filters.created_at_from && x.created_at <= this.filters.created_at_to);
        }
        if (this.filters.updated_at_value && this.filters.updated_at_type === 'eq') {
          data = data.filter(x => (x.updated_at || '').startsWith(this.filters.updated_at_value));
        }
        if (this.filters.updated_at_from && this.filters.updated_at_to && this.filters.updated_at_type === 'between') {
          data = data.filter(x => x.updated_at >= this.filters.updated_at_from && x.updated_at <= this.filters.updated_at_to);
        }
        return data;
      },
      toggleFilter(key) {
        Object.keys(this.openFilter).forEach(k => this.openFilter[k] = false);
        this.openFilter[key] = true;
      },
      applyFilter(key) {
        this.openFilter[key] = false;
      },
      resetFilter(key) {
        if (key in this.filters) {
          this.filters[key] = '';
        }
        ['_type', '_value', '_from', '_to'].forEach(suffix => {
          const composed = `${key}${suffix}`;
          if (composed in this.filters) this.filters[composed] = '';
        });
        if (key in this.openFilter) this.openFilter[key] = false;
      },
      async init() {
        await this.fetchAll();
      },
      async fetchAll() {
        this.loading = true;
        try {
          const resp = await fetch(api.list);
          if (!resp.ok) throw new Error('fetch_failed');
          const data = await resp.json();
          this.items = Array.isArray(data) ? data : (data.items || []);
        } catch (e) {
          console.error('Failed to load customers', e);
          this.showToast('Không thể tải danh sách khách hàng');
        } finally {
          this.loading = false;
        }
      },
      openCreate() {
        this.resetForm();
        this.openAdd = true;
      },
      openEditModal(customer) {
        this.resetForm();
        this.form = {
          ...customer,
          is_active: Number(customer.is_active ?? 1)
        };
        this.openEdit = true;
      },
      // reset validation/password state is handled in resetForm()
      // helper to clear a single field error
      clearError(field) { this.errors[field] = ''; },
      validateField(field) {
        const value = (this.form[field] || '').toString().trim();
        let msg = '';
        switch (field) {
          case 'username':
            if (!value) msg = 'Tài khoản không được để trống';
            else if (value.length < 6) msg = 'Tài khoản phải có ít nhất 6 ký tự';
            else if (value.length > 50) msg = 'Tài khoản tối đa 50 ký tự';
            else if (!/^[a-zA-Z_.]+$/.test(value)) msg = 'Tài khoản chỉ được chứa chữ cái không dấu, dấu gạch dưới _ hoặc dấu chấm .';
            break;
          case 'full_name':
            if (!value) msg = 'Họ tên không được để trống';
            else if (value.length < 3) msg = 'Họ tên phải có ít nhất 3 ký tự';
            break;
          case 'email':
            if (!value) msg = 'Email không được để trống';
            else if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) msg = 'Email không hợp lệ';
            break;
          case 'phone':
            if (value && !/^0\d{9,10}$/.test(value)) msg = 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
            break;
          case 'password':
            if (!this.form.id && !value) msg = 'Mật khẩu không được để trống';
            else if (value && value.length < 6) msg = 'Mật khẩu phải ít nhất 6 ký tự';
            break;
          case 'password_confirm':
            if (!this.form.id && (!value || value !== (this.form.password || ''))) msg = 'Mật khẩu không khớp';
            break;
        }
        this.errors[field] = msg;
        return msg === '';
      },
      generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        let len = Math.floor(Math.random() * 5) + 8; // 8-12
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
      serializeForm() {
        const out = {
          username: (this.form.username || '').trim(),
          full_name: (this.form.full_name || '').trim(),
          email: (this.form.email || '').trim(),
          phone: (this.form.phone || '').trim(),
          gender: this.form.gender || '',
          date_of_birth: this.form.date_of_birth || '',
          is_active: Number(this.form.is_active ?? 1)
        };
        if (!this.form.id && this.form.password) out.password = this.form.password;
        return out;
      },
      validateForm(isCreate = false) {
        const payload = this.serializeForm();
        if (isCreate && payload.username === '') return 'Tài khoản không được để trống';
        if (payload.full_name === '') return 'Họ tên không được để trống';
        if (!payload.email) return 'Email không được để trống';
        if (payload.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) return 'Email không hợp lệ';
        if (payload.phone && !/^0\d{9,10}$/.test(payload.phone)) return 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
        if (isCreate) {
          const pw = (this.form.password || '').trim();
          const pw2 = (this.form.password_confirm || '').trim();
          if (!pw) return 'Mật khẩu không được để trống';
          if (pw.length < 6) return 'Mật khẩu phải ít nhất 6 ký tự';
          if (!pw2) return 'Vui lòng nhập lại mật khẩu';
          if (pw !== pw2) return 'Mật khẩu không khớp';
        }
        return '';
      },
      async submitChangePassword() {
        this.changePasswordTouched = true;
        if (!this.validateChangePassword()) return;
        this.submitting = true;
        try {
          const res = await fetch(`/admin/api/customers/${this.formChangePassword.user_id}/password`, {
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

      validateChangePassword() {
        this.validateChangePasswordField('password');
        this.validateChangePasswordField('password_confirm');
        return Object.keys(this.changePasswordErrors).length === 0 ||
          (this.changePasswordErrors.password === '' && this.changePasswordErrors.password_confirm === '');
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
      async submitCreate() {
        const error = this.validateForm(true);
        if (error) {
          this.showToast(error);
          return;
        }

        this.submitting = true;
        try {
          const resp = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.serializeForm())
          });
          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            await this.fetchAll();
            this.openAdd = false;
            this.showToast('Thêm khách hàng thành công', 'success');
          } else {
            this.showToast(data.error || 'Không thể thêm khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể thêm khách hàng');
        } finally {
          this.submitting = false;
        }
      },
      async submitUpdate() {
        const error = this.validateForm(false);
        if (error) {
          this.showToast(error);
          return;
        }

        const id = this.form.id;
        if (!id) {
          this.showToast('Không xác định được khách hàng cần cập nhật');
          return;
        }

        this.submitting = true;
        try {
          const resp = await fetch(api.update(id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.serializeForm())
          });
          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            const idx = this.items.findIndex(item => Number(item.id) === Number(id));
            if (idx !== -1 && data) {
              this.items.splice(idx, 1, data);
            } else {
              await this.fetchAll();
            }
            this.openEdit = false;
            this.showToast('Cập nhật khách hàng thành công', 'success');
          } else {
            this.showToast(data.error || 'Không thể cập nhật khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể cập nhật khách hàng');
        } finally {
          this.submitting = false;
        }
      },
      async remove(id) {
        if (!confirm('Xóa khách hàng này?')) return;
        try {
          const resp = await fetch(api.remove(id), { method: 'DELETE' });
          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            this.items = this.items.filter(item => Number(item.id) !== Number(id));
            this.showToast('Đã xoá khách hàng', 'success');
          } else {
            this.showToast(data.error || 'Không thể xoá khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể xoá khách hàng');
        }
      },
      resetForm() {
        this.form = {
          id: null,
          username: '',
          full_name: '',
          email: '',
          phone: '',
          gender: '',
          date_of_birth: '',
          is_active: 1
        };
        this.errors = {
          username: '',
          full_name: '',
          email: '',
          phone: '',
          password: '',
          password_confirm: ''
        };
        this.touched = {
          username: false,
          full_name: false,
          email: false,
          phone: false,
          password: false,
          password_confirm: false
        };
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