<!-- Thông tin đơn hàng -->
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Mã đơn hàng</label>
        <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewOrder.code"></div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
        <div class="px-3 py-2 bg-gray-50 rounded border" x-text="viewOrder.customer_name || 'Khách vãng lai'"></div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức thanh toán</label>
        <div class="px-3 py-2 bg-gray-50 rounded border">
            <span
                x-text="viewOrder.payment_method === 'cash' ? 'Tiền mặt' : (viewOrder.payment_method === 'credit_card' ? 'Quẹt thẻ' : 'Chuyển khoản')"></span>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái thanh toán</label>
        <div class="px-3 py-2 bg-gray-50 rounded border">
            <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Đã thanh
                toán</span>
        </div>
    </div>
    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
        <div class="px-3 py-2 bg-gray-50 rounded border min-h-[60px]" x-text="viewOrder.note || '—'">
        </div>
    </div>
</div>

<!-- Chi tiết sản phẩm -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Chi tiết sản phẩm</label>
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
                        </td>
                        <td class="px-3 py-2 text-center font-semibold" x-text="item.qty"></td>
                        <td class="px-3 py-2 text-right" x-text="formatCurrency(item.unit_price)">
                        </td>
                        <td class="px-3 py-2 text-right font-semibold"
                            x-text="formatCurrency(item.qty * item.unit_price)"></td>
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
                <tr x-show="viewOrder.discount_amount > 0">
                    <td colspan="3" class="px-3 py-2 text-right text-red-600">Giảm giá:</td>
                    <td class="px-3 py-2 text-right font-semibold text-red-600"
                        x-text="'- ' + formatCurrency(viewOrder.discount_amount)"></td>
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Người tạo</label>
        <div class="px-3 py-2 bg-gray-50 rounded border text-sm" x-text="viewOrder.created_by_name || '—'"></div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian tạo</label>
        <div class="px-3 py-2 bg-gray-50 rounded border text-sm" x-text="viewOrder.created_at || '—'">
        </div>
    </div>
</div>