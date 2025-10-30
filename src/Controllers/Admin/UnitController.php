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

    protected function currentUserName(): ?string
    {
        return $_SESSION['admin_user']['full_name'] ?? null;
    }

    /** GET /admin/api/units/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Tên *');
        $sheet->setCellValue('C1', 'Slug');

        // Định dạng header
        $sheet->getStyle('A1:C1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');

        // Đánh dấu màu đỏ cho dấu * (trường bắt buộc)
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richText->createText('Tên ');
        $red = $richText->createTextRun('*');
        $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        $sheet->getCell('B1')->setValue($richText);

        // Border cho header
        $sheet->getStyle('A1:C1')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Dữ liệu mẫu
        $sampleData = [
            [1, 'Kilogram', 'kilogram'],
            [2, 'Hộp', 'hop'],
            [3, 'Chai', 'chai']
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->setCellValue('C' . $row, $data[2]);
            $row++;
        }

        // Border cho data
        $lastRow = $row - 1;
        $sheet->getStyle("A1:C$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_don_vi_tinh.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/units/import - Nhập Excel */
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
                        $errorMsg = 'File vượt quá kích thước cho phép (tối đa 10MB)';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg = 'File chỉ được tải lên một phần';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg = 'Không có file nào được chọn';
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
            if (count($data) > 10001) { // +1 cho header
                http_response_code(400);
                echo json_encode([
                    'error' => 'File vượt quá số lượng dòng cho phép (tối đa 10,000 dòng dữ liệu). Số dòng hiện tại: ' . (count($data) - 1)
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
                $rowNumber = $index + 2; // +2 vì bỏ header và index bắt đầu từ 0

                // Bỏ qua dòng trống
                if (empty(array_filter($row))) {
                    continue;
                }

                $name = trim($row[1] ?? '');
                $slug = trim($row[2] ?? '');

                $rowErrors = [];

                // Validate tên (bắt buộc)
                if (empty($name)) {
                    $rowErrors[] = "Tên đơn vị không được bỏ trống";
                } elseif (mb_strlen($name) > 250) {
                    $rowErrors[] = "Tên không được vượt quá 250 ký tự (hiện tại: " . mb_strlen($name) . " ký tự)";
                } elseif (preg_match('/[<>"\']/', $name)) {
                    $rowErrors[] = "Tên không được chứa các ký tự đặc biệt như < > \" '";
                }

                // Tự động tạo slug nếu không có
                if (empty($slug) && !empty($name)) {
                    $slug = $this->slugify($name);
                }

                // Validate slug
                if (!empty($slug)) {
                    if (mb_strlen($slug) > 250) {
                        $rowErrors[] = "Slug không được vượt quá 250 ký tự (hiện tại: " . mb_strlen($slug) . " ký tự)";
                    }

                    // Kiểm tra slug trùng trong DB
                    $existing = $this->unitRepo->findBySlug($slug);
                    if ($existing) {
                        $rowErrors[] = "Slug '$slug' đã tồn tại trong hệ thống";
                    }
                }

                // Lưu nội dung dòng
                $fileContent[] = [
                    'row' => $rowNumber,
                    'name' => $name,
                    'slug' => $slug,
                    'status' => empty($rowErrors) ? 'success' : 'failed',
                    'errors' => $rowErrors
                ];

                if (!empty($rowErrors)) {
                    $failedCount++;
                    if (empty($errors)) {
                        $errors[] = "Dòng $rowNumber: " . $rowErrors[0];
                    }
                    continue;
                }

                // Thêm vào DB
                try {
                    $this->unitRepo->create($name, $slug, $currentUserId);
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMsg = "Lỗi khi lưu: " . $e->getMessage();
                    $fileContent[count($fileContent) - 1]['status'] = 'failed';
                    $fileContent[count($fileContent) - 1]['errors'][] = $errorMsg;
                    if (empty($errors)) {
                        $errors[] = "Dòng $rowNumber: " . $errorMsg;
                    }
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
                'units',
                $file['name'],
                $successCount,
                $failedCount,
                $status,
                $errors,
                $fileContent
            );

            // Tạo message
            $message = "Nhập thành công $successCount đơn vị";
            if ($failedCount > 0) {
                $message .= ", thất bại $failedCount đơn vị";
                if (!empty($errors)) {
                    $message .= " (" . $errors[0] . " - xem chi tiết trong lịch sử nhập)";
                }
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
            'error_details' => json_encode($errors, JSON_UNESCAPED_UNICODE),
            'file_content' => json_encode($fileContent, JSON_UNESCAPED_UNICODE),
            'imported_by' => $this->currentUserId(),
            'imported_by_name' => $this->currentUserName()
        ]);
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
