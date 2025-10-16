<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã đơn hàng -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã đơn hàng <span
                class="text-red-500">*</span></label>
        <input x-model="form.code" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="mt-4 md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Sản phẩm <span class="text-red-500">*</span></label>
        
        <template x-if="orderItems.length === 0">
            <p class="text-red-500 text-xs mb-2">Vui lòng chọn ít nhất một sản phẩm.</p>
        </template>

        <div class="space-y-2">
            <!-- Header -->
            <div class="grid grid-cols-12 gap-2 items-center font-semibold text-xs text-slate-800 mb-1">
                <div class="col-span-5">Tên sản phẩm - Mã sản phẩm - Tồn kho</div>
                <div class="col-span-2">Số lượng</div>
                <div class="col-span-2">Đơn giá</div>
                <div class="col-span-3">Thành tiền</div>
            </div>

            <template x-for="(item, idx) in orderItems" :key="idx">
                <div class="grid grid-cols-12 gap-2 items-start">
                    <!-- Chọn sản phẩm -->
                    <div class="col-span-5 relative" x-data="{
                        open: false,
                        search: '',
                        filtered: [],
                        highlight: -1,
                        choose(p) {
                            item.product_id = p.id;
                            this.search = p.name + ' (' + p.sku + ' - ' + (p.stock || 0) + ')';
                            this.open = false;
                            if (p.sale_price) {
                                item.unit_price = parseInt(p.sale_price.toString().replace(/,/g, ''));
                            }
                            calculateTotal();
                        },
                        clear() {
                            item.product_id = '';
                            this.search = '';
                            this.filtered = products;
                            this.open = false;
                            calculateTotal();
                        },
                        filter() {
                            const used = orderItems.map((it, i) => i !== idx ? it.product_id : null).filter(Boolean);
                            this.filtered = products.filter(p => 
                                !used.includes(p.id) && 
                                (p.name + p.sku).toLowerCase().includes(this.search.toLowerCase())
                            );
                        },
                        reset() {
                            const selected = products.find(p => p.id == item.product_id);
                            this.search = selected ? (selected.name + ' (' + selected.sku + ' - ' + (selected.stock || 0) + ')') : '';
                            this.filter();
                        }
                    }" x-init="reset()" @click.away="open = false">
                        <div class="relative">
                            <input type="text" x-model="search" 
                                @focus="open = true; filter()" 
                                @input="filter()"
                                class="w-full border rounded px-3 py-2 pr-8 text-sm"
                                placeholder="-- Chọn sản phẩm --" />
                            
                            <button x-show="item.product_id" type="button" @click.stop="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">✕</button>
                            
                            <svg x-show="!item.product_id"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                            <template x-for="(p, i) in filtered" :key="p.id">
                                <div @click="choose(p)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                    :class="[
                                        highlight === i ? 'bg-[#002975] text-white' : 
                                        (item.product_id == p.id ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'),
                                        'px-3 py-2 cursor-pointer text-sm'
                                    ]"
                                    x-text="p.name + ' (' + p.sku + ' - ' + (p.stock || 0) + ')'">
                                </div>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                                Không tìm thấy sản phẩm
                            </div>
                        </div>
                    </div>

                    <!-- Số lượng -->
                    <div class="col-span-2">
                        <input x-model.number="item.quantity" @input="calculateTotal()" 
                            type="number" min="1" max="999"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="SL" />
                    </div>

                    <!-- Đơn giá -->
                    <div class="col-span-2">
                        <input :value="item.unit_price ? item.unit_price.toLocaleString('en-US') : ''"
                            @input="
                                let val = $event.target.value.replace(/[^\d]/g, '');
                                item.unit_price = val ? parseInt(val) : 0;
                                $event.target.value = item.unit_price.toLocaleString('en-US');
                                calculateTotal();
                            "
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="Đơn giá" />
                    </div>

                    <!-- Thành tiền & Action -->
                    <div class="col-span-3 flex items-center gap-2">
                        <div class="flex-1 font-semibold text-sm" 
                            x-text="((item.quantity || 0) * (item.unit_price || 0)).toLocaleString('en-US')">
                        </div>
                        
                        <button type="button" @click="removeItem(idx)" 
                            class="text-red-500 hover:text-red-700"
                            title="Xóa">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Thêm sản phẩm -->
            <button type="button" @click="addItem()" 
                class="mt-2 px-3 py-2 text-sm border border-dashed border-[#002975] text-[#002975] rounded hover:bg-[#002975] hover:text-white">
                <i class="fa-solid fa-plus mr-1"></i> Thêm sản phẩm
            </button>
        </div>
    </div>

    <!-- Phần tính tiền -->
    <div class="md:col-span-2 border-t-2 border-gray-300 pt-4 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Cột trái: Phương thức thanh toán -->
            <div class="space-y-4">
                <!-- Phương thức thanh toán -->
                <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            methods: [
                {id: 'cash', name: 'Tiền mặt'},
                {id: 'credit_card', name: 'Quẹt thẻ'},
                {id: 'bank_transfer', name: 'Chuyển khoản'}
            ],
            choose(method) {
                form.payment_method = method.id;
                this.search = method.name;
                this.open = false;
            },
            clear() {
                form.payment_method = '';
                this.search = '';
                this.filtered = this.methods;
                this.open = false;
            },
            reset() {
                const selected = this.methods.find(m => m.id == form.payment_method);
                this.search = selected ? selected.name : '';
                this.filtered = this.methods;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Phương thức thanh toán
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = methods"
                @input="open = true; filtered = methods.filter(m => m.name.toLowerCase().includes(search.toLowerCase()))"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="form.payment_method === '' ? 'text-slate-400' : 'text-slate-900'"
                placeholder="-- Chọn phương thức --" />

            <button x-show="form.payment_method" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.payment_method"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(method, i) in filtered" :key="method.id">
                <div @click="choose(method)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.payment_method == method.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="method.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy phương thức
            </div>
        </div>
                </div>
            </div>

            <!-- Cột phải: Tính tiền -->
            <div class="md:col-span-2 space-y-3">
                <!-- Tạm tính (readonly - tự động tính từ sản phẩm) -->
                <div class="flex items-center gap-4">
                    <label class="text-sm text-black font-semibold w-48">Tạm tính:</label>
                    <input x-model="form.subtotalFormatted" class="flex-1 border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <!-- Giảm giá -->
                <div class="flex items-center gap-4">
                    <label class="text-sm text-black font-semibold w-48">Giảm giá:</label>
                    <input x-model="form.discount_amountFormatted" @input="onAmountInput('discount_amount', $event)"
                        class="flex-1 border rounded px-3 py-2" placeholder="Nhập giảm giá (nếu có)">
                </div>

                <!-- Tổng tiền -->
                <div>
                    <div class="flex items-center gap-4">
                        <label class="text-lg text-black font-semibold w-48">Tổng tiền cần thanh toán <span class="text-red-500">*</span></label>
                        <input x-model="form.total_amountFormatted" class="flex-1 border rounded px-3 py-3 bg-gray-50 text-xl font-bold text-[#002975]" readonly>
                    </div>
                    <p class="text-red-600 text-xs mt-1 ml-52" x-show="touched.total_amount && errors.total_amount"
                        x-text="errors.total_amount"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ghi chú -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <textarea x-model="form.note" rows="3" class="w-full border rounded px-3 py-2"
            placeholder="Nhập ghi chú"></textarea>
    </div>
</div