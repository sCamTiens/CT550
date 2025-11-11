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
        $formatMoney = function($amount) {
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
}
