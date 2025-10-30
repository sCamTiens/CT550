<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\CouponRepository;
use App\Controllers\Admin\AuthController;

class CouponController extends BaseAdminController
{
    private $couponRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->couponRepo = new CouponRepository();
    }

    /** GET /admin/coupons (view) */
    public function index()
    {
        $items = $this->couponRepo->all();
        return $this->view('admin/coupons/coupon', ['items' => $items]);
    }

    /** GET /admin/api/coupons (list) */
    public function apiIndex()
    {
        $items = $this->couponRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/coupons (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $coupon = new \App\Models\Entities\Coupon($data);
            $id = $this->couponRepo->create($coupon, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->couponRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            
            // Log chi tiết để debug
            error_log("Coupon create error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo json_encode([
                'error' => 'Lỗi máy chủ khi tạo mã giảm giá: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** PUT /admin/coupons/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            // Kiểm tra xem mã đã được sử dụng chưa
            $existingCoupon = $this->couponRepo->findOne($id);
            if ($existingCoupon && $existingCoupon->used_count > 0) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Không thể chỉnh sửa mã giảm giá đã được sử dụng'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $coupon = new \App\Models\Entities\Coupon($data);
            $this->couponRepo->update($id, $coupon, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->couponRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật mã giảm giá: ' . $e->getMessage()]);
            exit;
        }
    }

    /** DELETE /admin/coupons/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            // Kiểm tra xem mã đã được sử dụng chưa
            $existingCoupon = $this->couponRepo->findOne($id);
            if ($existingCoupon && $existingCoupon->used_count > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Không thể xóa mã giảm giá đã được sử dụng'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $this->couponRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /admin/api/coupons/validate */
    public function validate()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $code = $data['code'] ?? '';
        $orderAmount = floatval($data['order_amount'] ?? 0);
        $userId = isset($data['user_id']) ? intval($data['user_id']) : null;

        try {
            $result = $this->couponRepo->validateCoupon($code, $orderAmount, $userId);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'valid' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['admin_user']['id'] ?? $_SESSION['user']['id'] ?? null;
    }

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        // Tự động tìm ngày nhỏ nhất và lớn nhất từ starts_at/ends_at
        $fromDate = '';
        $toDate = '';
        
        if (!empty($items)) {
            $dates = array_filter(array_map(function($item) {
                $date = $item['starts_at'] ?? '';
                if ($date && strpos($date, ' ') !== false) {
                    $date = explode(' ', $date)[0];
                }
                return $date;
            }, $items));
            
            if (!empty($dates)) {
                sort($dates);
                $fromDate = reset($dates);
                $toDate = end($dates);
            }
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:P1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:P2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:P3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH MÃ GIẢM GIÁ');
        $sheet->mergeCells('A5:P5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Mã', 'Mô tả', 'Loại', 'Giá trị', 'Đơn tối thiểu', 'Giảm tối đa', 'Số lần dùng', 'Đã dùng', 'Bắt đầu', 'Kết thúc', 'Trạng thái', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }
        $sheet->getStyle('A6:P6')->getFont()->setBold(true);
        $sheet->getStyle('A6:P6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 7;
        $stt = 1;
        foreach ($items as $c) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $c['code'] ?? '');
            $sheet->setCellValue('C' . $row, $c['description'] ?? '');
            $sheet->setCellValue('D' . $row, $c['discount_type'] ?? '');
            $sheet->setCellValue('E' . $row, $c['discount_value'] ?? 0);
            $sheet->setCellValue('F' . $row, $c['min_order_value'] ?? 0);
            $sheet->setCellValue('G' . $row, $c['max_discount'] ?? 0);
            $sheet->setCellValue('H' . $row, $c['max_uses'] ?? 0);
            $sheet->setCellValue('I' . $row, $c['used_count'] ?? 0);
            $sheet->setCellValue('J' . $row, $c['starts_at'] ?? '');
            $sheet->setCellValue('K' . $row, $c['ends_at'] ?? '');
            $sheet->setCellValue('L' . $row, $c['is_active'] ?? '');
            $sheet->setCellValue('M' . $row, $c['created_at'] ?? '');
            $sheet->setCellValue('N' . $row, $c['created_by_name'] ?? '');
            $sheet->setCellValue('O' . $row, $c['updated_at'] ?? '');
            $sheet->setCellValue('P' . $row, $c['updated_by_name'] ?? '');
            $row++;
        }

        $lastRow = $row - 1;

        // Format số có dấu phân cách nghìn
        $sheet->getStyle('E7:G' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:P' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'P') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Ma_giam_gia.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /** GET /admin/api/coupons/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'STT',
            ['text' => 'Mã giảm giá ', 'required' => true],
            'Tên mã giảm giá',
            'Mô tả',
            ['text' => 'Loại giảm giá ', 'required' => true, 'note' => '(percentage/fixed)'],
            ['text' => 'Giá trị giảm ', 'required' => true],
            'Giá trị đơn tối thiểu',
            'Giảm tối đa',
            'Số lần dùng tối đa',
            'Số lần dùng/khách',
            ['text' => 'Ngày bắt đầu ', 'required' => true, 'note' => '(dd/mm/yyyy HH:MM:SS)'],
            ['text' => 'Ngày kết thúc ', 'required' => true, 'note' => '(dd/mm/yyyy HH:MM:SS)'],
            ['text' => 'Trạng thái ', 'required' => true, 'note' => '(1: kích hoạt, 0: vô hiệu)']
        ];

        $col = 'A';
        foreach ($headers as $header) {
            if (is_array($header)) {
                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $richText->createText($header['text']);
                if ($header['required'] ?? false) {
                    $red = $richText->createTextRun('*');
                    $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
                }
                if ($header['note'] ?? false) {
                    $note = $richText->createTextRun("\n" . $header['note']);
                    $note->getFont()->setSize(9)->getColor()->setRGB('666666');
                }
                $sheet->setCellValue($col . '1', $richText);
            } else {
                $sheet->setCellValue($col . '1', $header);
            }
            $col++;
        }

        // Style header
        $sheet->getStyle('A1:M1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:M1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:M1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // Sample data
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'GIAM10');
        $sheet->setCellValue('C2', 'Giảm 10%');
        $sheet->setCellValue('D2', 'Khuyến mãi tháng 10');
        $sheet->setCellValue('E2', 'percentage');
        $sheet->setCellValue('F2', '10');
        $sheet->setCellValue('G2', '100000');
        $sheet->setCellValue('H2', '50000');
        $sheet->setCellValue('I2', '100');
        $sheet->setCellValue('J2', '1');
        $sheet->setCellValue('K2', '01/10/2025 00:00:00');
        $sheet->setCellValue('L2', '31/10/2025 23:59:59');
        $sheet->setCellValue('M2', '1');

        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', 'FIXED50K');
        $sheet->setCellValue('C3', 'Giảm 50k');
        $sheet->setCellValue('D3', 'Giảm cố định 50k');
        $sheet->setCellValue('E3', 'fixed');
        $sheet->setCellValue('F3', '50000');
        $sheet->setCellValue('G3', '200000');
        $sheet->setCellValue('H3', '50000');
        $sheet->setCellValue('I3', '50');
        $sheet->setCellValue('J3', '2');
        $sheet->setCellValue('K3', '01/11/2025 00:00:00');
        $sheet->setCellValue('L3', '30/11/2025 23:59:59');
        $sheet->setCellValue('M3', '1');

        // Borders
        $sheet->getStyle('A1:M3')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'M') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_ma_giam_gia.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/coupons/import - Nhập Excel */
    public function importExcel()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Kiểm tra file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Không thể tải file lên';
            if (isset($_FILES['file']['error'])) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg = 'File vượt quá kích thước cho phép';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg = 'Không có file được chọn';
                        break;
                }
            }
            http_response_code(400);
            echo json_encode(['error' => $errorMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $file = $_FILES['file'];

        // Kiểm tra kích thước file (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode([
                'error' => 'File vượt quá kích thước cho phép (tối đa 10MB). Kích thước file: ' . round($file['size'] / 1024 / 1024, 2) . 'MB'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra độ dài tên file
        if (strlen($file['name']) > 255) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tên file quá dài (tối đa 255 ký tự). Độ dài hiện tại: ' . strlen($file['name']) . ' ký tự'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra ký tự đặc biệt trong tên file
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
        if (!preg_match('/^[a-zA-Z0-9._\-\s()\[\]]+$/', $fileName)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tên file chứa ký tự đặc biệt không hợp lệ. Vui lòng chỉ sử dụng chữ cái, số, dấu gạch ngang, gạch dưới và khoảng trắng'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra định dạng file
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls', 'xlsx'])) {
            http_response_code(400);
            echo json_encode(['error' => 'File không đúng định dạng. Vui lòng chọn file Excel (.xls hoặc .xlsx)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));
            $spreadsheet = $reader->load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Kiểm tra số lượng dòng (tối đa 10,000)
            if (count($data) > 10001) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'File có quá nhiều dòng (tối đa 10,000). Số dòng hiện tại: ' . (count($data) - 1)
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Bỏ qua dòng header
            array_shift($data);

            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            $fileContent = [];

            $currentUserId = $this->currentUserId();
            $currentUserName = $this->currentUserName();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;

                $code = trim($row[1] ?? '');
                
                // Skip dòng trống
                if ($code === '') continue;

                $name = trim($row[2] ?? '');
                $description = trim($row[3] ?? '');
                $discountType = trim($row[4] ?? '');
                $discountValue = trim($row[5] ?? '0');
                $minOrderValue = trim($row[6] ?? '0');
                $maxDiscount = trim($row[7] ?? '0');
                $maxUses = trim($row[8] ?? '');
                $maxUsesPerCustomer = trim($row[9] ?? '0');
                $startsAt = trim($row[10] ?? '');
                $endsAt = trim($row[11] ?? '');
                $isActive = trim($row[12] ?? '1');

                $rowData = [
                    'row' => $rowNumber,
                    'code' => $code,
                    'name' => $name,
                    'description' => $description,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'min_order_value' => $minOrderValue,
                    'max_discount' => $maxDiscount,
                    'max_uses' => $maxUses,
                    'max_uses_per_customer' => $maxUsesPerCustomer,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_active' => $isActive
                ];

                // Validate
                $rowErrors = [];

                // 1. Mã giảm giá bắt buộc
                if ($code === '') {
                    $rowErrors[] = 'Mã giảm giá là bắt buộc';
                }

                // 2. Kiểm tra mã trùng
                if ($code !== '') {
                    $existing = $this->couponRepo->findByCode($code);
                    if ($existing) {
                        $rowErrors[] = "Mã giảm giá '$code' đã tồn tại";
                    }
                }

                // 3. Loại giảm giá
                if (!in_array($discountType, ['percentage', 'fixed'])) {
                    $rowErrors[] = 'Loại giảm giá phải là "percentage" hoặc "fixed"';
                }

                // 4. Giá trị giảm
                if (!is_numeric($discountValue) || $discountValue <= 0) {
                    $rowErrors[] = 'Giá trị giảm phải là số > 0';
                }

                // 5. Giá trị đơn tối thiểu
                if (!is_numeric($minOrderValue) || $minOrderValue < 0) {
                    $rowErrors[] = 'Giá trị đơn tối thiểu phải là số >= 0';
                }

                // 6. Giảm tối đa
                if (!is_numeric($maxDiscount) || $maxDiscount < 0) {
                    $rowErrors[] = 'Giảm tối đa phải là số >= 0';
                }

                // 7. Số lần dùng tối đa (nullable)
                if ($maxUses !== '' && (!is_numeric($maxUses) || $maxUses < 0)) {
                    $rowErrors[] = 'Số lần dùng tối đa phải là số >= 0';
                }

                // 8. Số lần dùng/khách
                if (!is_numeric($maxUsesPerCustomer) || $maxUsesPerCustomer < 0) {
                    $rowErrors[] = 'Số lần dùng/khách phải là số >= 0';
                }

                // 9. Ngày bắt đầu
                if ($startsAt === '') {
                    $rowErrors[] = 'Ngày bắt đầu là bắt buộc';
                } elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $startsAt)) {
                    $rowErrors[] = 'Ngày bắt đầu phải có định dạng dd/mm/yyyy HH:MM:SS';
                }

                // 10. Ngày kết thúc
                if ($endsAt === '') {
                    $rowErrors[] = 'Ngày kết thúc là bắt buộc';
                } elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $endsAt)) {
                    $rowErrors[] = 'Ngày kết thúc phải có định dạng dd/mm/yyyy HH:MM:SS';
                }

                // 11. Trạng thái
                if (!in_array($isActive, ['0', '1'])) {
                    $rowErrors[] = 'Trạng thái phải là 0 hoặc 1';
                }

                // Nếu có lỗi
                if (!empty($rowErrors)) {
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = implode('; ', $rowErrors);
                    $errors[] = "Dòng $rowNumber: " . implode('; ', $rowErrors);
                    $fileContent[] = $rowData;
                    $failedCount++;
                    continue;
                }

                // Tạo coupon
                try {
                    // Convert datetime
                    $startsAtConverted = $this->convertDateTimeFormat($startsAt);
                    $endsAtConverted = $this->convertDateTimeFormat($endsAt);

                    $couponData = [
                        'code' => $code,
                        'name' => $name ?: null,
                        'description' => $description ?: null,
                        'discount_type' => $discountType,
                        'discount_value' => (float)$discountValue,
                        'min_order_value' => (float)$minOrderValue,
                        'max_discount' => (float)$maxDiscount,
                        'max_uses' => $maxUses !== '' ? (int)$maxUses : null,
                        'max_uses_per_customer' => (int)$maxUsesPerCustomer,
                        'starts_at' => $startsAtConverted,
                        'ends_at' => $endsAtConverted,
                        'is_active' => (int)$isActive
                    ];

                    $coupon = new \App\Models\Entities\Coupon($couponData);
                    $couponId = $this->couponRepo->create($coupon, $currentUserId);

                    $rowData['status'] = 'success';
                    $rowData['id'] = $couponId;
                    $fileContent[] = $rowData;
                    $successCount++;
                } catch (\Exception $e) {
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $e->getMessage();
                    $errors[] = "Dòng $rowNumber: " . $e->getMessage();
                    $fileContent[] = $rowData;
                    $failedCount++;
                }
            }

            // Xác định status
            $status = 'success';
            if ($failedCount > 0 && $successCount === 0) {
                $status = 'failed';
            } elseif ($failedCount > 0 && $successCount > 0) {
                $status = 'partial';
            }

            // Lưu vào import_history
            $this->saveImportHistory(
                'coupons',
                $file['name'],
                $successCount,
                $failedCount,
                $status,
                $errors,
                $fileContent
            );

            // Tạo message
            $message = "Nhập thành công $successCount mã giảm giá";
            if ($failedCount > 0) {
                $firstError = !empty($errors) ? $errors[0] : '';
                $message = "Nhập thành công $successCount/" . ($successCount + $failedCount) . " mã giảm giá. Lỗi đầu tiên: $firstError";
            }

            echo json_encode([
                'success' => $successCount,
                'failed' => $failedCount,
                'status' => $status,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi khi đọc file: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    private function convertDateTimeFormat($dateTimeStr)
    {
        if (!$dateTimeStr) return null;
        
        // dd/mm/yyyy HH:MM:SS -> yyyy-mm-dd HH:MM:SS
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})$/', $dateTimeStr, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6];
        }
        
        return null;
    }

    private function saveImportHistory($tableName, $fileName, $successCount, $failedCount, $status, $errors, $fileContent)
    {
        require_once __DIR__ . '/../../Models/Repositories/ImportHistoryRepository.php';
        $importHistoryRepo = new \App\Models\Repositories\ImportHistoryRepository();

        $importHistoryRepo->create([
            'table_name' => $tableName,
            'file_name' => $fileName,
            'total_rows' => $successCount + $failedCount,
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
            'status' => $status,
            'error_details' => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'file_content' => !empty($fileContent) ? json_encode($fileContent, JSON_UNESCAPED_UNICODE) : null,
            'imported_by' => $currentUserId ?? null,
            'imported_by_name' => $this->currentUserName()
        ]);
    }

    protected function currentUserName(): ?string
    {
        return $_SESSION['admin_user']['full_name'] ?? $_SESSION['user']['full_name'] ?? null;
    }
}
