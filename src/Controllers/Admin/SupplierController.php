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

    /** GET /admin/api/suppliers/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Tên nhà cung cấp');
        $sheet->setCellValue('C1', 'Số điện thoại');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Địa chỉ');

        // Định dạng header
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');

        // Đánh dấu màu đỏ cho trường bắt buộc
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richText->createText('Tên nhà cung cấp ');
        $red = $richText->createTextRun('*');
        $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        $sheet->getCell('B1')->setValue($richText);

        // Border cho header
        $sheet->getStyle('A1:E1')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Dữ liệu mẫu
        $sampleData = [
            [1, 'Công ty TNHH ABC', '0901234567', 'abc@example.com', '123 Đường ABC, Q.1, TP.HCM'],
            [2, 'Công ty CP XYZ', '0912345678', 'xyz@example.com', '456 Đường XYZ, Q.2, TP.HCM'],
            [3, 'Nhà cung cấp DEF', '0923456789', 'def@example.com', '789 Đường DEF, Q.3, TP.HCM']
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->setCellValue('C' . $row, $data[2]);
            $sheet->setCellValue('D' . $row, $data[3]);
            $sheet->setCellValue('E' . $row, $data[4]);
            $row++;
        }

        // Border cho data
        $lastRow = $row - 1;
        $sheet->getStyle("A1:E$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_nha_cung_cap.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/suppliers/import - Nhập Excel */
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

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 vì bỏ header và index từ 0
                
                $name = trim($row[1] ?? '');
                
                // Skip dòng trống
                if ($name === '') continue;

                $phone = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $address = trim($row[4] ?? '');

                $rowData = [
                    'row' => $rowNumber,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address
                ];

                // Validate
                $rowErrors = [];

                // 1. Tên bắt buộc
                if ($name === '') {
                    $rowErrors[] = 'Tên nhà cung cấp là bắt buộc';
                }

                // 2. Độ dài tên
                if (mb_strlen($name) > 250) {
                    $rowErrors[] = 'Tên không được vượt quá 250 ký tự';
                }

                // 3. Số điện thoại (nếu có)
                if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
                    $rowErrors[] = 'Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số';
                }

                // 4. Email (nếu có)
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Email không hợp lệ';
                }

                // 5. Độ dài email
                if (mb_strlen($email) > 250) {
                    $rowErrors[] = 'Email không được vượt quá 250 ký tự';
                }

                // 6. Độ dài địa chỉ
                if (mb_strlen($address) > 255) {
                    $rowErrors[] = 'Địa chỉ không được vượt quá 255 ký tự';
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

                // Tạo supplier
                try {
                    $supplierId = $this->supplierRepo->create([
                        'name' => $name,
                        'phone' => $phone ?: null,
                        'email' => $email ?: null,
                        'address' => $address ?: null
                    ], $currentUserId);

                    $rowData['status'] = 'success';
                    $rowData['id'] = $supplierId;
                    $fileContent[] = $rowData;
                    $successCount++;
                } catch (\Exception $e) {
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $e->getMessage();
                    $errors[] = "Dòng $rowNumber: " . $e->getMessage();
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
                'suppliers',
                $file['name'],
                $successCount,
                $failedCount,
                $status,
                $errors,
                $fileContent
            );

            // Tạo message
            $message = "Nhập thành công $successCount nhà cung cấp";
            if ($failedCount > 0) {
                $firstError = !empty($errors) ? $errors[0] : '';
                $message = "Nhập thành công $successCount/$successCount + $failedCount) nhà cung cấp. Lỗi đầu tiên: $firstError";
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

    protected function currentUserName(): ?string
    {
        return $_SESSION['user']['full_name'] ?? null;
    }

    // ========== CÔNG NỢ NHÀ CUNG CẤP ==========

    /** GET /admin/supplier-debts */
    public function debtsIndex()
    {
        return $this->view('admin/supplier-debts/index');
    }

    /** GET /admin/api/supplier-debts/suppliers - Lấy danh sách NCC có công nợ > 0 */
    public function apiGetSuppliersWithDebt()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = \App\Core\DB::pdo();
            $sql = "
                SELECT 
                    s.id,
                    s.name,
                    s.phone,
                    s.email,
                    s.address,
                    COALESCE(SUM(po.total_amount - po.paid_amount), 0) as total_debt,
                    COUNT(DISTINCT po.id) as debt_orders_count
                FROM suppliers s
                LEFT JOIN purchase_orders po ON s.id = po.supplier_id 
                    AND po.paid_amount < po.total_amount
                GROUP BY s.id, s.name, s.phone, s.email, s.address
                HAVING total_debt > 0
                ORDER BY total_debt DESC
            ";

            $stmt = $pdo->query($sql);
            $suppliers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** GET /admin/api/supplier-debts/orders?id={id} - Lấy danh sách phiếu nhập còn nợ */
    public function apiGetDebtOrders()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $supplierId = $_GET['id'] ?? null;
        
        // Debug: kiểm tra parameter
        if (empty($supplierId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu supplier_id'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            $pdo = \App\Core\DB::pdo();
            $sql = "
                SELECT 
                    po.id,
                    po.code as order_code,
                    po.created_at as order_date,
                    po.total_amount,
                    po.paid_amount,
                    (po.total_amount - po.paid_amount) as remaining_debt,
                    po.payment_status,
                    po.note as notes,
                    s.name as supplier_name,
                    u.full_name as created_by_name
                FROM purchase_orders po
                INNER JOIN suppliers s ON po.supplier_id = s.id
                LEFT JOIN users u ON po.created_by = u.id
                WHERE po.supplier_id = :supplier_id
                    AND po.paid_amount < po.total_amount
                ORDER BY po.created_at DESC, po.id DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['supplier_id' => $supplierId]);
            $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $orders
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** GET /admin/supplier-debts/detail/{id} - Trang chi tiết công nợ */
    public function debtDetail($supplierId)
    {
        try {
            $pdo = \App\Core\DB::pdo();
            
            // Lấy thông tin nhà cung cấp
            $sql = "SELECT * FROM suppliers WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $supplierId]);
            $supplier = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$supplier) {
                $_SESSION['error'] = 'Không tìm thấy nhà cung cấp!';
                header('Location: /admin/supplier-debts');
                exit;
            }

            // Tính tổng công nợ
            $sql = "
                SELECT 
                    COALESCE(SUM(total_amount - paid_amount), 0) as total_debt
                FROM purchase_orders
                WHERE supplier_id = :supplier_id
                    AND paid_amount < total_amount
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['supplier_id' => $supplierId]);
            $debtInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $this->view('admin/supplier-debts/detail', [
                'supplier' => $supplier,
                'totalDebt' => $debtInfo['total_debt']
            ]);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: /admin/supplier-debts');
            exit;
        }
    }
}
