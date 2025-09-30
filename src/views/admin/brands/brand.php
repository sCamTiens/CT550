<?php
// views/admin/brands/brand.php

// (fallback) tránh notice khi chưa có dữ liệu
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Danh mục sản phẩm /
  <span class="text-slate-800 font-medium">Thương hiệu</span>
</nav>

<div x-data="brandPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý thương hiệu</h1>
    <button class="px-3 py-2 rounded-lg font-semibold text-[#002975] border border-[#002975]
             hover:bg-[#002975] hover:text-white transition-colors" @click="openCreate()">
      + Thêm thương hiệu
    </button>
  </div>

  <!-- Toolbar -->
  <div class="bg-white rounded-xl shadow p-4 mb-4 flex items-center gap-3">
    <input x-model.trim="search" placeholder="Tìm theo tên / slug..."
      class="border rounded px-3 py-2 w-72 focus:outline-none focus:ring-2 focus:ring-[#002975]">
    <div class="text-sm text-slate-500" x-show="loading">Đang tải...</div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-left text-slate-600">
          <th class="py-2 px-4 w-24">ID</th>
          <th class="py-2 px-4">Tên</th>
          <th class="py-2 px-4">Slug</th>
          <th class="py-2 px-4 w-56">Tạo lúc</th>
          <th class="py-2 px-4 text-right w-44">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="b in filtered()" :key="b.id">
          <tr class="border-t">
            <td class="py-2 px-4" x-text="b.id"></td>
            <td class="py-2 px-4" x-text="b.name"></td>
            <td class="py-2 px-4">
              <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700" x-text="b.slug || ''"></span>
            </td>
            <td class="py-2 px-4" x-text="formatDT(b.created_at)"></td>
            <td class="py-2 px-4 text-right space-x-2">
              <button class="px-2 py-1 rounded border border-[#002975] text-[#002975]
                       hover:bg-[#002975] hover:text-white transition-colors" @click="openEditModal(b)">
                Sửa
              </button>
              <button class="px-2 py-1 rounded border border-red-400 text-red-600 hover:bg-red-50"
                @click="remove(b.id)">
                Xóa
              </button>
            </td>
          </tr>
        </template>
        <tr x-show="!loading && filtered().length===0">
          <td colspan="5" class="py-8 text-center text-slate-500">Chưa có thương hiệu.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity
    style="display:none">
    <div class="bg-white w-full max-w-lg rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm thương hiệu</h3>
        <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
        <div class="space-y-1">
          <label class="text-sm text-slate-600">Tên <span class="text-red-500">*</span></label>
          <input class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#002975]"
            x-model.trim="form.name" @input="autoSlug()" placeholder="Nhập tên thương hiệu" required>
        </div>
        <div class="space-y-1">
          <label class="text-sm text-slate-600">Slug</label>
          <input class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#002975]"
            x-model.trim="form.slug" placeholder="Nhập slug thương hiệu">
        </div>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600
                         hover:bg-red-600 hover:text-white transition-colors" @click="openAdd=false">
            Hủy
          </button>
          <button class="px-4 py-2 rounded-md text-[#002975] border border-[#002975]
                   hover:bg-[#002975] hover:text-white transition-colors" :disabled="submitting"
            x-text="submitting?'Đang lưu...':'Lưu'">
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Edit -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEdit"
    x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-lg rounded-xl shadow" @click.outside="openEdit=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Sửa thương hiệu</h3>
        <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
        <div class="space-y-1">
          <label class="text-sm text-slate-600">Tên <span class="text-red-500">*</span></label>
          <input class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#002975]"
            x-model.trim="form.name" @input="autoSlug()" required>
        </div>
        <div class="space-y-1">
          <label class="text-sm text-slate-600">Slug</label>
          <input class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#002975]"
            x-model.trim="form.slug">
        </div>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600
                         hover:bg-red-600 hover:text-white transition-colors" @click="openEdit=false">
            Đóng
          </button>
          <button class="px-4 py-2 rounded-md text-[#002975] border border-[#002975]
                   hover:bg-[#002975] hover:text-white transition-colors" :disabled="submitting"
            x-text="submitting?'Đang lưu...':'Cập nhật'">
          </button>
        </div>
      </form>
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
      // state
      loading: true,
      submitting: false,
      search: '',
      openAdd: false,
      openEdit: false,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
      form: { id: null, name: '', slug: '' },

      // lifecycle
      async init() { await this.fetchAll(); },

      // helpers
      filtered() {
        if (!this.search) return this.items;
        const q = this.search.toLowerCase();
        return this.items.filter(b =>
          (b.name || '').toLowerCase().includes(q) ||
          (b.slug || '').toLowerCase().includes(q)
        );
      },
      resetForm() { this.form = { id: null, name: '', slug: '' }; },
      autoSlug() {
        if (!this.form.name) return;
        this.form.slug = this.form.name
          .toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      },
      formatDT(s) { return s ? new Date(s).toLocaleString('vi-VN') : ''; },

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
      openEditModal(b) { this.form = { ...b }; this.openEdit = true; },

      // CRUD
      async submitCreate() {
        this.submitting = true;
        try {
          const r = await fetch(api.create, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) throw new Error();
          const item = await r.json();
          this.items.unshift(item); this.openAdd = false;
        } catch { alert('Không thể thêm thương hiệu'); }
        finally { this.submitting = false; }
      },

      async submitUpdate() {
        if (!this.form.id) return;
        this.submitting = true;
        try {
          const r = await fetch(api.update(this.form.id), {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) throw new Error();
          const item = await r.json();
          const i = this.items.findIndex(x => x.id == item.id);
          if (i > -1) this.items[i] = item; else this.items.unshift(item);
          this.openEdit = false;
        } catch { alert('Không thể cập nhật thương hiệu'); }
        finally { this.submitting = false; }
      },

      async remove(id) {
        if (!confirm('Xóa thương hiệu này?')) return;
        try {
          const r = await fetch(api.remove(id), { method: 'POST' });
          if (!r.ok) throw new Error();
          this.items = this.items.filter(x => x.id != id);
        } catch { alert('Không thể xóa thương hiệu'); }
      }
    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>