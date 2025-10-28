<div class="space-y-4">
    <!-- Tên chương trình -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">
            Tên chương trình <span class="text-red-500">*</span>
        </label>
        <input x-model="form.name" @blur="touched.name = true; validateField('name')"
            @input="touched.name && validateField('name')" <?= input_attr_maxlength() ?>
            :class="['w-full border rounded px-3 py-2', (touched.name && errors.name) ? 'border-red-500' : '']"
            placeholder="Nhập tên chương trình khuyến mãi" required>
        <p class="text-red-600 text-xs mt-1" x-show="touched.name && errors.name" x-text="errors.name"></p>
    </div>

    <!-- Mô tả -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mô tả</label>
        <textarea x-model="form.description" rows="3" <?= input_attr_maxlength(500) ?>
            class="w-full border rounded px-3 py-2" placeholder="Nhập mô tả chương trình"></textarea>
    </div>

    <!-- Loại khuyến mãi -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">
            Loại khuyến mãi <span class="text-red-500">*</span>
        </label>
        <select x-model="form.promo_type" class="w-full border rounded px-3 py-2">
            <option value="discount">Giảm giá thường</option>
            <option value="bundle">Mua kèm (Bundle)</option>
            <option value="gift">Tặng quà</option>
            <option value="combo">Combo</option>
        </select>
    </div>

    <!-- ============ DISCOUNT ============ -->
    <div x-show="form.promo_type === 'discount'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Loại giảm giá -->
            <div>
                <label class="block text-sm text-black font-semibold mb-1">
                    Loại giảm giá <span class="text-red-500">*</span>
                </label>
                <select x-model="form.discount_type" class="w-full border rounded px-3 py-2">
                    <option value="percentage">Phần trăm</option>
                    <option value="fixed">Số tiền cố định</option>
                </select>
            </div>

            <!-- Giá trị giảm -->
            <div>
                <label class="block text-sm text-black font-semibold mb-1">
                    Giá trị giảm <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="form.discount_value"
                    @blur="touched.discount_value = true; validateField('discount_value')"
                    @input="formatDiscountValue(); touched.discount_value && validateField('discount_value')"
                    :class="['w-full border rounded px-3 py-2', (touched.discount_value && errors.discount_value) ? 'border-red-500' : '']"
                    :placeholder="form.discount_type === 'percentage' ? 'VD: 10' : 'VD: 50,000'">
                <p class="text-red-600 text-xs mt-1" x-show="touched.discount_value && errors.discount_value"
                    x-text="errors.discount_value"></p>
                <p class="text-gray-500 text-xs mt-1">
                </p>
            </div>

            <!-- Áp dụng cho -->
            <div>
                <label class="block text-sm text-black font-semibold mb-1">Áp dụng cho</label>
                <select x-model="form.apply_to" class="w-full border rounded px-3 py-2">
                    <option value="all">Toàn bộ sản phẩm</option>
                    <!-- <option value="category">Theo danh mục</option> -->
                    <option value="product">Sản phẩm cụ thể</option>
                </select>
            </div>
        </div>

        <!-- Danh mục (tạm ẩn) -->
        <!-- <div x-show="form.apply_to === 'category'">
            <label class="block text-sm text-black font-semibold mb-1">Chọn danh mục</label>
            <select x-model="form.category_ids" multiple class="w-full border rounded px-3 py-2" size="5">
                <template x-for="cat in categories" :key="cat.id">
                    <option :value="cat.id" x-text="cat.name"></option>
                </template>
            </select>
            <p class="text-xs text-gray-500 mt-1">Giữ Ctrl/Cmd để chọn nhiều</p>
        </div> -->

        <!-- Sản phẩm -->
        <div x-show="form.apply_to === 'product'">
            <label class="block text-sm text-black font-semibold mb-1">Chọn sản phẩm</label>

            <div class="space-y-2">
                <!-- Danh sách sản phẩm đã chọn -->
                <template x-if="form.product_ids && form.product_ids.length > 0">
                    <div class="border rounded p-3 bg-gray-50 space-y-2">
                        <template x-for="(prodId, idx) in form.product_ids" :key="idx">
                            <div class="flex items-center justify-between bg-white p-2 rounded border">
                                <span class="text-sm"
                                    x-text="products.find(p => p.id == prodId)?.name || 'Không xác định'"></span>
                                <button type="button"
                                    @click="form.product_ids = form.product_ids.filter((id, i) => i !== idx)"
                                    class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Nút thêm sản phẩm -->
                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    filtered: [],
                    highlight: -1,
                    toggleDropdown() {
                        this.open = !this.open;
                        if (this.open) {
                            this.search = '';
                            this.filtered = products.filter(p => !form.product_ids.includes(p.id));
                            this.$nextTick(() => {
                                this.$refs.searchInput?.focus();
                            });
                        }
                    },
                    choose(product) {
                        if (!form.product_ids.includes(product.id)) {
                            form.product_ids.push(product.id);
                        }
                        this.search = '';
                        this.open = false;
                        this.highlight = -1;
                    },
                    reset() {
                        this.search = '';
                        this.filtered = products.filter(p => !form.product_ids.includes(p.id));
                        this.highlight = -1;
                    }
                }" x-init="reset()" @click.away="open = false">

                    <!-- Nút thêm -->
                    <button type="button" @click="toggleDropdown()"
                        class="w-full border-2 border-dashed border-[#002975] rounded px-4 py-2 text-sm text-[#002975] hover:bg-[#002975] hover:text-white transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>Thêm sản phẩm</span>
                    </button>

                    <!-- Dropdown tìm kiếm -->
                    <div x-show="open" x-transition class="absolute z-20 mt-1 w-full bg-white border rounded shadow-lg">
                        <!-- Ô tìm kiếm -->
                        <div class="p-2 border-b sticky top-0 bg-white">
                            <div class="relative">
                                <input type="text" x-ref="searchInput" x-model="search"
                                    @input="filtered = products.filter(p => !form.product_ids.includes(p.id) && (p.name.toLowerCase().includes(search.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(search.toLowerCase()))))"
                                    class="w-full border rounded px-3 py-2 pr-8 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                    placeholder="Tìm kiếm theo tên hoặc mã SKU..." />

                                <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Danh sách sản phẩm -->
                        <div class="max-h-60 overflow-auto">
                            <template x-for="(product, i) in filtered" :key="product.id">
                                <div @click="choose(product)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                    :class="[
                                        highlight === i ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white text-black',
                                        'px-3 py-2 cursor-pointer transition-colors text-sm'
                                    ]">
                                    <div x-text="product.name"></div>
                                    <div class="text-xs opacity-75">
                                        <span x-text="product.sku ? 'SKU: ' + product.sku : ''"></span>
                                        <span x-show="product.sku && product.stock !== undefined"> - </span>
                                        <span
                                            x-text="product.stock !== undefined ? 'Tồn kho: ' + product.stock : ''"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-4 text-center text-gray-400 text-sm">
                                <template x-if="search">
                                    <span>Không tìm thấy sản phẩm phù hợp</span>
                                </template>
                                <template x-if="!search">
                                    <span>Tất cả sản phẩm đã được chọn</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ BUNDLE ============ -->
    <div x-show="form.promo_type === 'bundle'" class="space-y-3">
        <div class="border-l-4 border-blue-500 bg-blue-50 p-3 text-sm">
            <strong>Quy tắc Mua kèm:</strong> Mua N sản phẩm cùng loại → Tổng giá = Bundle Price<br>
            <span class="text-gray-600">VD: Mua 2 nước giặt = 165k (thay vì 2×130k)</span>
        </div>

        <template x-for="(rule, idx) in form.bundle_rules" :key="idx">
            <div class="border rounded p-3 bg-gray-50">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-sm">Quy tắc #<span x-text="idx + 1"></span></span>
                    <button type="button" @click="form.bundle_rules.splice(idx, 1)"
                        class="text-red-600 hover:text-red-800 text-sm">Xóa</button>
                </div>
                <div class="grid grid-cols-12 gap-2">
                    <!-- Sản phẩm (6 cột) -->
                    <div class="col-span-6">
                        <label class="text-xs">Sản phẩm <span class="text-red-500">*</span></label>
                        <template x-if="rule.product_id">
                            <div class="flex items-center justify-between bg-white p-2 rounded border">
                                <span class="text-sm truncate"
                                    x-text="products.find(p => p.id == rule.product_id)?.name || 'Không xác định'"></span>
                                <button type="button" @click="rule.product_id = ''"
                                    class="text-red-600 hover:text-red-800 text-sm ml-2">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </template>

                        <template x-if="!rule.product_id">
                            <div class="relative" x-data="{
                                    open: false,
                                    search: '',
                                    filtered: [],
                                    highlight: -1,
                                    toggleDropdown() {
                                        this.open = !this.open;
                                        if (this.open) {
                                            this.search = '';
                                            this.filtered = products;
                                            this.$nextTick(() => this.$refs.searchInput?.focus());
                                        }
                                    },
                                    choose(product) {
                                        rule.product_id = product.id;
                                        this.search = '';
                                        this.open = false;
                                        this.highlight = -1;
                                    }
                                }" x-init="search = ''; filtered = products" @click.away="open = false">

                                <button type="button" @click="toggleDropdown()"
                                    class="w-full border-2 border-dashed border-[#002975] rounded px-2 py-2 text-xs text-[#002975] hover:bg-[#002975] hover:text-white transition-colors flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Chọn</span>
                                </button>

                                <div x-show="open" x-transition
                                    class="absolute z-20 mt-1 w-full bg-white border rounded shadow-lg">
                                    <div class="p-2 border-b sticky top-0 bg-white">
                                        <input type="text" x-ref="searchInput" x-model="search"
                                            @input="filtered = products.filter(p => p.name.toLowerCase().includes(search.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(search.toLowerCase())))"
                                            class="w-full border rounded px-2 py-2 text-xs focus:ring-1 focus:ring-[#002975]"
                                            placeholder="Tìm kiếm..." />
                                    </div>
                                    <div class="max-h-48 overflow-auto">
                                        <template x-for="(product, i) in filtered" :key="product.id">
                                            <div @click="choose(product)" @mouseenter="highlight = i"
                                                @mouseleave="highlight = -1" :class="[
                                                    highlight === i ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white',
                                                    'px-2 py-2 cursor-pointer text-xs'
                                                ]">
                                                <div x-text="product.name"></div>
                                            </div>
                                        </template>
                                        <div x-show="filtered.length === 0" class="px-2 py-3 text-center text-xs text-gray-400">
                                            Không tìm thấy
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Số lượng (3 cột) -->
                    <div class="col-span-3">
                        <label class="text-xs">Số lượng <span class="text-red-500">*</span></label>
                        <input type="number" x-model="rule.qty" class="w-full border rounded px-2 py-2 text-sm"
                            min="2" required>
                    </div>

                    <!-- Giá bundle (3 cột) -->
                    <div class="col-span-3">
                        <label class="text-xs">Giá <span class="text-red-500">*</span></label>
                        <input type="text" x-model="rule.price" @input="rule.price = formatNumberInput(rule.price)"
                            class="w-full border rounded px-2 py-2 text-sm" placeholder="165,000" required>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" @click="form.bundle_rules.push({product_id: '', qty: 2, price: 0})"
            class="w-full border-2 border-dashed border-gray-300 rounded px-3 py-2 text-sm text-gray-600 hover:border-blue-500 hover:text-blue-600">
            + Thêm quy tắc Bundle
        </button>
    </div>

    <!-- ============ GIFT ============ -->
    <div x-show="form.promo_type === 'gift'" class="space-y-3">
        <div class="border-l-4 border-green-500 bg-green-50 p-3 text-sm">
            <strong>Quy tắc Tặng quà:</strong> Mua X sản phẩm → Tặng Y sản phẩm<br>
            <span class="text-gray-600">VD: Mua 3 hộp cà phê → Tặng 1 ly giữ nhiệt</span>
        </div>

        <template x-for="(rule, idx) in form.gift_rules" :key="idx">
            <div class="border rounded p-3 bg-gray-50">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-sm">Quy tắc #<span x-text="idx + 1"></span></span>
                    <button type="button" @click="form.gift_rules.splice(idx, 1)"
                        class="text-red-600 hover:text-red-800 text-sm">Xóa</button>
                </div>
                
                <!-- Điều kiện mua -->
                <div class="mb-2">
                    <div class="text-xs font-semibold mb-1 text-gray-600">Điều kiện mua:</div>
                    <div class="grid grid-cols-12 gap-2">
                        <!-- SP kích hoạt (9 cột) -->
                        <div class="col-span-9">
                            <label class="text-xs">Sản phẩm <span class="text-red-500">*</span></label>
                            <template x-if="rule.trigger_product_id">
                                <div class="flex items-center justify-between bg-white p-2 rounded border">
                                    <span class="text-sm truncate"
                                        x-text="products.find(p => p.id == rule.trigger_product_id)?.name || 'Không xác định'"></span>
                                    <button type="button" @click="rule.trigger_product_id = ''"
                                        class="text-red-600 hover:text-red-800 text-sm ml-2">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </template>

                            <template x-if="!rule.trigger_product_id">
                                <div class="relative" x-data="{
                                        open: false,
                                        search: '',
                                        filtered: [],
                                        toggleDropdown() {
                                            this.open = !this.open;
                                            if (this.open) {
                                                this.search = '';
                                                this.filtered = products;
                                                this.$nextTick(() => this.$refs.searchInput?.focus());
                                            }
                                        },
                                        choose(product) {
                                            rule.trigger_product_id = product.id;
                                            this.search = '';
                                            this.open = false;
                                        }
                                    }" x-init="search = ''; filtered = products" @click.away="open = false">
                                    <button type="button" @click="toggleDropdown()"
                                        class="w-full border-2 border-dashed border-[#002975] rounded px-2 py-2 text-xs text-[#002975] hover:bg-[#002975] hover:text-white transition-colors">
                                        <i class="fa-solid fa-plus"></i> Chọn
                                    </button>
                                    <div x-show="open" x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border rounded shadow-lg">
                                        <div class="p-2 border-b">
                                            <input type="text" x-ref="searchInput" x-model="search"
                                                @input="filtered = products.filter(p => p.name.toLowerCase().includes(search.toLowerCase()))"
                                                class="w-full border rounded px-2 py-2 text-xs" placeholder="Tìm..." />
                                        </div>
                                        <div class="max-h-48 overflow-auto">
                                            <template x-for="product in filtered" :key="product.id">
                                                <div @click="choose(product)"
                                                    class="px-2 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-xs"
                                                    x-text="product.name"></div>
                                            </template>
                                            <div x-show="filtered.length === 0" class="px-2 py-3 text-center text-xs text-gray-400">
                                                Không tìm thấy
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- SL mua (3 cột) -->
                        <div class="col-span-3">
                            <label class="text-xs">SL <span class="text-red-500">*</span></label>
                            <input type="number" x-model="rule.trigger_qty"
                                class="w-full border rounded px-2 py-2 text-sm" min="1" required>
                        </div>
                    </div>
                </div>

                <!-- Quà tặng -->
                <div>
                    <div class="text-xs font-semibold mb-1 text-gray-600">Quà tặng:</div>
                    <div class="grid grid-cols-12 gap-2">
                        <!-- SP tặng (9 cột) -->
                        <div class="col-span-9">
                            <label class="text-xs">Sản phẩm <span class="text-red-500">*</span></label>
                            <template x-if="rule.gift_product_id">
                                <div class="flex items-center justify-between bg-white p-2 rounded border">
                                    <span class="text-sm truncate"
                                        x-text="products.find(p => p.id == rule.gift_product_id)?.name || 'Không xác định'"></span>
                                    <button type="button" @click="rule.gift_product_id = ''"
                                        class="text-red-600 hover:text-red-800 text-sm ml-2">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </template>

                            <template x-if="!rule.gift_product_id">
                                <div class="relative" x-data="{
                                        open: false,
                                        search: '',
                                        filtered: [],
                                        toggleDropdown() {
                                            this.open = !this.open;
                                            if (this.open) {
                                                this.search = '';
                                                this.filtered = products;
                                                this.$nextTick(() => this.$refs.searchInput?.focus());
                                            }
                                        },
                                        choose(product) {
                                            rule.gift_product_id = product.id;
                                            this.search = '';
                                            this.open = false;
                                        }
                                    }" x-init="search = ''; filtered = products" @click.away="open = false">
                                    <button type="button" @click="toggleDropdown()"
                                        class="w-full border-2 border-dashed border-[#002975] rounded px-2 py-2 text-xs text-[#002975] hover:bg-[#002975] hover:text-white transition-colors">
                                        <i class="fa-solid fa-plus"></i> Chọn
                                    </button>
                                    <div x-show="open" x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border rounded shadow-lg">
                                        <div class="p-2 border-b">
                                            <input type="text" x-ref="searchInput" x-model="search"
                                                @input="filtered = products.filter(p => p.name.toLowerCase().includes(search.toLowerCase()))"
                                                class="w-full border rounded px-2 py-2 text-xs" placeholder="Tìm..." />
                                        </div>
                                        <div class="max-h-48 overflow-auto">
                                            <template x-for="product in filtered" :key="product.id">
                                                <div @click="choose(product)"
                                                    class="px-2 py-2 hover:bg-[#002975] hover:text-white cursor-pointer text-xs"
                                                    x-text="product.name"></div>
                                            </template>
                                            <div x-show="filtered.length === 0" class="px-2 py-3 text-center text-xs text-gray-400">
                                                Không tìm thấy
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- SL tặng (3 cột) -->
                        <div class="col-span-3">
                            <label class="text-xs">SL <span class="text-red-500">*</span></label>
                            <input type="number" x-model="rule.gift_qty" class="w-full border rounded px-2 py-2 text-sm"
                                min="1" required>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <button type="button"
            @click="form.gift_rules.push({trigger_product_id: '', trigger_qty: 1, gift_product_id: '', gift_qty: 1})"
            class="w-full border-2 border-dashed border-gray-300 rounded px-3 py-2 text-sm text-gray-600 hover:border-green-500 hover:text-green-600">
            + Thêm quy tắc Tặng quà
        </button>
    </div>

    <!-- ============ COMBO ============ -->
    <div x-show="form.promo_type === 'combo'" class="space-y-3">
        <div class="border-l-4 border-purple-500 bg-purple-50 p-3 text-sm">
            <strong>Quy tắc Combo:</strong> Mua nhiều sản phẩm khác nhau → Giá combo<br>
            <span class="text-gray-600">VD: Ổi + Muối = 25k (thay vì 20k + 8k)</span>
        </div>

        <div>
            <label class="block text-sm text-black font-semibold mb-1">Giá combo<span
                    class="text-red-500">*</span></label>
            <input type="text" x-model="form.combo_price"
                @input="form.combo_price = formatNumberInput(form.combo_price)" class="w-full border rounded px-3 py-2"
                placeholder="VD: 25,000" required>
        </div>

        <template x-for="(item, idx) in form.combo_items" :key="idx">
            <div class="border rounded p-3 bg-gray-50">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-sm">Sản phẩm #<span x-text="idx + 1"></span></span>
                    <button type="button" @click="form.combo_items.splice(idx, 1)"
                        class="text-red-600 hover:text-red-800 text-sm">Xóa</button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <!-- Sản phẩm đã chọn -->
                    <div class="col-span-2">
                        <label class="text-xs">Sản phẩm <span class="text-red-500">*</span></label>
                        <template x-if="item.product_id">
                            <div class="flex items-center justify-between bg-white p-2 rounded border">
                                <span class="text-sm"
                                    x-text="products.find(p => p.id == item.product_id)?.name || 'Không xác định'"></span>
                                <button type="button" @click="item.product_id = ''"
                                    class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </template>

                        <!-- Nút chọn sản phẩm -->
                        <template x-if="!item.product_id">
                            <div class="relative" x-data="{
                                    open: false,
                                    search: '',
                                    filtered: [],
                                    highlight: -1,
                                    toggleDropdown() {
                                        this.open = !this.open;
                                        if (this.open) {
                                            this.search = '';
                                            this.filtered = products;
                                            this.$nextTick(() => {
                                                this.$refs.searchInput?.focus();
                                            });
                                        }
                                    },
                                    choose(product) {
                                        item.product_id = product.id;
                                        this.search = '';
                                        this.open = false;
                                        this.highlight = -1;
                                    },
                                    reset() {
                                        this.search = '';
                                        this.filtered = products;
                                        this.highlight = -1;
                                    }
                                }" x-init="reset()" @click.away="open = false">

                                <!-- Nút chọn -->
                                <button type="button" @click="toggleDropdown()"
                                    class="w-full border-2 border-dashed border-[#002975] rounded px-3 py-2 text-sm text-[#002975] hover:bg-[#002975] hover:text-white transition-colors flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Chọn sản phẩm</span>
                                </button>

                                <!-- Dropdown tìm kiếm -->
                                <div x-show="open" x-transition
                                    class="absolute z-20 mt-1 w-full bg-white border rounded shadow-lg">
                                    <!-- Ô tìm kiếm -->
                                    <div class="p-2 border-b sticky top-0 bg-white">
                                        <div class="relative">
                                            <input type="text" x-ref="searchInput" x-model="search"
                                                @input="filtered = products.filter(p => p.name.toLowerCase().includes(search.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(search.toLowerCase())))"
                                                class="w-full border rounded px-3 py-2 pr-8 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                                                placeholder="Tìm kiếm theo tên hoặc mã SKU..." />

                                            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Danh sách sản phẩm -->
                                    <div class="max-h-60 overflow-auto">
                                        <template x-for="(product, i) in filtered" :key="product.id">
                                            <div @click="choose(product)" @mouseenter="highlight = i"
                                                @mouseleave="highlight = -1" :class="[
                                                    highlight === i ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white text-black',
                                                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                                                ]">
                                                <div x-text="product.name"></div>
                                                <div class="text-xs opacity-75">
                                                    <span x-text="product.sku ? 'SKU: ' + product.sku : ''"></span>
                                                    <span x-show="product.sku && product.stock !== undefined"> - </span>
                                                    <span
                                                        x-text="product.stock !== undefined ? 'Tồn kho: ' + product.stock : ''"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filtered.length === 0"
                                            class="px-3 py-4 text-center text-gray-400 text-sm">
                                            Không tìm thấy sản phẩm phù hợp
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Số lượng -->
                    <div>
                        <label class="text-xs">Số lượng <span class="text-red-500">*</span></label>
                        <input type="number" x-model="item.qty" class="w-full border rounded px-2 py-2 text-sm" min="1"
                            required>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" @click="form.combo_items.push({product_id: '', qty: 1})"
            class="w-full border-2 border-dashed border-gray-300 rounded px-3 py-2 text-sm text-gray-600 hover:border-purple-500 hover:text-purple-600">
            + Thêm sản phẩm vào Combo
        </button>
    </div>

    <!-- ============ COMMON FIELDS ============ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
        <!-- Độ ưu tiên -->
        <div>
            <label class="block text-sm text-black font-semibold mb-1">Độ ưu tiên</label>
            <input type="number" x-model="form.priority" class="w-full border rounded px-3 py-2" placeholder="0" min="0"
                step="1">
        </div>

        <!-- Trạng thái -->
        <div class="flex items-center gap-3 pt-6">
            <input id="isActive" type="checkbox" x-model="form.is_active" :true-value="1" :false-value="0"
                class="h-4 w-4">
            <label for="isActive">Kích hoạt</label>
        </div>

        <!-- Ngày bắt đầu -->
        <div>
            <label class="block text-sm font-semibold mb-1">Ngày bắt đầu <span class="text-red-500">*</span></label>
            <input x-model="form.starts_at" type="text"
                class="promotion-start-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.starts_at && errors.starts_at) ? 'border-red-500' : 'border-gray-300'"
                placeholder="Chọn ngày" autocomplete="off"
                @input="touched.starts_at = true; validateField('starts_at'); validateField('ends_at')" />
            <p x-show="touched.starts_at && errors.starts_at" x-text="errors.starts_at"
                class="text-red-500 text-xs mt-1"></p>
        </div>

        <!-- Ngày kết thúc -->
        <div>
            <label class="block text-sm font-semibold mb-1">Ngày kết thúc <span class="text-red-500">*</span></label>
            <input x-model="form.ends_at" type="text"
                class="promotion-end-date w-full border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.ends_at && errors.ends_at) ? 'border-red-500' : 'border-gray-300'"
                placeholder="Chọn ngày" autocomplete="off"
                @input="touched.ends_at = true; validateField('ends_at'); validateField('starts_at')" />
            <p x-show="touched.ends_at && errors.ends_at" x-text="errors.ends_at" class="text-red-500 text-xs mt-1"></p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {

            flatpickr(".promotion-start-date", {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true,
                onClose: function (selectedDates, dateStr, instance) {
                    instance.element.value = dateStr;
                    instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            flatpickr(".promotion-end-date", {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true,
                onClose: function (selectedDates, dateStr, instance) {
                    instance.element.value = dateStr;
                    instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

        }
    });
</script>