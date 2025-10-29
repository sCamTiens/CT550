<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ReceiptVoucherRepository;
use App\Models\Entities\ReceiptVoucher;

class ReceiptVoucherController extends BaseAdminController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new ReceiptVoucherRepository();
    }

    /** GET /admin/receipt_vouchers (trả về view) */
    public function index()
    {
        return $this->view('admin/receipts/receipt');
    }

    /** GET /admin/api/receipt_vouchers (list) */
    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/receipt_vouchers (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        try {
            $id = $this->repo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->repo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi máy chủ khi tạo phiếu thu'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/receipt_vouchers/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        try {
            $this->repo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->repo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi máy chủ khi cập nhật phiếu thu'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/receipt_vouchers/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * GET /admin/api/receipt_vouchers/next-code
     * Trả về mã phiếu thu tiếp theo
     */
    public function nextCode()
    {
        $code = $this->repo->getNextCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        // Tự động tìm ngày nhỏ nhất và lớn nhất từ received_at
        $fromDate = '';
        $toDate = '';
        
        if (!empty($items)) {
            $dates = array_filter(array_map(function($item) {
                $date = $item['received_at'] ?? '';
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
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH PHIẾU THU');
        $sheet->mergeCells('A5:M5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Mã phiếu', 'Người nộp', 'Đơn hàng', 'Phương thức', 'Số tiền', 'Mã giao dịch', 'Thời gian GD', 'Người thu', 'Ngày thu', 'Ghi chú', 'Thời gian tạo', 'Người tạo'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }
        $sheet->getStyle('A6:M6')->getFont()->setBold(true);
        $sheet->getStyle('A6:M6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 7;
        $stt = 1;
        foreach ($items as $r) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $r['code'] ?? '');
            $sheet->setCellValue('C' . $row, $r['payer_user_name'] ?? '');
            $sheet->setCellValue('D' . $row, $r['order_id'] ?? '');
            $sheet->setCellValue('E' . $row, $r['method'] ?? '');
            $sheet->setCellValue('F' . $row, $r['amount'] ?? 0);
            $sheet->setCellValue('G' . $row, $r['txn_ref'] ?? '');
            $sheet->setCellValue('H' . $row, $r['bank_time'] ?? '');
            $sheet->setCellValue('I' . $row, $r['received_by_name'] ?? '');
            $sheet->setCellValue('J' . $row, $r['received_at'] ?? '');
            $sheet->setCellValue('K' . $row, $r['note'] ?? '');
            $sheet->setCellValue('L' . $row, $r['created_at'] ?? '');
            $sheet->setCellValue('M' . $row, $r['created_by_name'] ?? '');
            $row++;
        }

        $lastRow = $row - 1;

        // Format số có dấu phân cách nghìn
        $sheet->getStyle('F7:F' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:M' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'M') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Phieu_thu.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
