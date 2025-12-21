<!-- Thông tin đơn hàng -->
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Mã đơn hàng</label>
        <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewOrder.code"></div>
    </div>
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Khách hàng</label>
        <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewOrder.customer_name || 'Khách vãng lai'"></div>
    </div>
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Phương thức thanh toán</label>
        <div class="px-3 py-2 bg-gray-50 rounded border">
            <span
                x-text="viewOrder.payment_method === 'cash' ? 'Tiền mặt' : (viewOrder.payment_method === 'credit_card' ? 'Quẹt thẻ' : 'Chuyển khoản')"></span>
        </div>
    </div>
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Trạng thái thanh toán</label>
        <div class="px-3 py-2 bg-gray-50 rounded border">
            <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Đã thanh
                toán</span>
        </div>
    </div>
    <div class="col-span-2">
        <label class="block text-sm text-gray-700 mb-1 font-bold">Ghi chú</label>
        <div class="px-3 py-2 bg-gray-50 rounded border min-h-[60px]" x-text="viewOrder.note || '—'">
        </div>
    </div>
    <div class="col-span-2" x-show="viewOrder.shipping_address">
        <label class="block text-sm text-gray-700 mb-1 font-bold">Địa chỉ giao hàng</label>
        <div class="px-3 py-2 rounded border min-h-[60px] bg-gray-50"
            x-text="viewOrder.shipping_address || '—'">
        </div>
    </div>
</div>

<!-- Chi tiết sản phẩm -->
<div>
    <label class="block text-sm text-gray-700 mb-2 font-bold">Chi tiết sản phẩm</label>
    <div class="border rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">Sản phẩm</th>
                    <th class="px-3 py-2 text-center text-sm font-semibold text-gray-700">Số lượng</th>
                    <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">Đơn giá</th>
                    <th class="px-3 py-2 text-right text-sm font-semibold text-gray-700">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, idx) in viewOrder.items || []" :key="idx">
                    <tr class="border-t">
                        <td class="px-3 py-2">
                            <div class="font-medium" x-text="item.product_name"></div>
                            <div class="text-xs text-gray-500">SKU: <span x-text="item.product_sku || '—'"></span></div>
                            <div x-show="item.promotion_name" class="mt-2 p-2 bg-orange-50 border border-orange-200 rounded">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-gift text-orange-600"></i>
                                    <span class="text-xs font-bold text-orange-800" x-text="item.promotion_name"></span>
                                </div>
                                <!-- Badge loại CTKM -->
                                <div class="mt-1">
                                    <span x-show="item.promotion_type === 'discount'" class="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-semibold">Giảm giá thường</span>
                                    <span x-show="item.promotion_type === 'bundle'" class="text-[10px] px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full font-semibold">Giảm giá theo số lượng</span>
                                    <span x-show="item.promotion_type === 'gift'" class="text-[10px] px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-semibold">Tặng quà</span>
                                    <span x-show="item.promotion_type === 'combo'" class="text-[10px] px-2 py-0.5 bg-pink-100 text-pink-700 rounded-full font-semibold">Combo sản phẩm</span>
                                </div>
                                <!-- Mô tả CTKM -->
                                <div x-show="item.promotion_description" class="text-[11px] text-gray-600 mt-1 italic" x-text="item.promotion_description"></div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center font-semibold" x-text="item.quantity"></td>
                        <td class="px-3 py-2 text-right" x-text="formatCurrency(item.unit_price)">
                        </td>
                        <td class="px-3 py-2 text-right font-semibold"
                            x-text="formatCurrency(item.quantity * item.unit_price)"></td>
                    </tr>
                </template>
                <tr x-show="!viewOrder.items || viewOrder.items.length === 0">
                    <td colspan="4" class="px-3 py-8 text-center text-gray-400">
                        Chưa có sản phẩm
                    </td>
                </tr>
            </tbody>
            <tfoot class="bg-gray-50 border-t">
                <tr>
                    <td colspan="3" class="px-3 py-2 text-right">Tạm tính:</td>
                    <td class="px-3 py-2 text-right font-semibold" x-text="formatCurrency(viewOrder.subtotal)"></td>
                </tr>
                <tr x-show="viewOrder.promotion_discount > 0">
                    <td colspan="3" class="px-3 py-2 text-right text-orange-600">Khuyến mãi:</td>
                    <td class="px-3 py-2 text-right font-semibold text-orange-600"
                        x-text="'- ' + formatCurrency(viewOrder.promotion_discount)"></td>
                </tr>
                <tr x-show="viewOrder.coupon_code">
                    <td colspan="3" class="px-3 py-2 text-right text-red-600">
                        <span>Mã giảm giá: </span>
                        <span class="font-mono font-bold" x-text="viewOrder.coupon_code"></span>
                    </td>
                    <td class="px-3 py-2 text-right font-semibold text-red-600"
                        x-text="'- ' + formatCurrency((viewOrder.discount_amount || 0) - (viewOrder.promotion_discount || 0))"></td>
                </tr>
                <tr class="border-t-2">
                    <td colspan="3" class="px-3 py-2 text-right font-semibold">Tổng cộng:</td>
                    <td class="px-3 py-2 text-right font-bold text-lg text-[#002975]"
                        x-text="formatCurrency(viewOrder.total_amount)"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<!-- Thông tin người tạo -->
<div class="grid grid-cols-2 gap-4 pt-4 border-t">
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Người tạo</label>
        <div class="px-3 py-2 bg-gray-50 rounded border text-sm" x-text="viewOrder.created_by_name || '—'"></div>
    </div>
    <div>
        <label class="block text-sm text-gray-700 mb-1 font-bold">Thời gian tạo</label>
        <div class="px-3 py-2 bg-gray-50 rounded border text-sm" x-text="viewOrder.created_at || '—'">
        </div>
    </div>
</div>