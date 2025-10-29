<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\StockRepository;

class StockController extends BaseAdminController
{
    /** GET /admin/stock (view) */
    public function index()
    {
        return $this->view('admin/stock/stock');
    }

    /** GET /admin/api/stocks (list JSON) */
    public function apiIndex()
    {
        $rows = StockRepository::all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stocks/export - Xuất Excel */
    public function export()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $items = $data['items'] ?? [];

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Không có dữ liệu để xuất']);
            exit;
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $exportDate = $data['export_date'] ?? date('d/m/Y');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', "Ngày xuất: $exportDate");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'BÁO CÁO TỒN KHO');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Column headers
        $headers = ['STT', 'SKU', 'Tên sản phẩm', 'Đơn vị tính', 'Tồn kho', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }
        $sheet->getStyle('A5:F5')->getFont()->setBold(true);
        $sheet->getStyle('A5:F5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');

        // Data
        $row = 6;
        foreach ($items as $index => $item) {
            $qty = $item['qty'] ?? 0;
            $safetyStock = $item['safety_stock'] ?? 0;

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['product_sku'] ?? '');
            $sheet->setCellValue('C' . $row, $item['product_name'] ?? '');
            $sheet->setCellValue('D' . $row, $item['unit_name'] ?? '');
            $sheet->setCellValue('E' . $row, $qty);
            $sheet->setCellValue('F' . $row, $item['updated_by_name'] ?? '');

            // Highlight hết hàng (qty = 0)
            if ($qty === 0) {
                $sheet->getStyle("A$row:F$row")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEE2E2'); // red-100
            }
            // Highlight cảnh báo (qty < safety_stock)
            elseif ($qty < $safetyStock) {
                $sheet->getStyle("A$row:F$row")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7'); // yellow-100
            }

            // Format số cho cột tồn kho
            $sheet->getStyle('E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0');

            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        $sheet->getStyle("A5:F$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . ($data['filename'] ?? 'Ton_kho.xlsx') . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
