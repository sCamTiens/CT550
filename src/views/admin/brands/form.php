<!-- Tên thương hiệu -->
<div>
  <label class="block text-sm mb-1">Tên thương hiệu <span class="text-red-500">*</span></label>
  <input x-model="form.name" @input="onNameInput(); touched.name && validateField('name')"
    @blur="touched.name = true; validateField('name')" :class="['w-full border rounded px-3 py-2',
             (touched.name && errors.name) ? 'border-red-500' : '']" placeholder="Nhập tên thương hiệu" required>
  <p class="text-red-600 text-xs mt-1" x-show="touched.name && errors.name" x-text="errors.name"></p>
</div>

<!-- Slug -->
<div>
  <label class="block text-sm mb-1 flex items-center gap-1">
    Slug <span class="text-red-500">*</span>
    <span title="Hệ thống sẽ tự tạo slug khi thêm mới; Bạn có thể bấm 'Tạo' để ghi đè"
      class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
  </label>
  <div class="flex gap-2">
    <input x-model="form.slug" @input="touched.slug && validateField('slug')"
      @blur="touched.slug = true; validateField('slug')" :class="['w-full border rounded px-3 py-2',
               (touched.slug && errors.slug) ? 'border-red-500' : '']" placeholder="Slug sẽ được tạo tự động từ tên">
    <button type="button"
      class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
      @click="form.slug = slugify(form.name); validateField('slug')">
      Tạo
    </button>
  </div>
  <p class="text-red-600 text-xs mt-1" x-show="touched.slug && errors.slug" x-text="errors.slug"></p>
</div>