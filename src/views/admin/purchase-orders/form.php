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
                    :class="(touched.created_at && errors.created_at) ? 'border-red-500' : 'border-gray-300'"
                    placeholder="Chọn ngày nhập" autocomplete="off">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fa-regular fa-calendar"></i>
                </span>
            </div>

            <p x-show="touched.created_at && errors.created_at" x-text="errors.created_at"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Trạng thái thanh toán -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Trạng thái thanh toán <span
                    class="text-red-500">*</span></label>
            <select x-model="form.payment_status" @change="
                    if (form.payment_status === 'Đã thanh toán hết') {
                        form.paid_amount = calculateTotal();
                    } else if (form.payment_status === 'Chưa đối soát') {
                        form.paid_amount = 0;
                    }
                    touched.payment_status=true; 
                    validateField('payment_status')
                "
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.payment_status && errors.payment_status) ? 'border-red-500' : 'border-gray-300'">
                <option value="Chưa đối soát">Chưa đối soát</option>
                <option value="Đã thanh toán một phần">Đã thanh toán một phần</option>
                <option value="Đã thanh toán hết">Đã thanh toán hết</option>
            </select>
        </div>

        <!-- Tổng tiền -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Tổng tiền</label>
            <input readonly :value="calculateTotal().toLocaleString('vn-VN') + ' đ'"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-gray-50 font-semibold text-[#002975]">
        </div>

        <!-- Số tiền thanh toán (chỉ hiện khi thanh toán một phần hoặc hết) -->
        <div x-show="form.payment_status === 'Đã thanh toán một phần' || form.payment_status === 'Đã thanh toán hết'">
            <label class="block text-sm text-black font-semibold mb-1">
                Số tiền thanh toán <span class="text-red-500">*</span>
            </label>
            <input
                :value="form.paid_amount !== undefined && form.paid_amount !== null && form.paid_amount !== '' ? parseInt(form.paid_amount).toLocaleString('en-US') : ''"
                @input="
                    let val = $event.target.value.replace(/[^\d]/g, '');
                    form.paid_amount = val ? parseInt(val, 10) : 0;
                    $event.target.value = form.paid_amount ? form.paid_amount.toLocaleString('en-US') : '';
                " @blur="
                    $event.target.value = form.paid_amount ? form.paid_amount.toLocaleString('en-US') : '';
                    touched.paid_amount=true; 
                    validateField('paid_amount')
                " @focus="$event.target.select()" :readonly="form.payment_status === 'Đã thanh toán hết'"
                inputmode="numeric" placeholder="Nhập số tiền"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="form.payment_status === 'Đã thanh toán hết' ? 'bg-gray-50' : ''">
            <p x-show="touched.paid_amount && errors.paid_amount" x-text="errors.paid_amount"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Công nợ (Tổng tiền - Số tiền thanh toán) -->
        <div x-show="form.payment_status === 'Đã thanh toán một phần' || form.payment_status === 'Chưa đối soát'">
            <label class="block text-sm text-black font-semibold mb-1">Công nợ</label>
            <input readonly :value="(calculateTotal() - (form.paid_amount || 0)).toLocaleString('vn-VN') + ' đ'"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-gray-50 font-semibold text-red-600">
        </div>

        <!-- Ngày hẹn thanh toán (chỉ hiện khi chưa đối soát hoặc thanh toán một phần) -->
        <div x-show="form.payment_status === 'Chưa đối soát' || form.payment_status === 'Đã thanh toán một phần'">
            <label class="block text-sm text-black font-semibold mb-1">
                Ngày hẹn thanh toán <span class="text-red-500">*</span>
            </label>

            <div class="relative">
                <input x-model="form.due_date" type="text" placeholder="Chọn ngày" autocomplete="off"
                    class="w-full border rounded-md px-3 py-2 due-date-picker text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                    :class="{
                'border-gray-300': !(touched.due_date && !form.due_date),
                'border-red-500': touched.due_date && !form.due_date
            }" @blur="touched.due_date = true">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fa-regular fa-calendar"></i>
                </span>
            </div>

            <!-- Hiển thị lỗi -->
            <p x-show="touched.due_date && !form.due_date" class="text-red-500 text-xs mt-1">
                Vui lòng chọn ngày hẹn thanh toán
            </p>
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
                    <div class="col-span-2">Ngày sản xuất <span class="text-red-500">*</span></div>
                    <div class="col-span-2">Hạn sử dụng <span class="text-red-500">*</span></div>
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
                                reset() {
                                    const selected = products.find(p => p.id == l.product_id);
                                    this.search = selected ? selected.name + ' (' + selected.sku + ')' : '';
                                    this.filtered = products.filter(p => !lines.some((line, i) => i !== idx && line.product_id == p.id));
                                    this.highlight = -1;
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
                                    :placeholder="l.product_id ? '' : '-- Chọn sản phẩm --'" />

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
                            <input x-model="l.mfg_date" type="text"
                                :class="'w-full border border-gray-300 rounded-md px-3 py-2 text-sm line-mfg-date-' + idx"
                                placeholder="Chọn ngày" autocomplete="off" @blur="touchedLines[idx] = true" />

                            <!-- Hiển thị lỗi nếu bỏ trống -->
                            <p x-show="touchedLines[idx] && !l.mfg_date" class="text-red-500 text-xs mt-1">
                                Vui lòng chọn ngày sản xuất.
                            </p>
                        </div>

                        <!-- Hạn sử dụng (HSD) -->
                        <div class="col-span-2">

                            <div class="relative">
                                <input x-model="l.exp_date" type="text"
                                    :class="'w-full border border-gray-300 rounded-md px-3 py-2 text-sm line-exp-date-' + idx"
                                    placeholder="Chọn ngày" autocomplete="off" @blur="touchedLines[idx] = true" />

                                <!-- Hiện lỗi khi bỏ trống -->
                                <p x-show="touchedLines[idx] && !l.exp_date" class="text-red-500 text-xs mt-1">
                                    Vui lòng chọn hạn sử dụng.
                                </p>

                                <!-- Hiện lỗi khi HSD < NSX -->
                                <p x-show="touchedLines[idx] && l.exp_date && l.mfg_date && 
                                    (new Date(l.exp_date.split('/').reverse().join('-')) < 
                                    new Date(l.mfg_date.split('/').reverse().join('-')))"
                                    class="text-red-500 text-xs mt-1">
                                    Hạn sử dụng phải lớn hơn hoặc bằng ngày sản xuất.
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

<script>
    // HÀM khởi tạo flatpickr cho từng dòng mới
    window.initLineFlatpickr = function (idx) {
        setTimeout(() => {
            // Ngày sản xuất
            const mfgInputs = document.querySelectorAll(`.line-mfg-date-${idx}`);
            mfgInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        allowInput: true,
                        clickOpens: true,
                        static: true
                    });
                }
            });

            // Hạn sử dụng
            const expInputs = document.querySelectorAll(`.line-exp-date-${idx}`);
            expInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        allowInput: true,
                        clickOpens: true,
                        static: true
                    });
                }
            });
        }, 100);
    };

    // HÀM khởi tạo tất cả flatpickr khi modal mở
    window.initAllDatePickers = function () {
        if (typeof flatpickr === 'undefined') {
            console.warn('flatpickr chưa được load');
            return;
        }

        setTimeout(() => {
            // Ngày nhập
            const dateInputs = document.querySelectorAll('.purchase-date-picker');
            dateInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        allowInput: true,
                        clickOpens: true,
                        static: true
                    });
                }
            });

            // Ngày hẹn thanh toán
            const dueDateInputs = document.querySelectorAll('.due-date-picker');
            dueDateInputs.forEach(function (input) {
                if (!input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'd/m/Y',
                        locale: 'vn',
                        allowInput: true,
                        clickOpens: true,
                        static: true
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
</script>