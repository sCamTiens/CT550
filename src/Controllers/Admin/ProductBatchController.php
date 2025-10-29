<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ProductBatchRepository;
use App\Controllers\Admin\AuthController;
use App\Models\Repositories\ProductRepository;

class ProductBatchController extends BaseAdminController
{
    private ProductBatchRepository $repo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new ProductBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    // GET /admin/product-batches (view)
    public function index()
    {
        $items = $this->repo->all();
        $products = $this->productRepo->all();
        return $this->view('admin/product-batches/product-batches', ['items' => $items, 'products' => $products]);
    }

    // GET /admin/api/product-batches
    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST /admin/api/product-batches
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
            echo json_encode(['error' => 'Lỗi khi tạo lô sản phẩm']);
            exit;
        }
    }

    // PUT /admin/api/product-batches/{id}
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
            echo json_encode(['error' => 'Lỗi khi cập nhật lô']);
            exit;
        }
    }

    // DELETE /admin/api/product-batches/{id}
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->delete($id); // soft-delete (archive)
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // POST /admin/api/product-batches/{id}/restore
    public function restore($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->repo->restore($id);
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

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        // Tự động tìm ngày nhỏ nhất và lớn nhất từ created_at
        $fromDate = '';
        $toDate = '';
        
        if (!empty($items)) {
            $dates = array_filter(array_map(function($item) {
                $date = $item['created_at'] ?? '';
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
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:K3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH LÔ SẢN PHẨM');
        $sheet->mergeCells('A5:K5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Sản phẩm', 'Mã lô', 'NSX', 'HSD', 'SL ban đầu', 'Tồn hiện tại', 'Giá nhập', 'Ghi chú', 'Thời gian tạo', 'Người tạo'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }
        $sheet->getStyle('A6:K6')->getFont()->setBold(true);
        $sheet->getStyle('A6:K6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 7;
        $stt = 1;
        foreach ($items as $b) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $b['product_name'] ?? '');
            $sheet->setCellValue('C' . $row, $b['batch_code'] ?? '');
            $sheet->setCellValue('D' . $row, $b['mfg_date'] ?? '');
            $sheet->setCellValue('E' . $row, $b['exp_date'] ?? '');
            $sheet->setCellValue('F' . $row, $b['initial_qty'] ?? 0);
            $sheet->setCellValue('G' . $row, $b['current_qty'] ?? 0);
            $sheet->setCellValue('H' . $row, $b['unit_cost'] ?? 0);
            $sheet->setCellValue('I' . $row, $b['note'] ?? '');
            $sheet->setCellValue('J' . $row, $b['created_at'] ?? '');
            $sheet->setCellValue('K' . $row, $b['created_by_name'] ?? '');
            $row++;
        }

        $lastRow = $row - 1;

        // Format số có dấu phân cách nghìn
        $sheet->getStyle('F7:H' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:K' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'K') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Lo_san_pham.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
