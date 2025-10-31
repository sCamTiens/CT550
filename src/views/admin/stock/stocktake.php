<?php
// views/admin/stock/stocktake.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Quản lý kho / <span class="text-slate-800 font-medium">Kiểm kê kho</span>
</nav>

<div x-data="stocktakePage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý kiểm kê kho</h1>
    <button @click="openCreateModal()"
      class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] flex items-center gap-2">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Tạo phiếu kiểm kê
    </button>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow pb-4">
    <!-- Loading overlay bên trong bảng -->
    <template x-if="loading">
      <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
        </div>
        </template> <div style="overflow-x:auto; max-width:100%;" class="pb-40">
        <table style="width:100%; min-width:1200px; border-collapse:collapse;">
          <thead>
            <tr class="bg-gray-50 text-slate-600">
              <th class="py-2 px-4 text-center" style="min-width: 120px;">Thao tác</th>
              <?= textFilterPopover('id', 'Mã kiểm kê') ?>
              <?= textFilterPopover('created_by_name', 'Người tạo') ?>
              <?= dateFilterPopover('created_at', 'Ngày tạo') ?>
              <?= textFilterPopover('note', 'Ghi chú') ?>
            </tr>
          </thead>
          <tbody>
            <template x-for="s in paginated()" :key="s.id">
              <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                <td class="py-2 px-4 text-center">
                  <button @click.stop="viewDetail(s.id)"
                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                    title="Xem chi tiết">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </td>
                <td class="py-2 px-4 break-words whitespace-pre-line text-center" x-text="s.id"></td>
                <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.created_by_name"></td>
                <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="s.created_at"></td>
                <td class="py-2 px-4 break-words whitespace-pre-line"
                  :class="(s.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="s.note || '—'"></td>
              </tr>
            </template>
            <tr x-show="!loading && filtered().length===0">
              <td colspan="5" class="py-12 text-center text-slate-500">
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

  <!-- Modal Tạo Kiểm Kê -->
  <div x-show="showCreateModal" @click.self="showCreateModal = false"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg w-full max-w-6xl max-h-[90vh] flex flex-col">
      <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="text-xl font-bold text-[#002975]">Tạo Phiếu Kiểm Kê Kho</h3>
        <button @click="showCreateModal = false" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto flex-1">
        <!-- Ghi chú -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
          <textarea x-model="newStocktake.note" rows="2"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Nhập ghi chú cho phiếu kiểm kê..."></textarea>
        </div>

        <!-- Tìm kiếm sản phẩm -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm sản phẩm</label>
          <input type="text" x-model="productSearch" @input="filterProducts()"
            placeholder="Tìm theo tên, mã sản phẩm..."
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Bảng sản phẩm -->
        <div class="border rounded-lg overflow-hidden">
          <div class="overflow-x-auto max-h-96">
            <table class="w-full">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tồn kho hệ thống</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kiểm kê thực tế</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Chênh lệch</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <template x-for="p in filteredProducts" :key="p.id">
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm" x-text="p.id"></td>
                    <td class="px-4 py-3 text-sm" x-text="p.name"></td>
                    <td class="px-4 py-3 text-sm text-right font-medium" x-text="p.stock_quantity"></td>
                    <td class="px-4 py-3 text-sm">
                      <input type="number" min="0" :value="newStocktake.items[p.id]?.actual_quantity ?? ''"
                        @input="updateActualQuantity(p.id, $event.target.value, p.stock_quantity)"
                        class="w-full px-2 py-1 border rounded text-right focus:ring-2 focus:ring-blue-500"
                        placeholder="0">
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-bold" :class="{
                        'text-red-600': (newStocktake.items[p.id]?.difference ?? 0) < 0,
                        'text-green-600': (newStocktake.items[p.id]?.difference ?? 0) > 0,
                        'text-gray-600': (newStocktake.items[p.id]?.difference ?? 0) === 0
                      }">
                      <span x-text="formatDifference(newStocktake.items[p.id]?.difference ?? 0)"></span>
                    </td>
                  </tr>
                </template>
                <tr x-show="filteredProducts.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-gray-500">Không tìm thấy sản phẩm</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tổng kết -->
        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-gray-600">Tổng SP kiểm kê:</span>
              <span class="font-bold ml-2" x-text="Object.keys(newStocktake.items).length"></span>
            </div>
            <div>
              <span class="text-gray-600">Tổng chênh dương:</span>
              <span class="font-bold ml-2 text-green-600" x-text="calculateTotalPositiveDiff()"></span>
            </div>
            <div>
              <span class="text-gray-600">Tổng chênh âm:</span>
              <span class="font-bold ml-2 text-red-600" x-text="calculateTotalNegativeDiff()"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="px-6 py-4 border-t flex justify-end gap-3">
        <button @click="showCreateModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
          Hủy
        </button>
        <button @click="saveStocktake()" :disabled="saving"
          class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] disabled:opacity-50">
          <span x-show="!saving">Lưu kiểm kê</span>
          <span x-show="saving">Đang lưu...</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Modal Xem Chi Tiết -->
  <div x-show="showDetailModal" @click.self="showDetailModal = false"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg w-full max-w-6xl max-h-[90vh] flex flex-col">
      <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="text-xl font-bold text-[#002975]">Chi Tiết Phiếu Kiểm Kê</h3>
        <button @click="showDetailModal = false" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto flex-1">
        <template x-if="selectedStocktake">
          <div>
            <!-- Thông tin chung -->
            <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="text-sm text-gray-600">Mã kiểm kê:</label>
                <div class="font-bold text-lg" x-text="'#' + selectedStocktake.id"></div>
              </div>
              <div>
                <label class="text-sm text-gray-600">Người tạo:</label>
                <div class="font-medium" x-text="selectedStocktake.created_by_name"></div>
              </div>
              <div>
                <label class="text-sm text-gray-600">Thời gian tạo:</label>
                <div x-text="selectedStocktake.created_at"></div>
              </div>
              <div>
                <label class="text-sm text-gray-600">Ghi chú:</label>
                <div x-text="selectedStocktake.note || '—'"></div>
              </div>
            </div>

            <!-- Bảng chi tiết sản phẩm -->
            <div class="border rounded-lg overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tồn kho hệ thống</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kiểm kê thực tế</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Chênh lệch</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <template x-for="item in selectedStocktake.items" :key="item.product_id">
                      <tr>
                        <td class="px-4 py-3 text-sm" x-text="item.product_id"></td>
                        <td class="px-4 py-3 text-sm" x-text="item.product_name"></td>
                        <td class="px-4 py-3 text-sm text-right font-medium" x-text="item.system_quantity"></td>
                        <td class="px-4 py-3 text-sm text-right font-medium" x-text="item.actual_quantity"></td>
                        <td class="px-4 py-3 text-sm text-right font-bold" :class="{
                            'text-red-600': item.difference < 0,
                            'text-green-600': item.difference > 0,
                            'text-gray-600': item.difference === 0
                          }">
                          <span x-text="formatDifference(item.difference)"></span>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tổng kết -->
            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
              <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Tổng SP:</span>
                  <span class="font-bold ml-2" x-text="selectedStocktake.items?.length || 0"></span>
                </div>
                <div>
                  <span class="text-gray-600">Tổng chênh dương:</span>
                  <span class="font-bold ml-2 text-green-600" x-text="calculateDetailPositiveDiff()"></span>
                </div>
                <div>
                  <span class="text-gray-600">Tổng chênh âm:</span>
                  <span class="font-bold ml-2 text-red-600" x-text="calculateDetailNegativeDiff()"></span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <div class="px-6 py-4 border-t flex justify-end">
        <button @click="showDetailModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
          Đóng
        </button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div id="toast-container" class="z-[60]"></div>
</div>
</div>

<script>
  function stocktakePage() {
    const api = {
      list: '/admin/api/stocktakes',
      create:  '/admin/api/stocktakes/create',
      detail: '/admin/api/stocktakes/',
      products: '/admin/api/products/stock-list',
    };
    return {
      loading: true,
      saving: false,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,

      // Modals
      showCreateModal: false,
      showDetailModal: false,

      // Products for stocktake
      allProducts: [],
      filteredProducts: [],
      productSearch: '',

      // New stocktake data
      newStocktake: {
        note: '',
        items: {} // { product_id: { actual_quantity, system_quantity, difference } }
      },

      // Selected stocktake for detail view
      selectedStocktake: null,

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

      // ===== FILTERS =====
      openFilter: {
        id: false, note: false, created_at: false, created_by_name: false
      },

      filters: {
        id: '', note: '', created_by_name: '',
        created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: ''
      },

      // ------------------------------------------------------------------
      // Hàm lọc tổng quát — hỗ trợ TEXT, NUMBER, DATE
      // ------------------------------------------------------------------
      applyFilter(val, type, { value, from, to, dataType }) {
        if (val == null) return false;

        // -------- TEXT --------
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

        // -------- NUMBER --------
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

        // -------- DATE --------
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
            return d < nextDay;
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
        let data = this.items; // đây là mảng danh sách phiếu xuất (s)

        // --- TEXT: các cột cấp phiếu ---
        ['id', 'note', 'created_by_name'].forEach(key => {
          if (this.filters[key]) {
            data = data.filter(s =>
              this.applyFilter(s[key], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // Ngày tạo, 
        ['created_at'].forEach(key => {
          if (this.filters[`${key}_type`]) {
            data = data.filter(s =>
              this.applyFilter(s[key], this.filters[`${key}_type`], {
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
        // --- Date type ---
        if (['created_at'].includes(key)) {
          this.filters[`${key}_type`] = '';
          this.filters[`${key}_value`] = '';
          this.filters[`${key}_from`] = '';
          this.filters[`${key}_to`] = '';
        }

        // --- Text type 
        this.filters[key] = '';


        // --- Close dropdown ---
        this.openFilter[key] = false;
      },

      async init() {
        this.loading = true;
        try {
          const r = await fetch(api.list);
          if (r.ok) {
            const data = await r.json();
            this.items = Array.isArray(data) ? data : [];
          }
        } finally { this.loading = false; }
      },

      // ===== CREATE STOCKTAKE =====
      async openCreateModal() {
        try {
          const r = await fetch(api.products);
          console.log('Products API response:', r);
          const text = await r.text();
          console.log('Products API raw text:', text);

          if (r.ok) {
            const data = JSON.parse(text);
            console.log('Products data:', data);
            this.allProducts = data.products || [];
            this.filteredProducts = this.allProducts;
            this.newStocktake = { note: '', items: {} };
            this.productSearch = '';
            this.showCreateModal = true;
          } else {
            this.showToast('Lỗi tải danh sách sản phẩm: ' + text, 'error');
          }
        } catch (err) {
          console.error(err);
          this.showToast('Lỗi kết nối: ' + err.message, 'error');
        }
      },

      filterProducts() {
        const search = this.productSearch.toLowerCase().trim();
        if (!search) {
          this.filteredProducts = this.allProducts;
          return;
        }
        this.filteredProducts = this.allProducts.filter(p =>
          String(p.id).includes(search) ||
          p.name.toLowerCase().includes(search)
        );
      },

      updateActualQuantity(productId, value, systemQty) {
        const actual = value === '' ? null : parseInt(value) || 0;
        if (actual === null || actual < 0) {
          delete this.newStocktake.items[productId];
          return;
        }

        this.newStocktake.items[productId] = {
          actual_quantity: actual,
          system_quantity: systemQty,
          difference: actual - systemQty
        };
      },

      formatDifference(diff) {
        if (diff > 0) return '+' + diff;
        return String(diff);
      },

      calculateTotalPositiveDiff() {
        return Object.values(this.newStocktake.items)
          .filter(item => item.difference > 0)
          .reduce((sum, item) => sum + item.difference, 0);
      },

      calculateTotalNegativeDiff() {
        return Object.values(this.newStocktake.items)
          .filter(item => item.difference < 0)
          .reduce((sum, item) => sum + item.difference, 0);
      },

      async saveStocktake() {
        const items = Object.entries(this.newStocktake.items).map(([productId, data]) => ({
          product_id: parseInt(productId),
          system_quantity: data.system_quantity,
          actual_quantity: data.actual_quantity,
          difference: data.difference
        }));

        if (items.length === 0) {
          this.showToast('Vui lòng nhập số lượng kiểm kê cho ít nhất 1 sản phẩm', 'error');
          return;
        }

        this.saving = true;
        try {
          const r = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              note: this.newStocktake.note,
              items: items
            })
          });

          const result = await r.json();
          if (r.ok && result.success) {
            this.showToast('Tạo phiếu kiểm kê thành công!', 'success');
            this.showCreateModal = false;
            await this.init(); // Reload list
          } else {
            this.showToast(result.error || 'Lỗi tạo phiếu kiểm kê', 'error');
          }
        } catch (err) {
          console.error(err);
          this.showToast('Lỗi kết nối: ' + err.message, 'error');
        } finally {
          this.saving = false;
        }
      },

      // ===== VIEW DETAIL =====
      async viewDetail(id) {
        try {
          const r = await fetch(api.detail + id);
          if (r.ok) {
            const data = await r.json();
            this.selectedStocktake = data;
            this.showDetailModal = true;
          } else {
            this.showToast('Lỗi tải chi tiết kiểm kê', 'error');
          }
        } catch (err) {
          console.error(err);
          this.showToast('Lỗi kết nối: ' + err.message, 'error');
        }
      },

      calculateDetailPositiveDiff() {
        if (!this.selectedStocktake?.items) return 0;
        return this.selectedStocktake.items
          .filter(item => item.difference > 0)
          .reduce((sum, item) => sum + item.difference, 0);
      },

      calculateDetailNegativeDiff() {
        if (!this.selectedStocktake?.items) return 0;
        return this.selectedStocktake.items
          .filter(item => item.difference < 0)
          .reduce((sum, item) => sum + item.difference, 0);
      },
      // ===== TOAST =====
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
            ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
            : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
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