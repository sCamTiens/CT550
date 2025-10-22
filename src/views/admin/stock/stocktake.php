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
  </div>
  <div class="bg-white rounded-xl shadow pb-4">
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table style="width:1200px; min-width:900px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <?= textFilterPopover('id', 'Mã kiểm kê') ?>
            <?= textFilterPopover('created_by_name', 'Người tạo') ?>
            <?= dateFilterPopover('created_at', 'Ngày tạo') ?>
            <?= textFilterPopover('note', 'Ghi chú') ?>
          </tr>
        </thead>
        <tbody>
          <template x-for="s in paginated()" :key="s.id">
            <tr class="border-t">
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.id"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="s.created_by_name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-right" x-text="s.created_at"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(s.note || '—') === '—' ? 'text-center' : 'text-left'" x-text="s.note || '—'"></td>
            </tr>
          </template>
          <tr x-show="!loading && filtered().length===0">
            <td colspan="4" class="py-12 text-center text-slate-500">
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
</div>

<script>
  function stocktakePage() {
    const api = {
      list: '/admin/api/stocktakes',
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
      filters: {},
      openFilter: {},

      // ===== helper riêng cho date filter =====
      applyDateFilter(val, type, value, from, to) {
        if (!val) return true;
        if (!type) return true;

        const normalizeDate = (dateStr) => {
          if (!dateStr) return null;
          const d = new Date(dateStr);
          if (isNaN(d.getTime())) return null;
          return new Date(d.getFullYear(), d.getMonth(), d.getDate());
        };

        const d = normalizeDate(val);
        if (!d) return true;

        if (type === 'eq') {
          if (!value) return true;
          const compareDate = normalizeDate(value);
          return compareDate ? d.getTime() === compareDate.getTime() : true;
        }

        if (type === 'between') {
          if (!from || !to) return true;
          const fromDate = normalizeDate(from);
          const toDate = normalizeDate(to);
          return fromDate && toDate ? (d >= fromDate && d <= toDate) : true;
        }

        if (type === 'lt') {
          if (!value) return true;
          const compareDate = normalizeDate(value);
          return compareDate ? d < compareDate : true;
        }

        if (type === 'gt') {
          if (!value) return true;
          const compareDate = normalizeDate(value);
          return compareDate ? d > compareDate : true;
        }

        if (type === 'lte') {
          if (!value) return true;
          const compareDate = normalizeDate(value);
          return compareDate ? d <= compareDate : true;
        }

        if (type === 'gte') {
          if (!value) return true;
          const compareDate = normalizeDate(value);
          return compareDate ? d >= compareDate : true;
        }

        return true;
      },

      filtered() {
        const fn = (v) => (v ?? '').toString().toLowerCase();
        const f = this.filters;

        return this.items.filter(s => {
          if (f.id && !String(s.id).includes(f.id)) return false;
          if (f.created_by && !fn(s.created_by_name || '').includes(fn(f.created_by))) return false;
          if (f.note && !fn(s.note).includes(fn(f.note))) return false;

          if (!this.applyDateFilter(s.created_at, f.created_at_type, f.created_at_value, f.created_at_from, f.created_at_to)) return false;

          return true;
        });
      },
      toggleFilter(key) {
        Object.keys(this.openFilter).forEach(k => this.openFilter[k] = (k === key ? !this.openFilter[k] : false));
      },
      applyFilter(key) {
        this.openFilter[key] = false;
      },
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
    }
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>