<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\SupplierRepository;

class SupplierController extends BaseAdminController
{
    private $supplierRepo;

    public function __construct()
    {
        parent::__construct();
        $this->supplierRepo = new SupplierRepository();
    }
    public function index()
    {
        return $this->view('admin/suppliers/supplier');
    }

    /** GET /admin/api/suppliers */
    public function apiIndex()
    {
        $items = $this->supplierRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/suppliers */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số']);
            exit;
        }
        $id = $this->supplierRepo->create($data, $currentUser);
        echo json_encode($this->supplierRepo->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** PUT /admin/suppliers/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số']);
            exit;
        }
        $this->supplierRepo->update($id, $data, $currentUser);
        echo json_encode($this->supplierRepo->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** DELETE /admin/suppliers/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->supplierRepo->delete($id);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi xoá', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // findOne now in SupplierRepository

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /** POST /admin/api/suppliers/export - Xuất Excel */
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
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $exportDate = $data['export_date'] ?? date('d/m/Y');
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', "Ngày xuất: $exportDate");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:I3');
        $sheet->setCellValue('A3', 'DANH SÁCH NHÀ CUNG CẤP');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Column headers
        $headers = ['STT', 'Tên NCC', 'Số điện thoại', 'Email', 'Địa chỉ', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);
        $sheet->getStyle('A5:I5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');

        // Data
        $row = 6;
        foreach ($items as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['name'] ?? '');
            $sheet->setCellValue('C' . $row, $item['phone'] ?? '');
            $sheet->setCellValue('D' . $row, $item['email'] ?? '');
            $sheet->setCellValue('E' . $row, $item['address'] ?? '');
            $sheet->setCellValue('F' . $row, $item['created_at'] ?? '');
            $sheet->setCellValue('G' . $row, $item['created_by_name'] ?? '');
            $sheet->setCellValue('H' . $row, $item['updated_at'] ?? '');
            $sheet->setCellValue('I' . $row, $item['updated_by_name'] ?? '');
            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        $sheet->getStyle("A5:I$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . ($data['filename'] ?? 'Nha_cung_cap.xlsx') . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
