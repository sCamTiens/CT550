<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\StockOutRepository;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StockOutController extends BaseAdminController
{
    private $stockOutRepo;

    public function __construct()
    {
        parent::__construct();
        $this->stockOutRepo = new StockOutRepository();
    }

    /** GET /admin/stock-outs (trả về view) */
    public function index()
    {
        return $this->view('admin/stock-outs/stock-out');
    }

    /** GET /admin/api/stock-outs (list) */
    public function apiIndex()
    {
        $items = $this->stockOutRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/stock-outs/next-code */
    public function nextCode()
    {
        $code = $this->stockOutRepo->generateCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stock-outs (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $id = $this->stockOutRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->stockOutRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo phiếu xuất kho: ' . $e->getMessage()]);
            exit;
        }
    }

    /** PUT /admin/api/stock-outs/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        
        try {
            $this->stockOutRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->stockOutRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật phiếu xuất kho: ' . $e->getMessage()]);
            exit;
        }
    }

    /** DELETE /admin/api/stock-outs/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->stockOutRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** GET /admin/api/stock-outs/pending */
    public function pending()
    {
        $items = $this->stockOutRepo->pending();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/stock-outs/{id}/items */
    public function getItems($id)
    {
        $items = $this->stockOutRepo->getItems($id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stock-outs/{id}/approve */
    public function approve($id)
    {
        $currentUser = $this->currentUserId();
        try {
            $this->stockOutRepo->approve($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /admin/api/stock-outs/{id}/complete */
    public function complete($id)
    {
        $currentUser = $this->currentUserId();
        try {
            $this->stockOutRepo->complete($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
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

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        // Tự động tìm ngày nhỏ nhất và lớn nhất từ danh sách phiếu xuất
        $fromDate = '';
        $toDate = '';
        
        if (!empty($items)) {
            $dates = array_filter(array_map(function($item) {
                $date = $item['out_date'] ?? '';
                // Chỉ lấy phần ngày (loại bỏ giờ)
                if ($date && strpos($date, ' ') !== false) {
                    $date = explode(' ', $date)[0];
                }
                return $date;
            }, $items));
            
            if (!empty($dates)) {
                sort($dates);
                $fromDate = reset($dates); // Ngày nhỏ nhất
                $toDate = end($dates);     // Ngày lớn nhất
            }
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:N3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH PHIẾU XUẤT KHO');
        $sheet->mergeCells('A5:N5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers with product details
        $headers = ['STT', 'Mã phiếu', 'Khách hàng', 'Đơn hàng', 'Loại', 'Trạng thái', 'Sản phẩm', 'Mã lô', 'Số lượng', 'Ngày xuất', 'Tổng tiền', 'Ghi chú', 'Thời gian tạo', 'Người tạo'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }
        $sheet->getStyle('A6:N6')->getFont()->setBold(true);
        $sheet->getStyle('A6:N6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data with merged cells for multiple products
        $row = 7;
        $stt = 1;
        foreach ($items as $s) {
            $productItems = $s['items'] ?? [];
            
            $startRow = $row; // Lưu dòng bắt đầu để merge cells
            
            if (empty($productItems)) {
                // No products - show empty row
                $sheet->setCellValue('A' . $row, $stt++);
                $sheet->setCellValue('B' . $row, $s['code'] ?? '');
                $sheet->setCellValue('C' . $row, $s['customer_name'] ?? '');
                $sheet->setCellValue('D' . $row, $s['order_code'] ?? '');
                $sheet->setCellValue('E' . $row, $s['type'] ?? '');
                $sheet->setCellValue('F' . $row, $s['status'] ?? '');
                $sheet->setCellValue('G' . $row, '');
                $sheet->setCellValue('H' . $row, '');
                $sheet->setCellValue('I' . $row, '');
                $sheet->setCellValue('J' . $row, $s['out_date'] ?? '');
                $sheet->setCellValue('K' . $row, $s['total_amount'] ?? 0);
                $sheet->setCellValue('L' . $row, $s['note'] ?? '');
                $sheet->setCellValue('M' . $row, $s['created_at'] ?? '');
                $sheet->setCellValue('N' . $row, $s['created_by_name'] ?? '');
                $row++;
            } else {
                // Xuất nhiều dòng cho mỗi sản phẩm
                foreach ($productItems as $idx => $item) {
                    // Chỉ ghi thông tin phiếu xuất ở dòng đầu tiên
                    if ($idx === 0) {
                        $sheet->setCellValue('A' . $row, $stt);
                        $sheet->setCellValue('B' . $row, $s['code'] ?? '');
                        $sheet->setCellValue('C' . $row, $s['customer_name'] ?? '');
                        $sheet->setCellValue('D' . $row, $s['order_code'] ?? '');
                        $sheet->setCellValue('E' . $row, $s['type'] ?? '');
                        $sheet->setCellValue('F' . $row, $s['status'] ?? '');
                        $sheet->setCellValue('J' . $row, $s['out_date'] ?? '');
                        $sheet->setCellValue('K' . $row, $s['total_amount'] ?? 0);
                        $sheet->setCellValue('L' . $row, $s['note'] ?? '');
                        $sheet->setCellValue('M' . $row, $s['created_at'] ?? '');
                        $sheet->setCellValue('N' . $row, $s['created_by_name'] ?? '');
                    }
                    
                    // Thông tin sản phẩm (ghi ở mọi dòng)
                    $sheet->setCellValue('G' . $row, $item['product_name'] ?? '');
                    $sheet->setCellValue('H' . $row, $item['batch_code'] ?? '');
                    $sheet->setCellValue('I' . $row, $item['quantity'] ?? 0);
                    $row++;
                }
                
                // Merge cells cho các cột thông tin phiếu xuất (nếu có nhiều hơn 1 sản phẩm)
                $endRow = $row - 1;
                if ($endRow > $startRow) {
                    // Merge các cột: STT, Mã phiếu, Khách hàng, Đơn hàng, Loại, Trạng thái, Ngày xuất, Tổng tiền, Ghi chú, Thời gian tạo, Người tạo
                    $mergeCols = ['A', 'B', 'C', 'D', 'E', 'F', 'J', 'K', 'L', 'M', 'N'];
                    foreach ($mergeCols as $col) {
                        $sheet->mergeCells($col . $startRow . ':' . $col . $endRow);
                        // Căn giữa theo chiều dọc
                        $sheet->getStyle($col . $startRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }
                }
                
                $stt++;
            }
        }

        $lastRow = $row - 1;

        // Format số có dấu phân cách nghìn
        $sheet->getStyle('I7:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('K7:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:N' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'N') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Phieu_xuat_kho.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
