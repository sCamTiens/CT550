<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Tên đơn vị -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">
            Tên đơn vị <span class="text-red-500">*</span>
        </label>
        <input x-model="form.name" @input="clearError('name'); validateField('name')"
            @blur="touched.name = true; validateField('name')" class="border rounded px-3 py-2 w-full"
            placeholder="Nhập tên đơn vị tính" required maxlength="250">
        <p x-show="touched.name && errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Slug -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1 flex items-center gap-1">
            Slug <span class="text-red-500">*</span>
            <span title="Hệ thống sẽ tự tạo slug từ tên; bạn có thể bấm 'Tạo' để ghi đè"
                class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
        </label>
        <div class="flex gap-2">
            <input x-model="form.slug" @input="clearError('slug'); validateField('slug')"
                @blur="touched.slug = true; validateField('slug')" class="border rounded px-3 py-2 w-full"
                placeholder="Tự tạo từ tên hoặc bấm nút Tạo" maxlength="250">
            <button type="button"
                class="px-3 py-2 rounded border text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white"
                @click="form.slug = slugify(form.name); validateField('slug')">
                Tạo
            </button>
        </div>
        <p x-show="touched.slug && errors.slug" x-text="errors.slug" class="text-red-500 text-xs mt-1"></p>
    </div>
</div>