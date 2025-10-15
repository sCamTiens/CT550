<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã phiếu thu (disabled, chỉ hiển thị value) -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã phiếu thu <span
                class="text-red-500">*</span></label>
        <input :value="form.code" disabled class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700" />
    </div>

    <!-- Khách hàng -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            choose(customer) {
                form.customer_id = customer.id;
                this.search = customer.name;
                this.open = false;
            },
            clear() {
                form.customer_id = '';
                this.search = '';
                this.filtered = customers;
                this.open = false;
            },
            reset() {
                const selected = customers.find(u => u.id == form.customer_id);
                this.search = selected ? selected.name : '';
                this.filtered = customers;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Khách hàng <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = customers"
                @input="open = true; filtered = customers.filter(u => u.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.customer_id = true; validateField('customer_id')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.customer_id && errors.customer_id) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn khách hàng --" />

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
                ]" x-text="customer.name">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy khách hàng
            </div>
        </div>

        <p x-show="customers.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p x-show="touched.customer_id && errors.customer_id" x-text="errors.customer_id"
            class="text-red-500 text-xs mt-1"></p>
    </div>

    <!-- Đơn hàng -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            choose(order) {
                form.order_id = order.id;
                this.search = order.code;
                this.open = false;
            },
            clear() {
                form.order_id = '';
                this.search = '';
                this.filtered = filteredOrders;
                this.open = false;
            },
            reset() {
                const selected = filteredOrders.find(o => o.id == form.order_id);
                this.search = selected ? selected.code : '';
                this.filtered = filteredOrders;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Mã đơn hàng
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = filteredOrders"
                @input="open = true; filtered = filteredOrders.filter(o => o.code.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.order_id = true; validateField('order_id')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.order_id && errors.order_id) ? 'border-red-500' : 'border-gray-300'"
                :placeholder="form.customer_id ? '-- Chọn đơn hàng --' : 'Chọn khách hàng trước'"
                :disabled="!form.customer_id" />

            <button x-show="form.order_id" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.order_id"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open && form.customer_id"
            class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(order, i) in filtered" :key="order.id">
                <div @click="choose(order)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[ 
                highlight === i ? 'bg-[#002975] text-white' 
                : (form.order_id == order.id ? 'bg-[#002975] text-white' 
                : 'hover:bg-[#002975] hover:text-white text-black'), 
                'px-3 py-2 cursor-pointer transition-colors text-sm'
            ]" x-text="order.code">
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy đơn hàng
            </div>
        </div>

        <p x-show="form.customer_id && filteredOrders.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>
        <p class="text-red-600 text-xs mt-1" x-show="touched.order_id && errors.order_id" x-text="errors.order_id"></p>
    </div>

    <!-- Phương thức thanh toán -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            methods: [
                { id: 'Tiền mặt', name: 'Tiền mặt' },
                { id: 'Chuyển khoản', name: 'Chuyển khoản' },
                { id: 'Quẹt thẻ', name: 'Quẹt thẻ' },
                { id: 'PayPal', name: 'PayPal' },
                { id: 'Thanh toán khi nhận hàng (COD)', name: 'Thanh toán khi nhận hàng (COD)' },
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
                :class="(touched.method && errors.method) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn phương thức --" />

            <!-- Nút clear -->
            <button x-show="form.method" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <!-- Icon mũi tên -->
            <svg x-show="!form.method"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown -->
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

        <!-- Hiển thị lỗi -->
        <p class="text-red-600 text-xs mt-1" x-show="touched.method && errors.method" x-text="errors.method"></p>
    </div>

    <!-- Mã giao dịch -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã giao dịch</label>
        <input x-model="form.txn_ref" class="w-full border rounded px-3 py-2" placeholder="Nhập mã giao dịch (nếu có)">
    </div>

    <!-- Ngày thu -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ngày thu <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="text" x-model="form.received_at" class="w-full border rounded px-3 py-2 receipts-datepicker"
                placeholder="Chọn ngày thu" autocomplete="off"
                x-init="flatpickr($el, {dateFormat: 'd/m/Y', allowInput: true, locale: 'vi'})"
                @blur="touched.received_at = true; validateField('received_at')"
                @input="touched.received_at && validateField('received_at')"
                :class="['w-full border rounded px-3 py-2', (touched.received_at && errors.received_at) ? 'border-red-500' : '']"
                required>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fa-regular fa-calendar"></i>
            </span>
        </div>
        <p class="text-red-600 text-xs mt-1" x-show="touched.received_at && errors.received_at"
            x-text="errors.received_at"></p>
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

    <!-- Người thu -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            choose(staff) {
                form.received_by = staff.id;   // Lưu id
                this.search = staff.name;      // Hiển thị tên
                this.open = false;
            },
            clear() {
                form.received_by = '';
                this.search = '';
                this.filtered = staffs;
                this.open = false;
            },
            reset() {
                const selected = staffs.find(u => u.id == form.received_by);
                this.search = selected ? selected.name : '';
                this.filtered = staffs;
                this.highlight = -1;
            }
        }" x-init="reset()" @click.away="open = false">

        <label class="block text-sm text-black font-semibold mb-1">
            Người thu <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filtered = staffs"
                @input="open = true; filtered = staffs.filter(u => u.name.toLowerCase().includes(search.toLowerCase()))"
                @blur="touched.received_by = true; validateField('received_by')"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.received_by && errors.received_by) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn nhân viên --" />

            <button x-show="form.received_by" type="button" @click.stop="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                ✕
            </button>

            <svg x-show="!form.received_by"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown -->
        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(staff, i) in filtered" :key="staff.id">
                <div @click="choose(staff)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[ 
                highlight === i ? 'bg-[#002975] text-white' 
                : (form.received_by == staff.id ? 'bg-[#002975] text-white' 
                : 'hover:bg-[#002975] hover:text-white text-black'), 
                'px-3 py-2 cursor-pointer transition-colors text-sm'
            ]" x-text="staff.name"></div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy nhân viên
            </div>
        </div>

        <p x-show="staffs.length === 0" class="text-red-400 text-xs italic mt-1">
            Danh sách trống
        </p>

        <p x-show="touched.received_by && errors.received_by" x-text="errors.received_by"
            class="text-red-500 text-xs mt-1"></p>
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
        <label for="isActive" class="text-sm">Đã thu</label>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            // Datepicker cho ngày thu
            document.querySelectorAll('.receipts-datepicker').forEach(function (input) {
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