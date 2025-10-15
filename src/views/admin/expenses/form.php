<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã phiếu chi (disabled, chỉ hiển thị value) -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã phiếu chi <span
                class="text-red-500">*</span></label>
        <input :value="form.code" disabled class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700" />
    </div>

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
                // Reset phiếu nhập khi đổi nhà cung cấp
                form.purchase_order_id = '';
            },
            clear() {
                form.supplier_id = '';
                this.search = '';
                this.filtered = suppliers;
                this.open = false;
                form.purchase_order_id = '';
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
            <input type="text" x-model="search" @focus="open = true; filtered = suppliers"
                @input="open = true; filtered = suppliers.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.supplier_id = true; validateField('supplier_id')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.supplier_id && errors.supplier_id) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn nhà cung cấp --" />

            <button x-show="form.supplier_id" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.supplier_id"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(s, i) in filtered" :key="s.id">
                <div @click="choose(s)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.supplier_id == s.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="s.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy nhà cung cấp
            </div>
        </div>

        <p x-show="suppliers.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p x-show="touched.supplier_id && errors.supplier_id" x-text="errors.supplier_id"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Phiếu nhập -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            choose(po) {
                form.purchase_order_id = po.id;
                this.search = po.code;
                this.open = false;
            },
            clear() {
                form.purchase_order_id = '';
                this.search = '';
                this.filtered = filteredPurchaseOrders;
                this.open = false;
            },
            reset() {
                const selected = filteredPurchaseOrders.find(po => po.id == form.purchase_order_id);
                this.search = selected ? selected.code : '';
                this.filtered = filteredPurchaseOrders;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Mã phiếu nhập <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = filteredPurchaseOrders"
                @input="open = true; filtered = filteredPurchaseOrders.filter(po => po.code.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.purchase_order_id = true; validateField('purchase_order_id')"
                :disabled="!form.supplier_id"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="[
                (touched.purchase_order_id && errors.purchase_order_id) ? 'border-red-500' : 'border-gray-300',
                form.purchase_order_id === '' ? 'text-slate-400' : 'text-slate-900',
                !form.supplier_id ? 'bg-gray-100 cursor-not-allowed' : ''
            ]" placeholder="-- Chọn phiếu nhập --" />

            <button x-show="form.purchase_order_id" type="button" @click.stop="clear()" :disabled="!form.supplier_id"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.purchase_order_id"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open && form.supplier_id"
            class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(po, i) in filtered" :key="po.id">
                <div @click="choose(po)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.purchase_order_id == po.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="po.code">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy phiếu nhập
            </div>
        </div>

        <p x-show="form.supplier_id && filteredPurchaseOrders.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p class="text-red-600 text-xs mt-1" x-show="touched.purchase_order_id && errors.purchase_order_id"
            x-text="errors.purchase_order_id"></p>
    </div>

    <!-- Phương thức thanh toán -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            methods: [
                {id: 'Tiền mặt', name: 'Tiền mặt'},
                {id: 'Chuyển khoản', name: 'Chuyển khoản'}
            ],
            choose(m) {
                form.method = m.id;
                this.search = m.name;
                this.open = false;
            },
            clear() {
                form.method = '';
                this.search = '';
                this.filtered = this.methods;
                this.open = false;
            },
            reset() {
                const selected = this.methods.find(m => m.id == form.method);
                this.search = selected ? selected.name : '';
                this.filtered = this.methods;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Phương thức thanh toán <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = methods"
                @input="open = true; filtered = methods.filter(m => m.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.method = true; validateField('method')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="[
                (touched.method && errors.method) ? 'border-red-500' : 'border-gray-300',
                form.method === '' ? 'text-slate-400' : 'text-slate-900'
            ]" placeholder="-- Chọn phương thức --" />

            <button x-show="form.method" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.method"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(m, i) in filtered" :key="m.id">
                <div @click="choose(m)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.method == m.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="m.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy phương thức
            </div>
        </div>

        <p class="text-red-600 text-xs mt-1" x-show="touched.method && errors.method" x-text="errors.method"></p>
    </div>

    <!-- Mã giao dịch -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã giao dịch</label>
        <input x-model="form.txn_ref" class="w-full border rounded px-3 py-2" placeholder="Nhập mã giao dịch (nếu có)">
    </div>

    <!-- Ngày chi -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ngày chi <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="text" x-model="form.paid_at" class="w-full border rounded px-3 py-2 expenses-datepicker"
                placeholder="Chọn ngày chi" autocomplete="off"
                x-init="flatpickr($el, {dateFormat: 'd/m/Y', allowInput: true, locale: 'vi'})"
                @blur="touched.paid_at = true; validateField('paid_at')"
                @input="touched.paid_at && validateField('paid_at')"
                :class="['w-full border rounded px-3 py-2', (touched.paid_at && errors.paid_at) ? 'border-red-500' : '']"
                required>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fa-regular fa-calendar"></i>
            </span>
        </div>
        <p class="text-red-600 text-xs mt-1" x-show="touched.paid_at && errors.paid_at" x-text="errors.paid_at"></p>
    </div>

    <!-- Số tiền -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Số tiền <span class="text-red-500">*</span></label>
        <input x-model="form.amountFormatted" @input="onAmountInput($event); touched.amount && validateField('amount')"
            @blur="touched.amount = true; validateField('amount')"
            :class="['w-full border rounded px-3 py-2', (touched.amount && errors.amount) ? 'border-red-500' : '']"
            placeholder="Nhập số tiền" required>
        <p class="text-red-600 text-xs mt-1" x-show="touched.amount && errors.amount" x-text="errors.amount"></p>
    </div>

    <!-- Người chi - Chọn từ danh sách nhân viên -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            choose(user) {
                form.paid_by = user.id;
                this.search = user.name;
                this.open = false;
            },
            clear() {
                form.paid_by = '';
                this.search = '';
                this.filtered = users;
                this.open = false;
            },
            reset() {
                const selected = users.find(u => u.id == form.paid_by);
                this.search = selected ? selected.name : '';
                this.filtered = users;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Người chi <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = users"
                @input="open = true; filtered = users.filter(u => u.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.paid_by = true; validateField('paid_by')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.paid_by && errors.paid_by) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn nhân viên --" />

            <button x-show="form.paid_by" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.paid_by"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(user, i) in filtered" :key="user.id">
                <div @click="choose(user)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white'
                    : (form.paid_by == user.id ? 'bg-[#002975] text-white'
                    : 'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                ]" x-text="user.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy nhân viên
            </div>
        </div>

        <p x-show="users.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p x-show="touched.paid_by && errors.paid_by" x-text="errors.paid_by" class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Ghi chú -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <input x-model="form.note" class="w-full border rounded px-3 py-2" placeholder="Ghi chú (nếu có)">
    </div>

    <!-- Xác nhận ngân hàng -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Xác nhận ngân hàng</label>
        <div class="relative">
            <input type="text" x-model="form.bank_time" class="w-full border rounded px-3 py-2 bank-time-datepicker"
                placeholder="Chọn thời gian xác nhận" autocomplete="off"
                x-init="flatpickr($el, {enableTime: true, dateFormat: 'd/m/Y H:i', allowInput: true, locale: 'vi', time_24hr: true})">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fa-regular fa-calendar"></i>
            </span>
        </div>
    </div>

    <!-- Trạng thái -->
    <div class="md:col-span-2 flex items-center gap-3">
        <input id="isActive" type="checkbox" x-model="form.is_active" true-value="1" false-value="0" class="h-4 w-4">
        <label for="isActive" class="text-sm">Đã chi</label>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            // Datepicker cho ngày chi
            document.querySelectorAll('.expenses-datepicker').forEach(function (input) {
                flatpickr(input, {
                    dateFormat: 'Y/m/d',
                    locale: 'vi',
                    allowInput: true,
                    static: true,
                    appendTo: input.parentElement
                });
            });

            // Datepicker cho xác nhận ngân hàng (có thời gian)
            document.querySelectorAll('.bank-time-datepicker').forEach(function (input) {
                flatpickr(input, {
                    enableTime: true,
                    dateFormat: 'Y/m/d H:i',
                    locale: 'vi',
                    allowInput: true,
                    time_24hr: true,
                    static: true,
                    appendTo: input.parentElement
                });
            });
        }
    });
</script>