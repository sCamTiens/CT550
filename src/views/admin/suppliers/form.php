<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Tên nhà cung cấp -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Tên nhà cung cấp <span class="text-red-500">*</span></label>
        <input x-model="form.name" @blur="markTouched('name')" @input="validateField('name')" :class="['w-full border rounded px-3 py-2',
                     touched.name && errors.name ? 'border-red-500' : '']" placeholder="Nhập tên nhà cung cấp"
            required>
        <p class="text-red-600 text-xs mt-1" x-show="touched.name && errors.name" x-text="errors.name"></p>
    </div>

    <!-- Số điện thoại -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Số điện thoại</label>
        <input x-model="form.phone" @blur="markTouched('phone')" @input="validateField('phone')" :class="['w-full border rounded px-3 py-2',
                     touched.phone && errors.phone ? 'border-red-500' : '']" placeholder="Nhập số điện thoại">
        <p class="text-red-600 text-xs mt-1" x-show="touched.phone && errors.phone" x-text="errors.phone"></p>
    </div>

    <!-- Email -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Email</label>
        <input type="email" x-model="form.email" @blur="markTouched('email')" @input="validateField('email')" :class="['w-full border rounded px-3 py-2',
                     touched.email && errors.email ? 'border-red-500' : '']" placeholder="Nhập email">
        <p class="text-red-600 text-xs mt-1" x-show="touched.email && errors.email" x-text="errors.email"></p>
    </div>

    <!-- Địa chỉ -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Địa chỉ</label>
        <textarea x-model="form.address" rows="2" @blur="markTouched('address')" @input="validateField('address')"
            :class="['w-full border rounded px-3 py-2',
                     touched.address && errors.address ? 'border-red-500' : '']"
            placeholder="Nhập địa chỉ"></textarea>
        <p class="text-red-600 text-xs mt-1" x-show="touched.address && errors.address" x-text="errors.address"></p>
    </div>
</div>