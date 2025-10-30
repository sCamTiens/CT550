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
    <div class="flex gap-2">
      <a href="/admin/import-history"
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2">
        <i class="fa-solid fa-history"></i> Lịch sử nhập
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
        @click="openCreate()">+ Thêm thương hiệu</button>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table style="width:100%; min-width:1200px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <th class="py-2 px-4 text-center">Thao tác</th>
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
              <td class="py-2 px-4 text-center space-x-2">
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

              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="b.name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="b.slug || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(b.created_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="b.created_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(b.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="b.created_by_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(b.updated_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="b.updated_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(b.updated_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="b.updated_by_name || '—'"></td>
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
    <div class="bg-white w-full max-w-xl rounded-xl shadow" @click.outside="openAdd=false">
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
    <div class="bg-white w-full max-w-xl rounded-xl shadow" @click.outside="openEdit=false">
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

  <!-- MODAL: Import Excel -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
    x-show="openImport" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
      @click.outside="openImport=false">
      <div class="px-5 py-3 border-b flex justify-between items-center">
        <h3 class="font-semibold text-2xl text-[#002975]">Nhập thương hiệu từ Excel</h3>
        <button class="text-slate-500" @click="openImport=false">✕</button>
      </div>

      <div class="p-5 space-y-4">
        <!-- Chọn file -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
          <input type="file" @change="onFileSelected" accept=".xlsx,.xls" class="hidden" x-ref="fileInput">
          <div x-show="!selectedFile" @click="$refs.fileInput.click()" class="cursor-pointer">
            <i class="fa-solid fa-cloud-arrow-up text-4xl text-[#002975] mb-3"></i>
            <p class="text-slate-600 mb-1">Nhấn để chọn file Excel</p>
            <p class="text-sm text-slate-400">Hỗ trợ định dạng .xlsx, .xls</p>
          </div>
          <div x-show="selectedFile" class="space-y-3">
            <div class="flex items-center justify-center gap-2 text-[#002975]">
              <i class="fa-solid fa-file-excel text-2xl"></i>
              <span class="font-medium" x-text="selectedFile?.name"></span>
            </div>
            <button type="button" @click="clearFile()" class="text-sm text-red-600 hover:underline">
              <i class="fa-solid fa-times mr-1"></i> Xóa file
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
                <li>• slug tự động tạo nếu để trống</li>
                <li>• File phải có định dạng .xls hoặc .xlsx</li>
                <li>• File tối đa 10MB, không quá 10,000 dòng</li>
              </ul>
              <button type="button" @click="downloadTemplate()"
                class="text-sm text-red-400 hover:text-red-600 hover:underline font-semibold flex items-center gap-1">
                <i class="fa-solid fa-download mr-1"></i> Tải file mẫu Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Nút hành động -->
        <div class="pt-2 flex justify-end gap-3">
          <button type="button"
            class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white transition-colors"
            @click="openImport=false">
            Hủy
          </button>
          <button type="button" @click="submitImport()" :disabled="!selectedFile || importing"
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
      openImport: false,
      importing: false,
      selectedFile: null,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,

      currentPage: 1,
      perPage: 20,
      perPageOptions: [5, 10, 20, 50, 100],

      form: { id: null, name: '', slug: '' },
      errors: { name: '', slug: '' },
      touched: { name: false, slug: false },

      // ===== FILTERS =====
      openFilter: {
        name: false, slug: false,
        created_at: false, created_by: false,
        updated_at: false, updated_by: false
      },

      filters: {
        name: '',
        slug: '',
        created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
        created_by: '',
        updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
        updated_by: ''
      },

      // -------------------------------------------
      // Hàm lọc tổng quát, hỗ trợ text / number / date
      // (Giữ nguyên giống mẫu chuẩn bạn gửi)
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
            ? raw === query
            : str === queryNoAccent;

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

      // -------------------------------------------
      // Lọc dữ liệu cho bảng
      // -------------------------------------------
      filtered() {
        let data = this.items;

        // --- Lọc theo chuỗi ---
        ['name', 'slug', 'created_by', 'updated_by'].forEach(key => {
          if (this.filters[key]) {
            const field = key.endsWith('_by') ? key + '_name' : key; // ví dụ: created_by → created_by_name
            data = data.filter(o =>
              this.applyFilter(o[field], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo ngày ---
        ['created_at', 'updated_at'].forEach(key => {
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

      // -------------------------------------------
      // Bật/tắt/reset filter
      // -------------------------------------------
      toggleFilter(key) {
        for (const k in this.openFilter) this.openFilter[k] = false;
        this.openFilter[key] = true;
      },
      closeFilter(key) { this.openFilter[key] = false; },
      resetFilter(key) {
        if (['created_at', 'updated_at'].includes(key)) {
          this.filters[`${key}_type`] = '';
          this.filters[`${key}_value`] = '';
          this.filters[`${key}_from`] = '';
          this.filters[`${key}_to`] = '';
        } else {
          this.filters[key] = '';
        }
        this.openFilter[key] = false;
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

      // ===== Import Excel =====
      openImportModal() {
        this.selectedFile = null;
        this.openImport = true;
      },

      onFileSelected(event) {
        const file = event.target.files[0];
        if (!file) return;

        // 1. Kiểm tra định dạng file
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xls', 'xlsx'].includes(ext)) {
          this.showToast('Vui lòng chọn file Excel (.xls hoặc .xlsx)');
          event.target.value = '';
          return;
        }

        // 2. Kiểm tra kích thước file (10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
          this.showToast('File vượt quá kích thước cho phép (tối đa 10MB). Kích thước file: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
          event.target.value = '';
          return;
        }

        // 3. Kiểm tra độ dài tên file
        if (file.name.length > 255) {
          this.showToast('Tên file quá dài (tối đa 255 ký tự). Độ dài hiện tại: ' + file.name.length + ' ký tự');
          event.target.value = '';
          return;
        }

        // 4. Kiểm tra ký tự đặc biệt trong tên file
        const fileNameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.'));
        const validFileName = /^[a-zA-Z0-9._\-\s()\[\]]+$/;
        if (!validFileName.test(fileNameWithoutExt)) {
          this.showToast('Tên file chứa ký tự đặc biệt không hợp lệ. Vui lòng chỉ sử dụng chữ cái, số, dấu gạch ngang, gạch dưới và khoảng trắng');
          event.target.value = '';
          return;
        }

        this.selectedFile = file;
      },

      clearFile() {
        this.selectedFile = null;
        if (this.$refs.fileInput) {
          this.$refs.fileInput.value = '';
        }
      },

      downloadTemplate() {
        fetch('/admin/api/brands/template')
          .then(res => {
            if (!res.ok) throw new Error('Download failed');
            return res.blob();
          })
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Mau_thuong_hieu.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
          })
          .catch(err => {
            console.error(err);
            this.showToast('Không thể tải file mẫu');
          });
      },

      async submitImport() {
        if (!this.selectedFile) {
          this.showToast('Vui lòng chọn file để nhập');
          return;
        }

        this.importing = true;
        const formData = new FormData();
        formData.append('file', this.selectedFile);

        try {
          const res = await fetch('/admin/api/brands/import', {
            method: 'POST',
            body: formData
          });

          const result = await res.json();

          if (!res.ok) {
            throw new Error(result.error || 'Lỗi khi nhập dữ liệu');
          }

          // Refresh danh sách
          await this.fetchAll();

          this.openImport = false;
          this.selectedFile = null;

          // Xác định loại thông báo dựa trên status
          let toastType = 'success';
          if (result.status === 'failed') {
            toastType = 'error';
          } else if (result.status === 'partial') {
            toastType = 'warning';
          }

          const msg = result.message || `Nhập thành công ${result.success || 0} bản ghi`;
          this.showToast(msg, toastType);

        } catch (err) {
          console.error(err);
          this.showToast(err.message || 'Không thể nhập dữ liệu từ file');
        } finally {
          this.importing = false;
        }
      },

      exportExcel() {
        const data = this.filtered().map(b => ({
          name: b.name || '',
          slug: b.slug || '',
          created_at: b.created_at || '',
          created_by_name: b.created_by_name || '',
          updated_at: b.updated_at || '',
          updated_by_name: b.updated_by_name || ''
        }));

        const now = new Date();
        const dateStr = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
        const timeStr = `${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
        const filename = `Thuong_hieu_${dateStr}_${timeStr}.xlsx`;

        fetch('/admin/api/brands/export', {
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

    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>