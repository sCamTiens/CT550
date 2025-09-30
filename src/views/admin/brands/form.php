<!-- Brand form partial -->
<div class="space-y-1">
  <label class="text-sm text-slate-600">Tên thương hiệu <span class="text-red-500">*</span></label>
  <input class="w-full border rounded px-3 py-2"
         x-model.trim="form.name"
         @input="autoSlug()"
         placeholder="VD: Vinamilk"
         required>
</div>

<div class="space-y-1">
  <label class="text-sm text-slate-600">Slug</label>
  <input class="w-full border rounded px-3 py-2"
         x-model.trim="form.slug"
         placeholder="vinamilk">
  <p class="text-xs text-slate-500 mt-1">
    Slug sẽ tự tạo theo tên; bạn có thể sửa lại nếu muốn.
  </p>
</div>
