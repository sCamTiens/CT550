<?php
// views/admin/stock/stock.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<style>
  [x-cloak] {
    display: none !important;
  }

  .stock-out-of-stock {
    background-color: #fee2e2 !important;
  }

  .stock-out-of-stock:hover {
    background-color: #fecaca !important;
  }

  .stock-low {
    background-color: #fef3c7 !important;
  }

  .stock-low:hover {
    background-color: #fde68a !important;
  }
</style>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / Quản lý kho / <span class="text-slate-800 font-medium">Tồn kho</span>
</nav>

<div x-data="stockPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý tồn kho</h1>

    <div class="flex items-center gap-4">
      <!-- Thống kê cảnh báo -->
      <div class="flex gap-4 text-sm">
        <div class="flex items-center gap-2 px-3 py-2 bg-red-50 rounded-lg border border-red-200">
          <i class="fa-solid fa-exclamation-circle text-red-600"></i>
          <span class="text-gray-700">Hết hàng: <strong class="text-red-600" x-text="outOfStockCount()"></strong></span>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-yellow-50 rounded-lg border border-yellow-200">
          <i class="fa-solid fa-exclamation-triangle text-yellow-600"></i>
          <span class="text-gray-700">Cảnh báo: <strong class="text-yellow-600"
              x-text="lowStockCount()"></strong></span>
        </div>
      </div>

      <!-- Nút xuất Excel -->
      <button
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
        @click="exportExcel()">
        <i class="fa-solid fa-file-excel"></i>
        Xuất Excel
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table :style="`width:${filtered().length === 0 ? '140%' : '100%'}; min-width:1250px; border-collapse:collapse;`">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <?= textFilterPopover('product_sku', 'SKU') ?>
            <?= textFilterPopover('product_name', 'Tên sản phẩm') ?>
            <?= textFilterPopover('unit_name', 'Đơn vị tính') ?>
            <?= numberFilterPopover('qty', 'Tồn kho') ?>
            <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
            <?= textFilterPopover('updated_by', 'Người cập nhật') ?>
          </tr>
        </thead>
        <tbody>
          <template x-for="s in paginated()" :key="s.product_id">
            <tr class="border-t hover:bg-blue-50 transition-colors duration-150" :class="{
                  'stock-out-of-stock': s.qty === 0,
                  'stock-low': s.qty > 0 && s.qty < (s.safety_stock || 5),
                  'hover:bg-blue-50': s.qty >= (s.safety_stock || 5)
                }">
              <td class="py-2 px-4 break-words whitespace-pre-line text-center" x-text="s.product_sku"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.product_name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.unit_name"></td>
              <td class="py-2 px-4 text-center">
                <span :class="{
                  'text-red-600 font-bold': s.qty === 0,
                  'text-yellow-600 font-semibold': s.qty > 0 && s.qty < (s.safety_stock || 5)
                }" x-text="s.qty"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(s.updated_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="s.updated_at || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.updated_by_name || '—'"></td>
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
    <div id="toast-container" class="z-[60]"></div>

</div>

<script>
  function stockPage() {
    const api = {
      list: '/admin/api/stocks',
      products: '/admin/api/products/all-including-inactive' // Lấy tất cả sản phẩm
    };
    return {
      loading: true,
      items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
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

      // ====== Thống kê ======
      outOfStockCount() {
        return this.filtered().filter(s => s.qty === 0).length;
      },
      lowStockCount() {
        return this.filtered().filter(s => s.qty > 0 && s.qty < (s.safety_stock || 5)).length;
      },

      // ===== FILTERS =====
      openFilter: {
        product_sku: false,
        product_name: false,
        unit_name: false,
        qty: false,
        updated_at: false,
        updated_by: false
      },

      filters: {
        product_sku: '',
        product_name: '',
        unit_name: '',
        qty_type: '', qty_value: '', qty_from: '', qty_to: '',
        updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
        updated_by: '',
      },

      // -------------------------------------------
      // Hàm lọc tổng quát, hỗ trợ text / number / date
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
            ? raw === query  // có dấu → so đúng dấu
            : str === queryNoAccent; // không dấu → so không dấu

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

          // --- Lọc “mờ” theo chuỗi số ---
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

      // ==============================
      // LỌC DỮ LIỆU
      // ==============================
      filtered() {
        let data = this.items;

        // --- TEXT ---
        ['product_sku', 'product_name', 'unit_name', 'updated_by'].forEach(key => {
          if (this.filters[key]) {
            data = data.filter(o =>
              this.applyFilter(o[key], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- NUMBER ---
        if (this.filters.qty_type) {
          data = data.filter(o =>
            this.applyFilter(o.qty, this.filters.qty_type, {
              value: this.filters.qty_value,
              from: this.filters.qty_from,
              to: this.filters.qty_to,
              dataType: 'number'
            })
          );
        }

        // --- DATE ---
        if (this.filters.updated_at_type) {
          data = data.filter(o =>
            this.applyFilter(o.updated_at, this.filters.updated_at_type, {
              value: this.filters.updated_at_value,
              from: this.filters.updated_at_from,
              to: this.filters.updated_at_to,
              dataType: 'date'
            })
          );
        }

        // SAU KHI LỌC → SẮP XẾP ƯU TIÊN TỒN KHO
        return data.sort((a, b) => {
          const aOut = a.qty === 0;
          const bOut = b.qty === 0;
          if (aOut && !bOut) return -1;
          if (!aOut && bOut) return 1;

          const aLow = a.qty > 0 && a.qty < (a.safety_stock || 5);
          const bLow = b.qty > 0 && b.qty < (b.safety_stock || 5);
          if (aLow && !bLow) return -1;
          if (!aLow && bLow) return 1;

          return a.qty - b.qty;
        });
      },

      // ==============================
      // BẬT/TẮT & RESET FILTER
      // ==============================
      toggleFilter(key) {
        for (const k in this.openFilter) this.openFilter[k] = false;
        this.openFilter[key] = true;
      },
      closeFilter(key) { this.openFilter[key] = false; },
      resetFilter(key) {
        if (['qty', 'updated_at'].includes(key)) {
          this.filters[`${key}_type`] = '';
          this.filters[`${key}_value`] = '';
          this.filters[`${key}_from`] = '';
          this.filters[`${key}_to`] = '';
        } else {
          this.filters[key] = '';
        }
        this.openFilter[key] = false;
      },

      exportExcel() {
        const data = this.filtered();

        if (data.length === 0) {
          this.showToast('Không có dữ liệu để xuất', 'error');
          return;
        }

        const now = new Date();
        const dateStr = now.toLocaleDateString('vi-VN').replace(/\//g, '-');
        const timeStr = now.toLocaleTimeString('vi-VN', { hour12: false }).replace(/:/g, '-');
        const filename = `Ton_kho_${dateStr}_${timeStr}.xlsx`;

        const exportData = {
          items: data.map(item => ({
            product_sku: item.product_sku || '',
            product_name: item.product_name || '',
            unit_name: item.unit_name || '',
            qty: item.qty || 0,
            updated_at: item.updated_at || '',
            updated_by_name: item.updated_by_name || ''
          })),
          export_date: now.toLocaleDateString('vi-VN'),
          filename: filename
        };

        fetch('/admin/api/stocks/export', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(exportData)
        })
          .then(response => {
            if (!response.ok) throw new Error('Export failed');
            return response.blob();
          })
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showToast('Xuất file Excel thành công!', 'success');
          })
          .catch(e => {
            console.error('Export error:', e);
            this.showToast('Không thể xuất file Excel', 'error');
          });
      },

      async init() {
        this.loading = true;
        try {
          const r = await fetch(api.list);
          if (r.ok) {
            const data = await r.json();
            this.items = Array.isArray(data) ? data : (data.items || []);
          }
        } finally { this.loading = false; }
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