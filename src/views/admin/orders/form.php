<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã đơn hàng -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã đơn hàng <span
                class="text-red-500">*</span></label>
        <input x-model="form.code" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
    </div>

    <!-- Khách hàng -->
    <div class="relative" x-data="{
        open: false,
        search: '',
        filtered: [],
        highlight: -1,
        async choose(customer) {
            form.customer_id = customer.id;
            this.search = customer.name;
            this.open = false;
            // Cập nhật điểm khách hàng khi chọn
            await updateCustomerLoyaltyPoints();
        },
        clear() {
            form.customer_id = null;
            this.search = '';
            this.filtered = customers;
            this.open = false;
            // Reset điểm khi bỏ chọn khách hàng
            customerLoyaltyPoints = 0;
            form.loyalty_points_used = 0;
            calculateTotal();
        },
        reset() {
            const selected = customers.find(c => c.id == form.customer_id);
            this.search = selected ? selected.name : '';
            this.filtered = customers;
            this.highlight = -1;
        }
    }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Khách hàng
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = customers"
                @input="open = true; filtered = customers.filter(c => c.name.toLowerCase().includes(search.toLowerCase()) || (c.phone && c.phone.includes(search)))"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="!form.customer_id ? 'text-slate-400' : 'text-slate-900'" placeholder="-- Chọn khách hàng --" />

            <button x-show="form.customer_id" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.customer_id"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(customer, i) in filtered" :key="customer.id">
                <div @click="choose(customer)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                        highlight === i ? 'bg-[#002975] text-white'
                        : (form.customer_id == customer.id ? 'bg-[#002975] text-white'
                        : 'hover:bg-[#002975] hover:text-white text-black'),
                        'px-3 py-2 cursor-pointer transition-colors text-sm'
                    ]">
                    <div x-text="customer.name"></div>
                    <div class="text-xs opacity-75" x-text="customer.phone || ''"></div>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy khách hàng
            </div>
        </div>

        <!-- Hiển thị điểm tích lũy -->
        <p x-show="form.customer_id && customerLoyaltyPoints >= 0" class="mt-2 text-xs text-yellow-600">
            <i class="fa-solid fa-star"></i> Điểm tích lũy:
            <span x-text="customerLoyaltyPoints.toLocaleString('en-US') + ' điểm'"></span>
        </p>
    </div>

    <!-- Empty column to maintain grid -->
    <div></div>

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
                <div class="grid grid-cols-12 gap-2 items-start"
                    :class="item.is_gift === true ? 'bg-green-50 border border-green-200 rounded p-2' : ''">
                    <!-- Chọn sản phẩm -->
                    <div class="col-span-5 relative" x-data="{
                        open: false,
                        search: '',
                        filtered: [],
                        highlight: -1,
                        choose(product) {
                            item.product_id = product.id;
                            item.product_name = product.name;
                            item.unit_price = product.sale_price || 0;
                            if (!item.quantity || item.quantity === 0) {
                                item.quantity = 1;
                            }
                            this.search = product.name + ' - ' + product.sku + ' - Tồn: ' + product.stock;
                            this.open = false;
                            calculateTotal();
                            checkPromotions();
                        },
                        clear() {
                            item.product_id = null;
                            item.product_name = '';
                            item.unit_price = 0;
                            this.search = '';
                            this.filtered = products;
                            this.open = false;
                            calculateTotal();
                            checkPromotions();
                        },
                        reset() {
                            const selected = products.find(p => p.id == item.product_id);
                            this.search = selected ? (selected.name + ' - ' + selected.sku + ' - Tồn: ' + selected.stock) : '';
                            this.filtered = products;
                            this.highlight = -1;
                        }
                    }" x-init="reset()" @click.away="open = false">
                        <div class="relative">
                            <input type="text" x-model="search" @focus="open = true; filtered = products"
                                @input="open = true; filtered = products.filter(p => p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase()))"
                                :disabled="item.is_gift === true"
                                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                :class="item.is_gift === true ? 'bg-green-50 cursor-not-allowed' : (!item.product_id ? 'text-slate-400' : 'text-slate-900')"
                                placeholder="-- Chọn sản phẩm --" />

                            <button x-show="item.product_id && !item.is_gift" type="button" @click.stop="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                                ✕
                            </button>

                            <svg x-show="!item.product_id || item.is_gift"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div x-show="open"
                            class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                            <template x-for="(product, i) in filtered" :key="product.id">
                                <div @click="choose(product)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                    :class="[
                                        highlight === i ? 'bg-[#002975] text-white'
                                        : (item.product_id == product.id ? 'bg-[#002975] text-white'
                                        : 'hover:bg-[#002975] hover:text-white text-black'),
                                        'px-3 py-2 cursor-pointer transition-colors text-sm'
                                    ]">
                                    <div x-text="product.name + ' - ' + product.sku"></div>
                                    <div class="text-xs opacity-75"
                                        x-text="'Tồn: ' + product.stock + ' | Giá: ' + (product.sale_price || 0).toLocaleString('en-US') + 'đ'">
                                    </div>
                                </div>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                                Không tìm thấy sản phẩm
                            </div>
                        </div>

                        <!-- Badge quà tặng -->
                        <div x-show="item.is_gift === true" class="mt-1 flex items-center gap-1">
                            <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">🎁 Quà tặng</span>
                        </div>
                        <!-- Badge bundle -->
                        <div x-show="item.bundle_applied === true" class="mt-1 flex items-center gap-1">
                            <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full">📦 Mua kèm</span>
                        </div>
                    </div>

                    <!-- Số lượng -->
                    <div class="col-span-2">
                        <input x-model.number="item.quantity" @input="validateQuantity(item); checkPromotions();"
                            :disabled="item.is_gift === true" type="number" min="0"
                            class="w-full border rounded px-3 py-2 text-sm"
                            :class="item.is_gift === true ? 'bg-green-50 cursor-not-allowed' : (item.product_id && products && item.quantity > (products.find(p => p.id == item.product_id)?.stock || 0) ? 'border-red-500 bg-red-50' : '')"
                            placeholder="SL" />
                    </div>

                    <!-- Đơn giá -->
                    <div class="col-span-2">
                        <input :value="item.unit_price ? Number(item.unit_price).toLocaleString('en-US') : ''" @input="
                            let val = $event.target.value.replace(/[^\d]/g, '');
                            item.unit_price = val ? parseInt(val) : 0;
                            $event.target.value = item.unit_price ? Number(item.unit_price).toLocaleString('en-US') : '';
                            calculateTotal();
                            " :disabled="item.is_gift === true || item.bundle_applied === true"
                            class="w-full border rounded px-3 py-2 text-sm text-right"
                            :class="(item.is_gift === true || item.bundle_applied === true) ? 'bg-gray-100 cursor-not-allowed' : ''"
                            placeholder="Đơn giá" />
                    </div>

                    <!-- Thành tiền & Action -->
                    <div class="col-span-3 flex items-center gap-2">
                        <div class="flex-1 font-semibold text-sm" :class="item.is_gift === true ? 'text-green-600' : ''"
                            x-text="((item.quantity || 0) * (item.unit_price || 0)).toLocaleString('en-US')">
                        </div>

                        <button type="button" @click="removeItem(idx)" :disabled="item.is_gift === true"
                            class="text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :title="item.is_gift === true ? 'Không thể xóa quà tặng' : 'Xóa'">
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

    <!-- Khuyến mãi áp dụng -->
    <div x-show="appliedPromotions && appliedPromotions.length > 0" class="md:col-span-2 mt-4">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-green-800 mb-2">Khuyến mãi đang áp dụng</h4>
                    <div class="space-y-2">
                        <template x-for="(promo, idx) in appliedPromotions" :key="idx">
                            <div class="bg-white rounded-lg p-3 shadow-sm border border-green-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span
                                                class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded"
                                                x-text="promo.type === 'discount' ? 'Giảm giá' : promo.type === 'bundle' ? 'Mua kèm' : promo.type === 'gift' ? 'Tặng quà' : 'Combo'"></span>
                                            <span class="font-medium text-gray-800" x-text="promo.name"></span>
                                        </div>
                                        <p class="text-sm text-gray-600" x-text="promo.description"></p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-lg font-bold text-green-600"
                                            x-show="promo.discount_amount > 0">
                                            -<span x-text="promo.discount_amount.toLocaleString('en-US')"></span>đ
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3 text-sm text-green-700">
                        <strong>Tổng tiết kiệm từ khuyến mãi: </strong>
                        <span class="text-lg font-bold" x-text="promotionDiscount.toLocaleString('en-US')"></span>đ
                    </div>
                </div>
            </div>
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
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open"
                        class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
                        <template x-for="(method, i) in filtered" :key="method.id">
                            <div @click="choose(method)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                :class="[
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
                    <input x-model="form.subtotalFormatted" class="flex-1 border rounded px-3 py-2 bg-gray-100"
                        readonly>
                </div>

                <!-- Mã giảm giá -->
                <div class="flex items-center gap-4">
                    <label class="text-sm text-black font-semibold w-48">Mã giảm giá:</label>
                    <div class="flex-1 flex gap-2">
                        <input x-model="form.coupon_code" class="flex-1 border rounded px-3 py-2"
                            placeholder="Nhập mã giảm giá (VD: MINIGO)">
                        <button type="button" @click="applyCoupon()"
                            class="px-4 py-2 bg-[#002975] text-white rounded hover:opacity-90 whitespace-nowrap">
                            Áp dụng
                        </button>
                    </div>
                </div>

                <!-- Giảm giá -->
                <div class="space-y-2">
                    <div class="flex items-center gap-4">
                        <label class="text-sm text-black font-semibold w-48">Giảm giá khuyến mãi:</label>
                        <div
                            class="flex-1 px-3 py-2 bg-green-50 border border-green-200 rounded text-green-700 font-semibold">
                            <span
                                x-text="promotionDiscount ? '-' + promotionDiscount.toLocaleString('en-US') + 'đ' : '0đ'"></span>
                        </div>
                    </div>

                    <!-- Sử dụng điểm tích lũy -->
                    <div x-show="form.customer_id">
                        <div class="flex items-center gap-4">
                            <label class="text-sm text-black font-semibold w-48">Sử dụng điểm tích lũy:</label>
                            <div class="flex-1 flex gap-2 items-center">
                                <input x-model.number="form.loyalty_points_used" @input="
                                        if (form.loyalty_points_used > customerLoyaltyPoints) {
                                            form.loyalty_points_used = customerLoyaltyPoints;
                                        }
                                        if (form.loyalty_points_used < 0) {
                                            form.loyalty_points_used = 0;
                                        }
                                        calculateTotal();
                                    " type="number" min="0" :max="customerLoyaltyPoints"
                                    :disabled="customerLoyaltyPoints == 0" class="flex-1 border rounded px-3 py-2"
                                    :class="form.loyalty_points_used > customerLoyaltyPoints ? 'border-red-500 bg-red-50' : ''"
                                    placeholder="Nhập số điểm">
                                <div class="text-sm text-gray-600 whitespace-nowrap">
                                    = <span class="font-semibold text-yellow-600"
                                        x-text="(form.loyalty_points_used || 0).toLocaleString('en-US') + 'đ'"></span>
                                </div>
                            </div>
                        </div>
                        <p x-show="form.loyalty_points_used > customerLoyaltyPoints"
                            class="text-red-500 text-xs mt-1 ml-52">
                            Số điểm sử dụng không được vượt quá số điểm hiện có!
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="text-sm text-black font-semibold w-48">Giảm giá thêm:</label>
                        <input x-model="form.discount_amountFormatted" @input="onAmountInput('discount_amount', $event)"
                            class="flex-1 border rounded px-3 py-2" placeholder="Nhập số tiền giảm thêm (nếu có)">
                    </div>
                </div>

                <!-- Tổng tiền -->
                <div>
                    <div class="flex items-center gap-4">
                        <label class="text-lg text-black font-semibold w-48">Tổng tiền cần thanh toán <span
                                class="text-red-500">*</span></label>
                        <input x-model="form.total_amountFormatted"
                            class="flex-1 border rounded px-3 py-3 bg-gray-50 text-xl font-bold text-[#002975]"
                            readonly>
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