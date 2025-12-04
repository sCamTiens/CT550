<?php
$pageTitle = 'Đặt hàng thành công';
require_once __DIR__ . '/../partials/head.php';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="max-width: 600px; margin: 80px auto; padding: 40px 20px;">
    <div class="success-card" style="background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center;">
        <!-- Success Icon -->
        <div class="success-icon" style="width: 80px; height: 80px; margin: 0 auto 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1 style="font-size: 28px; font-weight: 700; color: #1a202c; margin-bottom: 12px;">
            Đặt hàng thành công!
        </h1>

        <p style="font-size: 16px; color: #718096; margin-bottom: 32px;">
            Cảm ơn bạn đã đặt hàng. Chúng tôi sẽ xử lý đơn hàng của bạn trong thời gian sớm nhất.
        </p>

        <!-- Order Info -->
        <div class="order-info" style="background: #f7fafc; border-radius: 12px; padding: 24px; margin-bottom: 32px; text-align: left;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <span style="color: #718096; font-size: 14px;">Mã đơn hàng</span>
                <strong style="color: #1a202c; font-size: 14px;"><?= htmlspecialchars($order_code ?? 'N/A') ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <span style="color: #718096; font-size: 14px;">Tổng tiền</span>
                <strong style="color: #667eea; font-size: 16px;"><?= number_format($amount ?? 0, 0, ',', '.') ?>đ</strong>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <span style="color: #718096; font-size: 14px;">Phương thức thanh toán</span>
                <strong style="color: #1a202c; font-size: 14px;"><?= htmlspecialchars($payment_method ?? 'COD') ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between;">
                <span style="color: #718096; font-size: 14px;">Trạng thái</span>
                <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                    Chờ xử lý
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/profile?tab=orders"
                style="flex: 1; min-width: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-block; transition: transform 0.2s;">
                Xem đơn hàng
            </a>
            <a href="/"
                style="flex: 1; min-width: 200px; background: white; color: #667eea; padding: 14px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-block; border: 2px solid #667eea; transition: all 0.2s;">
                Về trang chủ
            </a>
        </div>
    </div>
</div>

<style>
    .actions a:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    @media (max-width: 640px) {
        .container {
            margin: 40px auto !important;
            padding: 20px !important;
        }

        .success-card {
            padding: 24px !important;
        }

        .actions {
            flex-direction: column;
        }

        .actions a {
            min-width: 100% !important;
        }
    }
</style>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>