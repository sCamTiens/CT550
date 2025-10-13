<form class="p-5" @submit.prevent="submit()">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Nhà cung cấp -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">
                Nhà cung cấp <span class="text-red-500">*</span>
            </label>
            <div class="relative" x-data="{
                    open: false,
                    search: '',
                    filtered: suppliers,
                    highlight: -1,
                    choose(s) {
                        $root.supplier_id = s.id; // Cập nhật supplier_id ở component cha
                        this.search = s.name;
                        this.open = false;
                    },
                    reset() {
                        const selected = suppliers.find(s => s.id == $root.supplier_id);
                        this.search = selected ? selected.name : '';
                        this.filtered = suppliers;
                        this.highlight = -1;
                    }
                    }" x-init="reset()" @click.away="open = false">

                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true; filtered = suppliers"
                        @input="filtered = suppliers.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 bg-white text-sm cursor-pointer select-none focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                        style="appearance: none;" :placeholder="supplier_id ? '' : '-- Chọn nhà cung cấp --'"
                        :class="supplier_id ? 'text-black' : 'text-gray-500'" />

                    <!-- Mũi tên giống <select> -->
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <!-- Dropdown -->
                <div x-show="open"
                    class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                    <template x-for="(s, idx) in filtered" :key="s.id">
                        <div @click="choose(s)" @mouseenter="highlight = idx" @mouseleave="highlight = -1" :class="[
                highlight === idx
                  ? 'bg-[#002975] text-white'
                  : (supplier_id == s.id
                      ? 'bg-[#002975] text-white'
                      : 'hover:bg-[#002975] hover:text-white text-black'),
                'px-3 py-2 cursor-pointer transition-colors text-sm'
              ]" x-text="s.name"></div>
                    </template>
                    <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm thấy</div>
                </div>
            </div>
        </div>

        <!-- Ngày nhập -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Ngày nhập</label>
            <input x-model="form.created_at" type="text"
                class="w-full border border-gray-300 rounded-md px-3 py-2 purchase-date-picker" placeholder="dd/mm/yyyy"
                autocomplete="off">
        </div>

        <!-- Danh sách mặt hàng -->
        <div class="mt-4 col-span-2">
            <label class="block text-sm text-black font-semibold mb-1">Mặt hàng
                <span class="text-red-500">*</span></label>
            <div class="space-y-2">
                <!-- Header row -->
                <div class="grid grid-cols-12 gap-2 items-center font-semibold text-xs text-slate-800 mb-1">
                    <div class="col-span-5">Tên sản phẩm</div>
                    <div class="col-span-2">Số lượng</div>
                    <div class="col-span-3">Giá nhập</div>
                    <div class="col-span-2"></div>
                </div>
                <template x-for="(l, idx) in lines" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-center">

                        <!-- Auto-complete sản phẩm -->
                        <div class="col-span-5 relative" x-data="{
                            open: false,
                            search: '',
                            filtered: [],
                            highlight: -1,
                            choose(p) {
                                l.product_id = p.id;
                                this.search = p.name + ' (' + p.sku + ')';
                                // Always set unit_cost to product's cost_price when choosing, formatted as integer (no decimals)
                                if (typeof p.cost_price !== 'undefined') {
                                    let val = p.cost_price;
                                    if (typeof val === 'string') {
                                        // Remove commas and decimals, keep only integer part
                                        val = val.replace(/,/g, '');
                                        val = val.split('.')[0];
                                        val = parseInt(val, 10);
                                    } else {
                                        val = Math.floor(val);
                                    }
                                    l.unit_cost = val;
                                    // Format the visible input if possible
                                    setTimeout(() => {
                                        const input = document.querySelectorAll('[data-unit-cost-input]')[idx];
                                        if (input && l.unit_cost !== '' && l.unit_cost !== undefined && l.unit_cost !== null) {
                                            input.value = l.unit_cost.toLocaleString('en-US');
                                        }
                                    }, 0);
                                }
                                this.open = false;
                            },
                            reset() {
                                const selected = products.find(p => p.id == l.product_id);
                                this.search = selected ? `${selected.name} (${selected.sku})` : '';
                                this.filtered = products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id));
                                this.highlight = -1;
                            }
                        }" x-init="reset()" @click.away="open = false">
                            <div class="relative">
                                <input type="text" x-model="search"
                                    @focus="open = true; filtered = products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id))"
                                    @input="filtered = products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id) && (p.name + ' ' + p.sku).toLowerCase().includes(search.toLowerCase()))"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 bg-white text-sm cursor-pointer select-none focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                    style="appearance: none;" :placeholder="l.product_id ? '' : '-- Chọn sản phẩm --'"
                                    :class="l.product_id ? 'text-black' : 'text-gray-500'" />
                                <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div x-show="open"
                                class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                                <template x-for="(p, i) in filtered" :key="p.id">
                                    <div @click="choose(p)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                        :class="[
                                            highlight === i
                                                ? 'bg-[#002975] text-white'
                                                : (l.product_id == p.id
                                                    ? 'bg-[#002975] text-white'
                                                    : 'hover:bg-[#002975] hover:text-white text-black'),
                                            'px-3 py-2 cursor-pointer transition-colors text-sm'
                                        ]" x-text="p.name + ' (' + p.sku + ')'">
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm thấy</div>
                            </div>
                        </div>

                        <!-- Số lượng -->
                        <div class="col-span-2">
                            <input x-model.number="l.qty" type="number" min="1" required placeholder="Số lượng"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                        </div>

                        <!-- Đơn giá -->
                        <div class="col-span-3">
                            <input
                                data-unit-cost-input
                                :value="l.unit_cost !== undefined && l.unit_cost !== null && l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : ''"
                                @input="
                                    let val = $event.target.value.replace(/[^\d]/g, '');
                                    l.unit_cost = val ? parseInt(val, 10) : '';
                                    $event.target.value = l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : '';
                                "
                                @blur="$event.target.value = l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : ''"
                                @focus="$event.target.select()"
                                required placeholder="Đơn giá"
                                inputmode="numeric"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                        </div>

                        <!-- Xóa -->
                        <div class="col-span-2 flex gap-1">
                            <button type="button" @click="lines.splice(idx,1)"
                                class="px-3 py-2 rounded border text-red-500 border-red-300 hover:bg-red-50 text-sm">
                                Xóa
                            </button>
                        </div>
                    </div>
                </template>

                <button type="button" @click="lines.push({product_id:'', qty:1, unit_cost:0})"
                    class="px-3 py-2 border rounded mt-2 text-sm">+ Thêm dòng</button>
            </div>
        </div>

        <!-- Ghi chú -->
        <div class="mt-4 col-span-2">
            <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
            <input x-model="form.note" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                placeholder="Ghi chú phiếu nhập">
        </div>

        <!-- Nút hành động -->
        <div class="flex justify-end gap-3 mt-6 col-span-2">
            <button type="button" @click="resetAll(); openForm=false"
                class="px-4 py-2 border rounded text-sm">Hủy</button>
            <button class="px-4 py-2 bg-[#002975] text-white rounded text-sm" :disabled="submitting"
                x-text="submitting ? 'Đang lưu...' : (form.id ? 'Cập nhật' : 'Tạo')"></button>
        </div>

    </div>
</form>

<!-- Flatpickr -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="module" src="/assets/js/flatpickr-vi.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            flatpickr('.purchase-date-picker', {
                dateFormat: 'd/m/Y',
                locale: 'vi',
                allowInput: true,
                defaultDate: undefined
            });
        }
    });
</script>