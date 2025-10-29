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
            $id = $this->couponRepo->create($data, $currentUser);
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
            $this->couponRepo->update($id, $data, $currentUser);
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

        try {
            $result = $this->couponRepo->validateCoupon($code, $orderAmount);
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
}
