<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\CategoryRepository;
use App\Models\Repositories\ImportHistoryRepository;

class CategoryController extends BaseAdminController
{
    private $categoryRepo;
    private $importHistoryRepo;

    public function __construct()
    {
        parent::__construct();
        $this->categoryRepo = new CategoryRepository();
        $this->importHistoryRepo = new ImportHistoryRepository();
    }
    /** GET /admin/categories (view) */
    public function index()
    {
        return $this->view('admin/categories/category');
    }

    /** GET /admin/api/categories (list JSON) */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        $rows = $this->categoryRepo->all();
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/categories (create) */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $parent_id = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $sort_order = (int) ($data['sort_order'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;
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
        if ($slug !== null && mb_strlen($slug) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 250 ký tự']);
            exit;
        }

        try {
            $id = $this->categoryRepo->create([
                'name' => $name,
                'slug' => $slug ?: null,
                'parent_id' => $parent_id,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'created_by' => $currentUserId,
                'updated_by' => $currentUserId,
            ]);
            $row = $this->categoryRepo->find($id);
            $row['created_by_name'] = $currentUserName;
            $row['updated_by_name'] = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo loại sản phẩm']);
            exit;
        }
    }

    /** PUT /admin/categories/{id} (update) */
    public function update($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $parent_id = array_key_exists('parent_id', $data) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $sort_order = (int) ($data['sort_order'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;
        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ((string) $parent_id === (string) $id) {
            http_response_code(422);
            echo json_encode(['error' => 'Loại cha không thể là chính nó']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $this->categoryRepo->update($id, [
                'name' => $name,
                'slug' => $slug ?: null,
                'parent_id' => $parent_id,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'updated_by' => $currentUserId,
            ]);
            $row = $this->categoryRepo->find($id);
            $row['updated_by_name'] = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật loại sản phẩm']);
            exit;
        }
    }

    /** DELETE /admin/categories/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $canDelete = $this->categoryRepo->canDelete($id);
        if ($canDelete === 'parent') {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xoá: đang là loại cha của mục khác']);
            exit;
        }
        if ($canDelete === 'product') {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xoá: loại sản phẩm đang ràng buộc với sản phẩm']);
            exit;
        }
        try {
            $this->categoryRepo->delete($id);
            echo json_encode(['ok' => true]);
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

    /** Helper: lấy 1 bản ghi */
    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.is_active, 
                                    c.created_at, c.updated_at,
                                    c.created_by, cu.full_name AS created_by_name,
                                    c.updated_by, uu.full_name AS updated_by_name
                             FROM categories c
                             LEFT JOIN users cu ON cu.id = c.created_by
                             LEFT JOIN users uu ON uu.id = c.updated_by
                             WHERE c.id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

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
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A3', 'DANH SÁCH LOẠI SẢN PHẨM');
        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Tên loại', 'Slug', 'Loại cha', 'Trạng thái', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);
        $sheet->getStyle('A5:I5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 6;
        $stt = 1;
        foreach ($items as $c) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $c['name'] ?? '');
            $sheet->setCellValue('C' . $row, $c['slug'] ?? '');
            $sheet->setCellValue('D' . $row, $c['parent'] ?? '');
            $sheet->setCellValue('E' . $row, $c['is_active'] ?? '');
            $sheet->setCellValue('F' . $row, $c['created_at'] ?? '');
            $sheet->setCellValue('G' . $row, $c['created_by_name'] ?? '');
            $sheet->setCellValue('H' . $row, $c['updated_at'] ?? '');
            $sheet->setCellValue('I' . $row, $c['updated_by_name'] ?? '');
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
        $sheet->getStyle('A5:I' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Loai_san_pham.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /** GET /admin/api/categories/template - Download file mẫu Excel */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Tên');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'Cấp cha');
        $sheet->setCellValue('E1', 'Trạng thái');

        // Định dạng header
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');

        // Đánh dấu màu đỏ cho dấu * (trường bắt buộc)
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richText->createText('Tên ');
        $red = $richText->createTextRun('*');
        $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        $sheet->getCell('B1')->setValue($richText);

        // Border cho header
        $sheet->getStyle('A1:E1')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Dữ liệu mẫu
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Thực phẩm tươi sống');
        $sheet->setCellValue('C2', 'thuc-pham-tuoi-song');
        $sheet->setCellValue('D2', '');
        $sheet->setCellValue('E2', 'Hiển thị');

        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Rau củ quả');
        $sheet->setCellValue('C3', 'rau-cu-qua');
        $sheet->setCellValue('D3', 'Thực phẩm tươi sống');
        $sheet->setCellValue('E3', 'Hiển thị');

        $sheet->setCellValue('A4', '3');
        $sheet->setCellValue('B4', 'Thịt, cá, hải sản');
        $sheet->setCellValue('C4', 'thit-ca-hai-san');
        $sheet->setCellValue('D4', 'Thực phẩm tươi sống');
        $sheet->setCellValue('E4', 'Hiển thị');

        // Border cho data
        $sheet->getStyle('A2:E4')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'E') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Mau_loai_san_pham.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/categories/import - Nhập dữ liệu từ Excel */
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

                // Kiểm tra lỗi cụ thể
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
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg = 'Thiếu thư mục tạm để lưu file';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg = 'Không thể ghi file vào ổ đĩa';
                            break;
                    }
                }

                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => 'unknown',
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode([$errorMsg], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => $errorMsg], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $fileName = $_FILES['file']['name'];
            $fileSize = $_FILES['file']['size'];
            $file = $_FILES['file']['tmp_name'];

            // 2. Kiểm tra kích thước file (10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if ($fileSize > $maxSize) {
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => $fileName,
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['File vượt quá kích thước cho phép (tối đa 10MB). Kích thước file: ' . round($fileSize / 1024 / 1024, 2) . 'MB'], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => 'File vượt quá kích thước cho phép (tối đa 10MB)'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 3. Kiểm tra độ dài tên file
            if (mb_strlen($fileName) > 255) {
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => mb_substr($fileName, 0, 255),
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['Tên file quá dài (tối đa 255 ký tự). Độ dài hiện tại: ' . mb_strlen($fileName) . ' ký tự'], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => 'Tên file quá dài (tối đa 255 ký tự)'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 4. Kiểm tra ký tự đặc biệt trong tên file
            $cleanFileName = preg_replace('/[^a-zA-Z0-9._\-\s()\[\]]/u', '', pathinfo($fileName, PATHINFO_FILENAME));
            $originalFileName = pathinfo($fileName, PATHINFO_FILENAME);
            if ($cleanFileName !== $originalFileName) {
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => $fileName,
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['Tên file chứa ký tự đặc biệt không hợp lệ. Vui lòng chỉ sử dụng chữ cái, số, dấu gạch ngang, gạch dưới và khoảng trắng'], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => 'Tên file chứa ký tự đặc biệt không hợp lệ'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 5. Kiểm tra định dạng file
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => $fileName,
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['File không đúng định dạng. Chỉ chấp nhận file .xls hoặc .xlsx'], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => 'File không đúng định dạng. Chỉ chấp nhận file .xls hoặc .xlsx'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // 6. Kiểm tra số lượng dòng (giới hạn để tránh file quá lớn)
            $maxRows = 10000; // Tối đa 10,000 dòng
            if ($highestRow > $maxRows + 1) { // +1 vì header ở dòng 1
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => $fileName,
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['File có quá nhiều dòng (tối đa ' . number_format($maxRows) . ' dòng). Số dòng hiện tại: ' . number_format($highestRow - 1)], JSON_UNESCAPED_UNICODE),
                    'file_content' => null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);

                http_response_code(400);
                echo json_encode(['error' => 'File có quá nhiều dòng (tối đa ' . number_format($maxRows) . ' dòng)'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Bắt đầu từ dòng 2 (sau header dòng 1)
            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($sheet->getCell('B' . $row)->getValue() ?? '');

                // Skip hàng trống hoàn toàn
                if ($name === '')
                    continue;

                $slug = trim($sheet->getCell('C' . $row)->getValue() ?? '');
                $parentName = trim($sheet->getCell('D' . $row)->getValue() ?? '');
                $status = trim($sheet->getCell('E' . $row)->getValue() ?? 'Hiển thị');

                // Lưu dữ liệu hàng
                $rowData = [
                    'row' => $row,
                    'name' => $name,
                    'slug' => $slug,
                    'parent_name' => $parentName,
                    'status' => $status,
                    'result' => 'pending',
                    'error' => ''
                ];

                // ===== VALIDATE DỮ LIỆU =====
                $rowErrors = [];

                // 1. Kiểm tra tên (bắt buộc)
                if ($name === '') {
                    $rowErrors[] = 'Tên loại sản phẩm không được bỏ trống';
                }

                // 2. Kiểm tra độ dài tên
                if (mb_strlen($name) > 250) {
                    $rowErrors[] = 'Tên loại sản phẩm không được vượt quá 250 ký tự';
                }

                // 3. Kiểm tra ký tự đặc biệt trong tên
                if (preg_match('/[<>\"\'\\\\]/', $name)) {
                    $rowErrors[] = 'Tên loại sản phẩm chứa ký tự không hợp lệ (< > " \' \\)';
                }

                // Auto-generate slug
                if ($slug === '') {
                    $slug = $this->slugify($name);
                    $rowData['slug'] = $slug;
                }

                // 4. Kiểm tra độ dài slug
                if (mb_strlen($slug) > 250) {
                    $rowErrors[] = 'Slug không được vượt quá 250 ký tự';
                }

                // 5. Kiểm tra slug đã tồn tại
                $existingSlug = $this->categoryRepo->findBySlug($slug);
                if ($existingSlug) {
                    $rowErrors[] = "Slug '$slug' đã tồn tại trong hệ thống";
                }

                // 6. Kiểm tra trạng thái hợp lệ
                $validStatuses = ['hiển thị', 'hien thi', 'ẩn', 'an'];
                if ($status !== '' && !in_array(mb_strtolower($status), $validStatuses)) {
                    $rowErrors[] = "Trạng thái phải là 'Hiển thị' hoặc 'Ẩn'";
                }

                // 7. Tìm parent_id từ tên
                $parent_id = null;
                if ($parentName !== '') {
                    $parent = $this->categoryRepo->findByName($parentName);
                    if ($parent) {
                        $parent_id = $parent['id'];
                    } else {
                        $rowErrors[] = "Không tìm thấy loại cha '$parentName'";
                    }
                }

                // Nếu có lỗi validation, ghi nhận và bỏ qua hàng này
                if (!empty($rowErrors)) {
                    $rowData['result'] = 'failed';
                    $rowData['error'] = implode('; ', $rowErrors);
                    $errors[] = "Dòng $row: " . implode('; ', $rowErrors);
                    $fileData[] = $rowData;
                    continue;
                }

                // Convert status
                $is_active = (in_array(mb_strtolower($status), ['hiển thị', 'hien thi'])) ? 1 : 0;

                // Tạo category
                try {
                    $id = $this->categoryRepo->create([
                        'name' => $name,
                        'slug' => $slug,
                        'parent_id' => $parent_id,
                        'sort_order' => 0,
                        'is_active' => $is_active,
                        'created_by' => $currentUserId,
                        'updated_by' => $currentUserId,
                    ]);

                    if ($id) {
                        $success++;
                        $rowData['result'] = 'success';
                        $rowData['id'] = $id;
                    } else {
                        $rowData['result'] = 'failed';
                        $rowData['error'] = 'Không thể tạo bản ghi trong cơ sở dữ liệu';
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

            // Lưu lịch sử nhập file
            $this->importHistoryRepo->create([
                'table_name' => 'categories',
                'file_name' => $fileName,
                'total_rows' => $totalRows,
                'success_rows' => $success,
                'failed_rows' => $failedRows,
                'status' => $importStatus,
                'error_details' => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
                'file_content' => json_encode($fileData, JSON_UNESCAPED_UNICODE),
                'imported_by' => $currentUserId,
                'imported_by_name' => $currentUserName,
            ]);

            // Tạo thông báo kết quả chi tiết - chỉ hiển thị 1 lỗi đầu tiên
            $message = '';
            if ($importStatus === 'success') {
                $message = "Nhập thành công $success/$totalRows bản ghi";
            } elseif ($importStatus === 'partial') {
                $message = "Nhập thành công $success/$totalRows bản ghi. Có $failedRows lỗi";
                if (!empty($errors)) {
                    // Chỉ hiển thị lỗi đầu tiên
                    $message .= ": " . $errors[0];
                    if (count($errors) > 1) {
                        $message .= " (xem chi tiết trong lịch sử nhập)";
                    }
                }
            } else {
                $message = "Nhập thất bại. Có $failedRows lỗi";
                if (!empty($errors)) {
                    // Chỉ hiển thị lỗi đầu tiên
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
            // Lưu lỗi hệ thống vào database
            try {
                $this->importHistoryRepo->create([
                    'table_name' => 'categories',
                    'file_name' => $fileName ?: 'unknown',
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0,
                    'status' => 'failed',
                    'error_details' => json_encode(['Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE),
                    'file_content' => !empty($fileData) ? json_encode($fileData, JSON_UNESCAPED_UNICODE) : null,
                    'imported_by' => $currentUserId,
                    'imported_by_name' => $currentUserName,
                ]);
            } catch (\Exception $saveError) {
                // Nếu không lưu được vào database thì bỏ qua
            }

            http_response_code(500);
            echo json_encode([
                'error' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'message' => 'Đã xảy ra lỗi trong quá trình nhập file'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

}
