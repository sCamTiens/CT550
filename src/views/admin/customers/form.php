<div class="space-y-4">
  <div>
    <label class="block font-medium mb-1">Tài khoản</label>
    <input x-model="form.username" class="form-input w-full" type="text" placeholder="Tài khoản khách hàng">
  </div>
  <div>
    <label class="block font-medium mb-1">Họ tên</label>
    <input x-model="form.full_name" class="form-input w-full" type="text" placeholder="Họ tên khách hàng">
  </div>
  <div>
    <label class="block font-medium mb-1">Email</label>
    <input x-model="form.email" class="form-input w-full" type="email" placeholder="Email">
  </div>
  <div>
    <label class="block font-medium mb-1">Số điện thoại</label>
    <input x-model="form.phone" class="form-input w-full" type="text" placeholder="Số điện thoại">
  </div>
  <div>
    <label class="block font-medium mb-1">Giới tính</label>
    <select x-model="form.gender" class="form-input w-full">
      <option value="">-- Chọn --</option>
      <option value="Nam">Nam</option>
      <option value="Nữ">Nữ</option>
    </select>
  </div>
  <div>
    <label class="block font-medium mb-1">Ngày sinh</label>
    <input x-model="form.date_of_birth" class="form-input w-full" type="date">
  </div>
  <div>
    <label class="block font-medium mb-1">Trạng thái</label>
    <select x-model="form.is_active" class="form-input w-full">
      <option :value="1">Hoạt động</option>
      <option :value="0">Khóa</option>
    </select>
  </div>
</div>
