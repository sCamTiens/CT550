<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã phiếu xuất -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã phiếu xuất <span
                class="text-red-500">*</span></label>
        <input x-model="form.code" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
    </div>

    <!-- Loại xuất -->
    <div class="relative" x-data="{
        open: false,
        search: '',
        options: [
            { value: 'sale',   label: 'Bán hàng' },
            { value: 'return', label: 'Trả hàng NCC' },
            { value: 'damage', label: 'Hư hỏng' },
            { value: 'other',  label: 'Khác' }
        ],
        highlight: -1,
        choose(opt) {
            form.type = opt.value;
            this.search = opt.label;
            this.open = false;
        },
        reset() {
            const selected = this.options.find(o => o.value === form.type);
            this.search = selected ? selected.label : '';
        }
    }" x-effect="reset()" @click.away="open=false">

        <label class="block text-sm text-black font-semibold mb-1">
            Loại xuất <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open=true" readonly
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />

            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown -->
        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(opt, i) in options" :key="opt.value">
                <div @click="choose(opt)" @mouseenter="highlight=i" @mouseleave="highlight=-1" :class="[
                    highlight===i ? 'bg-[#002975] text-white' : 
                    (form.type===opt.value ? 'bg-[#002975] text-white' : 
                    'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer text-sm transition-colors'
                 ]" x-text="opt.label">
                </div>
            </template>
        </div>
    </div>

    <!-- Đơn hàng -->
    <div class="relative" x-data="{
            open: false,
            search: '',
            filtered: [],
            highlight: -1,
            get allOrders() {
                return orders || [];
            },
            choose(order) {
                form.order_id = order.id;
                this.search = order.code + (order.customer_name ? ' - ' + order.customer_name : '');
                this.open = false;
            },
            clear() {
                form.order_id = '';
                this.search = '';
                this.filtered = this.allOrders;
                this.open = false;
            },
            filter() {
                if (!this.search) {
                    this.filtered = this.allOrders;
                } else {
                    const s = this.search.toLowerCase();
                    this.filtered = this.allOrders.filter(o => 
                        o.code.toLowerCase().includes(s) ||
                        (o.customer_name && o.customer_name.toLowerCase().includes(s))
                    );
                }
            },
            reset() {
                const selected = this.allOrders.find(o => o.id == form.order_id);
                this.search = selected ? (selected.code + (selected.customer_name ? ' - ' + selected.customer_name : '')) : '';
                this.filtered = this.allOrders;
                this.highlight = -1;
            }
        }" x-effect="reset()" @click.away="open = false">
        <label class="block text-sm text-black font-semibold mb-1">
            Mã đơn hàng <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true; filter()" @input="filter()"
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
                :class="(touched.order_id && errors.order_id) ? 'border-red-500' : 'border-gray-300'"
                placeholder="-- Chọn đơn hàng --" />

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

        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(order, i) in filtered" :key="order.id">
                <div @click="choose(order)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[ 
                highlight === i ? 'bg-[#002975] text-white' 
                : (form.order_id == order.id ? 'bg-[#002975] text-white' 
                : 'hover:bg-[#002975] hover:text-white text-black'), 
                'px-3 py-2 cursor-pointer transition-colors text-sm'
            ]">
                    <div class="font-medium" x-text="order.code"></div>
                    <div class="text-xs" x-show="order.customer_name" x-text="order.customer_name"></div>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                Không tìm thấy đơn hàng
            </div>
        </div>
    </div>

    <!-- Trạng thái -->
    <div class="relative" x-data="{
            open: false,
            options: [
                { value: 'pending',   label: 'Chờ duyệt' },
                { value: 'approved',  label: 'Đã duyệt' },
                { value: 'completed', label: 'Hoàn thành' },
                { value: 'cancelled', label: 'Đã hủy' }
            ],
            highlight: -1,
            search: '',
            choose(opt) {
                form.status = opt.value;
                this.search = opt.label;
                this.open = false;
            },
            reset() {
                const selected = this.options.find(o => o.value === form.status);
                this.search = selected ? selected.label : '';
            }
        }" x-effect="reset()" @click.away="open = false">

        <label class="block text-sm text-black font-semibold mb-1">
            Trạng thái <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input type="text" x-model="search" @focus="open = true" readonly
                class="w-full border rounded px-3 py-2 pr-8 bg-white text-sm cursor-pointer focus:ring-1 focus:ring-[#002975] focus:border-[#002975]" />

            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none"
                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown -->
        <div x-show="open" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto">
            <template x-for="(opt, i) in options" :key="opt.value">
                <div @click="choose(opt)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                    highlight === i ? 'bg-[#002975] text-white' :
                    (form.status === opt.value ? 'bg-[#002975] text-white' :
                    'hover:bg-[#002975] hover:text-white text-black'),
                    'px-3 py-2 cursor-pointer text-sm transition-colors'
                 ]" x-text="opt.label">
                </div>
            </template>
        </div>
    </div>

    <!-- Ngày xuất -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ngày xuất <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="text" x-model="form.out_date" class="w-full border rounded px-3 py-2 stock-out-datepicker"
                placeholder="Chọn ngày xuất" autocomplete="off"
                x-init="flatpickr($el, {dateFormat: 'd/m/Y', allowInput: true, locale: 'vn'})" required>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fa-regular fa-calendar"></i>
            </span>
        </div>
    </div>

    <!-- Tổng tiền -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">
            Tổng tiền <span class="text-red-500">*</span>
        </label>

        <input x-model="form.total_amountFormatted" @input="onAmountInput($event)"
            @blur="touched.total_amount = true; validateField('total_amount')" placeholder="Nhập tổng tiền"
            class="w-full border rounded px-3 py-2 text-sm focus:ring-1 focus:ring-[#002975] focus:border-[#002975]"
            :class="(touched.total_amount && errors.total_amount) ? 'border-red-500' : 'border-gray-300'">

        <p x-show="touched.total_amount && errors.total_amount" x-text="errors.total_amount"
            class="text-red-600 text-xs mt-1"></p>
    </div>

    <!-- Ghi chú -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <textarea x-model="form.note" rows="3" class="w-full border rounded px-3 py-2"
            placeholder="Ghi chú về phiếu xuất kho"></textarea>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.flatpickr) {
            document.querySelectorAll('.stock-out-datepicker').forEach(function (input) {
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    locale: 'vn',
                    allowInput: true,
                    static: false,
                    appendTo: document.body,
                    position: 'auto center'
                });
            });
        }
    });
</script>