<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?= htmlspecialchars($order['code'] ?? '') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #002975;
        }
        .company-sub {
            font-size: 14px;
            color: #666;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .invoice-code {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .info-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 14px;
        }
        .info-label {
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }
        th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 10px;
        }
        .border-b {
            border-bottom: 1px solid #e5e7eb;
        }
        .summary {
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .summary-label {
            color: #666;
        }
        .summary-value {
            font-weight: 600;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #002975;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .note-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            font-size: 14px;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <div>
                <div class="company-name">MINIGO</div>
                <div class="company-sub">Siêu thị mini</div>
            </div>
        </div>
        <div class="invoice-title">HÓA ĐƠN</div>
        <div class="invoice-code">#<?= htmlspecialchars($order['code'] ?? '') ?></div>
    </div>

    <div class="info-section">
        <div class="info-title">Thông tin khách hàng</div>
        <div class="info-grid">
            <div>
                <span class="info-label">Họ tên:</span>
                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Khách vãng lai') ?></strong>
            </div>
            <div>
                <span class="info-label">Ngày:</span>
                <strong><?= htmlspecialchars($order['created_at'] ?? '') ?></strong>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="info-label">Địa chỉ:</span>
                <strong><?= htmlspecialchars($order['shipping_address'] ?? '—') ?></strong>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th style="text-align:center;">Đơn giá</th>
                <th style="text-align:center;">Số lượng</th>
                <th style="text-align:right;">Tổng tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = $order['items'] ?? [];
            $totalQty = 0;
            foreach ($items as $item): 
                $qty = $item['qty'] ?? 0;
                $unitPrice = $item['unit_price'] ?? 0;
                $itemTotal = $qty * $unitPrice;
                $totalQty += $qty;
            ?>
            <tr class="border-b">
                <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                <td style="text-align:center;"><?= number_format($unitPrice, 0, ',', '.') ?></td>
                <td style="text-align:center;"><?= $qty ?></td>
                <td style="text-align:right;"><?= number_format($itemTotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Tổng số lượng:</span>
            <span class="summary-value"><?= $totalQty ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Tổng tiền hàng:</span>
            <span class="summary-value"><?= number_format($order['subtotal'] ?? 0, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Số tiền đã giảm:</span>
            <span class="summary-value"><?= number_format($order['discount_amount'] ?? 0, 0, ',', '.') ?></span>
        </div>
        <div class="summary-row total-row">
            <span>Tổng tiền thanh toán:</span>
            <span><?= number_format($order['total_amount'] ?? 0, 0, ',', '.') ?></span>
        </div>
    </div>

    <?php if (!empty($order['note'])): ?>
    <div class="note-section">
        <strong>Ghi chú:</strong> <?= htmlspecialchars($order['note']) ?>
    </div>
    <?php endif; ?>
</body>
</html>
