<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 587;
            $this->mailer->CharSet = 'UTF-8';

            // Default sender
            $this->mailer->setFrom(
                $_ENV['MAIL_FROM'] ?? $_ENV['MAIL_USERNAME'],
                $_ENV['MAIL_FROM_NAME'] ?? 'MINIGO MARKET'
            );
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }

    /**
     * Gửi email hóa đơn cho khách hàng
     */
    public function sendOrderInvoice($order, $customer, $items)
    {
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($customer['email'], $customer['name']);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Cảm ơn bạn đã mua sắm tại MINIGO!';
            $this->mailer->Body = $this->getInvoiceEmailBody($order, $customer, $items);
            $this->mailer->AltBody = $this->getInvoiceEmailPlainText($order, $customer, $items);

            $this->mailer->send();
            return ['success' => true, 'message' => 'Email đã được gửi thành công'];
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Gửi email xác nhận đơn hàng online cho khách hàng
     * Bao gồm thông tin đơn hàng chi tiết và trạng thái
     */
    public function sendOrderConfirmation($order, $customer, $items)
    {
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($customer['email'], $customer['full_name'] ?? $customer['name']);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '✓ Xác nhận đơn hàng #' . $order['code'] . ' - MINIGO MARKET';
            $this->mailer->Body = $this->getOrderConfirmationEmailBody($order, $customer, $items);
            $this->mailer->AltBody = $this->getOrderConfirmationEmailPlainText($order, $customer, $items);

            $this->mailer->send();

            error_log("Order confirmation email sent successfully to: " . $customer['email'] . " for order: " . $order['code']);
            return ['success' => true, 'message' => 'Email xác nhận đơn hàng đã được gửi thành công'];
        } catch (Exception $e) {
            error_log("Order confirmation email send error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Tạo nội dung email HTML
     */
    private function getInvoiceEmailBody($order, $customer, $items)
    {
        $orderDate = date('d/m/Y H:i', strtotime($order['created_at']));

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $itemsHtml .= sprintf(
                '<tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">%s</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: center;">%d</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">%s</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">%s</td>
                </tr>',
                htmlspecialchars($item['product_name']),
                $item['quantity'],
                number_format($item['unit_price'], 0, ',', '.') . 'đ',
                number_format($itemTotal, 0, ',', '.') . 'đ'
            );
        }

        $html = '
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn đơn hàng</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #002975 0%, #004ba8 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">MINIGO MARKET</h1>
        <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">Cảm ơn bạn đã mua sắm!</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Xin chào <strong>' . htmlspecialchars($customer['name']) . '</strong>,</p>
        
        <p style="color: #16a34a; font-size: 16px; margin-bottom: 20px;">
            ✓ Đơn hàng <strong>#' . htmlspecialchars($order['code']) . '</strong> của bạn đã được đặt thành công vào ngày <strong>' . $orderDate . '</strong>.
        </p>
        
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="color: #002975; font-size: 18px; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #002975; padding-bottom: 10px;">
                📋 Chi tiết đơn hàng
            </h2>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background: #002975; color: white;">
                        <th style="padding: 12px; text-align: left;">Sản phẩm</th>
                        <th style="padding: 12px; text-align: center; width: 80px;">SL</th>
                        <th style="padding: 12px; text-align: right; width: 100px;">Đơn giá</th>
                        <th style="padding: 12px; text-align: right; width: 120px;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $itemsHtml . '
                </tbody>
            </table>
        </div>
        
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; font-size: 14px;">Tạm tính:</td>
                    <td style="padding: 8px 0; text-align: right; font-size: 14px;">' . number_format($order['subtotal'], 0, ',', '.') . 'đ</td>
                </tr>';

        if (!empty($order['promotion_discount']) && $order['promotion_discount'] > 0) {
            $html .= '
                <tr>
                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px;">Khuyến mãi:</td>
                    <td style="padding: 8px 0; text-align: right; color: #dc2626; font-size: 14px;">-' . number_format($order['promotion_discount'], 0, ',', '.') . 'đ</td>
                </tr>';
        }

        if (!empty($order['discount_amount']) && $order['discount_amount'] > 0) {
            $html .= '
                <tr>
                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px;">Giảm giá:</td>
                    <td style="padding: 8px 0; text-align: right; color: #dc2626; font-size: 14px;">-' . number_format($order['discount_amount'], 0, ',', '.') . 'đ</td>
                </tr>';
        }

        $html .= '
                <tr style="border-top: 2px solid #002975;">
                    <td style="padding: 12px 0; font-size: 18px; font-weight: bold; color: #002975;">Tổng tiền:</td>
                    <td style="padding: 12px 0; text-align: right; font-size: 20px; font-weight: bold; color: #16a34a;">' . number_format($order['total_amount'], 0, ',', '.') . 'đ</td>
                </tr>
            </table>
        </div>
        
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>📦 Thông tin giao hàng:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . nl2br(htmlspecialchars($order['shipping_address'] ?: 'Nhận tại cửa hàng')) . '</p>
        </div>
        
        <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>💳 Phương thức thanh toán:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . htmlspecialchars($order['payment_method']) . '</p>
        </div>';

        if (!empty($order['note'])) {
            $html .= '
        <div style="background: #f3f4f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>📝 Ghi chú:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . nl2br(htmlspecialchars($order['note'])) . '</p>
        </div>';
        }

        $html .= '
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <p style="font-size: 16px; color: #002975; margin: 0 0 10px 0;">
                <strong>Cảm ơn bạn đã lựa chọn MINIGO!</strong>
            </p>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Nếu có thắc mắc, vui lòng liên hệ: <a href="mailto:tienb2105563@student.ctu.edu.vn" style="color: #002975; text-decoration: none;">tienb2105563@student.ctu.edu.vn</a>
            </p>
        </div>
        
        <p style="font-size: 14px; color: #6b7280; margin-top: 20px;">
            Trân trọng,<br>
            <strong style="color: #002975;">Đội ngũ MINIGO MARKET</strong>
        </p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;">
        <p style="margin: 0;">Email này được gửi tự động, vui lòng không trả lời.</p>
        <p style="margin: 5px 0 0 0;">© ' . date('Y') . ' MINIGO MARKET. All rights reserved.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Tạo nội dung email plain text (dự phòng)
     */
    private function getInvoiceEmailPlainText($order, $customer, $items)
    {
        $orderDate = date('d/m/Y H:i', strtotime($order['created_at']));

        $text = "Xin chào " . $customer['name'] . ",\n\n";
        $text .= "Đơn hàng #" . $order['code'] . " của bạn đã được đặt thành công vào ngày " . $orderDate . ".\n\n";
        $text .= "Chi tiết đơn hàng:\n";
        $text .= str_repeat("-", 60) . "\n";
        $text .= sprintf("%-30s %8s %10s %10s\n", "Sản phẩm", "SL", "Đơn giá", "Thành tiền");
        $text .= str_repeat("-", 60) . "\n";

        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $text .= sprintf(
                "%-30s %8d %10s %10s\n",
                substr($item['product_name'], 0, 30),
                $item['quantity'],
                number_format($item['unit_price'], 0, ',', '.') . 'đ',
                number_format($itemTotal, 0, ',', '.') . 'đ'
            );
        }

        $text .= str_repeat("-", 60) . "\n\n";
        $text .= "Tạm tính: " . number_format($order['subtotal'], 0, ',', '.') . "đ\n";

        if (!empty($order['promotion_discount']) && $order['promotion_discount'] > 0) {
            $text .= "Khuyến mãi: -" . number_format($order['promotion_discount'], 0, ',', '.') . "đ\n";
        }

        if (!empty($order['discount_amount']) && $order['discount_amount'] > 0) {
            $text .= "Giảm giá: -" . number_format($order['discount_amount'], 0, ',', '.') . "đ\n";
        }

        $text .= "Tổng tiền: " . number_format($order['total_amount'], 0, ',', '.') . "đ\n\n";
        $text .= "Phương thức thanh toán: " . $order['payment_method'] . "\n";
        $text .= "Địa chỉ giao hàng: " . ($order['shipping_address'] ?: 'Nhận tại cửa hàng') . "\n\n";

        if (!empty($order['note'])) {
            $text .= "Ghi chú: " . $order['note'] . "\n\n";
        }

        $text .= "Cảm ơn bạn đã lựa chọn MINIGO. Nếu có thắc mắc, vui lòng liên hệ: tienb2105563@student.ctu.edu.vn\n\n";
        $text .= "Trân trọng,\n";
        $text .= "Đội ngũ MINIGO MARKET\n";

        return $text;
    }

    /**
     * Gửi email thông báo trả lương cho nhân viên kèm PDF chấm công và bảng lương
     */
    public function sendPayrollNotification($staff, $payroll, $attendances, $month, $year)
    {
        try {
            // Kiểm tra email nhân viên
            if (empty($staff['email'])) {
                error_log("PayrollEmail: Staff has no email - user_id: " . ($staff['user_id'] ?? 'N/A'));
                return ['success' => false, 'message' => 'Nhân viên không có email'];
            }

            error_log("PayrollEmail: Starting to generate PDFs for staff: {$staff['full_name']} ({$staff['email']})");

            // Reset recipients và attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($staff['email'], $staff['full_name']);

            // Tạo file PDF chấm công
            $attendancePdfPath = $this->generateAttendancePDF($staff, $attendances, $month, $year);
            error_log("PayrollEmail: Attendance PDF path: " . ($attendancePdfPath ?: 'EMPTY'));

            // Tạo file PDF bảng lương
            $payrollPdfPath = $this->generatePayrollPDF($staff, $payroll, $month, $year);
            error_log("PayrollEmail: Payroll PDF path: " . ($payrollPdfPath ?: 'EMPTY'));

            // Đính kèm file PDF
            if ($attendancePdfPath && file_exists($attendancePdfPath)) {
                $this->mailer->addAttachment($attendancePdfPath, 'BangChamCong_Thang' . $month . '_' . $year . '.pdf');
                error_log("PayrollEmail: Attached attendance PDF");
            } else {
                error_log("PayrollEmail: Failed to attach attendance PDF - file does not exist or path is empty");
            }

            if ($payrollPdfPath && file_exists($payrollPdfPath)) {
                $this->mailer->addAttachment($payrollPdfPath, 'BangLuong_Thang' . $month . '_' . $year . '.pdf');
                error_log("PayrollEmail: Attached payroll PDF");
            } else {
                error_log("PayrollEmail: Failed to attach payroll PDF - file does not exist or path is empty");
            }

            // Tính deadline (7 ngày sau)
            $deadlineDate = date('d/m/Y', strtotime('+7 days'));
            $companyName = $_ENV['MAIL_FROM_NAME'] ?? 'MINIGO MARKET';

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Thông báo bảng chấm công và bảng lương tháng ' . $month . '/' . $year;
            $this->mailer->Body = $this->getPayrollEmailBody($staff['full_name'], $month, $year, $deadlineDate, $companyName);
            $this->mailer->AltBody = $this->getPayrollEmailPlainText($staff['full_name'], $month, $year, $deadlineDate, $companyName);

            error_log("PayrollEmail: Sending email to {$staff['email']}...");
            $this->mailer->send();
            error_log("PayrollEmail: Email sent successfully!");

            // Xóa file PDF tạm sau khi gửi
            if ($attendancePdfPath && file_exists($attendancePdfPath)) {
                unlink($attendancePdfPath);
                error_log("PayrollEmail: Deleted attendance PDF");
            }
            if ($payrollPdfPath && file_exists($payrollPdfPath)) {
                unlink($payrollPdfPath);
                error_log("PayrollEmail: Deleted payroll PDF");
            }

            return ['success' => true, 'message' => 'Email đã được gửi thành công'];
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Xóa file PDF nếu có lỗi
            if (isset($attendancePdfPath) && $attendancePdfPath && file_exists($attendancePdfPath)) {
                unlink($attendancePdfPath);
            }
            if (isset($payrollPdfPath) && $payrollPdfPath && file_exists($payrollPdfPath)) {
                unlink($payrollPdfPath);
            }

            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Tạo PDF bảng chấm công
     */
    private function generateAttendancePDF($staff, $attendances, $month, $year)
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        // Tạo HTML cho PDF
        $html = '
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
            h1 { text-align: center; color: #002975; font-size: 20px; margin-bottom: 5px; }
            h2 { text-align: center; color: #004ba8; font-size: 16px; margin-top: 5px; margin-bottom: 20px; }
            .info { margin-bottom: 15px; }
            .info strong { color: #002975; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #002975; color: white; padding: 8px; text-align: center; font-size: 11px; }
            td { border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 10px; }
            .summary { margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; }
            .summary-item { margin: 5px 0; }
        </style>
        
        <h1>BẢNG CHẤM CÔNG NHÂN VIÊN</h1>
        <h2>Tháng ' . $month . '/' . $year . '</h2>
        
        <div class="info">
            <strong>Họ tên:</strong> ' . htmlspecialchars($staff['full_name']) . '<br>
            <strong>Mã nhân viên:</strong> ' . htmlspecialchars($staff['username']) . '<br>
            <strong>Vai trò:</strong> ' . htmlspecialchars($staff['staff_role']) . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="8%">STT</th>
                    <th width="12%">Ngày</th>
                    <th width="15%">Ca làm</th>
                    <th width="12%">Giờ vào</th>
                    <th width="10%">Vào</th>
                    <th width="12%">Giờ ra</th>
                    <th width="10%">Ra</th>
                    <th width="10%">Giờ làm</th>
                    <th width="11%">Trạng thái</th>
                </tr>
            </thead>
            <tbody>';

        $stt = 1;
        $totalHours = 0;
        foreach ($attendances as $att) {
            $checkInTime = $att['check_in_time'] ? date('H:i', strtotime($att['check_in_time'])) : '—';
            $checkOutTime = $att['check_out_time'] ? date('H:i', strtotime($att['check_out_time'])) : '—';
            $workHours = $att['work_hours'] ? number_format($att['work_hours'], 1) : '0';
            $totalHours += floatval($att['work_hours'] ?? 0);

            // Trạng thái vào/ra
            $checkInStatus = $att['check_in_status'] ?? '—';
            $checkOutStatus = $att['check_out_status'] ?? '—';

            $html .= '
                <tr>
                    <td>' . $stt++ . '</td>
                    <td>' . date('d/m/Y', strtotime($att['attendance_date'])) . '</td>
                    <td>' . htmlspecialchars($att['shift_name']) . '</td>
                    <td>' . $checkInTime . '</td>
                    <td>' . htmlspecialchars($checkInStatus) . '</td>
                    <td>' . $checkOutTime . '</td>
                    <td>' . htmlspecialchars($checkOutStatus) . '</td>
                    <td>' . $workHours . 'h</td>
                    <td>' . htmlspecialchars($att['status']) . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>
        
        <div class="summary">
            <div class="summary-item"><strong>Tổng số ca làm việc:</strong> ' . count($attendances) . ' ca</div>
            <div class="summary-item"><strong>Tổng số giờ làm việc:</strong> ' . number_format($totalHours, 1) . ' giờ</div>
        </div>
        
        <p style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
            Bảng chấm công được tạo tự động từ hệ thống - ' . $_ENV['MAIL_FROM_NAME'] . '<br>
            Ngày xuất: ' . date('d/m/Y H:i', strtotime('+7 hours')) . ' (GMT+7)
        </p>';

        // Sử dụng mPDF để tạo PDF
        try {
            // Dùng Windows temp directory thay vì OneDrive folder
            $tempDir = sys_get_temp_dir() . '/mpdf_temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $tempDir
            ]);

            $mpdf->WriteHTML($html);

            $staffId = $staff['user_id'] ?? $staff['id'] ?? 'unknown';
            $filename = $tempDir . '/attendance_' . $staffId . '_' . $month . '_' . $year . '_' . time() . '.pdf';
            $mpdf->Output($filename, 'F');

            error_log("Attendance PDF created: $filename (size: " . filesize($filename) . " bytes)");
            return $filename;
        } catch (\Exception $e) {
            error_log("PDF generation error (attendance): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Tạo PDF bảng lương
     */
    private function generatePayrollPDF($staff, $payroll, $month, $year)
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        // Format số tiền
        $formatMoney = function ($amount) {
            return number_format($amount, 0, ',', '.') . ' VNĐ';
        };

        // Tạo HTML cho PDF
        $html = '
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
            h1 { text-align: center; color: #002975; font-size: 20px; margin-bottom: 5px; }
            h2 { text-align: center; color: #004ba8; font-size: 16px; margin-top: 5px; margin-bottom: 20px; }
            .info { margin-bottom: 15px; }
            .info strong { color: #002975; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #002975; color: white; padding: 10px; text-align: left; font-size: 12px; }
            td { border: 1px solid #ddd; padding: 10px; font-size: 11px; }
            .label { font-weight: bold; background: #f0f0f0; width: 40%; }
            .value { text-align: right; }
            .total-row { background: #e8f4f8; font-weight: bold; font-size: 14px; }
            .total-row td { color: #16a34a; padding: 12px; }
            .note { margin-top: 20px; padding: 10px; background: #fff8dc; border-left: 4px solid #f59e0b; font-size: 11px; }
        </style>
        
        <h1>BẢNG LƯƠNG NHÂN VIÊN</h1>
        <h2>Tháng ' . $month . '/' . $year . '</h2>
        
        <div class="info">
            <strong>Họ tên:</strong> ' . htmlspecialchars($staff['full_name']) . '<br>
            <strong>Mã nhân viên:</strong> ' . htmlspecialchars($staff['username']) . '<br>
            <strong>Vai trò:</strong> ' . htmlspecialchars($staff['staff_role']) . '
        </div>
        
        <table>
            <tr>
                <td class="label">Số ca làm việc thực tế</td>
                <td class="value">' . $payroll['total_shifts_worked'] . ' ca</td>
            </tr>
            <tr>
                <td class="label">Số ngày công yêu cầu</td>
                <td class="value">' . $payroll['required_shifts'] . ' ca</td>
            </tr>
            <tr style="border-top: 2px solid #002975;">
                <td class="label">Lương cơ bản</td>
                <td class="value">' . $formatMoney($payroll['base_salary']) . '</td>
            </tr>
            <tr>
                <td class="label">Lương thực tế (theo công)</td>
                <td class="value">' . $formatMoney($payroll['actual_salary']) . '</td>
            </tr>
            <tr style="background: #f0fdf4;">
                <td class="label" style="color: #16a34a;">Thưởng</td>
                <td class="value" style="color: #16a34a;">+' . $formatMoney($payroll['bonus'] ?? 0) . '</td>
            </tr>
            <tr style="background: #fef2f2;">
                <td class="label" style="color: #dc2626;">Phạt/Khấu trừ</td>
                <td class="value" style="color: #dc2626;">-' . $formatMoney($payroll['deduction'] ?? 0) . '</td>
            </tr>
            <tr style="background: #fff7ed;">
                <td class="label" style="color: #ea580c;">Phạt đi muộn</td>
                <td class="value" style="color: #ea580c;">-' . $formatMoney($payroll['late_deduction'] ?? 0) . '</td>
            </tr>
            <tr class="total-row">
                <td class="label">TỔNG LƯƠNG NHẬN</td>
                <td class="value">' . $formatMoney($payroll['total_salary']) . '</td>
            </tr>
        </table>
        
        <div class="note">
            <strong>📌 Lưu ý:</strong><br>
            - Lương thực tế được tính dựa trên số ca làm việc và lương cơ bản.<br>
            - Vui lòng kiểm tra kỹ thông tin và phản hồi nếu có sai sót.<br>
            - Trạng thái: <strong>' . htmlspecialchars($payroll['status']) . '</strong>
        </div>
        
        <p style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
            Bảng lương được tạo tự động từ hệ thống - ' . $_ENV['MAIL_FROM_NAME'] . '<br>
            Ngày xuất: ' . date('d/m/Y H:i', strtotime('+7 hours')) . ' (GMT+7)
        </p>';

        // Sử dụng mPDF để tạo PDF
        try {
            // Dùng Windows temp directory thay vì OneDrive folder
            $tempDir = sys_get_temp_dir() . '/mpdf_temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $tempDir
            ]);

            $mpdf->WriteHTML($html);

            $staffId = $staff['user_id'] ?? $staff['id'] ?? 'unknown';
            $filename = $tempDir . '/payroll_' . $staffId . '_' . $month . '_' . $year . '_' . time() . '.pdf';
            $mpdf->Output($filename, 'F');

            error_log("Payroll PDF created: $filename (size: " . filesize($filename) . " bytes)");
            return $filename;
        } catch (\Exception $e) {
            error_log("PDF generation error (payroll): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Nội dung email HTML cho thông báo lương
     */
    private function getPayrollEmailBody($staffName, $month, $year, $deadlineDate, $companyName)
    {
        return '
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo bảng lương</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #002975 0%, #004ba8 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">THÔNG BÁO BẢNG LƯƠNG</h1>
        <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">Tháng ' . $month . '/' . $year . '</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Xin chào <strong style="color: #002975;">' . htmlspecialchars($staffName) . '</strong>,</p>
        
        <p style="font-size: 15px; line-height: 1.8;">
            Phòng Nhân sự xin gửi đến bạn <strong>bảng chấm công</strong> và <strong>bảng lương tháng ' . $month . '/' . $year . '</strong> (đính kèm trong email này).
        </p>
        
        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #002975;">📋 Vui lòng kiểm tra kỹ các thông tin trong hai file PDF:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li style="margin: 5px 0;"><strong>Bảng chấm công:</strong> ghi nhận số ngày làm việc, ngày phép, ngày nghỉ, làm thêm...</li>
                <li style="margin: 5px 0;"><strong>Bảng lương:</strong> chi tiết các khoản thu nhập, khấu trừ, và số tiền thực nhận.</li>
            </ul>
        </div>
        
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;">
                ⚠️ Nếu có thắc mắc, bạn vui lòng phản hồi lại email này hoặc liên hệ phòng Nhân sự <strong>trước ngày ' . $deadlineDate . '</strong> để được hỗ trợ.
            </p>
        </div>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <p style="font-size: 16px; color: #16a34a; margin: 0 0 10px 0;">
                <strong>✓ Lương đã được thanh toán</strong>
            </p>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Vui lòng kiểm tra tài khoản của bạn
            </p>
        </div>
        
        <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
            Trân trọng,<br>
            <strong style="color: #002975;">Phòng Nhân sự – ' . htmlspecialchars($companyName) . '</strong>
        </p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;">
        <p style="margin: 0;">Email này được gửi tự động từ hệ thống quản lý nhân sự.</p>
        <p style="margin: 5px 0 0 0;">© ' . date('Y') . ' ' . htmlspecialchars($companyName) . '. All rights reserved.</p>
    </div>
</body>
</html>';
    }

    /**
     * Nội dung email plain text cho thông báo lương
     */
    private function getPayrollEmailPlainText($staffName, $month, $year, $deadlineDate, $companyName)
    {
        return 'Xin chào ' . $staffName . ',

Phòng Nhân sự xin gửi đến bạn bảng chấm công và bảng lương tháng ' . $month . '/' . $year . ' (đính kèm trong email này).

Vui lòng kiểm tra kỹ các thông tin trong hai file PDF:

• Bảng chấm công: ghi nhận số ngày làm việc, ngày phép, ngày nghỉ, làm thêm...
• Bảng lương: chi tiết các khoản thu nhập, khấu trừ, và số tiền thực nhận.

Nếu có thắc mắc, bạn vui lòng phản hồi lại email này hoặc liên hệ phòng Nhân sự trước ngày ' . $deadlineDate . ' để được hỗ trợ.

Lương đã được thanh toán. Vui lòng kiểm tra tài khoản của bạn.

Trân trọng,
Phòng Nhân sự – ' . $companyName . '

---
Email này được gửi tự động từ hệ thống quản lý nhân sự.
© ' . date('Y') . ' ' . $companyName . '. All rights reserved.';
    }

    /**
     * Tạo nội dung email HTML cho xác nhận đơn hàng online
     */
    private function getOrderConfirmationEmailBody($order, $customer, $items)
    {
        $orderDate = date('d/m/Y H:i', strtotime($order['created_at']));
        $customerName = $customer['full_name'] ?? $customer['name'];

        // Tạo status badge với màu sắc phù hợp
        $statusColor = [
            'Chờ xử lý' => '#f59e0b',
            'Đang xử lý' => '#3b82f6',
            'Đang giao hàng' => '#8b5cf6',
            'Đã giao' => '#10b981',
            'Đã hủy' => '#ef4444'
        ];
        $statusBgColor = $statusColor[$order['status']] ?? '#6b7280';

        // Render danh sách sản phẩm
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item['line_total'] ?? ($item['qty'] * $item['unit_price']);
            $itemsHtml .= sprintf(
                '<tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">%s</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: center;">%d</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">%s</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">%s</td>
                </tr>',
                htmlspecialchars($item['product_name'] ?? $item['name']),
                $item['qty'],
                number_format($item['unit_price'], 0, ',', '.') . 'đ',
                number_format($itemTotal, 0, ',', '.') . 'đ'
            );
        }

        // Địa chỉ giao hàng
        $shippingAddress = '';
        if (!empty($order['delivery_name'])) {
            $shippingAddress .= '<strong>' . htmlspecialchars($order['delivery_name']) . '</strong><br>';
        }
        if (!empty($order['delivery_phone'])) {
            $shippingAddress .= '📱 ' . htmlspecialchars($order['delivery_phone']) . '<br>';
        }
        if (!empty($order['delivery_address'])) {
            $shippingAddress .= '📍 ' . htmlspecialchars($order['delivery_address']);
        }
        if (!empty($order['shipping_ward'])) {
            $shippingAddress .= ', ' . htmlspecialchars($order['shipping_ward']);
        }
        if (!empty($order['shipping_district'])) {
            $shippingAddress .= ', ' . htmlspecialchars($order['shipping_district']);
        }
        if (!empty($order['shipping_province'])) {
            $shippingAddress .= ', ' . htmlspecialchars($order['shipping_province']);
        }

        $html = '
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f3f4f6;">
    <div style="background: linear-gradient(135deg, #002975 0%, #004ba8 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">MINIGO MARKET</h1>
        <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">Cảm ơn bạn đã đặt hàng!</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="display: inline-block; background: ' . $statusBgColor . '; color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: bold;">
                ✓ ' . htmlspecialchars($order['status']) . '
            </div>
        </div>
        
        <p style="font-size: 16px; margin-bottom: 20px;">Xin chào <strong>' . htmlspecialchars($customerName) . '</strong>,</p>
        
        <p style="color: #16a34a; font-size: 16px; margin-bottom: 20px;">
            ✓ Đơn hàng <strong>#' . htmlspecialchars($order['code']) . '</strong> của bạn đã được đặt thành công vào ngày <strong>' . $orderDate . '</strong>.
        </p>
        
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="color: #002975; font-size: 18px; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #002975; padding-bottom: 10px;">
                📋 Chi tiết đơn hàng
            </h2>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background: #002975; color: white;">
                        <th style="padding: 12px; text-align: left;">Sản phẩm</th>
                        <th style="padding: 12px; text-align: center; width: 80px;">SL</th>
                        <th style="padding: 12px; text-align: right; width: 100px;">Đơn giá</th>
                        <th style="padding: 12px; text-align: right; width: 120px;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $itemsHtml . '
                </tbody>
            </table>
        </div>
        
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; font-size: 14px;">Tạm tính:</td>
                    <td style="padding: 8px 0; text-align: right; font-size: 14px;">' . number_format($order['subtotal'], 0, ',', '.') . 'đ</td>
                </tr>';

        if (!empty($order['promotion_discount']) && $order['promotion_discount'] > 0) {
            $html .= '
                <tr>
                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px;">Khuyến mãi:</td>
                    <td style="padding: 8px 0; text-align: right; color: #dc2626; font-size: 14px;">-' . number_format($order['promotion_discount'], 0, ',', '.') . 'đ</td>
                </tr>';
        }

        if (!empty($order['discount_total']) && $order['discount_total'] > 0) {
            $html .= '
                <tr>
                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px;">Giảm giá:</td>
                    <td style="padding: 8px 0; text-align: right; color: #dc2626; font-size: 14px;">-' . number_format($order['discount_total'], 0, ',', '.') . 'đ</td>
                </tr>';
        }

        if (!empty($order['shipping_fee']) && $order['shipping_fee'] > 0) {
            $html .= '
                <tr>
                    <td style="padding: 8px 0; font-size: 14px;">Phí vận chuyển:</td>
                    <td style="padding: 8px 0; text-align: right; font-size: 14px;">' . number_format($order['shipping_fee'], 0, ',', '.') . 'đ</td>
                </tr>';
        }

        $html .= '
                <tr style="border-top: 2px solid #002975;">
                    <td style="padding: 12px 0; font-size: 18px; font-weight: bold; color: #002975;">Tổng tiền:</td>
                    <td style="padding: 12px 0; text-align: right; font-size: 20px; font-weight: bold; color: #16a34a;">' . number_format($order['grand_total'], 0, ',', '.') . 'đ</td>
                </tr>
            </table>
        </div>
        
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>📦 Thông tin giao hàng:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . $shippingAddress . '</p>
        </div>
        
        <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>💳 Phương thức thanh toán:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . htmlspecialchars($order['payment_method']) . '</p>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #6b7280;">Trạng thái: <strong>' . htmlspecialchars($order['payment_status']) . '</strong></p>
        </div>';

        if (!empty($order['note'])) {
            $html .= '
        <div style="background: #f3f4f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>📝 Ghi chú:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 14px;">' . nl2br(htmlspecialchars($order['note'])) . '</p>
        </div>';
        }

        // Tracking info nếu có
        if (!empty($order['ghn_order_code'])) {
            $html .= '
        <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;"><strong>🚚 Mã vận đơn GHN:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 16px; font-weight: bold; color: #10b981;">' . htmlspecialchars($order['ghn_order_code']) . '</p>
        </div>';
        }

        $html .= '
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <p style="font-size: 16px; color: #002975; margin: 0 0 10px 0;">
                <strong>Cảm ơn bạn đã lựa chọn MINIGO!</strong>
            </p>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Nếu có thắc mắc, vui lòng liên hệ: <a href="mailto:tienb2105563@student.ctu.edu.vn" style="color: #002975; text-decoration: none;">tienb2105563@student.ctu.edu.vn</a>
            </p>
        </div>
        
        <p style="font-size: 14px; color: #6b7280; margin-top: 20px;">
            Trân trọng,<br>
            <strong style="color: #002975;">Đội ngũ MINIGO MARKET</strong>
        </p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px; background: #ffffff; border-radius: 0 0 10px 10px;">
        <p style="margin: 0;">Email này được gửi tự động, vui lòng không trả lời.</p>
        <p style="margin: 5px 0 0 0;">© ' . date('Y') . ' MINIGO MARKET. All rights reserved.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Tạo nội dung email plain text cho xác nhận đơn hàng
     */
    private function getOrderConfirmationEmailPlainText($order, $customer, $items)
    {
        $orderDate = date('d/m/Y H:i', strtotime($order['created_at']));
        $customerName = $customer['full_name'] ?? $customer['name'];

        $text = "MINIGO MARKET - Xác nhận đơn hàng\n\n";
        $text .= "Xin chào " . $customerName . ",\n\n";
        $text .= "Đơn hàng #" . $order['code'] . " của bạn đã được đặt thành công vào ngày " . $orderDate . ".\n";
        $text .= "Trạng thái: " . $order['status'] . "\n\n";

        $text .= "Chi tiết đơn hàng:\n";
        $text .= str_repeat("-", 60) . "\n";
        $text .= sprintf("%-30s %8s %10s %10s\n", "Sản phẩm", "SL", "Đơn giá", "Thành tiền");
        $text .= str_repeat("-", 60) . "\n";

        foreach ($items as $item) {
            $itemTotal = $item['line_total'] ?? ($item['qty'] * $item['unit_price']);
            $text .= sprintf(
                "%-30s %8d %10s %10s\n",
                substr($item['product_name'] ?? $item['name'], 0, 30),
                $item['qty'],
                number_format($item['unit_price'], 0, ',', '.') . 'đ',
                number_format($itemTotal, 0, ',', '.') . 'đ'
            );
        }

        $text .= str_repeat("-", 60) . "\n\n";
        $text .= "Tạm tính: " . number_format($order['subtotal'], 0, ',', '.') . "đ\n";

        if (!empty($order['promotion_discount']) && $order['promotion_discount'] > 0) {
            $text .= "Khuyến mãi: -" . number_format($order['promotion_discount'], 0, ',', '.') . "đ\n";
        }

        if (!empty($order['discount_total']) && $order['discount_total'] > 0) {
            $text .= "Giảm giá: -" . number_format($order['discount_total'], 0, ',', '.') . "đ\n";
        }

        if (!empty($order['shipping_fee']) && $order['shipping_fee'] > 0) {
            $text .= "Phí vận chuyển: " . number_format($order['shipping_fee'], 0, ',', '.') . "đ\n";
        }

        $text .= "Tổng tiền: " . number_format($order['grand_total'], 0, ',', '.') . "đ\n\n";

        $text .= "Phương thức thanh toán: " . $order['payment_method'] . "\n";
        $text .= "Trạng thái thanh toán: " . $order['payment_status'] . "\n\n";

        $text .= "Thông tin giao hàng:\n";
        if (!empty($order['delivery_name'])) {
            $text .= "Người nhận: " . $order['delivery_name'] . "\n";
        }
        if (!empty($order['delivery_phone'])) {
            $text .= "SĐT: " . $order['delivery_phone'] . "\n";
        }
        if (!empty($order['delivery_address'])) {
            $text .= "Địa chỉ: " . $order['delivery_address'];
            if (!empty($order['shipping_ward'])) $text .= ", " . $order['shipping_ward'];
            if (!empty($order['shipping_district'])) $text .= ", " . $order['shipping_district'];
            if (!empty($order['shipping_province'])) $text .= ", " . $order['shipping_province'];
            $text .= "\n";
        }

        if (!empty($order['note'])) {
            $text .= "\nGhi chú: " . $order['note'] . "\n";
        }

        if (!empty($order['ghn_order_code'])) {
            $text .= "\nMã vận đơn GHN: " . $order['ghn_order_code'] . "\n";
        }

        $text .= "\nCảm ơn bạn đã lựa chọn MINIGO. Nếu có thắc mắc, vui lòng liên hệ: tienb2105563@student.ctu.edu.vn\n\n";
        $text .= "Trân trọng,\n";
        $text .= "Đội ngũ MINIGO MARKET\n";

        return $text;
    }

    /**
     * Gửi email mật khẩu cho khách hàng hoặc nhân viên
     * @param array $user - Thông tin người dùng (email, full_name, username)
     * @param string $password - Mật khẩu mới
     * @param string $type - Loại: 'customer' hoặc 'staff'
     * @param bool $isNew - true: tạo mới, false: đổi mật khẩu
     */
    public function sendPasswordEmail($user, $password, $type = 'customer', $isNew = false)
    {
        try {
            // Kiểm tra email
            if (empty($user['email'])) {
                error_log("Password Email: User has no email - username: " . ($user['username'] ?? 'N/A'));
                return ['success' => false, 'message' => 'Người dùng không có email'];
            }

            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($user['email'], $user['full_name'] ?? $user['username']);

            // Content
            $this->mailer->isHTML(true);

            $subject = $isNew
                ? ($type === 'customer' ? 'Chào mừng đến với MINIGO MARKET' : 'Tài khoản nhân viên đã được tạo')
                : ($type === 'customer' ? 'Mật khẩu mới của bạn - MINIGO MARKET' : 'Mật khẩu nhân viên đã được đổi');

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->getPasswordEmailBody($user, $password, $type, $isNew);
            $this->mailer->AltBody = $this->getPasswordEmailPlainText($user, $password, $type, $isNew);

            $this->mailer->send();

            error_log("Password email sent successfully to: " . $user['email']);
            return ['success' => true, 'message' => 'Email đã được gửi thành công'];
        } catch (Exception $e) {
            error_log("Password email send error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Tạo nội dung email HTML cho thông báo mật khẩu
     */
    private function getPasswordEmailBody($user, $password, $type, $isNew)
    {
        $userName = htmlspecialchars($user['full_name'] ?? $user['username']);
        $username = htmlspecialchars($user['username']);
        $isCustomer = $type === 'customer';

        $title = $isNew
            ? ($isCustomer ? 'Chào mừng đến với MINIGO!' : 'Tài khoản nhân viên')
            : ($isCustomer ? 'Mật khẩu mới' : 'Mật khẩu đã được đổi');

        $greeting = $isNew
            ? ($isCustomer ? 'Cảm ơn bạn đã đăng ký tài khoản tại MINIGO MARKET!' : 'Tài khoản nhân viên của bạn đã được tạo.')
            : 'Mật khẩu của bạn đã được thay đổi thành công.';

        $loginUrl = $isCustomer ? '/auth/login' : '/admin/auth/login';
        $loginUrlFull = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
            '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $loginUrl;

        $html = '
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f3f4f6;">
    <div style="background: linear-gradient(135deg, #002975 0%, #004ba8 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">MINIGO MARKET</h1>
        <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">' . $title . '</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Xin chào <strong style="color: #002975;">' . $userName . '</strong>,</p>
        
        <p style="font-size: 15px; line-height: 1.8; color: #16a34a; margin-bottom: 20px;">
            ✓ ' . $greeting . '
        </p>
        
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="color: #002975; font-size: 18px; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #002975; padding-bottom: 10px;">
                🔐 Thông tin đăng nhập
            </h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; font-weight: bold; color: #002975; width: 35%;">Tài khoản:</td>
                    <td style="padding: 10px 0; font-family: monospace; font-size: 16px; color: #1f2937;">' . $username . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; font-weight: bold; color: #002975;">Mật khẩu:</td>
                    <td style="padding: 10px 0; font-family: monospace; font-size: 16px; background: #fef3c7; padding: 8px 12px; border-radius: 4px; color: #92400e; font-weight: bold;">' . htmlspecialchars($password) . '</td>
                </tr>
            </table>
        </div>
        
        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #002975;">⚠️ Lưu ý bảo mật:</p>
            <ul style="margin: 10px 0; padding-left: 20px; font-size: 14px;">
                <li style="margin: 5px 0;">Vui lòng <strong>đổi mật khẩu</strong> sau khi đăng nhập lần đầu</li>
                <li style="margin: 5px 0;">Không chia sẻ mật khẩu với bất kỳ ai</li>
                <li style="margin: 5px 0;">Sử dụng mật khẩu mạnh và duy nhất</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $loginUrlFull . '" style="display: inline-block; background: #002975; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                Đăng nhập ngay
            </a>
        </div>
        
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 13px; color: #7f1d1d;">
                <strong>🔒 Bảo mật:</strong> Nếu bạn không yêu cầu thay đổi mật khẩu này, vui lòng liên hệ ngay với chúng tôi để được hỗ trợ.
            </p>
        </div>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <p style="font-size: 16px; color: #002975; margin: 0 0 10px 0;">
                <strong>Cảm ơn bạn đã sử dụng dịch vụ của MINIGO!</strong>
            </p>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Nếu có thắc mắc, vui lòng liên hệ: <a href="mailto:tienb2105563@student.ctu.edu.vn" style="color: #002975; text-decoration: none;">tienb2105563@student.ctu.edu.vn</a>
            </p>
        </div>
        
        <p style="font-size: 14px; color: #6b7280; margin-top: 20px;">
            Trân trọng,<br>
            <strong style="color: #002975;">Đội ngũ MINIGO MARKET</strong>
        </p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;">
        <p style="margin: 0;">Email này được gửi tự động, vui lòng không trả lời.</p>
        <p style="margin: 5px 0 0 0;">© ' . date('Y') . ' MINIGO MARKET. All rights reserved.</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Tạo nội dung email plain text cho thông báo mật khẩu
     */
    private function getPasswordEmailPlainText($user, $password, $type, $isNew)
    {
        $userName = $user['full_name'] ?? $user['username'];
        $username = $user['username'];
        $isCustomer = $type === 'customer';

        $greeting = $isNew
            ? ($isCustomer ? 'Cảm ơn bạn đã đăng ký tài khoản tại MINIGO MARKET!' : 'Tài khoản nhân viên của bạn đã được tạo.')
            : 'Mật khẩu của bạn đã được thay đổi thành công.';

        $loginUrl = $isCustomer ? '/auth/login' : '/admin/auth/login';

        $text = "Xin chào " . $userName . ",\n\n";
        $text .= $greeting . "\n\n";
        $text .= "THÔNG TIN ĐĂNG NHẬP:\n";
        $text .= str_repeat("-", 60) . "\n";
        $text .= "Tài khoản: " . $username . "\n";
        $text .= "Mật khẩu: " . $password . "\n";
        $text .= str_repeat("-", 60) . "\n\n";
        $text .= "LƯU Ý BẢO MẬT:\n";
        $text .= "- Vui lòng đổi mật khẩu sau khi đăng nhập lần đầu\n";
        $text .= "- Không chia sẻ mật khẩu với bất kỳ ai\n";
        $text .= "- Sử dụng mật khẩu mạnh và duy nhất\n\n";
        $text .= "Đăng nhập tại: " . $loginUrl . "\n\n";
        $text .= "Nếu bạn không yêu cầu thay đổi mật khẩu này, vui lòng liên hệ ngay với chúng tôi.\n\n";
        $text .= "Cảm ơn bạn đã sử dụng dịch vụ của MINIGO. Nếu có thắc mắc, vui lòng liên hệ: tienb2105563@student.ctu.edu.vn\n\n";
        $text .= "Trân trọng,\n";
        $text .= "Đội ngũ MINIGO MARKET\n";

        return $text;
    }

    /**
     * Gửi mã OTP để reset mật khẩu
     * 
     * @param string $email Email của khách hàng
     * @param string $otpCode Mã OTP (6 số)
     * @param string $fullName Tên đầy đủ của khách hàng
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendOTPEmail($email, $otpCode, $fullName = '')
    {
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($email, $fullName);

            // Subject
            $this->mailer->Subject = '[MINIGO] Mã xác nhận đặt lại mật khẩu';

            // Email content
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getOTPEmailBody($otpCode, $fullName);
            $this->mailer->AltBody = $this->getOTPEmailPlainText($otpCode, $fullName);

            $this->mailer->send();
            return ['success' => true, 'message' => 'OTP đã được gửi đến email'];
        } catch (Exception $e) {
            error_log("OTP email send error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Tạo nội dung HTML cho email OTP
     */
    private function getOTPEmailBody($otpCode, $fullName)
    {
        $greeting = !empty($fullName) ? "Xin chào <strong>{$fullName}</strong>," : "Xin chào,";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #002975 0%, #004aad 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .otp-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px; margin: 25px 0; }
                .otp-code { font-size: 42px; font-weight: bold; letter-spacing: 8px; margin: 10px 0; font-family: 'Courier New', monospace; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
                .highlight { color: #002975; font-weight: bold; }
                ul { padding-left: 20px; }
                li { margin: 8px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 MINIGO MARKET</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px;'>Đặt lại mật khẩu</p>
                </div>
                
                <div class='content'>
                    <p>{$greeting}</p>
                    
                    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>MINIGO MARKET</strong>.</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; font-size: 16px;'>Mã xác nhận của bạn:</p>
                        <div class='otp-code'>{$otpCode}</div>
                        <p style='margin: 0; font-size: 14px; opacity: 0.9;'>Mã có hiệu lực trong <strong>10 phút</strong></p>
                    </div>
                    
                    <p><strong>Vui lòng nhập mã này để tiếp tục đặt lại mật khẩu.</strong></p>
                    
                    <div class='warning'>
                        <p style='margin: 0;'><strong>⚠️ Lưu ý bảo mật:</strong></p>
                        <ul style='margin: 10px 0 0 0;'>
                            <li>Không chia sẻ mã này với bất kỳ ai</li>
                            <li>MINIGO sẽ không bao giờ yêu cầu mã OTP qua điện thoại</li>
                            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
                        </ul>
                    </div>
                    
                    <p style='margin-top: 25px;'>Nếu bạn gặp bất kỳ vấn đề nào, vui lòng liên hệ: <a href='mailto:tienb2105563@student.ctu.edu.vn' class='highlight'>tienb2105563@student.ctu.edu.vn</a></p>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0 0 10px 0;'><strong>MINIGO MARKET</strong></p>
                    <p style='margin: 0; font-size: 12px;'>Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Tạo nội dung plain text cho email OTP
     */
    private function getOTPEmailPlainText($otpCode, $fullName)
    {
        $greeting = !empty($fullName) ? "Xin chào {$fullName}," : "Xin chào,";

        $text = "{$greeting}\n\n";
        $text .= "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại MINIGO MARKET.\n\n";
        $text .= "MÃ XÁC NHẬN CỦA BẠN: {$otpCode}\n\n";
        $text .= "Mã có hiệu lực trong 10 phút.\n\n";
        $text .= "Vui lòng nhập mã này để tiếp tục đặt lại mật khẩu.\n\n";
        $text .= "LƯU Ý BẢO MẬT:\n";
        $text .= "- Không chia sẻ mã này với bất kỳ ai\n";
        $text .= "- MINIGO sẽ không bao giờ yêu cầu mã OTP qua điện thoại\n";
        $text .= "- Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này\n\n";
        $text .= "Nếu bạn gặp bất kỳ vấn đề nào, vui lòng liên hệ: tienb2105563@student.ctu.edu.vn\n\n";
        $text .= "Trân trọng,\n";
        $text .= "Đội ngũ MINIGO MARKET\n";

        return $text;
    }
}
