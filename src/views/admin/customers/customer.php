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
    <button class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]" @click="openCreate()">+ Thêm khách hàng</button>
  </div>
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;">
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
            <?= selectFilterPopover('is_active', 'Trạng thái', [ '' => '-- Tất cả --', '1' => 'Hoạt động', '0' => 'Khóa' ]) ?>
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
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="remove(c.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.username"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.full_name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.email"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="c.phone"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.gender"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="c.date_of_birth"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line">
                <span x-text="c.is_active ? 'Hoạt động' : 'Khóa'" :class="c.is_active ? 'text-green-600' : 'text-red-600'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="c.created_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.created_by_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="c.updated_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.updated_by_name || '—'"></td>
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
      <button @click="goToPage(currentPage-1)" :disabled="currentPage===1" class="px-2 py-1 border rounded disabled:opacity-50">&lt;</button>
      <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages()"></span></span>
      <button @click="goToPage(currentPage+1)" :disabled="currentPage===totalPages()" class="px-2 py-1 border rounded disabled:opacity-50">&gt;</button>
      <div x-data="{ open: false }" class="relative">
        <button @click="open=!open" class="border rounded px-2 py-1 w-28 flex justify-between items-center">
          <span x-text="perPage + ' / trang'"></span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" @click.outside="open=false" class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
          <template x-for="opt in perPageOptions" :key="opt">
            <div @click="perPage=opt;open=false" class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white" x-text="opt + ' / trang'"></div>
          </template>
        </div>
      </div>
    </div>
  </div>
  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm khách hàng</h3>
        <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white" @click="openAdd=false">Hủy</button>
          <button class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]" :disabled="submitting" x-text="submitting?'Đang lưu...':'Lưu'"></button>
        </div>
      </form>
    </div>
  </div>
  <!-- MODAL: Edit -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openEdit=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Sửa khách hàng</h3>
        <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
          <button class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]" :disabled="submitting" x-text="submitting?'Đang lưu...':'Cập nhật'"></button>
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
    create: '/admin/customers',
    update: (id) => `/admin/customers/${id}`,
    remove: (id) => `/admin/customers/${id}`
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
    loading: true,
    submitting: false,
    openAdd: false,
    openEdit: false,
    items: [],
    currentPage: 1,
    perPage: 20,
    perPageOptions: [5, 10, 20, 50, 100],
    openFilter: {},
    filters: {},
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
    openFilter: {},
    filters: {},
    filtered() {
      let data = this.items;
      if (this.filters.username) data = data.filter(x => (x.username || '').toLowerCase().includes(this.filters.username.toLowerCase()));
      if (this.filters.full_name) data = data.filter(x => (x.full_name || '').toLowerCase().includes(this.filters.full_name.toLowerCase()));
      if (this.filters.email) data = data.filter(x => (x.email || '').toLowerCase().includes(this.filters.email.toLowerCase()));
      if (this.filters.phone) data = data.filter(x => (x.phone || '').toLowerCase().includes(this.filters.phone.toLowerCase()));
      if (this.filters.gender) data = data.filter(x => (x.gender || '').toLowerCase().includes(this.filters.gender.toLowerCase()));
      if (this.filters.date_of_birth) data = data.filter(x => (x.date_of_birth || '').includes(this.filters.date_of_birth));
      if (this.filters.is_active !== undefined && this.filters.is_active !== '') data = data.filter(x => String(x.is_active) === String(this.filters.is_active));
      if (this.filters.created_by_name) data = data.filter(x => (x.created_by_name || '').toLowerCase().includes(this.filters.created_by_name.toLowerCase()));
      if (this.filters.updated_by_name) data = data.filter(x => (x.updated_by_name || '').toLowerCase().includes(this.filters.updated_by_name.toLowerCase()));
      // Lọc ngày tạo
      if (this.filters.created_at_value && this.filters.created_at_type === 'eq') {
        data = data.filter(x => (x.created_at || '').startsWith(this.filters.created_at_value));
      }
      if (this.filters.created_at_from && this.filters.created_at_to && this.filters.created_at_type === 'between') {
        data = data.filter(x => x.created_at >= this.filters.created_at_from && x.created_at <= this.filters.created_at_to);
      }
      // Lọc ngày cập nhật
      if (this.filters.updated_at_value && this.filters.updated_at_type === 'eq') {
        data = data.filter(x => (x.updated_at || '').startsWith(this.filters.updated_at_value));
      }
      if (this.filters.updated_at_from && this.filters.updated_at_to && this.filters.updated_at_type === 'between') {
        data = data.filter(x => x.updated_at >= this.filters.updated_at_from && x.updated_at <= this.filters.updated_at_to);
      }
      return data;
    },
    toggleFilter(key) {
      for (const k in this.openFilter) this.openFilter[k] = false;
      this.openFilter[key] = true;
    },
    applyFilter(key) { this.openFilter[key] = false; },
    resetFilter(key) {
      this.filters[key] = '';
      this.filters[key + '_type'] = '';
      this.filters[key + '_value'] = '';
      this.filters[key + '_from'] = '';
      this.filters[key + '_to'] = '';
      this.openFilter[key] = false;
    },
    async init() {
      await this.fetchAll();
    },
    async fetchAll() {
      this.loading = true;
      try {
        const r = await fetch(api.list);
        if (r.ok) {
          const data = await r.json();
          this.items = Array.isArray(data) ? data : (data.items || []);
        }
      } finally { this.loading = false; }
    },
    openCreate() {
      this.resetForm();
      this.openAdd = true;
    },
    openEditModal(c) {
      this.resetForm();
      this.form = { ...c };
      this.openEdit = true;
    },
    async submitCreate() {
      // Thêm logic submit tạo mới nếu cần
    },
    async submitUpdate() {
      // Thêm logic submit cập nhật nếu cần
    },
    async remove(id) {
      if (!confirm('Xóa khách hàng này?')) return;
      // Thêm logic xóa nếu cần
    },
    resetForm() {
      this.form = {
        id: null, username: '', full_name: '', email: '', phone: '', gender: '', date_of_birth: '', is_active: 1
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
