<?php // views/admin/categories/form.php ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <!-- Tên -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">
      Tên <span class="text-red-500">*</span>
    </label>
    <input x-model="form.name" @input="onNameInput(); clearError('name'); validateField('name')"
      @blur="touched.name = true; validateField('name')" class="border rounded px-3 py-2 w-full"
      :class="(touched.name && errors.name) ? 'border-red-500' : 'border-gray-300'"
      placeholder="Nhập tên loại sản phẩm" required maxlength="250">
    <p x-show="touched.name && errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1"></p>
  </div>

  <!-- Slug -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1 flex items-center gap-1">
      Slug <span class="text-red-500">*</span>
      <span title="Hệ thống sẽ tự tạo slug khi thêm mới; Bạn có thể bấm 'Tạo' để ghi đè"
        class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
    </label>
    <div class="flex gap-2">
      <input x-model="form.slug" @input="clearError('slug'); validateField('slug')"
        @blur="touched.slug = true; validateField('slug')" class="border rounded px-3 py-2 w-full"
        :class="(touched.slug && errors.slug) ? 'border-red-500' : 'border-gray-300'" 
        placeholder="Tự tạo từ tên hoặc bấm nút Tạo" maxlength="250">
      <button type="button"
        class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
        @click="form.slug = slugify(form.name); validateField('slug')">
        Tạo
      </button>
    </div>
    <p x-show="touched.slug && errors.slug" x-text="errors.slug" class="text-red-500 text-xs mt-1"></p>
  </div>

  <!-- Loại cha -->
  <div class="relative" x-data="{
          open: false,
          search: '',
          filtered: [],
          highlight: -1,
          choose(item) {
              form.parent_id = item.id;   // Lưu id
              this.search = item.name;    // Hiển thị tên
              this.open = false;
          },
          clear() {
              form.parent_id = '';
              this.search = '';
              this.filtered = items;
              this.open = false;
          },
          reset() {
              const selected = items.find(c => c.id == form.parent_id);
              this.search = selected ? selected.name : '';
              this.filtered = items;
              this.highlight = -1;
          }
      }" x-effect="reset()" @click.away="open = false">

    <label class="block text-sm text-black font-semibold mb-1">
      Loại cha
    </label>

    <div class="relative">
      <input type="text" x-model="search" @focus="open = true; filtered = items"
        @input="open = true; filtered = items.filter(c => c.name.toLowerCase().includes(search.toLowerCase()))" class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer 
                   focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" placeholder="-- Không có --"
        :class="form.parent_id ? 'text-black' : 'text-gray-500'" />

      <!-- Nút xóa -->
      <button x-show="form.parent_id" type="button" @click.stop="clear()"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
        ✕
      </button>

      <!-- Icon -->
      <svg x-show="!form.parent_id"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Dropdown -->
    <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
      <template x-for="(c, i) in filtered" :key="c.id">
        <div @click="choose(c)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[ 
                    highlight === i ? 'bg-[#002975] text-white' 
                    : (form.parent_id == c.id ? 'bg-[#002975] text-white' 
                    : 'hover:bg-[#002975] hover:text-white text-black'), 
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="c.name">
        </div>
      </template>
      <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
        Không tìm thấy loại cha
      </div>
    </div>

    <p x-show="items.length === 0" class="text-red-400 text-xs italic mt-1">
      Danh sách trống
    </p>
  </div>

  <!-- Thứ tự hiển thị -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Thứ tự hiển thị</label>
    <input type="number" x-model.number="form.sort_order" class="border rounded px-3 py-2 w-full placeholder-gray-400"
      min="0" placeholder="Nhập thứ tự hiển thị">
  </div>

  <!-- Hiển thị -->
  <div class="md:col-span-2 flex items-center gap-3 pt-2">
    <input id="is_active" type="checkbox" x-model="form.is_active" class="h-4 w-4" checked>
    <label for="is_active" class="text-sm text-black font-semibold">Hiển thị</label>
  </div>
</div>