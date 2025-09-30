<?php // views/admin/categories/form.php ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm text-slate-600 mb-1">Tên <span class="text-red-500">*</span></label>
    <input x-model="form.name" class="border rounded px-3 py-2 w-full" placeholder="Ví dụ: Đồ uống" required>
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Slug</label>
    <input x-model="form.slug" class="border rounded px-3 py-2 w-full" placeholder="do-uong">
    <p class="text-xs text-slate-400 mt-1">Để trống hệ thống có thể tự tạo dựa trên tên.</p>
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Loại cha</label>
    <select x-model="form.parent_id" class="border rounded px-3 py-2 w-full">
      <option value="">— Không có —</option>
      <template x-for="c in items" :key="c.id">
        <option
          :value="c.id"
          x-text="c.name"
          :disabled="form.id && String(form.id) === String(c.id)"></option>
      </template>
    </select>
    <p class="text-xs text-slate-400 mt-1">Không chọn nếu đây là loại cấp 1.</p>
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Thứ tự hiển thị</label>
    <input type="number" x-model.number="form.sort_order" class="border rounded px-3 py-2 w-full" min="0">
  </div>

  <div class="md:col-span-2 flex items-center gap-3 pt-2">
    <input id="is_active" type="checkbox" x-model="form.is_active" class="h-4 w-4">
    <label for="is_active" class="text-sm text-slate-700">Hiển thị</label>
  </div>
</div>
