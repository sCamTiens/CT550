<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Tên chương trình -->
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold mb-1">Tên chương trình <span class="text-red-500">*</span></label>
        <input x-model="form.name" type="text"
            @blur="touched.name = true; validateField('name')"
            @input="validateField('name')"
            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.name && errors.name) ? 'border-red-500' : 'border-gray-300'"
            placeholder="Tên chương trình khuyến mãi" />
        <p x-show="touched.name && errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Mô tả -->
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold mb-1">Mô tả</label>
        <textarea x-model="form.description" rows="3"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            placeholder="Mô tả chi tiết về chương trình khuyến mãi"></textarea>
    </div>

    <!-- Loại giảm giá -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            types: [
                {id: 'percentage', name: 'Phần trăm (%)'},
                {id: 'fixed', name: 'Số tiền cố định (₫)'}
            ],
            choose(t) {
                form.discount_type = t.id;
                this.search = t.name;
                this.open = false;
                validateField('discount_value');
            },
            clear() {
                form.discount_type = 'percentage';
                this.search = 'Phần trăm (%)';
                this.filtered = this.types;
                this.open = false;
            },
            reset() {
                const selected = this.types.find(t => t.id == form.discount_type);
                this.search = selected ? selected.name : 'Phần trăm (%)';
                this.filtered = this.types;
                this.highlight = -1;
            }
        }" x-effect="reset()" @click.away="open = false">
        <label class="block text-sm font-semibold mb-1">Loại giảm giá <span class="text-red-500">*</span></label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = types"
                @input="open = true; filtered = types.filter(t => t.name.toLowerCase().includes(search.toLowerCase()))"
                class="w-full border border-gray-300 rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                placeholder="-- Chọn loại --" />

            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(t, i) in filtered" :key="t.id">
                <div @click="choose(t)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.discount_type == t.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="t.name"></div>
            </template>
        </div>
    </div>

    <!-- Giá trị giảm -->
    <div>
        <label class="block text-sm font-semibold mb-1">
            Giá trị giảm <span class="text-red-500">*</span>
            <span class="text-xs text-gray-500" x-text="form.discount_type === 'percentage' ? '(%)' : '(₫)'"></span>
        </label>
        <input x-model.number="form.discount_value" type="number" min="0" step="0.01"
            @input="validateField('discount_value')"
            @blur="touched.discount_value = true; validateField('discount_value')"
            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.discount_value && errors.discount_value) ? 'border-red-500' : 'border-gray-300'"
            :placeholder="form.discount_type === 'percentage' ? 'VD: 10' : 'VD: 50000'" />
        <p x-show="touched.discount_value && errors.discount_value" x-text="errors.discount_value"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Áp dụng cho -->
    <div class="relative md:col-span-2" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            applyOptions: [
                {id: 'all', name: 'Toàn bộ sản phẩm'},
                {id: 'category', name: 'Theo danh mục'},
                {id: 'product', name: 'Sản phẩm cụ thể'}
            ],
            choose(opt) {
                form.apply_to = opt.id;
                this.search = opt.name;
                this.open = false;
                // Reset danh sách khi đổi loại
                if (opt.id === 'all') {
                    form.category_ids = [];
                    form.product_ids = [];
                }
            },
            reset() {
                const selected = this.applyOptions.find(o => o.id == form.apply_to);
                this.search = selected ? selected.name : 'Toàn bộ sản phẩm';
                this.filtered = this.applyOptions;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm font-semibold mb-1">Áp dụng cho <span class="text-red-500">*</span></label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = applyOptions"
                @input="open = true; filtered = applyOptions.filter(o => o.name.toLowerCase().includes(search.toLowerCase()))"
                class="w-full border border-gray-300 rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                placeholder="-- Chọn phạm vi áp dụng --" />

            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(opt, i) in filtered" :key="opt.id">
                <div @click="choose(opt)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.apply_to == opt.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="opt.name"></div>
            </template>
        </div>
    </div>

    <!-- Chọn danh mục (hiện khi apply_to = category) -->
    <div class="md:col-span-2" x-show="form.apply_to === 'category'">
        <label class="block text-sm font-semibold mb-1">Chọn danh mục <span class="text-red-500">*</span></label>
        <div class="border border-gray-300 rounded-md p-3 max-h-40 overflow-y-auto space-y-2">
            <template x-for="cat in categories" :key="cat.id">
                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                    <input type="checkbox" :value="cat.id" 
                        @change="if ($event.target.checked) { 
                            if (!form.category_ids.includes(cat.id)) form.category_ids.push(cat.id);
                        } else { 
                            form.category_ids = form.category_ids.filter(id => id !== cat.id);
                        }"
                        :checked="form.category_ids.includes(cat.id)"
                        class="h-4 w-4">
                    <span class="text-sm" x-text="cat.name"></span>
                </label>
            </template>
            <p x-show="categories.length === 0" class="text-gray-400 text-sm">Không có danh mục</p>
        </div>
    </div>

    <!-- Chọn sản phẩm (hiện khi apply_to = product) -->
    <div class="md:col-span-2" x-show="form.apply_to === 'product'">
        <label class="block text-sm font-semibold mb-1">Chọn sản phẩm <span class="text-red-500">*</span></label>
        <div class="border border-gray-300 rounded-md p-3 max-h-40 overflow-y-auto space-y-2">
            <template x-for="prod in products" :key="prod.id">
                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                    <input type="checkbox" :value="prod.id"
                        @change="if ($event.target.checked) {
                            if (!form.product_ids.includes(prod.id)) form.product_ids.push(prod.id);
                        } else {
                            form.product_ids = form.product_ids.filter(id => id !== prod.id);
                        }"
                        :checked="form.product_ids.includes(prod.id)"
                        class="h-4 w-4">
                    <span class="text-sm" x-text="prod.name + ' (' + prod.sku + ')'"></span>
                </label>
            </template>
            <p x-show="products.length === 0" class="text-gray-400 text-sm">Không có sản phẩm</p>
        </div>
    </div>

    <!-- Độ ưu tiên -->
    <div>
        <label class="block text-sm font-semibold mb-1">Độ ưu tiên</label>
        <input x-model.number="form.priority" type="number" min="0"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            placeholder="VD: 1" />
        <p class="text-xs text-gray-500 mt-1">Số càng lớn càng ưu tiên áp dụng trước</p>
    </div>

    <!-- Ngày bắt đầu -->
    <div>
        <label class="block text-sm font-semibold mb-1">Ngày bắt đầu <span class="text-red-500">*</span></label>
        <input x-model="form.starts_at" type="text" class="promotion-start-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.starts_at && errors.starts_at) ? 'border-red-500' : 'border-gray-300'"
            placeholder="dd/mm/yyyy" autocomplete="off"
            @blur="touched.starts_at = true; validateField('starts_at')" />
        <p x-show="touched.starts_at && errors.starts_at" x-text="errors.starts_at" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Ngày kết thúc -->
    <div>
        <label class="block text-sm font-semibold mb-1">Ngày kết thúc <span class="text-red-500">*</span></label>
        <input x-model="form.ends_at" type="text" class="promotion-end-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.ends_at && errors.ends_at) ? 'border-red-500' : 'border-gray-300'"
            placeholder="dd/mm/yyyy" autocomplete="off"
            @blur="touched.ends_at = true; validateField('ends_at')" />
        <p x-show="touched.ends_at && errors.ends_at" x-text="errors.ends_at" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Trạng thái -->
    <div class="md:col-span-2 flex items-center gap-3">
        <input id="isActivePromo" type="checkbox" x-model="form.is_active" true-value="1" false-value="0" class="h-4 w-4">
        <label for="isActivePromo" class="text-sm">Kích hoạt</label>
    </div>
</div>

<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/vi.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            // Ngày bắt đầu
            flatpickr(".promotion-start-date", {
                dateFormat: "d/m/Y",
                locale: "vi",
                allowInput: true
            });

            // Ngày kết thúc
            flatpickr(".promotion-end-date", {
                dateFormat: "d/m/Y",
                locale: "vi",
                allowInput: true
            });
        }
    });
</script>
