<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Mã đơn hàng -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Mã đơn hàng <span
                class="text-red-500">*</span></label>
        <input x-model="form.code" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
    </div>

    <!-- Khách hàng -->
    <div x-data="customerDropdown()">
        <label class="block text-sm text-black font-semibold mb-1">Khách hàng <span
                class="text-red-500">*</span></label>
        <div class="relative">
            <input type="text" x-model="search" @focus="open = true" @input="open = true"
                @blur="touched.customer_id = true; validateField('customer_id')"
                :class="['w-full border rounded px-3 py-2', (touched.customer_id && errors.customer_id) ? 'border-red-500' : '', form.customer_id ? 'text-slate-900' : 'text-slate-400']"
                placeholder="Tìm kiếm khách hàng...">

            <!-- Dropdown -->
            <div x-show="open && filteredCustomers().length > 0" @click.outside="open=false"
                class="absolute z-50 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
                <template x-for="c in filteredCustomers()" :key="c.id">
                    <div @click="select(c)"
                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b last:border-b-0">
                        <div class="font-medium text-sm" x-text="c.name"></div>
                    </div>
                </template>
            </div>

            <!-- Không tìm thấy -->
            <div x-show="open && search && filteredCustomers().length === 0" @click.outside="open=false"
                class="absolute z-50 w-full mt-1 bg-white border rounded shadow-lg px-3 py-2 text-slate-500 text-sm">
                Không tìm thấy khách hàng
            </div>

            <!-- Clear button -->
            <button type="button" x-show="form.customer_id" @click="clear()"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <p class="text-red-600 text-xs mt-1" x-show="touched.customer_id && errors.customer_id"
            x-text="errors.customer_id"></p>
    </div>

    <!-- Trạng thái đơn hàng -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Trạng thái đơn hàng <span
                class="text-red-500">*</span></label>
        <select x-model="form.status" class="w-full border rounded px-3 py-2">
            <option value="pending">Chờ xử lý</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="preparing">Đang chuẩn bị</option>
            <option value="shipping">Đang giao</option>
            <option value="delivered">Đã giao</option>
            <option value="cancelled">Đã hủy</option>
            <option value="returned">Đã trả</option>
        </select>
    </div>

    <!-- Phương thức thanh toán -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Phương thức thanh toán</label>
        <select x-model="form.payment_method" class="w-full border rounded px-3 py-2">
            <option value="">-- Chọn phương thức --</option>
            <option value="cash">Tiền mặt</option>
            <option value="bank_transfer">Chuyển khoản</option>
            <option value="credit_card">Thẻ tín dụng</option>
            <option value="momo">Momo</option>
            <option value="zalopay">ZaloPay</option>
            <option value="vnpay">VNPay</option>
        </select>
    </div>

    <!-- Trạng thái thanh toán -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Trạng thái thanh toán</label>
        <select x-model="form.payment_status" class="w-full border rounded px-3 py-2">
            <option value="pending">Chờ thanh toán</option>
            <option value="paid">Đã thanh toán</option>
            <option value="failed">Thất bại</option>
            <option value="refunded">Đã hoàn</option>
        </select>
    </div>

    <!-- Tạm tính -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Tạm tính <span
                class="text-red-500">*</span></label>
        <input x-model="form.subtotalFormatted" @input="onAmountInput('subtotal', $event)"
            class="w-full border rounded px-3 py-2" placeholder="Nhập tạm tính">
    </div>

    <!-- Giảm giá -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Giảm giá</label>
        <input x-model="form.discount_amountFormatted" @input="onAmountInput('discount_amount', $event)"
            class="w-full border rounded px-3 py-2" placeholder="Nhập giảm giá">
    </div>

    <!-- Phí vận chuyển -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Phí vận chuyển</label>
        <input x-model="form.shipping_feeFormatted" @input="onAmountInput('shipping_fee', $event)"
            class="w-full border rounded px-3 py-2" placeholder="Nhập phí vận chuyển">
    </div>

    <!-- Thuế -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Thuế</label>
        <input x-model="form.tax_amountFormatted" @input="onAmountInput('tax_amount', $event)"
            class="w-full border rounded px-3 py-2" placeholder="Nhập thuế">
    </div>

    <!-- Tổng tiền -->
    <div>
        <label class="block text-sm text-black font-semibold mb-1">Tổng tiền <span
                class="text-red-500">*</span></label>
        <input x-model="form.total_amountFormatted" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
        <p class="text-red-600 text-xs mt-1" x-show="touched.total_amount && errors.total_amount"
            x-text="errors.total_amount"></p>
    </div>

    <!-- Địa chỉ giao hàng -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Địa chỉ giao hàng</label>
        <textarea x-model="form.shipping_address" rows="2" class="w-full border rounded px-3 py-2"
            placeholder="Nhập địa chỉ giao hàng"></textarea>
    </div>

    <!-- Ghi chú -->
    <div class="md:col-span-2">
        <label class="block text-sm text-black font-semibold mb-1">Ghi chú</label>
        <textarea x-model="form.note" rows="3" class="w-full border rounded px-3 py-2"
            placeholder="Ghi chú về đơn hàng"></textarea>
    </div>
</div>

<script>
    function customerDropdown() {
        return {
            open: false,
            search: '',

            get customers() {
                return this.$root.customers || [];
            },

            filteredCustomers() {
                if (!this.search) return this.customers;
                const s = this.search.toLowerCase();
                return this.customers.filter(c =>
                    c.name.toLowerCase().includes(s)
                );
            },

            select(customer) {
                this.$root.form.customer_id = String(customer.id);
                this.search = customer.name;
                this.open = false;
            },

            clear() {
                this.$root.form.customer_id = '';
                this.search = '';
                this.open = false;
            },

            reset() {
                this.search = '';
                this.open = false;
            },

            init() {
                // Nếu đang edit và có customer_id, hiển thị tên
                if (this.$root.form.customer_id) {
                    const c = this.customers.find(x => String(x.id) === String(this.$root.form.customer_id));
                    if (c) this.search = c.name;
                }
            }
        };
    }
</script>
