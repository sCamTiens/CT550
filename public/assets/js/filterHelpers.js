export function filterMixin() {
  return {
    // trạng thái filter
    filters: {},
    openFilter: {},

    toggleFilter(key) {
      this.openFilter[key] = !this.openFilter[key];
    },

    applyFilter(key) {
      // chỉ cần đóng lại, filtered() sẽ tự đọc filters
      this.openFilter[key] = false;
    },

    resetFilter(key) {
      // xóa filter field
      if (typeof this.filters[key] === 'object') {
        this.filters[key] = {};
      } else {
        this.filters[key] = '';
      }
      this.openFilter[key] = false;
    },

    // Hàm lọc chung: nhận items và trả items đã lọc
    filterItems(items, config = {}) {
      return items.filter(row => {
        for (const [field, val] of Object.entries(this.filters)) {
          if (!val) continue;

          const raw = (row[field] || '').toString().toLowerCase();
          if (typeof val === 'string' && !raw.includes(val.toLowerCase())) {
            return false;
          }

          // lọc số
          if (typeof val === 'number' && Number(raw) !== val) {
            return false;
          }

          // lọc ngày (simple: eq)
          if (val?._type === 'eq' && raw !== val._value) {
            return false;
          }
          if (val?._type === 'between') {
            if (raw < val._from || raw > val._to) return false;
          }
        }
        return true;
      });
    }
  };
}
