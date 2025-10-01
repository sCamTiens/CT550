<?php
// views/admin/products/product.php
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

  <!-- Table -->
  <div class="bg-white rounded-xl shadow overflow-x-auto pb-40">
    <table class="min-w-max text-sm"> <!-- đổi từ min-w-full => min-w-max -->
      <thead>
        <tr class="bg-gray-50 text-left text-slate-600">
          <th class="py-2 px-4 whitespace-nowrap">Thao tác</th>
          <?= textFilterPopover('sku', 'SKU') ?>
          <?= textFilterPopover('name', 'Tên') ?>
          <?= textFilterPopover('brand', 'Thương hiệu') ?>
          <?= textFilterPopover('category', 'Loại') ?>
          <?= numberFilterPopover('price', 'Giá') ?>
          <?= selectFilterPopover('status', 'Trạng thái', [
            '' => '-- Tất cả --',
            '1' => 'Bán',
            '0' => 'Ẩn'
          ]) ?>
          <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
          <?= textFilterPopover('created_by', 'Người tạo') ?>
          <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
          <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
        </tr>
      </thead>

      <tbody>
        <template x-for="p in paginated()" :key="p.id">
          <tr class="border-t">
            <td class="py-2 px-4 space-x-2">
              <button @click="openEditModal(p)" class="p-2 rounded hover:bg-gray-100 text-[#002975]"
                title="Sửa">✎</button>
              <button @click="remove(p.id)" class="p-2 rounded hover:bg-gray-100 text-red-600" title="Xóa">🗑</button>
            </td>
            <td class="py-2 px-4" x-text="p.sku"></td>
            <td class="py-2 px-4" x-text="p.name"></td>
            <td class="py-2 px-4" x-text="p.brand_name || ''"></td>
            <td class="py-2 px-4" x-text="p.category_name || ''"></td>
            <td class="py-2 px-4" x-text="formatCurrency(p.sale_price)"></td>
            <td class="py-2 px-4">
              <span class="px-2 py-0.5 rounded text-xs"
                :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                x-text="p.is_active ? 'Bán' : 'Ẩn'"></span>
            </td>
            <td class="py-2 px-4" x-text="p.created_at || '—'"></td>
            <td class="py-2 px-4" x-text="p.created_by_name || '—'"></td>
            <td class="py-2 px-4" x-text="p.updated_at || '—'"></td>
            <td class="py-2 px-4" x-text="p.updated_by_name || '—'"></td>
          </tr>
        </template>

        <tr x-show="!loading && filtered().length===0">
          <td colspan="11" class="py-12 text-center text-slate-500">
            <div class="flex flex-col items-center justify-center">
              <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
              <div class="text-lg text-slate-300">Trống</div>
            </div>
          </td>
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
  function productPage() {
    const api = {
      list: '/admin/api/products',
      create: '/admin/products',
      update: (id) => `/admin/products/${id}`,
      remove: (id) => `/admin/products/${id}/delete`,
      brands: '/admin/api/brands',
      categories: '/admin/api/categories',
    };

    const MAX_PRICE = 1_000_000_000;
    const MAXLEN = 255;
    const MAXDESC = 500;

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

      // pagination
      currentPage: 1,
      perPage: 20,
      perPageOptions: [5, 10, 20, 50, 100],

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

      form: {
        id: null, name: '', sku: '', price: 0, priceFormatted: '', // thêm priceFormatted
        unit: '', brand_id: '', category_id: '', pack_size: '', barcode: '',
        description: '', is_active: 1
      },

      // Khi gõ vào ô input giá
      onPriceInput(e) {
        let raw = e.target.value.replace(/,/g, '');     // bỏ dấu phẩy
        let val = Number(raw);
        if (Number.isNaN(val)) val = 0;
        this.form.price = val;                          // giá trị gốc (dùng để lưu DB)
        this.form.priceFormatted = val.toLocaleString('en-US'); // hiển thị: 100,000
      },

      // inline errors + touched (để hiện lỗi khi blur)
      errors: {
        name: '', sku: '', price: '', brand_id: '', category_id: '',
        unit: '', pack_size: '', description: ''
      },

      touched: {
        name: false, sku: false, price: false, brand_id: false, category_id: false,
        unit: false, pack_size: false, description: false
      },

      // lifecycle
      async init() {
        await this.fetchOptions();
        await this.fetchAll();
      },

      // helpers
      formatCurrency(n) {
        try { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0) }
        catch { return n }
      },
      filtered() {
        if (!this.search) return this.items;
        const q = this.search.toLowerCase();
        return this.items.filter(p =>
          (p.name || '').toLowerCase().includes(q) ||
          (p.sku || '').toLowerCase().includes(q)
        );
      },
      resetForm() {
        this.form = {
          id: null, name: '', sku: '', price: 0, unit: '',
          brand_id: '', category_id: '', pack_size: '', barcode: '',
          description: '', is_active: 1
        };
        this.errors = {
          name: '', sku: '', price: '', brand_id: '', category_id: '',
          unit: '', pack_size: '', description: ''
        };
        this.touched = {
          name: false, sku: false, price: false, brand_id: false, category_id: false,
          unit: false, pack_size: false, description: false
        };
      },

      // ===== Barcode helpers (EAN-13, prefix 893) =====
      randomDigits(n) { return Array.from({ length: n }, () => Math.floor(Math.random() * 10)).join(''); },
      ean13CheckDigit12(d12) {
        let sum = 0;
        for (let i = 0; i < 12; i++) {
          const num = d12.charCodeAt(i) - 48;
          sum += (i % 2 === 0) ? num : num * 3;
        }
        const mod = sum % 10;
        return (10 - mod) % 10;
      },
      generateEAN13() {
        const prefix = '893';
        const core9 = this.randomDigits(9);
        const d12 = prefix + core9;
        const cd = this.ean13CheckDigit12(d12);
        return d12 + cd;
      },
      // ===== SKU helpers =====
      generateSKU() {
        const date = new Date();
        const ymd = date.toISOString().slice(0, 10).replace(/-/g, ''); // 20251001
        const rand = Math.random().toString(36).substring(2, 5).toUpperCase(); // 3 ký tự random
        return `SP-${ymd}-${rand}`;
      },


      // toast
      showToast(msg) {
        const box = document.getElementById('toast-container');
        if (!box) return;
        box.innerHTML = `
          <div class="fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold text-red-700 bg-white rounded-xl shadow-lg border-2 border-red-400">
            <svg class="flex-shrink-0 w-6 h-6 text-red-600 me-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
            </svg>
            <div class="flex-1">${msg}</div>
          </div>`;
        setTimeout(() => { box.innerHTML = '' }, 3000);
      },

      // chặn giá âm (dùng khi cần ép về 0)
      clampPriceValue() {
        const v = Number(this.form.price);
        if (!Number.isFinite(v) || v < 0) this.form.price = 0;
      },

      // ===== validate 1 field (gọi khi blur / input) =====
      validateField(field) {
        this.errors[field] = '';

        if (field === 'name') {
          if (!this.form.name?.trim()) this.errors.name = 'Tên không được bỏ trống';
          else if ((this.form.name || '').length > MAXLEN) this.errors.name = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'sku') {
          if (!this.form.sku?.trim()) this.errors.sku = 'SKU không được bỏ trống';
          else if ((this.form.sku || '').length > MAXLEN) this.errors.sku = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'unit') {
          if ((this.form.unit || '').length > MAXLEN) this.errors.unit = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'pack_size') {
          if ((this.form.pack_size || '').length > MAXLEN) this.errors.pack_size = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'description') {
          if ((this.form.description || '').length > MAXDESC) this.errors.description = `Không vượt quá ${MAXDESC} ký tự`;
        }

        if (field === 'price') {
          const raw = (this.form.priceFormatted || '').replace(/,/g, '').trim();
          const val = Number(raw);

          if (!raw) {
            this.errors.price = 'Giá bán không được bỏ trống';
          } else if (Number.isNaN(val)) {
            this.errors.price = 'Giá bán phải là số';
          } else if (val < 0) {
            this.errors.price = 'Giá bán phải là số không âm';
          } else if (val > MAX_PRICE) {
            this.errors.price = 'Giá bán không vượt quá 1.000.000.000';
          } else {
            this.errors.price = '';
          }
        }

        if (field === 'brand_id') {
          if (!String(this.form.brand_id || '').trim()) this.errors.brand_id = 'Vui lòng chọn thương hiệu';
        }

        if (field === 'category_id') {
          if (!String(this.form.category_id || '').trim()) this.errors.category_id = 'Vui lòng chọn loại sản phẩm';
        }
      },

      // ===== validate khi submit =====
      validateForm() {
        this.errors = {
          name: '', sku: '', price: '', brand_id: '', category_id: '',
          unit: '', pack_size: '', description: ''
        };
        let ok = true;

        // gọi validateField cho tất cả fields
        ['name', 'sku', 'price', 'brand_id', 'category_id', 'unit', 'pack_size', 'description'].forEach(f => {
          this.touched[f] = true;
          this.validateField(f);
          if (this.errors[f]) ok = false;
        });

        if (!ok) {
          const first = Object.values(this.errors).find(x => !!x);
          this.showToast(first || 'Vui lòng kiểm tra lại dữ liệu');
        }
        return ok;
      },

      // data fetch
      async fetchOptions() {
        try {
          const r = await fetch(api.brands);
          if (r.ok) {
            const data = await r.json();
            this.brands = data.items || [];   // ✅ lấy đúng mảng
          }
        } catch (e) { console.error(e); }

        try {
          const r = await fetch(api.categories);
          if (r.ok) {
            const data = await r.json();
            this.categories = data.items || [];  // ✅ lấy đúng mảng
          }
        } catch (e) { console.error(e); }
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
      openCreate() {
        this.resetForm();
        this.form.barcode = this.generateEAN13();
        this.form.sku = this.generateSKU();
        this.openAdd = true;
      },
      openEditModal(p) {
        this.resetForm();
        this.form = { ...p };
        this.openEdit = true;
      },

      // CRUD
      async submitCreate() {
        if (!this.validateForm()) return;
        this.submitting = true;
        try {
          if (!this.form.barcode || !/^\d{13}$/.test(this.form.barcode)) {
            this.form.barcode = this.generateEAN13();
          }
          const r = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) {
            let msg = 'Không thể thêm sản phẩm';
            try { const data = await r.json(); if (data?.message) msg = data.message; } catch { }
            throw new Error(msg);
          }
          const item = await r.json();
          this.items.unshift(item);
          this.openAdd = false;
        } catch (e) {
          this.showToast(e.message || 'Không thể thêm sản phẩm');
        } finally { this.submitting = false; }
      },

      async submitUpdate() {
        if (!this.form.id) return;
        if (!this.validateForm()) return;
        this.submitting = true;
        try {
          const r = await fetch(api.update(this.form.id), {
            method: 'PUT', // đổi thành PUT
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });

          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');

          const i = this.items.findIndex(x => x.id == res.id);
          if (i > -1) {
            this.items[i] = res;
          } else {
            this.items.unshift(res);
          }

          this.openEdit = false;
        } catch (e) {
          this.showToast(e.message || 'Không thể cập nhật sản phẩm');
        } finally {
          this.submitting = false;
        }
      },

      async remove(id) {
        if (!confirm('Xóa sản phẩm này?')) return;
        try {
          const r = await fetch(api.remove(id), { method: 'DELETE' }); // đổi thành DELETE
          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ khi xóa');
          this.items = this.items.filter(x => x.id != id);
        } catch (e) {
          this.showToast(e.message || 'Không thể xóa sản phẩm');
        }
      },
    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>