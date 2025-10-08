<?php
// views/admin/brands/brand.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Danh mục sản phẩm / <span class="text-slate-800 font-medium">Thương hiệu</span>
</nav>

<div x-data="brandPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý thương hiệu</h1>
    <button
      class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
      @click="openCreate()">+ Thêm thương hiệu</button>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;">
      <table style="width:100%; min-width:1200px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-left text-slate-600">
            <th class="py-2 px-4 text-left">Thao tác</th>
            <?= textFilterPopover('name', 'Tên') ?>
            <?= textFilterPopover('slug', 'Slug') ?>
            <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
            <?= textFilterPopover('created_by', 'Người tạo') ?>
            <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
            <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
          </tr>
        </thead>

        <tbody>
          <template x-for="b in paginated()" :key="b.id">
            <tr class="border-t">
              <td class="py-2 px-4 text-left space-x-2">
                <!-- Sửa -->
                <button @click="openEditModal(b)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Sửa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16.862 4.487l1.65-1.65a1.875 1.875 0 112.652 2.652l-1.65 1.65M18.513 6.138L7.5 17.25H4.5v-3l11.013-11.112z" />
                  </svg>
                </button>

                <!-- Xóa -->
                <button @click="remove(b.id)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6 7h12M9 7V4h6v3m-7 4v7m4-7v7m4-7v7M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
                  </svg>
                </button>
              </td>

              <td class="py-2 px-4" x-text="b.name"></td>
              <td class="py-2 px-4" x-text="b.slug || ''"></td>
              <td class="py-2 px-4" x-text="b.created_at || '—'"></td>
              <td class="py-2 px-4" x-text="b.created_by_name || '—'"></td>
              <td class="py-2 px-4" x-text="b.updated_at || '—'"></td>
              <td class="py-2 px-4" x-text="b.updated_by_name || '—'"></td>
            </tr>
          </template>

          <tr x-show="!loading && filtered().length===0">
            <td colspan="7" class="py-12 text-center text-slate-500">
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

  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity
    style="display:none">
    <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm thương hiệu</h3>
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
        <h3 class="font-semibold text-2xl text-[#002975]">Sửa thương hiệu</h3>
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
            <div @click="perPage=opt;open=false" class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
              x-text="opt + ' / trang'"></div>
          </template>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function brandPage() {
    const api = {
      list: '/admin/api/brands',
      create: '/admin/brands',
      update: (id) => `/admin/brands/${id}`,
      remove: (id) => `/admin/brands/${id}/delete`,
    };

    return {
      loading: true, submitting: false,
      openAdd: false, openEdit: false,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,

      currentPage: 1,
      perPage: 20,
      perPageOptions: [5, 10, 20, 50, 100],

      form: { id: null, name: '', slug: '' },
      errors: { name: '', slug: '' },
      touched: { name: false, slug: false },

      filters: {
        name: '', slug: '',
        created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
        updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
      },
      openFilter: {
        name: false, slug: false,
        created_at: false, created_by: false, updated_at: false, updated_by: false,
      },

      async init() { await this.fetchAll(); },

      // ===== pagination =====
      paginated() {
        const start = (this.currentPage - 1) * this.perPage;
        return this.filtered().slice(start, start + this.perPage);
      },
      totalPages() {
        return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
      },
      goToPage(p) {
        if (p < 1) p = 1;
        if (p > this.totalPages()) p = this.totalPages();
        this.currentPage = p;
      },

      // ===== utilities =====
      slugify(s) {
        return (s || '').toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '')
          .slice(0, 190);
      },
      onNameInput() { if (!this.form.id) this.form.slug = this.slugify(this.form.name); },

      // ===== validate =====
      validateField(f) {
        if (f === 'name') {
          if (!this.form.name?.trim()) this.errors.name = 'Tên không được bỏ trống';
          else if (this.form.name.length > 255) this.errors.name = 'Tên không vượt quá 255 ký tự';
          else this.errors.name = '';
        }
        if (f === 'slug') {
          if (!this.form.slug?.trim()) this.errors.slug = 'Slug không được bỏ trống';
          else if (this.form.slug.length > 255) this.errors.slug = 'Slug không vượt quá 255 ký tự';
          else this.errors.slug = '';
        }
      },
      validateForm() {
        this.validateField('name'); this.validateField('slug');
        if (this.errors.name) { this.showToast(this.errors.name); return false; }
        if (this.errors.slug) { this.showToast(this.errors.slug); return false; }
        if (!this.form.slug) this.form.slug = this.slugify(this.form.name);
        return true;
      },

      // ===== filters =====
      toggleFilter(k) { Object.keys(this.openFilter).forEach(x => this.openFilter[x] = (x === k ? !this.openFilter[x] : false)); },
      applyFilter(k) { this.openFilter[k] = false },
      resetFilter(k) {
        if (['created_at', 'updated_at'].includes(k)) {
          this.filters[`${k}_type`] = ''; this.filters[`${k}_value`] = ''; this.filters[`${k}_from`] = ''; this.filters[`${k}_to`] = '';
        } else { this.filters[k] = ''; }
        this.openFilter[k] = false;
      },
      applyDateFilter(val, type, value, from, to) {
        if (!val) return true;
        const d = new Date(val);
        if (type === 'eq' && value) return d.toDateString() === new Date(value).toDateString();
        if (type === 'between' && from && to) return d >= new Date(from) && d <= new Date(to);
        if (type === 'lt' && value) return d < new Date(value);
        if (type === 'gt' && value) return d > new Date(value);
        if (type === 'lte' && value) return d <= new Date(value);
        if (type === 'gte' && value) return d >= new Date(value);
        return true;
      },
      filtered() {
        const fn = v => (v ?? '').toString().toLowerCase();
        const f = this.filters;
        return this.items.filter(b => {
          if (f.name && !fn(b.name).includes(fn(f.name))) return false;
          if (f.slug && !fn(b.slug).includes(fn(f.slug))) return false;
          if (!this.applyDateFilter(b.created_at, f.created_at_type, f.created_at_value, f.created_at_from, f.created_at_to)) return false;
          if (!this.applyDateFilter(b.updated_at, f.updated_at_type, f.updated_at_value, f.updated_at_from, f.updated_at_to)) return false;
          return true;
        });
      },

      resetForm() { this.form = { id: null, name: '', slug: '' }; this.errors = { name: '', slug: '' }; this.touched = { name: false, slug: false }; },

      // ===== data =====
      async fetchAll() {
        this.loading = true;
        try {
          const r = await fetch(api.list);
          if (r.ok) { const d = await r.json(); this.items = Array.isArray(d) ? d : (d.items || []); }
        } finally { this.loading = false; }
      },

      // ===== ui =====
      openCreate() { this.resetForm(); this.openAdd = true; },
      openEditModal(b) { this.resetForm(); this.form = { ...b }; this.openEdit = true; },

      // ===== CRUD =====
      async submitCreate() {
        this.touched.name = true; this.touched.slug = true;
        if (!this.validateForm()) return;
        this.submitting = true;
        try {
          const r = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');
          this.items.unshift(res);
          this.openAdd = false;
          this.showToast('Thêm loại sản phẩm thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể thêm loại', 'error');
        } finally { this.submitting = false; }
      },

      async submitUpdate() {
        this.touched.name = true; this.touched.slug = true;
        if (!this.form.id || !this.validateForm()) return;
        this.submitting = true;
        try {
          const r = await fetch(api.update(this.form.id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');

          const i = this.items.findIndex(x => x.id == res.id);
          if (i > -1) this.items[i] = res;
          else this.items.unshift(res);

          this.openEdit = false;
          this.showToast('Cập nhật thương hiệu thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể cập nhật thương hiệu', 'error');
        } finally { this.submitting = false; }
      },

      async remove(id) {
        if (!confirm('Xóa thương hiệu này?')) return;
        try {
          const r = await fetch(`/admin/brands/${id}`, { method: 'DELETE' });
          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ khi xóa');
          this.items = this.items.filter(x => x.id != id);
          this.showToast('Xóa thương hiệu thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể xóa thương hiệu', 'error');
        }
      },

      // ===== toast =====
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

    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>