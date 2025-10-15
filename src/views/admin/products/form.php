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

  <!-- Đơn vị -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">
      Đơn vị <span class="text-red-500">*</span>
    </label>

    <input list="unitOptions" x-model="form.unit_id" @blur="touched.unit_id = true; validateField('unit_id')"
      @input="touched.unit_id && validateField('unit_id')" :class="[
      'w-full border rounded px-3 py-2',
      (touched.unit_id && errors.unit_id) ? 'border-red-500' : '',
      form.unit_id === '' ? 'text-slate-400' : 'text-slate-900'
    ]" placeholder="-- Chọn đơn vị --" />

    <datalist id="unitOptions">
      <template x-for="u in units" :key="u.id">
        <option :value="u.name"></option>
      </template>
    </datalist>

    <p class="text-red-600 text-xs mt-1" x-show="touched.unit_id && errors.unit_id" x-text="errors.unit_id"></p>
  </div>

  <!-- Thương hiệu -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">
      Thương hiệu <span class="text-red-500">*</span>
    </label>

    <input list="brandOptions" x-model="form.brand_id" @blur="touched.brand_id = true; validateField('brand_id')"
      @input="touched.brand_id && validateField('brand_id')" :class="[
      'w-full border rounded px-3 py-2',
      (touched.brand_id && errors.brand_id) ? 'border-red-500' : '',
      form.brand_id === '' ? 'text-slate-400' : 'text-slate-900'
    ]" placeholder="-- Chọn thương hiệu --" />

    <datalist id="brandOptions">
      <template x-for="b in brands" :key="b.id">
        <option :value="b.name"></option>
      </template>
    </datalist>

    <p class="text-red-600 text-xs mt-1" x-show="touched.brand_id && errors.brand_id" x-text="errors.brand_id"></p>
  </div>

  <!-- Loại sản phẩm -->
  <div>
    <label class="block text-sm text-black font-semibold mb-1">
      Loại sản phẩm <span class="text-red-500">*</span>
    </label>

    <input list="categoryOptions" x-model="form.category_id"
      @blur="touched.category_id = true; validateField('category_id')"
      @input="touched.category_id && validateField('category_id')" :class="[
      'w-full border rounded px-3 py-2',
      (touched.category_id && errors.category_id) ? 'border-red-500' : '',
      form.category_id === '' ? 'text-slate-400' : 'text-slate-900'
    ]" placeholder="-- Chọn loại sản phẩm --" />

    <datalist id="categoryOptions">
      <template x-for="c in categories" :key="c.id">
        <option :value="c.name"></option>
      </template>
    </datalist>

    <p class="text-red-600 text-xs mt-1" x-show="touched.category_id && errors.category_id" x-text="errors.category_id">
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
    <input id="isActive" type="checkbox" x-model="form.is_active" true-value="1" false-value="0" class="h-4 w-4">
    <label for="isActive" class="text-sm">Đang bán</label>
  </div>
</div>