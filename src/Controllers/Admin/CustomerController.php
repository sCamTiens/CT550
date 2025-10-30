<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\CustomerRepository;

class CustomerController extends BaseAdminController
{
    private CustomerRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new CustomerRepository();
    }

    /** Trang quản lý khách hàng */
    public function index(): string
    {
        return $this->view('admin/customers/customer');
    }

    /** API: danh sách khách hàng */
    public function apiIndex(): void
    {
        try {
            $items = $this->repo->all();
            $this->json(['items' => $items]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải danh sách khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: tạo khách hàng */
    public function store(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = $this->validateInput($payload, true);
        if (!empty($errors)) {
            $this->json(['error' => $errors[0]], 422);
        }

        $payload['created_by'] = $this->currentUserId();
        $payload['updated_by'] = $this->currentUserId();

        try {
            $created = $this->repo->create($payload);

            if ($created === false) {
                $this->json(['error' => 'Không thể tạo khách hàng'], 500);
            }

            if (is_string($created)) {
                $this->json(['error' => $created], 422); // báo chi tiết lỗi
            }

            $this->json($created, 201);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi tạo khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: cập nhật khách hàng */
    public function update($id): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = $this->validateInput($payload, false);
        if (!empty($errors)) {
            $this->json(['error' => $errors[0]], 422);
        }

        $payload['updated_by'] = $this->currentUserId();

        try {
            $updated = $this->repo->update($id, $payload);
            if ($updated === false) {
                $this->json(['error' => 'Không thể cập nhật khách hàng'], 404);
            }
            $this->json($updated);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi cập nhật khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: xoá khách hàng */
    public function destroy($id): void
    {
        try {
            $deleted = $this->repo->delete($id);
            if (!$deleted) {
                $this->json(['error' => 'Không thể xoá khách hàng'], 404);
            }
            $this->json(['ok' => true]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi xoá khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateInput(array $data, bool $isCreate): array
    {
        $errors = [];

        $username = trim($data['username'] ?? '');
        if ($isCreate && $username === '') {
            $errors[] = 'Tài khoản không được để trống';
        }

        $fullName = trim($data['full_name'] ?? '');
        if ($fullName === '') {
            $errors[] = 'Họ tên không được để trống';
        }

        $email = trim($data['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }

        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            $errors[] = 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
        }

        if ($isCreate) {
            $password = trim($data['password'] ?? '');
            if ($password !== '' && strlen($password) < 6) {
                $errors[] = 'Mật khẩu phải ít nhất 6 ký tự';
            }
        }

        return $errors;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * API: Đổi mật khẩu khách hàng
     * PUT /admin/api/customers/{id}/password
     */
    public function changePassword($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $password = trim($data['password'] ?? '');
        if ($password === '' || strlen($password) < 8) {
            return $this->json(['error' => 'Mật khẩu phải ít nhất 8 ký tự'], 422);
        }
        try {
            $ok = $this->repo->changePassword($id, $password);
            if ($ok) {
                $this->json(['ok' => true]);
            } else {
                $this->json(['error' => 'Không thể đổi mật khẩu'], 500);
            }
        } catch (\Throwable $e) {
            $this->json(['error' => 'Lỗi khi đổi mật khẩu', 'detail' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy danh sách địa chỉ của khách hàng
     * GET /admin/api/customers/{id}/addresses
     */
    public function getAddresses($id): void
    {
        try {
            $addresses = $this->repo->getAddresses($id);
            $this->json(['addresses' => $addresses]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải danh sách địa chỉ',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy thông tin chi tiết khách hàng và đơn hàng
     * GET /admin/api/customers/{id}/detail
     */
    public function getDetail($id): void
    {
        try {
            $customer = $this->repo->findById($id);
            if (!$customer) {
                $this->json(['error' => 'Không tìm thấy khách hàng'], 404);
                return;
            }

            $orders = $this->repo->getOrders($id);

            $this->json([
                'customer' => $customer,
                'orders' => $orders
            ]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải thông tin chi tiết',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Lấy chi tiết items của đơn hàng
     * GET /admin/api/orders/{id}/items
     */
    public function getOrderItems($id): void
    {
        try {
            $items = $this->repo->getOrderItems($id);
            $this->json(['items' => $items]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải chi tiết đơn hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** GET /admin/api/customers/template - Download Excel template */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define headers
        $headers = [
            'STT',
            'Họ tên*',
            'Email*',
            'Số điện thoại*',
            'Ngày sinh',
            'Giới tính',
            'Địa chỉ',
            'Điểm tích lũy',
            'Trạng thái*'
        ];

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Sample data rows
        $samples = [
            [
                1,
                'Nguyễn Văn A',
                'nguyenvana@example.com',
                '0901234567',
                '15/05/1990',
                'Nam',
                '123 Đường ABC, Q.1, TP.HCM',
                100,
                1
            ],
            [
                2,
                'Trần Thị B',
                'tranthib@example.com',
                '0907654321',
                '20/08/1995',
                'Nữ',
                '456 Đường XYZ, Q.3, TP.HCM',
                50,
                1
            ]
        ];

        $row = 2;
        foreach ($samples as $sample) {
            $col = 'A';
            foreach ($sample as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_khachhang.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/customers/import - Import Excel file */
    public function importExcel()
    {
        // Check file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Không có file được tải lên hoặc có lỗi xảy ra'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $file = $_FILES['file'];

        // Validate file extension
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Chỉ chấp nhận file Excel (.xls, .xlsx)'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'File không được vượt quá 5MB'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate filename length
        if (strlen($file['name']) > 255) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Tên file quá dài (tối đa 255 ký tự)'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate special characters
        if (preg_match('/[<>:"|?*]/', $file['name'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Tên file chứa ký tự không hợp lệ (< > : " | ? *)'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Remove header row
            array_shift($rows);

            // Check if empty
            if (empty($rows)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'File Excel không có dữ liệu'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Check row count limit
            if (count($rows) > 1000) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'File Excel không được vượt quá 1000 dòng dữ liệu'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $currentUser = $this->currentUserId();
            $errors = [];
            $success = [];
            $rowNumber = 1; // Start from 1 (after header)

            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map columns: STT, Họ tên*, Email*, SĐT*, Ngày sinh, Giới tính, Địa chỉ, Điểm, Trạng thái*
                $full_name = trim($row[1] ?? '');
                $email = trim($row[2] ?? '');
                $phone = trim($row[3] ?? '');
                $date_of_birth = trim($row[4] ?? '');
                $gender = trim($row[5] ?? '');
                $address = trim($row[6] ?? '');
                $loyalty_points = trim($row[7] ?? '');
                $is_active = trim($row[8] ?? '');

                $rowErrors = [];

                // Validate required fields
                if (empty($full_name)) {
                    $rowErrors[] = 'Họ tên không được để trống';
                } elseif (strlen($full_name) < 3) {
                    $rowErrors[] = 'Họ tên phải có ít nhất 3 ký tự';
                }

                // Validate email
                if (empty($email)) {
                    $rowErrors[] = 'Email không được để trống';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Email không hợp lệ';
                } else {
                    // Check if email exists
                    $existingCustomer = $this->repo->findByEmail($email);
                    if ($existingCustomer) {
                        $rowErrors[] = "Email '$email' đã tồn tại";
                    }
                }

                // Validate phone
                if (empty($phone)) {
                    $rowErrors[] = 'Số điện thoại không được để trống';
                } elseif (!preg_match('/^0\d{9}$/', $phone)) {
                    $rowErrors[] = 'Số điện thoại phải bắt đầu bằng 0 và có 10 chữ số';
                } else {
                    // Check if phone exists
                    $existingCustomer = $this->repo->findByPhone($phone);
                    if ($existingCustomer) {
                        $rowErrors[] = "Số điện thoại '$phone' đã tồn tại";
                    }
                }

                // Validate date_of_birth (optional)
                if (!empty($date_of_birth)) {
                    $date_of_birth = $this->convertDateFormat($date_of_birth);
                    if (!$date_of_birth) {
                        $rowErrors[] = 'Ngày sinh không đúng định dạng (dd/mm/yyyy)';
                    }
                }

                // Validate gender (optional)
                if (!empty($gender) && !in_array($gender, ['Nam', 'Nữ'])) {
                    $rowErrors[] = "Giới tính phải là 'Nam' hoặc 'Nữ'";
                }

                // Validate loyalty_points (optional)
                if (!empty($loyalty_points) && (!is_numeric($loyalty_points) || $loyalty_points < 0)) {
                    $rowErrors[] = 'Điểm tích lũy phải là số không âm';
                }

                // Validate is_active
                if (!in_array($is_active, ['0', '1', 0, 1], true)) {
                    $rowErrors[] = "Trạng thái phải là 0 hoặc 1";
                }

                // If errors exist, log and continue
                if (!empty($rowErrors)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'loyalty_points' => $loyalty_points,
                        'is_active' => $is_active,
                        'errors' => implode('; ', $rowErrors)
                    ];
                    continue;
                }

                // Create customer data
                $customerData = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'date_of_birth' => $date_of_birth ?: null,
                    'gender' => $gender ?: null,
                    'address' => $address ?: null,
                    'loyalty_points' => !empty($loyalty_points) ? (int)$loyalty_points : 0,
                    'is_active' => (int)$is_active,
                    'created_by' => $currentUser
                ];

                try {
                    $this->repo->create($customerData);
                    $success[] = [
                        'row' => $rowNumber,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'loyalty_points' => $loyalty_points ?: 0,
                        'is_active' => $is_active
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'loyalty_points' => $loyalty_points,
                        'is_active' => $is_active,
                        'errors' => $e->getMessage()
                    ];
                }
            }

            // Save import history
            $this->saveImportHistory($file['name'], count($success), count($errors), $success, $errors);

            // Response
            $message = "Nhập thành công " . count($success) . " khách hàng";
            if (count($errors) > 0) {
                $message .= ", " . count($errors) . " lỗi";
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'summary' => [
                    'total' => count($rows),
                    'success' => count($success),
                    'errors' => count($errors)
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi xử lý file Excel: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Convert date format from dd/mm/yyyy to yyyy-mm-dd
     */
    private function convertDateFormat($dateStr)
    {
        // Try to parse dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }
        return false;
    }

    /**
     * Save import history to database
     */
    private function saveImportHistory($filename, $successCount, $errorCount, $successRows, $errorRows)
    {
        $importHistoryRepo = new \App\Models\Repositories\ImportHistoryRepository();
        
        $importHistoryRepo->create([
            'module' => 'customers',
            'filename' => $filename,
            'total_rows' => $successCount + $errorCount,
            'success_rows' => $successCount,
            'error_rows' => $errorCount,
            'success_data' => json_encode($successRows, JSON_UNESCAPED_UNICODE),
            'error_data' => json_encode($errorRows, JSON_UNESCAPED_UNICODE),
            'imported_by' => $this->currentUserId(),
            'imported_by_name' => $this->currentUserName()
        ]);
    }

    /**
     * Get current user name from session
     */
    protected function currentUserName()
    {
        return $_SESSION['admin_user']['full_name'] ?? $_SESSION['user']['full_name'] ?? 'Unknown';
    }

    /** POST /admin/api/customers/export - Xuất Excel */
    public function export()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $items = $data['items'] ?? [];

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Không có dữ liệu để xuất']);
            exit;
        }

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

        // Header
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH KHÁCH HÀNG');
        $sheet->mergeCells('A5:L5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Column headers
        $headers = ['STT', 'Tài khoản', 'Họ tên', 'Email', 'Số điện thoại', 'Giới tính', 'Ngày sinh', 'Trạng thái', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $col++;
        }
        $sheet->getStyle('A6:L6')->getFont()->setBold(true);
        $sheet->getStyle('A6:L6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');

        // Data
        $row = 7;
        foreach ($items as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['username'] ?? '');
            $sheet->setCellValue('C' . $row, $item['full_name'] ?? '');
            $sheet->setCellValue('D' . $row, $item['email'] ?? '');
            $sheet->setCellValue('E' . $row, $item['phone'] ?? '');
            $sheet->setCellValue('F' . $row, ($item['gender'] ?? 0) ? 'Nam' : 'Nữ');
            $sheet->setCellValue('G' . $row, $item['date_of_birth'] ?? '');
            $sheet->setCellValue('H' . $row, ($item['is_active'] ?? 0) ? 'Hoạt động' : 'Khóa');
            $sheet->setCellValue('I' . $row, $item['created_at'] ?? '');
            $sheet->setCellValue('J' . $row, $item['created_by_name'] ?? '');
            $sheet->setCellValue('K' . $row, $item['updated_at'] ?? '');
            $sheet->setCellValue('L' . $row, $item['updated_by_name'] ?? '');
            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        $sheet->getStyle("A6:L$lastRow")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . ($data['filename'] ?? 'Export.xlsx') . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

}
