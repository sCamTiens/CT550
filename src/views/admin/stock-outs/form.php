<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã phiếu xuất -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã phiếu xuất <span
                class="text-red-500">*</span></label>
        <input x-model="form.code" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
    </div>

    <!-- Loại xuất -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Loại xuất <span
                class="text-red-500">*</span></label>
        <select x-model="form.type" class="w-full border rounded px-3 py-2">
            <option value="sale">Bán hàng</option>
            <option value="return">Trả hàng NCC</option>
            <option value="damage">Hư hỏng</option>
            <option value="other">Khác</option>
        </select>
    </div>

    <!-- Đơn hàng -->
    <div x-data="orderDropdown()">
        <label class="block text-sm text-black font-semibold mb-1">Đơn hàng (nếu có)</label>
        <div class="relative">
            <input type="text" x-model="search" @focus="open = true" @input="open = true"
                :class="['w-full border rounded px-3 py-2', form.order_id ? 'text-slate-900' : 'text-slate-400']"
                placeholder="Tìm kiếm đơn hàng...">

            <!-- Dropdown -->
            <div x-show="open && filteredOrders().length > 0" @click.outside="open=false"
                class="absolute z-50 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
                <template x-for="o in filteredOrders()" :key="o.id">
                    <div @click="select(o)"
                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b last:border-b-0">
                        <div class="font-medium text-sm" x-text="o.code"></div>
                        <div class="text-xs text-gray-500" x-text="o.customer_name"></div>
                    </div>
                </template>
            </div>

            <!-- Không tìm thấy -->
            <div x-show="open && search && filteredOrders().length === 0" @click.outside="open=false"
                class="absolute z-50 w-full mt-1 bg-white border rounded shadow-lg px-3 py-2 text-slate-500 text-sm">
                Không tìm thấy đơn hàng
            </div>

            <!-- Clear button -->
            <button type="button" x-show="form.order_id" @click="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">✕</button>
        </div>
    </div>

    <!-- Trạng thái -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Trạng thái <span
                class="text-red-500">*</span></label>
        <select x-model="form.status" class="w-full border rounded px-3 py-2">
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="completed">Hoàn thành</option>
            <option value="cancelled">Đã hủy</option>
        </select>
    </div>

    <!-- Ngày xuất -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Ngày xuất <span
                class="text-red-500">*</span></label>
        <input x-model="form.out_date" type="date" class="w-full border rounded px-3 py-2">
    </div>

    <!-- Tổng tiền -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Tổng tiền <span
                class="text-red-500">*</span></label>
        <input x-model="form.total_amountFormatted" @input="onAmountInput($event)"
            class="w-full border rounded px-3 py-2" placeholder="Nhập tổng tiền">
        <p class="text-red-600 text-xs mt-1" x-show="touched.total_amount && errors.total_amount"
            x-text="errors.total_amount"></p>
    </div>

    <!-- Ghi chú -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <textarea x-model="form.note" rows="3" class="w-full border rounded px-3 py-2"
            placeholder="Ghi chú về phiếu xuất kho"></textarea>
    </div>
</div>

<script>
    function orderDropdown() {
        return {
            open: false,
            search: '',

            get orders() {
                return this.$root.orders || [];
            },

            filteredOrders() {
                if (!this.search) return this.orders;
                const s = this.search.toLowerCase();
                return this.orders.filter(o =>
                    o.code.toLowerCase().includes(s) ||
                    (o.customer_name && o.customer_name.toLowerCase().includes(s))
                );
            },

            select(order) {
                this.$root.form.order_id = String(order.id);
                this.search = order.code + (order.customer_name ? ' - ' + order.customer_name : '');
                this.open = false;
            },

            clear() {
                this.$root.form.order_id = '';
                this.search = '';
                this.open = false;
            },

            reset() {
                this.search = '';
                this.open = false;
            },

            init() {
                // Nếu đang edit và có order_id, hiển thị mã đơn
                if (this.$root.form.order_id) {
                    const o = this.orders.find(x => String(x.id) === String(this.$root.form.order_id));
                    if (o) this.search = o.code + (o.customer_name ? ' - ' + o.customer_name : '');
                }
            }
        };
    }
</script>
