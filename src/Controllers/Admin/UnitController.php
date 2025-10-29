<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\UnitRepository;

class UnitController extends BaseAdminController
{
    private $unitRepo;

    public function __construct()
    {
        parent::__construct();
        $this->unitRepo = new UnitRepository();
    }
    /** GET /admin/units (view) */
    public function index()
    {
        return $this->view('admin/units/unit');
    }

    /** GET /admin/api/units (list JSON) */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        $items = $this->unitRepo->all();
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/units (create) */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');

        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        // validate
        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        if ($slug !== null && mb_strlen($slug) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 250 ký tự']);
            exit;
        }

        try {
            $id = $this->unitRepo->create($name, $slug, $currentUserId);
            $row = $this->unitRepo->findOne($id);
            $row->created_by_name = $currentUserName;
            $row->updated_by_name = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo đơn vị']);
            exit;
        }
    }

    /** PUT /admin/units/{id} */
    public function update($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');

        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $this->unitRepo->update($id, $name, $slug, $currentUserId);
            $row = $this->unitRepo->findOne($id);
            $row->updated_by_name = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật đơn vị']);
            exit;
        }
    }

    /** DELETE /admin/units/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->unitRepo->delete($id);
            echo json_encode(['ok' => true]);
        } catch (\RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Lỗi máy chủ khi xoá',
                'detail' => $e->getMessage(),
                'code' => $e->getCode(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // findOne đã chuyển sang UnitRepository

    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        if (class_exists('\Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        }
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 250) ?: null;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['admin_user']['id'] ?? null;
    }

    private function currentUserName(): ?string
    {
        return $_SESSION['admin_user']['full_name'] ?? null;
    }

    /** POST /admin/api/units/export - Xuất Excel */
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
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $exportDate = $data['export_date'] ?? date('d/m/Y');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', "Ngày xuất: $exportDate");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'DANH SÁCH ĐƠN VỊ TÍNH');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Column headers
        $headers = ['STT', 'Tên đơn vị', 'Slug', 'Ngày tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }
        $sheet->getStyle('A5:G5')->getFont()->setBold(true);
        $sheet->getStyle('A5:G5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');

        // Data
        $row = 6;
        foreach ($items as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['name'] ?? '');
            $sheet->setCellValue('C' . $row, $item['slug'] ?? '');
            $sheet->setCellValue('D' . $row, $item['created_at'] ?? '');
            $sheet->setCellValue('E' . $row, $item['created_by_name'] ?? '');
            $sheet->setCellValue('F' . $row, $item['updated_at'] ?? '');
            $sheet->setCellValue('G' . $row, $item['updated_by_name'] ?? '');
            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        $sheet->getStyle("A5:G$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . ($data['filename'] ?? 'Don_vi_tinh.xlsx') . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
