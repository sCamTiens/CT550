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
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table style="width:200%; min-width:1200px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <th class="py-2 px-4 whitespace-nowrap text-center">Thao tác</th>
            <th class="py-2 px-4 whitespace-nowrap text-center">Ảnh</th>
            <?= textFilterPopover('sku', 'SKU') ?>
            <?= textFilterPopover('barcode', 'Mã vạch') ?>
            <?= textFilterPopover('name', 'Tên') ?>
            <?= textFilterPopover('slug', 'Slug') ?>
            <?= textFilterPopover('brand', 'Thương hiệu') ?>
            <?= textFilterPopover('category', 'Loại') ?>
            <?= numberFilterPopover('sale_price', 'Giá bán') ?>
            <?= numberFilterPopover('cost_price', 'Giá nhập') ?>
            <?= textFilterPopover('unit', 'Đơn vị tính') ?>
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
            <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
              <td class="py-2 px-4 space-x-2 text-center">
                <button @click="openEditModal(p)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Sửa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="remove(p.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
              <td class="py-2 px-4 text-center">
                <img :src="`/assets/images/products/${p.id}/1.png`" 
                     @error="$event.target.src='/assets/images/products/default.png'"
                     class="w-12 h-12 object-cover rounded-full border mx-auto"
                     :alt="p.name">
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="p.sku"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="p.barcode"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="p.name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="p.slug"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(p.brand_name || '—') === '—' ? 'text-center' : 'text-left'" x-text="p.brand_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(p.category_name || '—') === '—' ? 'text-center' : 'text-right'"
                x-text="p.category_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="formatCurrency(p.sale_price)">
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="formatCurrency(p.cost_price)">
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(p.unit_name || '—') === '—' ? 'text-center' : 'text-left'" x-text="p.unit_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                <span class="px-2 py-0.5 rounded text-xs"
                  :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  x-text="p.is_active ? 'Bán' : 'Ẩn'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                :class="(p.created_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="p.created_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(p.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="p.created_by_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right"
                :class="(p.updated_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="p.updated_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(p.updated_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="p.updated_by_name || '—'"></td>
            </tr>
          </template>

          <tr x-show="!loading && filtered().length===0">
            <td colspan="10" class="py-12 text-center text-slate-500">
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
    <div
      class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
      x-show="openAdd" x-transition.opacity style="display:none">
      <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
        @click.outside="openAdd=false" style="max-height: 90vh; overflow-y: auto;">
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
    <div
      class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
      x-show="openEdit" x-transition.opacity style="display:none">
      <div class="bg-white w-full max-w-3xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
        @click.outside="openEdit=false" style="max-height: 90vh; overflow-y: auto;">
        <div class="px-5 py-3 border-b flex justify-center items-center relative">
          <h3 class="font-semibold text-2xl text-[#002975]">Sửa sản phẩm</h3>
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
</div>

<script>
  function productPage() {
    const api = {
      list: '/admin/api/products',
      create: '/admin/products',
      update: (id) => `/admin/products/${id}`,
      remove: (id) => `/admin/products/${id}`,
      brands: '/admin/api/brands',
      categories: '/admin/api/categories',
      units: '/admin/api/units',
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
      units: [],

      // phân trang
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
        id: null,
        name: '',
        slug: '',
        sku: '',
        sale_price: 0,
        sale_priceFormatted: '',
        cost_price: 0,
        cost_priceFormatted: '',
        unit_id: '',
        brand_id: '',
        category_id: '',
        pack_size: '',
        barcode: '',
        description: '',
        is_active: 1,
        mainImage: null,
        mainImagePreview: '',
        subImages: []
      },

      errors: {
        name: '', sku: '', slug: '', sale_price: '', cost_price: '', brand_id: '', category_id: '',
        unit_id: '', pack_size: '', description: ''
      },

      touched: {
        name: false, sku: false, sale_price: false, cost_price: false, brand_id: false, category_id: false,
        unit_id: false, pack_size: false, description: ''
      },

      // lifecycle
      async init() {
        await this.fetchOptions();
        await this.fetchAll();
      },

      // --- utils ---
      formatCurrency(n) {
        try {
          return new Intl.NumberFormat('vi-VN').format(n || 0);
        } catch {
          return n;
        }
      },

      // ===== FILTERS =====
      openFilter: {
        sku: false, barcode: false, name: false, slug: false,
        brand: false, category: false, sale_price: false, cost_price: false,
        unit: false, status: false, created_at: false, created_by: false,
        updated_at: false, updated_by: false
      },

      filters: {
        sku: '', barcode: '', name: '', slug: '',
        brand: '', category: '',
        sale_price_type: '', sale_price_value: '', sale_price_from: '', sale_price_to: '',
        cost_price_type: '', cost_price_value: '', cost_price_from: '', cost_price_to: '',
        unit: '', status: '',
        created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
        created_by: '',
        updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
        updated_by: ''
      },

      // ------------------------------------------------------------------
      // Hàm lọc tổng quát — hỗ trợ TEXT, NUMBER, DATE
      // ------------------------------------------------------------------
      applyFilter(val, type, { value, from, to, dataType }) {
        if (val == null) return false;

        // ---------------- TEXT ----------------
        if (dataType === 'text') {
          const hasAccent = (s) => /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(s);
          const normalize = (str) => String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
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
            return hasAccent(query)
              ? raw.includes(query)
              : str.includes(queryNoAccent);
          }

          return true;
        }

        // ---------------- NUMBER ----------------
        if (dataType === 'number') {
          const parseNum = (v) => {
            if (v === '' || v === null || v === undefined) return null;
            const s = String(v).replace(/[^\d.-]/g, '');
            const n = Number(s);
            return isNaN(n) ? null : n;
          };

          const num = parseNum(val);
          const v = parseNum(value);
          const f = parseNum(from);
          const t = parseNum(to);

          if (num === null) return false;
          if (!type) return true;

          if (type === 'eq') return v === null ? true : num === v;
          if (type === 'lt') return v === null ? true : num < v;
          if (type === 'gt') return v === null ? true : num > v;
          if (type === 'lte') return v === null ? true : num <= v;
          if (type === 'gte') return v === null ? true : num >= v;
          if (type === 'between') return f === null || t === null ? true : num >= f && num <= t;

          if (type === 'like') {
            const raw = String(val).replace(/[^\d]/g, '');
            const query = String(value || '').replace(/[^\d]/g, '');
            return raw.includes(query);
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
            return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
          }
          if (type === 'lte') {
            if (!v) return true;
            const nextDay = new Date(v);
            nextDay.setDate(v.getDate() + 1);
            return d < nextDay; // cộng thêm 1 ngày
          }
          if (type === 'gte') return v ? d >= v : true;
          if (type === 'between') return f && t ? d >= f && d <= t : true;

          return true;
        }

        return true;
      },

      // ------------------------------------------------------------------
      // Áp dụng filter cho toàn bộ bảng
      // ------------------------------------------------------------------
      filtered() {
        let data = this.items;

        // --- Lọc theo text ---
        // Bao gồm các trường hiển thị tên như brand_name, category_name, created_by_name, updated_by_name
        ['sku', 'barcode', 'name', 'slug'].forEach(key => {
          if (this.filters[key]) {
            data = data.filter(o =>
              this.applyFilter(o[key], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo thương hiệu và loại (text hiển thị) ---
        ['brand', 'category', 'unit'].forEach(key => {
          if (this.filters[key]) {
            const field = key === 'brand' ? 'brand_name'
              : key === 'category' ? 'category_name'
                : key === 'unit' ? 'unit_name'
                  : key;
            data = data.filter(o =>
              this.applyFilter(o[field], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo người tạo / người cập nhật ---
        ['created_by', 'updated_by'].forEach(key => {
          if (this.filters[key]) {
            const field = `${key}_name`;
            data = data.filter(o =>
              this.applyFilter(o[field], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo select ---
        if (this.filters.status) {
          data = data.filter(o =>
            this.applyFilter(o.is_active ? '1' : '0', 'eq', {
              value: this.filters.status,
              dataType: 'text'
            })
          );
        }

        // --- Lọc theo số ---
        ['sale_price', 'cost_price'].forEach(key => {
          if (this.filters[`${key}_type`]) {
            data = data.filter(o =>
              this.applyFilter(o[key], this.filters[`${key}_type`], {
                value: this.filters[`${key}_value`],
                from: this.filters[`${key}_from`],
                to: this.filters[`${key}_to`],
                dataType: 'number'
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

      // ------------------------------------------------------------------
      // Mở / đóng / reset filter
      // ------------------------------------------------------------------
      toggleFilter(key) {
        for (const k in this.openFilter) this.openFilter[k] = false;
        this.openFilter[key] = true;
      },
      closeFilter(key) { this.openFilter[key] = false; },
      resetFilter(key) {
        if (['created_at', 'updated_at', 'sale_price', 'cost_price'].includes(key)) {
          this.filters[`${key}_type`] = '';
          this.filters[`${key}_value`] = '';
          this.filters[`${key}_from`] = '';
          this.filters[`${key}_to`] = '';
        } else {
          this.filters[key] = '';
        }
        this.openFilter[key] = false;
      },

      resetForm() {
        this.form = {
          id: null, name: '', slug: '', sku: '',
          sale_price: 0, sale_priceFormatted: '',
          cost_price: 0, cost_priceFormatted: '',
          unit_id: '', brand_id: '', category_id: '',
          pack_size: '', barcode: '', description: '',
          is_active: 1,
          mainImage: null,
          mainImagePreview: '',
          subImages: []
        };
        this.errors = { name: '', sku: '', slug: '', sale_price: '', cost_price: '', brand_id: '', category_id: '', unit_id: '', pack_size: '', description: '' };
        this.touched = { name: false, sku: false, slug: false, sale_price: false, cost_price: false, brand_id: false, category_id: false, unit_id: false, pack_size: false, description: false };
      },

      // ===== utilities =====
      slugify(s) {
        return (s || '')
          .toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '')
          .slice(0, 190);
      },
      onNameInput() {
        if (!this.form.id) this.form.slug = this.slugify(this.form.name);
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

      // chặn giá âm (dùng khi cần ép về 0)
      clampPriceValue() {
        let v1 = Number(this.form.sale_price);
        if (!Number.isFinite(v1) || v1 < 0) this.form.sale_price = 0;

        let v2 = Number(this.form.cost_price);
        if (!Number.isFinite(v2) || v2 < 0) this.form.cost_price = 0;
      },

      // Khi gõ vào ô input giá bán
      onSalePriceInput(e) {
        let raw = e.target.value.replace(/,/g, '');     // bỏ dấu phẩy
        let val = Number(raw);
        if (Number.isNaN(val)) val = 0;
        this.form.sale_price = val;                          // giá trị gốc (dùng để lưu DB)
        this.form.sale_priceFormatted = val.toLocaleString('en-US'); // hiển thị: 100,000
      },

      // Khi gõ vào ô input giá nhập
      onCostPriceInput(e) {
        let raw = e.target.value.replace(/,/g, '');     // bỏ dấu phẩy
        let val = Number(raw);
        if (Number.isNaN(val)) val = 0;
        this.form.cost_price = val;                          // giá trị gốc (dùng để lưu DB)
        this.form.cost_priceFormatted = val.toLocaleString('en-US'); // hiển thị: 100,000
      },

      // ===== IMAGE HANDLING =====
      onMainImageChange(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file
        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
          alert('Chỉ chấp nhận file PNG, JPG, JPEG');
          e.target.value = '';
          return;
        }

        if (file.size > 2 * 1024 * 1024) { // 2MB
          alert('Kích thước file không được vượt quá 2MB');
          e.target.value = '';
          return;
        }

        this.form.mainImage = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (evt) => {
          this.form.mainImagePreview = evt.target.result;
        };
        reader.readAsDataURL(file);
      },

      removeMainImage() {
        this.form.mainImage = null;
        this.form.mainImagePreview = '';
      },

      onSubImageChange(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        // Check if adding these files would exceed the limit
        if (this.form.subImages.length + files.length > 5) {
          alert('Tối đa 5 ảnh phụ');
          e.target.value = '';
          return;
        }

        files.forEach(file => {
          // Validate file
          if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            alert('Chỉ chấp nhận file PNG, JPG, JPEG');
            return;
          }

          if (file.size > 2 * 1024 * 1024) { // 2MB
            alert('Kích thước file không được vượt quá 2MB');
            return;
          }

          // Create preview
          const reader = new FileReader();
          reader.onload = (evt) => {
            this.form.subImages.push({
              file: file,
              preview: evt.target.result
            });
          };
          reader.readAsDataURL(file);
        });

        e.target.value = '';
      },

      removeSubImage(index) {
        this.form.subImages.splice(index, 1);
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

        if (field === 'slug') {
          if (!this.form.slug?.trim()) this.errors.slug = 'Slug không được bỏ trống';
          else if ((this.form.slug || '').length > MAXLEN) this.errors.slug = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'unit_id') {
          if ((this.form.unit_id || '').length > MAXLEN) this.errors.unit_id = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'pack_size') {
          if ((this.form.pack_size || '').length > MAXLEN) this.errors.pack_size = `Không vượt quá ${MAXLEN} ký tự`;
        }

        if (field === 'description') {
          if ((this.form.description || '').length > MAXDESC) this.errors.description = `Không vượt quá ${MAXDESC} ký tự`;
        }

        if (field === 'sale_price') {
          const raw = (this.form.sale_priceFormatted || '').replace(/,/g, '').trim();
          const val = Number(raw);

          if (!raw) {
            this.errors.sale_price = 'Giá bán không được bỏ trống';
          } else if (Number.isNaN(val)) {
            this.errors.sale_price = 'Giá bán phải là số';
          } else if (val < 0) {
            this.errors.sale_price = 'Giá bán phải >= 0';
          } else if (val > MAX_PRICE) {
            this.errors.sale_price = 'Giá bán không vượt quá 1.000.000.000';
          } else {
            this.errors.sale_price = '';
            this.form.sale_price = val;
          }
        }

        if (field === 'cost_price') {
          const raw = (this.form.cost_priceFormatted || '').replace(/,/g, '').trim();
          const val = Number(raw);

          if (!raw) {
            this.errors.cost_price = 'Giá nhập không được bỏ trống';
          } else if (Number.isNaN(val)) {
            this.errors.cost_price = 'Giá nhập phải là số';
          } else if (val < 0) {
            this.errors.cost_price = 'Giá nhập phải >= 0';
          } else if (val > MAX_PRICE) {
            this.errors.cost_price = 'Giá nhập không vượt quá 1.000.000.000';
          } else {
            this.errors.cost_price = '';
            this.form.cost_price = val;
          }
        }

        if (field === 'brand_id') {
          if (!String(this.form.brand_id || '').trim()) this.errors.brand_id = 'Vui lòng chọn thương hiệu';
        }

        if (field === 'category_id') {
          if (!String(this.form.category_id || '').trim()) this.errors.category_id = 'Vui lòng chọn loại sản phẩm';
        }

        if (field === 'unit_id') {
          if (!String(this.form.unit_id || '').trim()) this.errors.unit_id = 'Vui lòng chọn đơn vị tính';
        }
      },

      // ===== validate khi submit =====
      validateForm() {
        this.errors = {
          name: '', sku: '', slug: '', sale_price: '', cost_price: '', brand_id: '', category_id: '',
          unit_id: '', pack_size: '', description: ''
        };
        let ok = true;

        // gọi validateField cho tất cả fields
        ['name', 'sku', 'slug', 'sale_price', 'cost_price', 'brand_id', 'category_id', 'unit_id', 'pack_size', 'description'].forEach(f => {
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
            this.brands = data.items;
          }
        } catch (e) { console.error(e); }

        try {
          const r = await fetch(api.categories);
          if (r.ok) {
            const data = await r.json();
            this.categories = data.items;
          }
        } catch (e) { console.error(e); }

        try {
          const r = await fetch(api.units);
          if (r.ok) {
            const data = await r.json();
            this.units = data.items;
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

      async openEditModal(p) {
        this.resetForm();

        // Tìm id tương ứng với tên để fill lại
        const brand = this.brands.find(b => b.name === p.brand_name);
        const category = this.categories.find(c => c.name === p.category_name);
        const unit = this.units.find(u => u.name === p.unit_name);

        this.form = {
          id: p.id,
          name: p.name || '',
          slug: p.slug || '',
          sku: p.sku || '',
          sale_price: Number(p.sale_price) || 0,
          sale_priceFormatted: Number(p.sale_price || 0).toLocaleString('en-US'),
          cost_price: Number(p.cost_price) || 0,
          cost_priceFormatted: Number(p.cost_price || 0).toLocaleString('en-US'),
          unit_id: p.unit_id || (unit ? unit.id : ''),
          brand_id: p.brand_id || (brand ? brand.id : ''),
          category_id: p.category_id || (category ? category.id : ''),
          pack_size: p.pack_size || '',
          barcode: p.barcode || '',
          description: p.description || '',
          is_active: p.is_active == 1,
          mainImage: null,
          mainImagePreview: '',
          subImages: []
        };

        // Mở modal trước
        this.openEdit = true;

        // Đợi modal render xong rồi mới load ảnh
        await this.$nextTick();

        // Load ảnh chính sau khi modal đã mở
        const mainImageUrl = `/assets/images/products/${p.id}/1.png?t=${Date.now()}`;
        try {
          const checkImg = new Image();
          await new Promise((resolve, reject) => {
            checkImg.onload = () => {
              // Set preview SAU KHI ảnh đã load xong
              this.form.mainImagePreview = mainImageUrl;
              console.log('Ảnh chính đã load:', mainImageUrl);
              resolve();
            };
            checkImg.onerror = () => {
              console.warn('Ảnh chính không tồn tại:', mainImageUrl);
              reject();
            };
            checkImg.src = mainImageUrl;
          });
        } catch (e) {
          console.log('Không tìm thấy ảnh chính');
        }

        // Load ảnh phụ
        for (let i = 2; i <= 6; i++) {
          try {
            const img = new Image();
            await new Promise((resolve, reject) => {
              img.onload = () => {
                this.form.subImages.push({
                  file: null,
                  preview: img.src,
                  existing: true
                });
                resolve();
              };
              img.onerror = reject;
              img.src = `/assets/images/products/${p.id}/${i}.png?t=${Date.now()}`;
            });
          } catch (e) {
            // Bỏ qua nếu ảnh không tồn tại
          }
        }
      },

      // CRUD
      async submitCreate() {
        if (!this.validateForm()) return;

        // Validate main image
        if (!this.form.mainImage) {
          this.showToast('Vui lòng chọn ảnh chính cho sản phẩm');
          return;
        }

        this.submitting = true;
        try {
          if (!this.form.barcode || !/^\d{13}$/.test(this.form.barcode)) {
            this.form.barcode = this.generateEAN13();
          }

          // Step 1: Create product first
          const r = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form)
          });
          if (!r.ok) throw new Error((await r.json()).error || 'Không thể thêm sản phẩm');
          const product = await r.json();

          // Step 2: Upload images
          const formData = new FormData();
          formData.append('product_id', product.id);
          formData.append('main_image', this.form.mainImage);

          this.form.subImages.forEach((img, idx) => {
            formData.append(`sub_images[]`, img.file);
          });

          const uploadRes = await fetch('/admin/api/products/upload-images', {
            method: 'POST',
            body: formData
          });

          if (!uploadRes.ok) {
            console.error('Lỗi upload ảnh:', await uploadRes.text());
            this.showToast('Sản phẩm đã tạo nhưng có lỗi khi upload ảnh', 'warning');
          }

          this.items.unshift(product);
          this.openAdd = false;
          this.resetForm();
          this.showToast('Thêm sản phẩm thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể thêm sản phẩm');
        } finally { this.submitting = false; }
      },

      async submitUpdate() {
        if (!this.form.id) return;
        if (!this.validateForm()) return;
        this.submitting = true;
        try {
          // Clone form và ép boolean -> 1/0
          const payload = {
            ...this.form,
            is_active: this.form.is_active ? 1 : 0
          };

          const r = await fetch(api.update(this.form.id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });

          const res = await r.json();
          if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');

          // Upload images if there are new ones
          const hasNewMainImage = this.form.mainImage !== null;
          const hasNewSubImages = this.form.subImages.some(img => img.file !== null);

          if (hasNewMainImage || hasNewSubImages) {
            const formData = new FormData();
            formData.append('product_id', this.form.id);

            if (hasNewMainImage) {
              formData.append('main_image', this.form.mainImage);
            }

            this.form.subImages.forEach((img, idx) => {
              if (img.file) {
                formData.append(`sub_images[]`, img.file);
              }
            });

            const uploadRes = await fetch('/admin/api/products/upload-images', {
              method: 'POST',
              body: formData
            });

            if (!uploadRes.ok) {
              console.error('Lỗi upload ảnh:', await uploadRes.text());
              this.showToast('Sản phẩm đã cập nhật nhưng có lỗi khi upload ảnh', 'warning');
            }
          }

          const i = this.items.findIndex(x => x.id == res.id);
          if (i > -1) this.items[i] = res; else this.items.unshift(res);
          this.openEdit = false;
          this.resetForm();
          this.showToast('Cập nhật sản phẩm thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể cập nhật sản phẩm');
        } finally {
          this.submitting = false;
        }
      },

      async remove(id) {
        if (!confirm('Xóa sản phẩm này?')) return;
        try {
          const r = await fetch(api.remove(id), { method: 'DELETE' });
          if (!r.ok) {
            const txt = await r.text();   // đọc thô để debug
            throw new Error(`Server error: ${txt}`);
          }
          const res = await r.json();
          this.items = this.items.filter(x => x.id != id);
          this.showToast('Xóa sản phẩm thành công!', 'success');
        } catch (e) {
          this.showToast(e.message || 'Không thể xóa sản phẩm');
        }
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

    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>