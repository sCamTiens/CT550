<?php
// views/admin/categories/category.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Danh mục sản phẩm / <span class="text-slate-800 font-medium">Loại sản phẩm</span>
</nav>

<div x-data="categoryPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý loại sản phẩm</h1>
    <button
      class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
      @click="openCreate()">+ Thêm loại</button>
  </div>

  <!-- Toolbar -->
  <div class="bg-white rounded-xl shadow p-4 mb-4 flex items-center gap-3">
    <input x-model.trim="search" placeholder="Tìm theo tên / slug..." class="border rounded px-3 py-2 w-80">
    <div class="text-sm text-slate-500" x-show="loading">Đang tải...</div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-left text-slate-600">
          <th class="py-2 px-4">Tên</th>
          <th class="py-2 px-4">Slug</th>
          <th class="py-2 px-4">Cấp cha</th>
          <th class="py-2 px-4">Thứ tự</th>
          <th class="py-2 px-4">Trạng thái</th>
          <th class="py-2 px-4 text-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="c in filtered()" :key="c.id">
          <tr class="border-t">
            <td class="py-2 px-4" x-text="c.name"></td>
            <td class="py-2 px-4" x-text="c.slug || ''"></td>
            <td class="py-2 px-4" x-text="parentName(c.parent_id)"></td>
            <td class="py-2 px-4" x-text="c.sort_order ?? 0"></td>
            <td class="py-2 px-4">
              <span class="px-2 py-0.5 rounded text-xs"
                :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                x-text="c.is_active ? 'Hiển thị' : 'Ẩn'"></span>
            </td>
            <td class="py-2 px-4 text-right space-x-2">
              <button class="px-2 py-1 rounded border hover:bg-gray-50" @click="openEditModal(c)">Sửa</button>
              <button class="px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50"
                @click="remove(c.id)">Xóa</button>
            </td>
          </tr>
        </template>
        <tr x-show="!loading && filtered().length===0">
          <td colspan="6" class="py-8 text-center text-slate-500">Chưa có loại sản phẩm.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity
    style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm loại sản phẩm</h3>
        <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
      </div>

      <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600 
           hover:bg-red-600 hover:text-white transition-colors" @click="openAdd=false">
            Hủy
          </button>
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
      <div class="px-5 py-3 border-b flex justify-between items-center">
        <h3 class="font-semibold">Sửa loại sản phẩm</h3>
        <button class="text-slate-500" @click="openEdit=false">✕</button>
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
</div>

<script>
  function categoryPage() {
    const api = {
      list: '/admin/api/categories',
      create: '/admin/categories',
      update: (id) => `/admin/categories/${id}`,
      remove: (id) => `/admin/categories/${id}/delete`,
      // lấy lại options parent nếu cần (dùng luôn list)
    };

    return {
      // state
      loading: true,
      submitting: false,
      search: '',
      openAdd: false,
      openEdit: false,

      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
      form: {
        id: null, name: '', slug: '', parent_id: '', sort_order: 0, is_active: 1
      },

      // lifecycle
      async init() {
        await this.fetchAll();
      },

      // helpers
      filtered() {
        if (!this.search) return this.items;
        const q = this.search.toLowerCase();
        return this.items.filter(c =>
          (c.name || '').toLowerCase().includes(q) ||
          (c.slug || '').toLowerCase().includes(q)
        );
      },
      parentName(pid) {
        if (!pid) return '—';
        const p = this.items.find(x => String(x.id) === String(pid));
        return p ? p.name : '—';
      },
      resetForm() {
        this.form = { id: null, name: '', slug: '', parent_id: '', sort_order: 0, is_active: 1 };
      },

      // data
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

      // ui actions
      openCreate() { this.resetForm(); this.openAdd = true; },
      openEditModal(c) {
        // tránh self-parent khi render form
        this.form = { ...c, parent_id: c.parent_id || '' };
        this.openEdit = true;
      },

      // CRUD
      async submitCreate() {
        if (!this.form.name?.trim()) { alert('Vui lòng nhập tên'); return; }
        // chặn tự chọn chính nó (khi thêm thì không có id, nhưng cứ phòng)
        if (this.form.id && String(this.form.parent_id) === String(this.form.id)) {
          alert('Loại cha không thể là chính nó'); return;
        }
        this.submitting = true;
        try {
          const r = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) throw new Error();
          const item = await r.json();
          this.items.unshift(item);
          this.openAdd = false;
        } catch {
          alert('Không thể thêm loại');
        } finally { this.submitting = false; }
      },

      async submitUpdate() {
        if (!this.form.id) return;
        if (!this.form.name?.trim()) { alert('Vui lòng nhập tên'); return; }
        if (String(this.form.parent_id) === String(this.form.id)) {
          alert('Loại cha không thể là chính nó'); return;
        }
        this.submitting = true;
        try {
          const r = await fetch(api.update(this.form.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) throw new Error();
          const item = await r.json();
          const i = this.items.findIndex(x => x.id == item.id);
          if (i > -1) this.items[i] = item; else this.items.unshift(item);
          this.openEdit = false;
        } catch {
          alert('Không thể cập nhật loại');
        } finally { this.submitting = false; }
      },

      async remove(id) {
        if (!confirm('Xóa loại này?')) return;
        try {
          const r = await fetch(api.remove(id), { method: 'POST' });
          if (!r.ok) throw new Error();
          this.items = this.items.filter(x => x.id != id);
        } catch {
          alert('Không thể xóa loại');
        }
      },
    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>
