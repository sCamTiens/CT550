<form class="p-5" @submit.prevent="submit()">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Nhà cung cấp -->
        <div class="relative" x-data="{
                open: false,
                search: '',
                filtered: [],
                highlight: -1,
                choose(s) {
                    form.supplier_id = s.id;
                    this.search = s.name;
                    this.open = false;
                },
                clear() {
                    form.supplier_id = '';
                    this.search = '';
                    this.filtered = suppliers;
                    this.open = false;
                },
                reset() {
                    const selected = suppliers.find(s => s.id == form.supplier_id);
                    this.search = selected ? selected.name : '';
                    this.filtered = suppliers;
                    this.highlight = -1;
                }
            }" x-init="reset()" @click.away="open = false">
            <label class="block text-sm text-black font-semibold mb-1">
                Nhà cung cấp <span class="text-red-500">*</span>
            </label>

            <div class="relative">
                <input type="text" x-model="search" @focus="open = true; filtered = suppliers" @input="
                open = true;
                filtered = suppliers.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))
                " @blur="touched.supplier_id = true; validateField('supplier_id')"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                    :class="(touched.supplier_id && errors.supplier_id) ? 'border-red-500' : 'border-gray-300'"
                    placeholder="-- Chọn nhà cung cấp --" />

                <button x-show="form.supplier_id" type="button" @click.stop="clear()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                    ✕
                </button>

                <svg x-show="!form.supplier_id"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                <template x-for="(s, i) in filtered" :key="s.id">
                    <div @click="choose(s)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[highlight === i
                    ? 'bg-[#002975] text-white'
                    : (form.supplier_id == s.id
                        ? 'bg-[#002975] text-white'
                        : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="s.name">
                    </div>
                </template>
                <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                    Không tìm thấy nhà cung cấp
                </div>
            </div>

            <p x-show="touched.supplier_id && errors.supplier_id" x-text="errors.supplier_id"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Ngày nhập -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Ngày nhập <span
                    class="text-red-500">*</span></label>

            <div class="relative">
                <input x-model="form.created_at" @blur="touched.created_at=true; validateField('created_at')"
                    @input="touched.created_at && validateField('created_at')" type="text"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 purchase-date-picker"
                    placeholder="Chọn ngày nhập" autocomplete="off">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fa-regular fa-calendar"></i>
                </span>
            </div>

            <p x-show="touched.created_at && errors.created_at" x-text="errors.created_at"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Danh sách mặt hàng -->
        <div class="mt-4 col-span-2">
            <label class="block text-sm text-black font-semibold mb-1">Mặt hàng
                <span class="text-red-500">*</span></label>

            <template x-if="lines.length === 0">
                <p class="text-red-500 text-xs mb-2">Vui lòng chọn ít nhất một mặt hàng.</p>
            </template>

            <div class="space-y-2"
                x-init="if (!lines || lines.length === 0) lines.push({product_id:'', qty:1, unit_cost:0})">
                <!-- Header row -->
                <div class="grid grid-cols-12 gap-2 items-center font-semibold text-xs text-slate-800 mb-1">
                    <div class="col-span-5">Tên sản phẩm</div>
                    <div class="col-span-2">Số lượng</div>
                    <div class="col-span-3">Giá nhập</div>
                    <div class="col-span-2"></div>
                </div>

                <template x-for="(l, idx) in lines" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-start">

                        <!-- Auto-complete sản phẩm -->
                        <div class="col-span-5 relative" x-data="autocomplete({
                                model: l,
                                key: 'product_id',
                                options: products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id)),
                                label: 'Sản phẩm',
                                placeholder: '-- Chọn sản phẩm --',
                                display(p) { return p.name + ' (' + p.sku + ')' },
                                onChoose(p) {
                                    if (typeof p.cost_price !== 'undefined') {
                                        let val = parseInt(p.cost_price.toString().replace(/,/g, '').split('.')[0]);
                                        l.unit_cost = isNaN(val) ? 0 : val;
                                        setTimeout(() => {
                                            const input = document.querySelectorAll('[data-unit-cost-input]')[idx];
                                            if (input && l.unit_cost) input.value = l.unit_cost.toLocaleString('en-US');
                                        }, 0);
                                    }
                                }
                            })" @click.away="open = false">
                            <div class="relative">
                                <input type="text" x-model="search" @focus="open = true; filter()" @input="filter()"
                                    @blur="touchedLines[idx]=true; validateField('product', idx)"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                    style="appearance: none;" :placeholder="l.product_id ? '' : placeholder"
                                    :class="l.product_id ? 'text-black' : 'text-gray-500'" />

                                <!-- Xóa -->
                                <button x-show="model[key]" type="button" @click.stop="clear()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">✕</button>

                                <!-- Icon -->
                                <svg x-show="!model[key]"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <!-- Dropdown -->
                            <div x-show="open"
                                class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                                <template x-for="(p, i) in filtered" :key="p.id">
                                    <div @click="choose(p)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                        :class="[highlight === i ? 'bg-[#002975] text-white'
                                        : (l.product_id == p.id ? 'bg-[#002975] text-white'
                                        : 'hover:bg-[#002975] hover:text-white text-black'),
                                        'px-3 py-2 cursor-pointer text-sm transition-colors']" x-text="display(p)">
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm
                                    thấy</div>
                            </div>

                            <p x-show="!l.product_id && touchedLines[idx]" class="text-red-500 text-xs mt-1">Vui lòng
                                chọn sản phẩm.</p>
                        </div>

                        <!-- Số lượng -->
                        <div class="col-span-2">
                            <input x-model.number="l.qty" type="number" min="1" max="999" required
                                placeholder="Số lượng" @blur="touchedLines[idx]=true; validateField('qty', idx)"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                            <template x-if="touchedLines[idx]">
                                <div>
                                    <p x-show="l.qty < 1" class="text-red-500 text-xs mt-1">Số lượng phải lớn hơn 0.</p>
                                    <p x-show="l.qty > 999" class="text-red-500 text-xs mt-1">Số lượng không được vượt
                                        quá 999.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Đơn giá -->
                        <div class="col-span-3">
                            <input data-unit-cost-input
                                :value="l.unit_cost !== undefined && l.unit_cost !== null && l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : ''"
                                @input="
                                    let val = $event.target.value.replace(/[^\d]/g, '');
                                    l.unit_cost = val ? parseInt(val, 10) : '';
                                    $event.target.value = l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : '';
                                "
                                @blur="$event.target.value = l.unit_cost !== '' ? l.unit_cost.toLocaleString('en-US') : ''; touchedLines[idx]=true; validateField('unit_cost', idx)"
                                @focus="$event.target.select()" required placeholder="Đơn giá" inputmode="numeric"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                            <template x-if="touchedLines[idx]">
                                <div>
                                    <p x-show="l.unit_cost < 0" class="text-red-500 text-xs mt-1">Giá nhập không được
                                        âm.</p>
                                    <p x-show="l.unit_cost > 999999999" class="text-red-500 text-xs mt-1">Giá nhập không
                                        vượt quá 999,999,999.</p>
                                </div>
                            </template>
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
                placeholder="Nhập ghi chú">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            document.querySelectorAll('.purchase-date-picker').forEach(function (input) {
                flatpickr(input, {
                    dateFormat: 'd/m/Y',
                    locale: 'vi',
                    allowInput: true,
                    static: true,
                    appendTo: input.parentElement
                });
            });
        }
    });
</script>