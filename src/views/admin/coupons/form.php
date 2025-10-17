<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã giảm giá -->
    <div>
        <label class="block text-sm font-semibold mb-1">Mã giảm giá <span class="text-red-500">*</span></label>
        <input x-model="form.code" type="text" @blur="touched.code = true; validateField('code')"
            @input="form.code = form.code.toUpperCase(); validateField('code')"
            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975] uppercase"
            :class="(touched.code && errors.code) ? 'border-red-500' : 'border-gray-300'"
            placeholder="Nhập mã giảm giá" />
        <p x-show="touched.code && errors.code" x-text="errors.code" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Mô tả -->
    <div>
        <label class="block text-sm font-semibold mb-1">Mô tả</label>
        <input x-model="form.description" type="text"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            placeholder="Mô tả ngắn về mã giảm giá" />
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

            <button x-show="form.discount_type" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.discount_type"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
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
    <div x-data="{
        formatted: '',
        onInput(e) {
            if (form.discount_type === 'percentage') {
                // Cho phần trăm, chỉ cần nhập số thập phân
                form.discount_value = e.target.value ? Number(e.target.value) : 0;
                this.formatted = e.target.value;
            } else {
                // Cho fixed amount, format với dấu phẩy
                const raw = e.target.value.replace(/[^\d]/g, '');
                form.discount_value = raw ? Number(raw) : 0;
                this.formatted = raw ? Number(raw).toLocaleString('en-US') : '';
            }
            validateField('discount_value');
        }
    }" x-init="formatted = form.discount_type === 'percentage' ? form.discount_value : (form.discount_value ? form.discount_value.toLocaleString('en-US') : '')"
        x-effect="if (form.discount_type === 'percentage') { formatted = form.discount_value; } else { formatted = form.discount_value ? form.discount_value.toLocaleString('en-US') : ''; }">
        <label class="block text-sm font-semibold mb-1">
            Giá trị giảm <span class="text-red-500">*</span>
            <span class="text-xs text-gray-500" x-text="form.discount_type === 'percentage' ? '(%)' : '(₫)'"></span>
        </label>
        <input x-model="formatted" :type="form.discount_type === 'percentage' ? 'number' : 'text'"
            :min="form.discount_type === 'percentage' ? 0 : undefined"
            :step="form.discount_type === 'percentage' ? 0.01 : undefined" @input="onInput($event)"
            @blur="touched.discount_value = true; validateField('discount_value')"
            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.discount_value && errors.discount_value) ? 'border-red-500' : 'border-gray-300'"
            :placeholder="form.discount_type === 'percentage' ? 'VD: 10' : 'VD: 50,000'" />
        <p x-show="touched.discount_value && errors.discount_value" x-text="errors.discount_value"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Giá trị đơn tối thiểu -->
    <div x-data="{
        formatted: '',
        onInput(e) {
            const raw = e.target.value.replace(/[^\d]/g, '');
            form.min_order_value = raw ? Number(raw) : 0;
            this.formatted = raw ? Number(raw).toLocaleString('en-US') : '';
            validateField('min_order_value');
        }
    }" x-init="formatted = form.min_order_value ? form.min_order_value.toLocaleString('en-US') : ''">
        <label class="block text-sm font-semibold mb-1">Giá trị đơn tối thiểu (₫)</label>
        <input x-model="formatted" type="text" @input="onInput($event)"
            @blur="touched.min_order_value = true; validateField('min_order_value')"
            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.min_order_value && errors.min_order_value) ? 'border-red-500' : 'border-gray-300'"
            placeholder="VD: 100,000" />
        <p x-show="touched.min_order_value && errors.min_order_value" x-text="errors.min_order_value"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Giảm tối đa (chỉ cho percentage) -->
    <div x-show="form.discount_type === 'percentage'" x-data="{
        formatted: '',
        onInput(e) {
            const raw = e.target.value.replace(/[^\d]/g, '');
            form.max_discount = raw ? Number(raw) : 0;
            this.formatted = raw ? Number(raw).toLocaleString('en-US') : '';
        }
    }" x-init="formatted = form.max_discount ? form.max_discount.toLocaleString('en-US') : ''">
        <label class="block text-sm font-semibold mb-1">Giảm tối đa (₫)
            <span title="Để trống nếu không giới hạn số tiền giảm tối đa"
                class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
        </label>
        <input x-model="formatted" type="text" @input="onInput($event)"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            placeholder="VD: 500,000 (để trống nếu không giới hạn)" />
    </div>

    <!-- Ngày bắt đầu -->
    <div>
        <label class="block text-sm font-semibold mb-1">Ngày bắt đầu <span class="text-red-500">*</span></label>
        <input x-model="form.starts_at" type="text"
            class="coupon-start-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.starts_at && errors.starts_at) ? 'border-red-500' : 'border-gray-300'"
            placeholder="Chọn ngày" autocomplete="off" @blur="touched.starts_at = true; validateField('starts_at')" />
        <p x-show="touched.starts_at && errors.starts_at" x-text="errors.starts_at" class="text-red-500 text-xs mt-1">
        </p>
    </div>

    <!-- Ngày kết thúc -->
    <div>
        <label class="block text-sm font-semibold mb-1">Ngày kết thúc <span class="text-red-500">*</span></label>
        <input x-model="form.ends_at" type="text"
            class="coupon-end-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.ends_at && errors.ends_at) ? 'border-red-500' : 'border-gray-300'" placeholder="Chọn ngày"
            autocomplete="off" @blur="touched.ends_at = true; validateField('ends_at')" />
        <p x-show="touched.ends_at && errors.ends_at" x-text="errors.ends_at" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Số lần sử dụng tối đa -->
    <div>
        <label class="block text-sm font-semibold mb-1">Số lần sử dụng tối đa
            <span title="Tổng số lần mã có thể được sử dụng"
                class="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 text-gray-400 text-xs font-bold cursor-help">?</span>
        </label>
        <input x-model.number="form.max_uses" type="number" min="0"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            placeholder="Để trống nếu không giới hạn" />
    </div>

    <!-- Trạng thái -->
    <div class="md:col-span-2 flex items-center gap-3">
        <input id="isActive" type="checkbox" x-model="form.is_active" true-value="1" false-value="0" class="h-4 w-4">
        <label for="isActive" class="text-sm">Kích hoạt</label>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            // Ngày bắt đầu
            flatpickr(".coupon-start-date", {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true
            });

            // Ngày kết thúc
            flatpickr(".coupon-end-date", {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true
            });
        }
    });
</script>