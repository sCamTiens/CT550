<?php
// views/admin/products/product.php

// (tuỳ chọn) dữ liệu fallback để tránh notice khi chưa có items
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Danh mục sản phẩm / <span class="text-slate-800 font-medium">Sản phẩm</span>
</nav>

<div x-data="productPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý sản phẩm</h1>
    <button
      class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
      @click="openCreate()">+ Thêm sản phẩm</button>
  </div>

  <!-- Toolbar -->
  <div class="bg-white rounded-xl shadow p-4 mb-4 flex items-center gap-3">
    <input x-model.trim="search" placeholder="Tìm theo tên / SKU..." class="border rounded px-3 py-2 w-72">
    <div class="text-sm text-slate-500" x-show="loading">Đang tải...</div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-left text-slate-600">
          <th class="py-2 px-4">SKU</th>
          <th class="py-2 px-4">Tên</th>
          <th class="py-2 px-4">Thương hiệu</th>
          <th class="py-2 px-4">Loại</th>
          <th class="py-2 px-4">Giá</th>
          <th class="py-2 px-4">Trạng thái</th>
          <th class="py-2 px-4 text-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="p in filtered()" :key="p.id">
          <tr class="border-t">
            <td class="py-2 px-4" x-text="p.sku"></td>
            <td class="py-2 px-4" x-text="p.name"></td>
            <td class="py-2 px-4" x-text="p.brand_name || ''"></td>
            <td class="py-2 px-4" x-text="p.category_name || ''"></td>
            <td class="py-2 px-4" x-text="formatCurrency(p.price)"></td>
            <td class="py-2 px-4">
              <span class="px-2 py-0.5 rounded text-xs"
                :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                x-text="p.is_active ? 'Bán' : 'Ẩn'"></span>
            </td>
            <td class="py-2 px-4 text-right space-x-2">
              <button class="px-2 py-1 rounded border hover:bg-gray-50" @click="openEditModal(p)">Sửa</button>
              <button class="px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50"
                @click="remove(p.id)">Xóa</button>
            </td>
          </tr>
        </template>
        <tr x-show="!loading && filtered().length===0">
          <td colspan="7" class="py-8 text-center text-slate-500">Chưa có sản phẩm.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- MODAL: Create -->
  <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openAdd" x-transition.opacity
    style="display:none">
    <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm sản phẩm</h3>
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
    <div class="bg-white w-full max-w-3xl rounded-xl shadow" @click.outside="openEdit=false">
      <div class="px-5 py-3 border-b flex justify-between items-center">
        <h3 class="font-semibold">Sửa sản phẩm</h3>
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
  function productPage() {
    const api = {
      list: '/admin/api/products',
      create: '/admin/products',
      update: (id) => `/admin/products/${id}`,
      remove: (id) => `/admin/products/${id}/delete`,
      brands: '/admin/api/brands',
      categories: '/admin/api/categories',
    };

    return {
      // state
      loading: true,
      submitting: false,
      search: '',
      openAdd: false,
      openEdit: false,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
      brands: [],
      categories: [],
      form: {
        id: null, name: '', sku: '', price: 0, unit: '',
        brand_id: '', category_id: '', pack_size: '', barcode: '',
        description: '', is_active: 1
      },

      // lifecycle
      async init() {
        await this.fetchOptions();
        await this.fetchAll();
      },

      // helpers
      formatCurrency(n) { try { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0) } catch { return n } },
      filtered() {
        if (!this.search) return this.items;
        const q = this.search.toLowerCase();
        return this.items.filter(p =>
          (p.name || '').toLowerCase().includes(q) ||
          (p.sku || '').toLowerCase().includes(q)
        );
      },
      resetForm() {
        this.form = { id: null, name: '', sku: '', price: 0, unit: '', brand_id: '', category_id: '', pack_size: '', barcode: '', description: '', is_active: 1 };
      },

      // data fetch
      async fetchOptions() {
        try { const r = await fetch(api.brands); if (r.ok) { this.brands = await r.json(); } } catch { }
        try { const r = await fetch(api.categories); if (r.ok) { this.categories = await r.json(); } } catch { }
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

      // ui actions
      openCreate() { this.resetForm(); this.openAdd = true; },
      openEditModal(p) { this.form = { ...p }; this.openEdit = true; },

      // CRUD
      async submitCreate() {
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
          alert('Không thể thêm sản phẩm');
        } finally { this.submitting = false; }
      },

      async submitUpdate() {
        if (!this.form.id) return;
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
          alert('Không thể cập nhật sản phẩm');
        } finally { this.submitting = false; }
      },

      async remove(id) {
        if (!confirm('Xóa sản phẩm này?')) return;
        try {
          const r = await fetch(api.remove(id), { method: 'POST' });
          if (!r.ok) throw new Error();
          this.items = this.items.filter(x => x.id != id);
        } catch {
          alert('Không thể xóa sản phẩm');
        }
      },
    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>