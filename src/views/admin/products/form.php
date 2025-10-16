<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <!-- Tên -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
    <input x-model="form.name" @blur="touched.name = true; validateField('name')"
      @input="onNameInput(); touched.name && validateField('name')" <?= input_attr_maxlength() ?>
      :class="['w-full border rounded px-3 py-2', (touched.name && errors.name) ? 'border-red-500' : '']"
      placeholder="Nhập tên sản phẩm" required>
    <p class="text-red-600 text-xs mt-1" x-show="touched.name && errors.name" x-text="errors.name"></p>
  </div>

  <!-- Slug -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1 flex items-center gap-1">
      Slug <span class="text-red-500">*</span>
      <span title="Hệ thống sẽ tự tạo slug từ Tên; Bạn có thể bấm 'Tạo' để ghi đè"
        class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
    </label>
    <div class="flex gap-2">
      <!-- Input slug -->
      <input x-model="form.slug" @input="touched.slug && validateField('slug')"
        @blur="touched.slug = true; validateField('slug')"
        :class="['border rounded px-3 py-2 w-full', (touched.slug && errors.slug) ? 'border-red-500' : '']"
        placeholder="Tự tạo từ tên hoặc bấm nút Tạo" maxlength="250">

      <!-- Nút tạo slug từ name -->
      <button type="button"
        class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
        @click="form.slug = slugify(form.name); validateField('slug')">
        Tạo
      </button>
    </div>

    <!-- Hiện lỗi -->
    <p x-show="touched.slug && errors.slug" x-text="errors.slug" class="text-red-500 text-xs mt-1"></p>
  </div>

  <!-- SKU -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1 flex items-center gap-1">
      SKU <span class="text-red-500">*</span>
      <span title="Hệ thống sẽ tự tạo SKU khi thêm mới; Bạn có thể bấm 'Tạo' để ghi đè"
        class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
    </label>
    <div class="flex gap-2">
      <input x-model="form.sku" @blur="touched.sku = true; validateField('sku')"
        @input="touched.sku && validateField('sku')"
        :class="['w-full border rounded px-3 py-2', (touched.sku && errors.sku) ? 'border-red-500' : '']"
        placeholder="Tự tạo hoặc bấm nút Tạo" required>
      <button type="button"
        class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
        @click="form.sku = generateSKU(); validateField('sku')">
        Tạo
      </button>
    </div>
    <p class="text-red-600 text-xs mt-1" x-show="touched.sku && errors.sku" x-text="errors.sku"></p>
  </div>

  <!-- Giá bán (sale_price) -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Giá bán <span class="text-red-500">*</span></label>
    <input x-model="form.sale_priceFormatted"
      @input="onSalePriceInput($event); touched.sale_price && validateField('sale_price')"
      @blur="touched.sale_price = true; validateField('sale_price')"
      :class="['w-full border rounded px-3 py-2', (touched.sale_price && errors.sale_price) ? 'border-red-500' : '']"
      placeholder="Nhập giá bán" required>
    <p class="text-red-600 text-xs mt-1" x-show="touched.sale_price && errors.sale_price" x-text="errors.sale_price">
    </p>
  </div>

  <!-- Giá nhập (cost_price) -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Giá nhập</label>
    <input x-model="form.cost_priceFormatted"
      @input="onCostPriceInput($event); touched.cost_price && validateField('cost_price')"
      @blur="touched.cost_price = true; validateField('cost_price')"
      :class="['w-full border rounded px-3 py-2', (touched.cost_price && errors.cost_price) ? 'border-red-500' : '']"
      placeholder="Nhập giá nhập (mặc định = 0)">
    <p class="text-red-600 text-xs mt-1" x-show="touched.cost_price && errors.cost_price" x-text="errors.cost_price">
    </p>
  </div>

  <!-- Đơn vị tính -->
  <div class="relative" x-data="{
        open: false,
        search: '',
        filtered: [],
        highlight: -1,
        choose(u) {
            form.unit_id = u.id;
            this.search = u.name;
            this.open = false;
            touched.unit_id = true;
            validateField('unit_id');
        },
        clear() {
            form.unit_id = '';
            this.search = '';
            this.filtered = units;
            this.open = false;
        },
        reset() {
            const selected = units.find(u => u.id == form.unit_id);
            this.search = selected ? selected.name : '';
            this.filtered = units;
            this.highlight = -1;
        }
    }" x-effect="reset()" @click.away="open = false">
    <label class="block text-sm text-black font-semibold mb-1">
      Đơn vị tính<span class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input type="text" x-model="search" @focus="open = true; filtered = units"
        @input="open = true; filtered = units.filter(u => u.name.toLowerCase().includes(search.toLowerCase()))"
        @blur="touched.unit_id = true; validateField('unit_id')"
        class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
        :class="(touched.unit_id && errors.unit_id) ? 'border-red-500' : 'border-gray-300'"
        placeholder="-- Chọn đơn vị --" />

      <button x-show="form.unit_id" type="button" @click.stop="clear()"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
        ✕
      </button>

      <svg x-show="!form.unit_id"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Dropdown -->
    <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
      <template x-for="(u, i) in filtered" :key="u.id">
        <div @click="choose(u)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[highlight === i ? 'bg-[#002975] text-white'
                : (form.unit_id == u.id ? 'bg-[#002975] text-white'
                : 'hover:bg-[#002975] hover:text-white text-black'),
                'px-3 py-2 cursor-pointer transition-colors text-sm']" x-text="u.name"></div>
      </template>
      <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm thấy đơn vị</div>
    </div>

    <p x-show="units.length === 0" class="text-red-400 text-xs italic mt-1">Danh sách trống</p>
    <p x-show="touched.unit_id && errors.unit_id" x-text="errors.unit_id" class="text-red-500 text-xs mt-1"></p>
  </div>

  <!-- Thương hiệu -->
  <div class="relative" x-data="{
        open: false,
        search: '',
        filtered: [],
        highlight: -1,
        choose(b) {
            form.brand_id = b.id;
            this.search = b.name;
            this.open = false;
            touched.brand_id = true;
            validateField('brand_id');
        },
        clear() {
            form.brand_id = '';
            this.search = '';
            this.filtered = brands;
            this.open = false;
        },
        reset() {
            const selected = brands.find(b => b.id == form.brand_id);
            this.search = selected ? selected.name : '';
            this.filtered = brands;
            this.highlight = -1;
        }
    }" x-effect="reset()" @click.away="open = false">
    <label class="block text-sm text-black font-semibold mb-1">
      Thương hiệu <span class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input type="text" x-model="search" @focus="open = true; filtered = brands"
        @input="open = true; filtered = brands.filter(b => b.name.toLowerCase().includes(search.toLowerCase()))"
        @blur="touched.brand_id = true; validateField('brand_id')"
        class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
        :class="(touched.brand_id && errors.brand_id) ? 'border-red-500' : 'border-gray-300'"
        placeholder="-- Chọn thương hiệu --" />

      <button x-show="form.brand_id" type="button" @click.stop="clear()"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
        ✕
      </button>

      <svg x-show="!form.brand_id"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Dropdown -->
    <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
      <template x-for="(b, i) in filtered" :key="b.id">
        <div @click="choose(b)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[highlight === i ? 'bg-[#002975] text-white'
                : (form.brand_id == b.id ? 'bg-[#002975] text-white'
                : 'hover:bg-[#002975] hover:text-white text-black'),
                'px-3 py-2 cursor-pointer transition-colors text-sm']" x-text="b.name"></div>
      </template>
      <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm thấy thương hiệu</div>
    </div>

    <p x-show="brands.length === 0" class="text-red-400 text-xs italic mt-1">Danh sách trống</p>
    <p x-show="touched.brand_id && errors.brand_id" x-text="errors.brand_id" class="text-red-500 text-xs mt-1"></p>
  </div>

  <!-- Loại sản phẩm -->
  <div class="relative" x-data="{
        open: false,
        search: '',
        filtered: [],
        highlight: -1,
        choose(c) {
            form.category_id = c.id;
            this.search = c.name;
            this.open = false;
            touched.category_id = true;
            validateField('category_id');
        },
        clear() {
            form.category_id = '';
            this.search = '';
            this.filtered = categories;
            this.open = false;
        },
        reset() {
            const selected = categories.find(c => c.id == form.category_id);
            this.search = selected ? selected.name : '';
            this.filtered = categories;
            this.highlight = -1;
        }
    }" x-effect="reset()" @click.away="open = false">
    <label class="block text-sm text-black font-semibold mb-1">
      Loại sản phẩm <span class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input type="text" x-model="search" @focus="open = true; filtered = categories"
        @input="open = true; filtered = categories.filter(c => c.name.toLowerCase().includes(search.toLowerCase()))"
        @blur="touched.category_id = true; validateField('category_id')"
        class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
        :class="(touched.category_id && errors.category_id) ? 'border-red-500' : 'border-gray-300'"
        placeholder="-- Chọn loại sản phẩm --" />

      <button x-show="form.category_id" type="button" @click.stop="clear()"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
        ✕
      </button>

      <svg x-show="!form.category_id"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Dropdown -->
    <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
      <template x-for="(c, i) in filtered" :key="c.id">
        <div @click="choose(c)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[highlight === i ? 'bg-[#002975] text-white'
                : (form.category_id == c.id ? 'bg-[#002975] text-white'
                : 'hover:bg-[#002975] hover:text-white text-black'),
                'px-3 py-2 cursor-pointer transition-colors text-sm']" x-text="c.name"></div>
      </template>
      <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm thấy loại sản phẩm</div>
    </div>

    <p x-show="categories.length === 0" class="text-red-400 text-xs italic mt-1">Danh sách trống</p>
    <p x-show="touched.category_id && errors.category_id" x-text="errors.category_id" class="text-red-500 text-xs mt-1">
    </p>
  </div>


  <!-- Pack size -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Quy cách</label>
    <input x-model="form.pack_size" <?= input_attr_maxlength() ?> class="w-full border rounded px-3 py-2"
      placeholder="thùng 24 lon, 1kg...">
  </div>

  <!-- Barcode -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">Mã vạch</label>
    <div class="flex gap-2">
      <input x-model="form.barcode" type="text" pattern="\d{13}" maxlength="13" class="w-full border rounded px-3 py-2"
        placeholder="VD: 8934567890123 (EAN-13)">
      <button type="button"
        class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
        @click="form.barcode = generateEAN13()">
        Tạo
      </button>
    </div>
  </div>

  <!-- Mô tả -->
  <div class="md:col-span-2">
    <label class="block text-sm text-black font-semibold mb-1">Mô tả</label>
    <textarea x-model="form.description" rows="3" <?= input_attr_maxlength(500) ?>
      class="w-full border rounded px-3 py-2" placeholder="Mô tả sản phẩm"></textarea>
  </div>

  <!-- Đang bán -->
  <div class="md:col-span-2 flex items-center gap-3">
    <input id="isActive" type="checkbox" x-model="form.is_active" class="h-4 w-4">
    <label for="isActive">Đang bán</label>
  </div>
</div>