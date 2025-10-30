<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\BrandRepository;

class BrandController extends BaseAdminController
{
    private $brandRepo;

    public function __construct()
    {
        parent::__construct(); // Gọi constructor của BaseAdminController
        $this->brandRepo = new BrandRepository();
    }
    /** GET /admin/brands (view) */
    public function index()
    {
        return $this->view('admin/brands/brand');
    }

    /** GET /admin/api/brands (list JSON) */
    public function apiIndex()
    {
        $rows = $this->brandRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/brands (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        if ($slug !== null && mb_strlen($slug) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 190 ký tự']);
            exit;
        }

        try {
            $brand = $this->brandRepo->create($name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi tạo thương hiệu']);
            }
            exit;
        }
    }

    /** PUT /admin/brands/{id} (update) */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $brand = $this->brandRepo->update($id, $name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật thương hiệu']);
            }
            exit;
        }
    }

    /** DELETE /admin/brands/{id} */
    public function destroy($id)
    {
        // Kiểm tra ràng buộc: nếu thương hiệu đã có sản phẩm thì không cho xóa
        if ($this->brandHasProducts($id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xóa, thương hiệu đang bị ràng buộc với sản phẩm.']);
            exit;
        }
        try {
            $this->brandRepo->delete($id);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi xóa thương hiệu']);
        }
        exit;
    }

    // Helper: fallback nếu chưa có canDelete trong BrandRepository
    private function brandHasProducts($id)
    {
        $pdo = \App\Core\DB::pdo();
        $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $count->execute([$id]);
        return $count->fetchColumn() > 0;
    }

    // ====== Helper Methods ======

    /** Convert Brand entity or array to plain array */
    private function entityToArray($brand)
    {
        if (is_array($brand)) {
            return array_map([$this, 'entityToArray'], $brand);
        }
        if (!is_object($brand)) {
            return $brand;
        }
        return get_object_vars($brand);
    }

    /** Chuyển text thành slug */
    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 190) ?: null;
    }

    /** Lấy ID user hiện tại từ session */
    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A3', 'DANH SÁCH THƯƠNG HIỆU');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Tên thương hiệu', 'Slug', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:G5')->getFont()->setBold(true);
        $sheet->getStyle('A5:G5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 6;
        $stt = 1;
        foreach ($items as $b) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $b['name'] ?? '');
            $sheet->setCellValue('C' . $row, $b['slug'] ?? '');
            $sheet->setCellValue('D' . $row, $b['created_at'] ?? '');
            $sheet->setCellValue('E' . $row, $b['created_by_name'] ?? '');
            $sheet->setCellValue('F' . $row, $b['updated_at'] ?? '');
            $sheet->setCellValue('G' . $row, $b['updated_by_name'] ?? '');
            $row++;
        }

        $lastRow = $row - 1;

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A5:G' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'G') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Thuong_hieu.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /** GET /admin/api/brands/template - Download file mẫu Excel */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Tên');
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
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Coca Cola');
        $sheet->setCellValue('C2', 'coca-cola');

        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Pepsi');
        $sheet->setCellValue('C3', 'pepsi');

        $sheet->setCellValue('A4', '3');
        $sheet->setCellValue('B4', 'Sting');
        $sheet->setCellValue('C4', 'sting');

        // Border cho data
        $sheet->getStyle('A2:C4')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Mau_thuong_hieu.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/brands/import - Import Excel */
    public function importExcel()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();
        $fileName = '';
        $success = 0;
        $errors = [];
        $fileData = [];

        try {
            // 1. Kiểm tra file có được upload không
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Không có file được tải lên';
                
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
                
                $this->saveImportHistory('brands', 'unknown', 0, 0, 0, 'failed', [$errorMsg], null);
                
                http_response_code(400);
                echo json_encode(['error' => $errorMsg], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $fileName = $_FILES['file']['name'];
            $fileSize = $_FILES['file']['size'];
            $file = $_FILES['file']['tmp_name'];
            
            // 2. Kiểm tra kích thước file (10MB)
            $maxSize = 10 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $this->saveImportHistory('brands', $fileName, 0, 0, 0, 'failed', 
                    ['File vượt quá kích thước cho phép (tối đa 10MB). Kích thước: ' . round($fileSize / 1024 / 1024, 2) . 'MB'], null);
                
                http_response_code(400);
                echo json_encode(['error' => 'File vượt quá kích thước cho phép (tối đa 10MB)'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 3. Kiểm tra độ dài tên file
            if (mb_strlen($fileName) > 255) {
                $this->saveImportHistory('brands', mb_substr($fileName, 0, 255), 0, 0, 0, 'failed',
                    ['Tên file quá dài (tối đa 255 ký tự)'], null);
                
                http_response_code(400);
                echo json_encode(['error' => 'Tên file quá dài (tối đa 255 ký tự)'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 4. Kiểm tra ký tự đặc biệt
            $cleanFileName = preg_replace('/[^a-zA-Z0-9._\-\s()\[\]]/u', '', pathinfo($fileName, PATHINFO_FILENAME));
            if ($cleanFileName !== pathinfo($fileName, PATHINFO_FILENAME)) {
                $this->saveImportHistory('brands', $fileName, 0, 0, 0, 'failed',
                    ['Tên file chứa ký tự đặc biệt không hợp lệ'], null);
                
                http_response_code(400);
                echo json_encode(['error' => 'Tên file chứa ký tự đặc biệt không hợp lệ'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 5. Kiểm tra định dạng file
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                $this->saveImportHistory('brands', $fileName, 0, 0, 0, 'failed',
                    ['File không đúng định dạng. Chỉ chấp nhận .xls hoặc .xlsx'], null);
                
                http_response_code(400);
                echo json_encode(['error' => 'File không đúng định dạng. Chỉ chấp nhận .xls hoặc .xlsx'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            
            // 6. Kiểm tra số lượng dòng
            $maxRows = 10000;
            if ($highestRow > $maxRows + 1) { // +1 vì header ở dòng 1
                $this->saveImportHistory('brands', $fileName, 0, 0, 0, 'failed',
                    ['File có quá nhiều dòng (tối đa ' . number_format($maxRows) . ' dòng)'], null);
                
                http_response_code(400);
                echo json_encode(['error' => 'File có quá nhiều dòng (tối đa ' . number_format($maxRows) . ' dòng)'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Bắt đầu từ dòng 2 (sau header dòng 1)
            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                
                // Skip hàng trống
                if ($name === '') continue;

                $slug = trim($sheet->getCell('C' . $row)->getValue() ?? '');

                $rowData = [
                    'row' => $row,
                    'name' => $name,
                    'slug' => $slug,
                    'result' => 'pending',
                    'error' => ''
                ];

                // ===== VALIDATE =====
                $rowErrors = [];
                
                // 1. Tên bắt buộc
                if ($name === '') {
                    $rowErrors[] = 'Tên thương hiệu không được bỏ trống';
                }
                
                // 2. Độ dài tên
                if (mb_strlen($name) > 250) {
                    $rowErrors[] = 'Tên không được vượt quá 250 ký tự';
                }
                
                // 3. Ký tự đặc biệt trong tên
                if (preg_match('/[<>\"\'\\\\]/', $name)) {
                    $rowErrors[] = 'Tên chứa ký tự không hợp lệ (< > " \' \\)';
                }

                // Auto-generate slug
                if ($slug === '') {
                    $slug = $this->slugify($name);
                    $rowData['slug'] = $slug;
                }
                
                // 4. Độ dài slug
                if (mb_strlen($slug) > 250) {
                    $rowErrors[] = 'Slug không được vượt quá 250 ký tự';
                }
                
                // 5. Slug trùng lặp
                $existingSlug = $this->brandRepo->findBySlug($slug);
                if ($existingSlug) {
                    $rowErrors[] = "Slug '$slug' đã tồn tại trong hệ thống";
                }

                // Nếu có lỗi
                if (!empty($rowErrors)) {
                    $rowData['result'] = 'failed';
                    $rowData['error'] = implode('; ', $rowErrors);
                    $errors[] = "Dòng $row: " . implode('; ', $rowErrors);
                    $fileData[] = $rowData;
                    continue;
                }

                // Tạo brand
                try {
                    $id = $this->brandRepo->create($name, $slug, $currentUserId);

                    if ($id) {
                        $success++;
                        $rowData['result'] = 'success';
                        $rowData['id'] = $id;
                    } else {
                        $rowData['result'] = 'failed';
                        $rowData['error'] = 'Không thể tạo bản ghi';
                        $errors[] = "Dòng $row: Không thể tạo bản ghi";
                    }
                } catch (\Exception $e) {
                    $rowData['result'] = 'failed';
                    $rowData['error'] = 'Lỗi database: ' . $e->getMessage();
                    $errors[] = "Dòng $row: " . $e->getMessage();
                }
                
                $fileData[] = $rowData;
            }

            $totalRows = count($fileData);
            $failedRows = count($errors);
            
            // Xác định status
            $importStatus = 'success';
            if ($success === 0 && $totalRows > 0) {
                $importStatus = 'failed';
            } elseif ($failedRows > 0 && $success > 0) {
                $importStatus = 'partial';
            } elseif ($totalRows === 0) {
                $importStatus = 'failed';
            }

            // Lưu lịch sử
            $this->saveImportHistory('brands', $fileName, $totalRows, $success, $failedRows, $importStatus, $errors, $fileData);

            // Tạo thông báo
            $message = '';
            if ($importStatus === 'success') {
                $message = "Nhập thành công $success/$totalRows bản ghi";
            } elseif ($importStatus === 'partial') {
                $message = "Nhập thành công $success/$totalRows bản ghi. Có $failedRows lỗi";
                if (!empty($errors)) {
                    $message .= ": " . $errors[0];
                    if (count($errors) > 1) {
                        $message .= " (xem chi tiết trong lịch sử nhập)";
                    }
                }
            } else {
                $message = "Nhập thất bại. Có $failedRows lỗi";
                if (!empty($errors)) {
                    $message .= ": " . $errors[0];
                    if (count($errors) > 1) {
                        $message .= " (xem chi tiết trong lịch sử nhập)";
                    }
                }
            }

            echo json_encode([
                'success' => $success,
                'total' => $totalRows,
                'failed' => $failedRows,
                'status' => $importStatus,
                'errors' => $errors,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Exception $e) {
            // Lưu lỗi hệ thống
            try {
                $this->saveImportHistory('brands', $fileName ?: 'unknown', 0, 0, 0, 'failed',
                    ['Lỗi hệ thống: ' . $e->getMessage()], !empty($fileData) ? $fileData : null);
            } catch (\Exception $saveError) {
                // Ignore
            }
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'message' => 'Đã xảy ra lỗi trong quá trình nhập file'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function saveImportHistory($tableName, $fileName, $totalRows, $successRows, $failedRows, $status, $errors, $fileContent)
    {
        $importHistoryRepo = new \App\Models\Repositories\ImportHistoryRepository();
        return $importHistoryRepo->create([
            'table_name' => $tableName,
            'file_name' => $fileName,
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'status' => $status,
            'error_details' => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'file_content' => $fileContent ? json_encode($fileContent, JSON_UNESCAPED_UNICODE) : null,
            'imported_by' => $this->currentUserId(),
            'imported_by_name' => $this->currentUserName(),
        ]);
    }
}
