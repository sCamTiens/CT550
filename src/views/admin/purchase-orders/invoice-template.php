<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Phiếu nhập kho #<?= htmlspecialchars($po['code'] ?? '') ?></title>
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

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-not-paid {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-partial {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        @media print {
            body {
                padding: 0;
            }
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
        <div class="invoice-title">PHIẾU NHẬP KHO</div>
        <div class="invoice-code">#<?= htmlspecialchars($po['code'] ?? '') ?></div>
    </div>

    <div class="info-section">
        <div class="info-title">Thông tin phiếu nhập</div>
        <div class="info-grid">
            <div>
                <span class="info-label">Mã phiếu nhập:</span>
                <strong><?= htmlspecialchars($po['code'] ?? '—') ?></strong>
            </div>
            <div>
                <span class="info-label">Ngày nhập:</span>
                <strong><?= htmlspecialchars($po['created_at'] ?? '—') ?></strong>
            </div>
            <div>
                <span class="info-label">Ngày hẹn trả:</span>
                <strong><?= htmlspecialchars($po['due_date'] ?? '—') ?></strong>
            </div>
            <div>
                <span class="info-label">Trạng thái thanh toán:</span>
                <strong>
                    <?php
                    $paidAmount = floatval($po['paid_amount'] ?? 0);
                    $totalAmount = floatval($po['total_amount'] ?? 0);
                    $statusClass = 'status-not-paid';
                    $statusText = 'Chưa đối soát';
                    
                    if ($paidAmount > 0) {
                        if ($paidAmount >= $totalAmount) {
                            $statusClass = 'status-paid';
                            $statusText = 'Đã thanh toán hết';
                        } else {
                            $statusClass = 'status-partial';
                            $statusText = 'Đã thanh toán một phần';
                        }
                    }
                    ?>
                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                </strong>
            </div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-title">Thông tin nhà cung cấp</div>
        <div class="info-grid">
            <div>
                <span class="info-label">Tên nhà cung cấp:</span>
                <strong><?= htmlspecialchars($po['supplier_name'] ?? '—') ?></strong>
            </div>
            <div>
                <span class="info-label">Số điện thoại:</span>
                <strong><?= htmlspecialchars($po['supplier_phone'] ?? '—') ?></strong>
            </div>
            <div style="grid-column: 1 / -1;">
                <span class="info-label">Địa chỉ:</span>
                <strong><?= htmlspecialchars($po['supplier_address'] ?? '—') ?></strong>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th style="text-align:center;">Mã lô</th>
                <th style="text-align:center;">Số lượng</th>
                <th style="text-align:center;">Đơn giá</th>
                <th style="text-align:center;">HSD</th>
                <th style="text-align:right;">Tổng tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $items = $po['items'] ?? [];
            $totalQty = 0;
            $totalItems = count($items);
            $currentIndex = 0;
            foreach ($items as $item):
                $currentIndex++;
                $qty = $item['quantity'] ?? 0;
                $unitCost = $item['unit_cost'] ?? 0;
                $itemTotal = $qty * $unitCost;
                $totalQty += $qty;
                // Chỉ hiển thị border-b nếu không phải dòng cuối
                $borderClass = ($currentIndex < $totalItems) ? 'border-b' : '';
                ?>
                <tr class="<?= $borderClass ?>">
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($item['product_name'] ?? '') ?></div>
                        <div style="font-size: 12px; color: #666;">SKU: <?= htmlspecialchars($item['product_sku'] ?? '—') ?></div>
                    </td>
                    <td style="text-align:center;"><?= htmlspecialchars($item['batch_code'] ?? '—') ?></td>
                    <td style="text-align:center;"><?= $qty ?></td>
                    <td style="text-align:center;"><?= number_format($unitCost, 0, ',', '.') ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($item['expiry_date'] ?? '—') ?></td>
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
            <span class="summary-value"><?= number_format($po['total_amount'] ?? 0, 0, ',', '.') ?> VNĐ</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Số tiền đã thanh toán:</span>
            <span class="summary-value"><?= number_format($po['paid_amount'] ?? 0, 0, ',', '.') ?> VNĐ</span>
        </div>
        <div class="summary-row total-row">
            <span>Số tiền còn nợ:</span>
            <span><?= number_format(($po['total_amount'] ?? 0) - ($po['paid_amount'] ?? 0), 0, ',', '.') ?> VNĐ</span>
        </div>
    </div>

    <?php if (!empty($po['note'])): ?>
        <div class="note-section">
            <strong>Ghi chú:</strong> <?= htmlspecialchars($po['note']) ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 40px; text-align: center; padding: 20px; border-top: 2px solid #002975;">
        <div class="info-grid" style="text-align: left; max-width: 600px; margin: 0 auto;">
            <div>
                <div style="font-weight: bold; margin-bottom: 5px;">Người lập phiếu</div>
                <div style="color: #666;"><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></div>
                <div style="font-size: 12px; color: #999; margin-top: 40px;">Ký tên</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold; margin-bottom: 5px;">Người giao hàng</div>
                <div style="color: #666;">&nbsp;</div>
                <div style="font-size: 12px; color: #999; margin-top: 40px;">Ký tên</div>
            </div>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #666;">
        <div><strong>Hotline:</strong> 0901 234 567 | <strong>Địa chỉ:</strong> 123 Đường ABC, TP. Cần Thơ</div>
    </div>
</body>

</html>
