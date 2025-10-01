<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Tên đơn vị -->
    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Tên đơn vị <span class="text-red-500">*</span></label>
        <input x-model="form.name" @input="onNameInput" @blur="touched.name = true; validateField('name')"
            @input="touched.name && validateField('name')"
            :class="['w-full border rounded px-3 py-2', (touched.name && errors.name) ? 'border-red-500' : '']"
            placeholder="Nhập tên đơn vị tính" required>
        <p class="text-red-600 text-xs mt-1" x-show="touched.name && errors.name" x-text="errors.name"></p>
    </div>

    <!-- Slug -->
    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Slug <span class="text-red-500">*</span></label>
        <input x-model="form.slug" @blur="touched.slug = true; validateField('slug')"
            @input="touched.slug && validateField('slug')"
            :class="['w-full border rounded px-3 py-2', (touched.slug && errors.slug) ? 'border-red-500' : '']"
            placeholder="Tự sinh từ tên hoặc nhập thủ công" required>
        <p class="text-red-600 text-xs mt-1" x-show="touched.slug && errors.slug" x-text="errors.slug"></p>
    </div>
</div>