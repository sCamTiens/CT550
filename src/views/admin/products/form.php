<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm mb-1">Tên sản phẩm *</label>
    <input x-model="form.name" required class="w-full border rounded px-3 py-2" placeholder="Nhập tên sản phẩm">
  </div>
  <div>
    <label class="block text-sm mb-1">SKU *</label>
    <input x-model="form.sku" required class="w-full border rounded px-3 py-2" placeholder="Mã SKU duy nhất">
  </div>

  <div>
    <label class="block text-sm mb-1">Giá bán *</label>
    <input x-model.number="form.price" type="number" min="0" step="1000" required class="w-full border rounded px-3 py-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Đơn vị</label>
    <input x-model="form.unit" class="w-full border rounded px-3 py-2" placeholder="Nhập đơn vị (chai, bịch, kg...)">
  </div>

  <div>
    <label class="block text-sm mb-1">Thương hiệu</label>
    <select x-model="form.brand_id" class="w-full border rounded px-3 py-2">
      <option value="">-- Chọn thương hiệu --</option>
      <template x-for="b in brands" :key="b.id">
        <option :value="b.id" x-text="b.name"></option>
      </template>
    </select>
  </div>
  <div>
    <label class="block text-sm mb-1">Loại sản phẩm</label>
    <select x-model="form.category_id" class="w-full border rounded px-3 py-2">
      <option value="">-- Chọn loại sản phẩm --</option>
      <template x-for="c in categories" :key="c.id">
        <option :value="c.id" x-text="c.name"></option>
      </template>
    </select>
  </div>

  <div>
    <label class="block text-sm mb-1">Quy cách / Pack size</label>
    <input x-model="form.pack_size" class="w-full border rounded px-3 py-2" placeholder="thùng 24 lon, 1kg...">
  </div>
  <div>
    <label class="block text-sm mb-1">Mã vạch</label>
    <input x-model="form.barcode" class="w-full border rounded px-3 py-2">
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm mb-1">Mô tả</label>
    <textarea x-model="form.description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
  </div>

  <div class="md:col-span-2 flex items-center gap-3">
    <input id="isActive" type="checkbox" x-model="form.is_active" class="h-4 w-4">
    <label for="isActive" class="text-sm">Đang bán</label>
  </div>
</div>
