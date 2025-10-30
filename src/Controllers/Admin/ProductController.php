<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ProductRepository;

use App\Controllers\Admin\AuthController;
class ProductController extends BaseAdminController
{
    private $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->productRepo = new ProductRepository();
    }
    /** GET /admin/products (trả về view) */
    public function index()
    {
        return $this->view('admin/products/product');
    }

    /** GET /admin/api/products (list) */
    public function apiIndex()
    {
        $items = $this->productRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $id = $this->productRepo->create($data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi tạo sản phẩm'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/products/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $this->productRepo->update($id, $data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi cập nhật sản phẩm'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/products/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->productRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // findOne now in ProductRepository

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-') ?: uniqid('sp-');
    }

    /** GET /admin/api/products/all-including-inactive - Danh sách tất cả sản phẩm (cho quà tặng) */
    public function apiAllProducts()
    {
        $items = $this->productRepo->allIncludingInactive();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/products/stock-list - Danh sách sản phẩm với tồn kho cho kiểm kê */
    public function apiStockList()
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT 
                    p.id, 
                    p.name,
                    COALESCE(SUM(pb.current_qty), 0) AS stock_quantity
                FROM products p
                LEFT JOIN product_batches pb ON pb.product_id = p.id
                WHERE p.is_active = 1
                GROUP BY p.id, p.name
                ORDER BY p.name ASC";
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['products' => $products], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products/upload-images - Upload ảnh sản phẩm */
    public function uploadImages()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!isset($_POST['product_id'])) {
                throw new \Exception('Thiếu product_id');
            }
            
            $productId = (int)$_POST['product_id'];
            $uploadDir = __DIR__ . '/../../../public/assets/images/products/' . $productId;
            
            // Tạo thư mục nếu chưa có
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadedFiles = [];
            
            // Upload ảnh chính (1.png)
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $mainImage = $_FILES['main_image'];
                $ext = strtolower(pathinfo($mainImage['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    throw new \Exception('Ảnh chính chỉ chấp nhận PNG, JPG, JPEG');
                }
                
                if ($mainImage['size'] > 2 * 1024 * 1024) {
                    throw new \Exception('Ảnh chính không được vượt quá 2MB');
                }
                
                $mainPath = $uploadDir . '/1.png';
                
                // Convert to PNG if needed
                $this->convertAndSaveImage($mainImage['tmp_name'], $mainPath, $ext);
                $uploadedFiles[] = '1.png';
            }
            
            // Upload ảnh phụ (2.png, 3.png, ...)
            if (isset($_FILES['sub_images']) && is_array($_FILES['sub_images']['name'])) {
                $subImages = $_FILES['sub_images'];
                $count = count($subImages['name']);
                
                for ($i = 0; $i < $count && $i < 5; $i++) {
                    if ($subImages['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($subImages['name'][$i], PATHINFO_EXTENSION));
                        
                        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                            continue;
                        }
                        
                        if ($subImages['size'][$i] > 2 * 1024 * 1024) {
                            continue;
                        }
                        
                        $subPath = $uploadDir . '/' . ($i + 2) . '.png';
                        $this->convertAndSaveImage($subImages['tmp_name'][$i], $subPath, $ext);
                        $uploadedFiles[] = ($i + 2) . '.png';
                    }
                }
            }
            
            // Lưu thông tin ảnh vào database
            $pdo = \App\Core\DB::pdo();
            $currentUser = $_SESSION['user']['id'] ?? null;
            
            $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            foreach ($uploadedFiles as $idx => $filename) {
                $stmt = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary, created_by, updated_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $productId,
                    "/assets/images/products/{$productId}/{$filename}",
                    $idx === 0 ? 1 : 0,
                    $currentUser,
                    $currentUser
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'uploaded' => $uploadedFiles,
                'message' => 'Upload ảnh thành công'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function convertAndSaveImage($sourcePath, $destPath, $sourceExt)
    {
        // Load image based on type
        switch ($sourceExt) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                throw new \Exception('Định dạng ảnh không được hỗ trợ');
        }
        
        if (!$image) {
            throw new \Exception('Không thể đọc file ảnh');
        }
        
        // Convert to PNG and save
        imagepng($image, $destPath, 9);
        imagedestroy($image);
    }

    /** POST /admin/api/products/export - Xuất Excel */
    public function export()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $products = $data['products'] ?? [];
        
        if (empty($products)) {
            http_response_code(400);
            echo json_encode(['error' => 'Không có dữ liệu để xuất']);
            exit;
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $exportDate = $data['export_date'] ?? date('d/m/Y');
        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', "Ngày xuất: $exportDate");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A3', 'DANH SÁCH SẢN PHẨM');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Column headers
        $headers = ['STT', 'SKU', 'Tên sản phẩm', 'Loại sản phẩm', 'Thương hiệu', 'Giá bán', 'Giá nhập', 'Tồn kho', 'Trạng thái', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }
        $sheet->getStyle('A5:M5')->getFont()->setBold(true);
        $sheet->getStyle('A5:M5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');
        
        // Data
        $row = 6;
        foreach ($products as $index => $product) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $product['sku'] ?? '');
            $sheet->setCellValue('C' . $row, $product['name'] ?? '');
            $sheet->setCellValue('D' . $row, $product['category_name'] ?? '');
            $sheet->setCellValue('E' . $row, $product['brand_name'] ?? '');
            $sheet->setCellValue('F' . $row, $product['sale_price'] ?? 0);
            $sheet->setCellValue('G' . $row, $product['cost_price'] ?? 0);
            $sheet->setCellValue('H' . $row, $product['stock_qty'] ?? 0);
            $sheet->setCellValue('I' . $row, $product['is_active'] ? 'Hoạt động' : 'Ngừng');
            $sheet->setCellValue('J' . $row, $product['created_at'] ?? '');
            $sheet->setCellValue('K' . $row, $product['created_by_name'] ?? '');
            $sheet->setCellValue('L' . $row, $product['updated_at'] ?? '');
            $sheet->setCellValue('M' . $row, $product['updated_by_name'] ?? '');

            $row++;
        }
        
        // Number format for prices and stock
        $lastRow = $row - 1;
        $sheet->getStyle("F6:H$lastRow")->getNumberFormat()
            ->setFormatCode('#,##0');
        
        // Borders
        $sheet->getStyle("A5:M$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . ($data['filename'] ?? 'San_pham.xlsx') . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** GET /admin/api/products/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // ========== SHEET 1: Mẫu nhập sản phẩm ==========
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu nhập');
        
        // Header row
        $headers = [
            'STT',
            ['text' => 'Tên sản phẩm ', 'required' => true],
            ['text' => 'Slug ', 'required' => true, 'note' => '(VD: coca-cola-330ml)'],
            ['text' => 'ID Loại sản phẩm', 'note' => '(xem sheet Loại sản phẩm)'],
            ['text' => 'ID Thương hiệu', 'note' => '(xem sheet Thương hiệu)'],
            ['text' => 'ID Đơn vị', 'note' => '(xem sheet Đơn vị)'],
            'Quy cách',
            'Giá bán',
            'Giá nhập',
            'Trạng thái'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            if (is_array($header)) {
                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $richText->createText($header['text']);
                if ($header['required'] ?? false) {
                    $red = $richText->createTextRun('*');
                    $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
                }
                if ($header['note'] ?? false) {
                    $note = $richText->createTextRun("\n" . $header['note']);
                    $note->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('666666');
                }
                $sheet->setCellValue($col . '1', $richText);
            } else {
                $sheet->setCellValue($col . '1', $header);
            }
            $col++;
        }
        
        // Style header
        $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:J1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(40);
        
        // Sample data (sẽ để trống để user tự điền, hoặc có 1 dòng mẫu)
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'Coca Cola 330ml');
        $sheet->setCellValue('C2', 'coca-cola-330ml');
        $sheet->setCellValue('D2', '1');
        $sheet->setCellValue('E2', '1');
        $sheet->setCellValue('F2', '1');
        $sheet->setCellValue('G2', 'Lon 330ml');
        $sheet->setCellValue('H2', 10000);
        $sheet->setCellValue('I2', 8000);
        $sheet->setCellValue('J2', 'Hoạt động');
        
        // Borders
        $sheet->getStyle('A1:J2')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Auto-size columns
        foreach (range('A', 'J') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
        
        // ========== SHEET 2: Danh sách Loại sản phẩm ==========
        $categorySheet = $spreadsheet->createSheet();
        $categorySheet->setTitle('Loại sản phẩm');
        
        // Get categories from database
        $pdo = \App\Core\DB::pdo();
        $categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        
        // Headers
        $categorySheet->setCellValue('A1', 'ID');
        $categorySheet->setCellValue('B1', 'Tên loại sản phẩm');
        $categorySheet->setCellValue('C1', 'Slug');
        $categorySheet->getStyle('A1:C1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $categorySheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');
        
        // Data
        $row = 2;
        foreach ($categories as $category) {
            $categorySheet->setCellValue('A' . $row, $category['id']);
            $categorySheet->setCellValue('B' . $row, $category['name']);
            $categorySheet->setCellValue('C' . $row, $category['slug']);
            $row++;
        }
        
        // Style
        $categorySheet->getStyle('A1:C' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'C') as $c) {
            $categorySheet->getColumnDimension($c)->setAutoSize(true);
        }
        
        // ========== SHEET 3: Danh sách Thương hiệu ==========
        $brandSheet = $spreadsheet->createSheet();
        $brandSheet->setTitle('Thương hiệu');
        
        // Get brands from database
        $pdo = \App\Core\DB::pdo();
        $brands = $pdo->query("SELECT id, name, slug FROM brands ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        
        // Headers
        $brandSheet->setCellValue('A1', 'ID');
        $brandSheet->setCellValue('B1', 'Tên thương hiệu');
        $brandSheet->setCellValue('C1', 'Slug');
        $brandSheet->getStyle('A1:C1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $brandSheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF9800');
        
        // Data
        $row = 2;
        foreach ($brands as $brand) {
            $brandSheet->setCellValue('A' . $row, $brand['id']);
            $brandSheet->setCellValue('B' . $row, $brand['name']);
            $brandSheet->setCellValue('C' . $row, $brand['slug']);
            $row++;
        }
        
        // Style
        $brandSheet->getStyle('A1:C' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'C') as $c) {
            $brandSheet->getColumnDimension($c)->setAutoSize(true);
        }
        
        // ========== SHEET 4: Danh sách Đơn vị tính ==========
        $unitSheet = $spreadsheet->createSheet();
        $unitSheet->setTitle('Đơn vị tính');
        
        // Get units from database
        $units = $pdo->query("SELECT id, name, slug FROM units ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        
        // Headers
        $unitSheet->setCellValue('A1', 'ID');
        $unitSheet->setCellValue('B1', 'Tên đơn vị');
        $unitSheet->setCellValue('C1', 'Slug');
        $unitSheet->getStyle('A1:C1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $unitSheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2196F3');
        
        // Data
        $row = 2;
        foreach ($units as $unit) {
            $unitSheet->setCellValue('A' . $row, $unit['id']);
            $unitSheet->setCellValue('B' . $row, $unit['name']);
            $unitSheet->setCellValue('C' . $row, $unit['slug']);
            $row++;
        }
        
        // Style
        $unitSheet->getStyle('A1:C' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'C') as $c) {
            $unitSheet->getColumnDimension($c)->setAutoSize(true);
        }
        
        // Set active sheet back to first sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_nhap_san_pham.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/products/import - Nhập Excel */
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
                        $errorMsg = 'File vượt quá kích thước cho phép';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg = 'Không có file được chọn';
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
                    'error' => 'File có quá nhiều dòng (tối đa 10,000). Số dòng hiện tại: ' . (count($data) - 1)
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

            // Get repositories
            $unitRepo = new \App\Models\Repositories\UnitRepository();
            $brandRepo = new \App\Models\Repositories\BrandRepository();
            $categoryRepo = new \App\Models\Repositories\CategoryRepository();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 vì bỏ header và index từ 0
                
                $name = trim($row[1] ?? '');
                
                // Skip dòng trống
                if ($name === '') continue;

                $slug = trim($row[2] ?? '');
                $categoryId = $row[3] ?? null;
                $brandId = $row[4] ?? null;
                $unitId = $row[5] ?? null;
                $packSize = trim($row[6] ?? '');
                $salePrice = $row[7] ?? 0;
                $costPrice = $row[8] ?? 0;
                $status = trim($row[9] ?? '');

                // Convert to integer or null
                $categoryId = is_numeric($categoryId) ? (int)$categoryId : null;
                $brandId = is_numeric($brandId) ? (int)$brandId : null;
                $unitId = is_numeric($unitId) ? (int)$unitId : null;

                // Auto-generate SKU và Barcode (đảm bảo unique)
                $sku = 'SP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -3));
                // Generate barcode EAN-13 format (893 prefix + timestamp + random)
                $barcode = '893' . date('ymdHis') . rand(100, 999);
                $barcode = substr($barcode, 0, 13); // Ensure 13 digits

                // Auto-generate slug if empty
                if (empty($slug)) {
                    $slug = $this->slugify($name);
                }

                $rowData = [
                    'row' => $rowNumber,
                    'name' => $name,
                    'slug' => $slug,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,
                    'pack_size' => $packSize,
                    'sale_price' => $salePrice,
                    'cost_price' => $costPrice,
                    'status' => $status
                ];

                // Validate
                $rowErrors = [];

                // 1. Tên bắt buộc
                if ($name === '') {
                    $rowErrors[] = 'Tên sản phẩm là bắt buộc';
                }

                // 2. Độ dài tên
                if (mb_strlen($name) > 250) {
                    $rowErrors[] = 'Tên không được vượt quá 250 ký tự';
                }

                // 3. Slug bắt buộc
                if ($slug === '') {
                    $rowErrors[] = 'Slug là bắt buộc';
                }

                // 4. Độ dài slug
                if (mb_strlen($slug) > 250) {
                    $rowErrors[] = 'Slug không được vượt quá 250 ký tự';
                }

                // 5. Category ID (nếu có) phải tồn tại
                if ($categoryId !== null) {
                    $category = $categoryRepo->find($categoryId);
                    if (!$category) {
                        $rowErrors[] = "ID Loại sản phẩm $categoryId không tồn tại";
                    }
                }

                // 6. Brand ID (nếu có) phải tồn tại
                if ($brandId !== null) {
                    $brand = $brandRepo->find($brandId);
                    if (!$brand) {
                        $rowErrors[] = "ID Thương hiệu $brandId không tồn tại";
                    }
                }

                // 7. Unit ID (nếu có) phải tồn tại
                if ($unitId !== null) {
                    $unit = $unitRepo->findOne($unitId);
                    if (!$unit) {
                        $rowErrors[] = "ID Đơn vị $unitId không tồn tại";
                    }
                }

                // 8. Độ dài pack_size
                if (mb_strlen($packSize) > 100) {
                    $rowErrors[] = 'Quy cách không được vượt quá 100 ký tự';
                }

                // 9. Sale price phải là số và >= 0
                if (!is_numeric($salePrice) || $salePrice < 0) {
                    $rowErrors[] = 'Giá bán phải là số và >= 0';
                }

                // 10. Cost price phải là số và >= 0
                if (!is_numeric($costPrice) || $costPrice < 0) {
                    $rowErrors[] = 'Giá nhập phải là số và >= 0';
                }

                // 11. Status (nếu có) phải là "Hoạt động" hoặc "Ngừng"
                $isActive = 1; // Default
                if ($status !== '') {
                    if (in_array(mb_strtolower($status), ['hoạt động', 'active', '1'])) {
                        $isActive = 1;
                    } elseif (in_array(mb_strtolower($status), ['ngừng', 'inactive', '0'])) {
                        $isActive = 0;
                    } else {
                        $rowErrors[] = 'Trạng thái phải là "Hoạt động" hoặc "Ngừng"';
                    }
                }

                // Nếu có lỗi
                if (!empty($rowErrors)) {
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = implode('; ', $rowErrors);
                    $errors[] = "Dòng $rowNumber: " . implode('; ', $rowErrors);
                    $fileContent[] = $rowData;
                    $failedCount++;
                    continue;
                }

                // Tạo product
                try {
                    $productId = $this->productRepo->create([
                        'name' => $name,
                        'slug' => $slug,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'unit_id' => $unitId,
                        'pack_size' => $packSize ?: null,
                        'sale_price' => $salePrice,
                        'cost_price' => $costPrice,
                        'is_active' => $isActive
                    ], $currentUserId, $slug);

                    $rowData['status'] = 'success';
                    $rowData['id'] = $productId;
                    $fileContent[] = $rowData;
                    $successCount++;
                } catch (\PDOException $e) {
                    // Xử lý lỗi database với thông báo tiếng Việt
                    $errorMessage = 'Lỗi database';
                    
                    // Kiểm tra lỗi duplicate entry (SQLSTATE 23000, Error 1062)
                    if ($e->getCode() == 23000 || (isset($e->errorInfo[0]) && $e->errorInfo[0] == '23000')) {
                        // Phân tích thông báo lỗi để xác định trường bị trùng
                        $originalMessage = $e->getMessage();
                        
                        if (strpos($originalMessage, "for key 'slug'") !== false || strpos($originalMessage, "for key 'products.slug'") !== false) {
                            $errorMessage = "Slug '$slug' đã tồn tại trong hệ thống";
                        } elseif (strpos($originalMessage, "for key 'sku'") !== false || strpos($originalMessage, "for key 'products.sku'") !== false) {
                            $errorMessage = "Mã SKU '$sku' đã tồn tại trong hệ thống";
                        } elseif (strpos($originalMessage, "for key 'barcode'") !== false || strpos($originalMessage, "for key 'products.barcode'") !== false) {
                            $errorMessage = "Mã vạch '$barcode' đã tồn tại trong hệ thống";
                        } else {
                            $errorMessage = "Dữ liệu bị trùng lặp trong hệ thống";
                        }
                    } else {
                        $errorMessage = 'Lỗi database: ' . $e->getMessage();
                    }
                    
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $errorMessage;
                    $errors[] = "Dòng $rowNumber: " . $errorMessage;
                    $fileContent[] = $rowData;
                    $failedCount++;
                } catch (\Exception $e) {
                    $errorMessage = 'Lỗi: ' . $e->getMessage();
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $errorMessage;
                    $errors[] = "Dòng $rowNumber: " . $errorMessage;
                    $fileContent[] = $rowData;
                    $failedCount++;
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
                'products',
                $file['name'],
                $successCount,
                $failedCount,
                $status,
                $errors,
                $fileContent
            );

            // Tạo message
            $message = "Nhập thành công $successCount sản phẩm";
            if ($failedCount > 0) {
                $firstError = !empty($errors) ? $errors[0] : '';
                $message = "Nhập thành công $successCount/" . ($successCount + $failedCount) . " sản phẩm. Lỗi đầu tiên: $firstError";
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
            'error_details' => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'file_content' => !empty($fileContent) ? json_encode($fileContent, JSON_UNESCAPED_UNICODE) : null,
            'imported_by' => $this->currentUserId(),
            'imported_by_name' => $this->currentUserName()
        ]);
    }

    protected function currentUserName(): string
    {
        return $_SESSION['user']['full_name'] ?? 'Unknown';
    }
}
