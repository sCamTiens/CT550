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
                    touched.supplier_id = true;
                    validateField('supplier_id');
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
            }" x-effect="reset()" @click.away="open = false">
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
                x-init="if (!lines || lines.length === 0) lines.push({product_id:'', qty:1, unit_cost:0, mfg_date:'', exp_date:''})">
                <!-- Header row -->
                <div class="grid grid-cols-12 gap-2 items-center font-semibold text-xs text-slate-800 mb-1">
                    <div class="col-span-3">Tên sản phẩm</div>
                    <div class="col-span-1">SL</div>
                    <div class="col-span-2">Giá nhập</div>
                    <div class="col-span-2">NSX</div>
                    <div class="col-span-2">HSD</div>
                    <div class="col-span-2"></div>
                </div>

                <template x-for="(l, idx) in lines" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-start">

                        <!-- Auto-complete sản phẩm -->
                        <div class="col-span-3 relative" x-data="{
                                open: false,
                                search: '',
                                filtered: [],
                                highlight: -1,
                                init() {
                                    const selected = products.find(p => p.id == l.product_id);
                                    this.search = selected ? selected.name + ' (' + selected.sku + ')' : '';
                                    this.filtered = products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id));
                                },
                                filter() {
                                    this.filtered = products.filter(p => {
                                        const alreadySelected = lines.some((line, i) => i !== idx && line.product_id == p.id);
                                        if (alreadySelected) return false;
                                        const text = p.name + ' ' + p.sku;
                                        return text.toLowerCase().includes(this.search.toLowerCase());
                                    });
                                },
                                choose(p) {
                                    l.product_id = p.id;
                                    this.search = p.name + ' (' + p.sku + ')';
                                    this.open = false;
                                    touched.product_id = true;
                                    validateField('product_id');
                                    
                                    if (typeof p.cost_price !== 'undefined') {
                                        let val = parseInt(p.cost_price.toString().replace(/,/g, '').split('.')[0]);
                                        l.unit_cost = isNaN(val) ? 0 : val;
                                        setTimeout(() => {
                                            const input = document.querySelectorAll('[data-unit-cost-input]')[idx];
                                            if (input && l.unit_cost) input.value = l.unit_cost.toLocaleString('en-US');
                                        }, 0);
                                    }
                                },
                                clear() {
                                    l.product_id = '';
                                    this.search = '';
                                    this.filter();
                                    this.open = false;
                                }
                            }" x-effect="reset()" @click.away="open = false">
                            <div class="relative">
                                <input type="text" x-model="search" @focus="open = true; filter()" @input="filter()"
                                    @blur="touchedLines[idx]=true; validateField('product', idx)"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                    :placeholder="l.product_id ? '' : '-- Chọn sản phẩm --'"
                                    :class="l.product_id ? 'text-black' : 'text-gray-500'" />

                                <!-- Xóa -->
                                <button x-show="l.product_id" type="button" @click.stop="clear()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">✕</button>

                                <!-- Icon -->
                                <svg x-show="!l.product_id"
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
                                        'px-3 py-2 cursor-pointer text-sm transition-colors']"
                                        x-text="p.name + ' (' + p.sku + ')'">
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">Không tìm
                                    thấy</div>
                            </div>

                            <p x-show="!l.product_id && touchedLines[idx]" class="text-red-500 text-xs mt-1">Vui lòng
                                chọn sản phẩm.</p>
                        </div>

                        <!-- Số lượng -->
                        <div class="col-span-1">
                            <input x-model.number="l.qty" type="number" min="1" max="999" required placeholder="SL"
                                @blur="touchedLines[idx]=true; validateField('qty', idx)"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                            <template x-if="touchedLines[idx]">
                                <div>
                                    <p x-show="l.qty < 1" class="text-red-500 text-xs mt-1">Phải > 0</p>
                                    <p x-show="l.qty > 999" class="text-red-500 text-xs mt-1">Max 999</p>
                                </div>
                            </template>
                        </div>

                        <!-- Đơn giá -->
                        <div class="col-span-2">
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
                                    <p x-show="l.unit_cost < 0" class="text-red-500 text-xs mt-1">Không được âm</p>
                                    <p x-show="l.unit_cost > 999999999" class="text-red-500 text-xs mt-1">Max 999M</p>
                                </div>
                            </template>
                        </div>

                        <!-- Ngày sản xuất (NSX) -->
                        <div class="col-span-2">
                            <input x-model="l.mfg_date" type="text" :class="'line-mfg-date-' + idx"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                placeholder="dd/mm/yyyy" autocomplete="off" />
                        </div>

                        <!-- Hạn sử dụng (HSD) -->
                        <div class="col-span-2">
                            <div class="relative">
                                <input x-model="l.exp_date" type="text" :class="'line-exp-date-' + idx"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                    placeholder="dd/mm/yyyy" autocomplete="off" @blur="touchedLines[idx]=true" />

                                <!-- Hiện lỗi khi HSD < NSX -->
                                <p x-show="touchedLines[idx] && l.exp_date && l.mfg_date && (new Date(l.exp_date.split('/').reverse().join('-')) < new Date(l.mfg_date.split('/').reverse().join('-')))"
                                    class="text-red-500 text-xs mt-1">
                                    Hạn sử dụng phải lớn hơn hoặc bằng ngày sản xuất
                                </p>
                            </div>
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

                <button type="button" @click="lines.push({product_id:'', qty:1, unit_cost:0, mfg_date:'', exp_date:''}); 
                    setTimeout(() => initLineFlatpickr(lines.length - 1), 100)"
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
            <button type="button" class="px-4 py-2 rounded-md text-red-600 border border-red-600 
                  hover:bg-red-600 hover:text-white transition-colors" @click="openAdd=false">Hủy</button>
            <button
                class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
                :disabled="submitting" x-text="submitting?'Đang lưu...':'Lưu'"></button>
        </div>
    </div>
</form>

<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/vi.js"></script>
<script>
    // Global function để khởi tạo flatpickr cho từng dòng mới
    window.initLineFlatpickr = function (idx) {
        setTimeout(() => {
            // Ngày sản xuất
            const mfgInputs = document.querySelectorAll(`.line-mfg-date-${idx}`);
            mfgInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vi',
                        allowInput: true,
                        clickOpens: true,
                        static: false
                    });
                }
            });

            // Hạn sử dụng
            const expInputs = document.querySelectorAll(`.line-exp-date-${idx}`);
            expInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vi',
                        allowInput: true,
                        clickOpens: true,
                        static: false
                    });
                }
            });
        }, 100);
    };

    // Khởi tạo tất cả flatpickr khi modal mở
    window.initAllDatePickers = function () {
        setTimeout(() => {
            // Ngày nhập
            const dateInputs = document.querySelectorAll('.purchase-date-picker');
            dateInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vi',
                        allowInput: true,
                        clickOpens: true,
                        static: false
                    });
                }
            });

            // Khởi tạo cho tất cả các dòng hiện có
            const lines = document.querySelectorAll('[class*="line-mfg-date-"], [class*="line-exp-date-"]');
            const indices = new Set();
            lines.forEach(input => {
                const classList = Array.from(input.classList);
                classList.forEach(cls => {
                    const match = cls.match(/line-(mfg|exp)-date-(\d+)/);
                    if (match) {
                        indices.add(parseInt(match[2]));
                    }
                });
            });

            indices.forEach(idx => {
                initLineFlatpickr(idx);
            });
        }, 300);
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAllDatePickers();
    });
</script>