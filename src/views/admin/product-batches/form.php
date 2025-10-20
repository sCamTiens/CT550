<form class="p-5 space-y-4" @submit.prevent="submit()">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Sản phẩm -->
        <div class="relative" x-data="{
                open: false,
                search: '',
                filtered: [],
                highlight: -1,
                choose(p) {
                    form.product_id = p.id;
                    this.search = p.name + ' (' + p.sku + ')';
                    this.open = false;
                    touched.product_id = true;
                    validateField('product_id');
                },
                clear() {
                    form.product_id = '';
                    this.search = '';
                    this.filtered = products;
                    this.open = false;
                },
                reset() {
                    const selected = products.find(p => p.id == form.product_id);
                    this.search = selected ? (selected.name + ' (' + selected.sku + ')') : '';
                    this.filtered = products;
                    this.highlight = -1;
                }
            }" x-effect="reset()" @click.away="open = false">
            <label class="block text-sm text-black font-semibold mb-1">
                Sản phẩm <span class="text-red-500">*</span>
            </label>

            <div class="relative">
                <input type="text" x-model="search" @focus="open = true; filtered = products" @input="open = true; filtered = products.filter(p => 
                (p.name + ' ' + p.sku).toLowerCase().includes(search.toLowerCase()))"
                    @blur="touched.product_id = true; validateField('product_id')" class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer 
                   focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                    :class="(touched.product_id && errors.product_id) ? 'border-red-500' : 'border-gray-300'"
                    placeholder="-- Chọn sản phẩm --" />

                <button x-show="form.product_id" type="button" @click.stop="clear()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                    ✕
                </button>

                <svg x-show="!form.product_id"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <!-- Dropdown -->
            <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                <template x-for="(p, i) in filtered" :key="p.id">
                    <div @click="choose(p)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.product_id == p.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="p.name + ' (' + p.sku + ')'">
                    </div>
                </template>
                <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                    Không tìm thấy sản phẩm
                </div>
            </div>

            <p x-show="products.length === 0" class="text-red-400 text-xs italic mt-1">
                Danh sách trống
            </p>

            <p x-show="touched.product_id && errors.product_id" x-text="errors.product_id"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Ngày sản xuất -->
        <div>
            <label class="block text-sm font-semibold mb-1">Ngày sản xuất <span class="text-red-500">*</span></label>
            <input x-model="form.mfg_date" type="text" @blur="touched.mfg_date = true; validateField('mfg_date')"
                class="batch-mfg-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.mfg_date && errors.mfg_date) ? 'border-red-500' : 'border-gray-300'"
                placeholder="Chọn ngày" autocomplete="off" />
            <p x-show="touched.mfg_date && errors.mfg_date" x-text="errors.mfg_date" class="text-red-500 text-xs mt-1">
            </p>
        </div>

        <!-- Hạn sử dụng -->
        <div>
            <label class="block text-sm font-semibold mb-1">Hạn sử dụng <span class="text-red-500">*</span></label>
            <input x-model="form.exp_date" type="text" @blur="touched.exp_date = true; validateField('exp_date')"
                class="batch-exp-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.exp_date && errors.exp_date) ? 'border-red-500' : 'border-gray-300'"
                placeholder="Chọn ngày" autocomplete="off" />
            <p x-show="touched.exp_date && errors.exp_date" x-text="errors.exp_date" class="text-red-500 text-xs mt-1">
            </p>
        </div>

        <!-- Số lượng ban đầu -->
        <div>
            <label class="block text-sm font-semibold mb-1">Số lượng <span class="text-red-500">*</span></label>
            <input x-model.number="form.initial_qty" type="number" min="1"
                @input="form.current_qty = form.initial_qty; validateField('initial_qty')"
                @blur="touched.initial_qty = true; validateField('initial_qty')"
                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.initial_qty && errors.initial_qty) ? 'border-red-500' : 'border-gray-300'" />
            <p x-show="touched.initial_qty && errors.initial_qty" x-text="errors.initial_qty"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Giá nhập -->
        <div>
            <label class="block text-sm font-semibold mb-1">Giá nhập <span class="text-red-500">*</span></label>
            <input x-model.number="form.unit_cost" type="number" step="1" min="0" @input="validateField('unit_cost')"
                @blur="touched.unit_cost = true; validateField('unit_cost')"
                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.unit_cost && errors.unit_cost) ? 'border-red-500' : 'border-gray-300'" />
            <p x-show="touched.unit_cost && errors.unit_cost" x-text="errors.unit_cost"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Ghi chú -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold mb-1">Ghi chú</label>
            <input x-model="form.note" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                placeholder="Ghi chú">
        </div>
    </div>

    <!-- Nút -->
    <div class="flex justify-end gap-3 pt-4">
        <button type="button" @click="openForm = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
        <button type="submit" class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
            x-text="submitting ? 'Đang lưu...' : (form.id ? 'Cập nhật' : 'Tạo')"></button>
    </div>
</form>

<script>
    // Khởi tạo flatpickr cho ngày sản xuất
    flatpickr(".batch-mfg-date", {
        dateFormat: "d/m/Y",
        locale: "vn",
        allowInput: true,
        static: true  // Render calendar bên trong modal thay vì append vào body
    });

    // Khởi tạo flatpickr cho hạn sử dụng
    flatpickr(".batch-exp-date", {
        dateFormat: "d/m/Y",
        locale: "vn",
        allowInput: true,
        static: true  // Render calendar bên trong modal thay vì append vào body
    });
</script>