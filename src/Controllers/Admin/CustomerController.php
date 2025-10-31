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

    /**
     * API: Lấy lịch sử giao dịch điểm của khách hàng
     * GET /admin/api/customers/{id}/loyalty-transactions
     */
    public function getLoyaltyTransactions($id): void
    {
        try {
            $transactions = $this->repo->getLoyaltyTransactions($id);
            $this->json(['transactions' => $transactions]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải lịch sử điểm',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** GET /admin/api/customers/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers (thêm cột Tài khoản)
        $headers = [
            'STT',
            ['text' => 'Tài khoản ', 'required' => true, 'note' => '(Không dấu, 6-32 ký tự)'],
            ['text' => 'Họ tên ', 'required' => true],
            ['text' => 'Email ', 'required' => true],
            ['text' => 'Số điện thoại ', 'required' => true],
            ['text' => 'Ngày sinh', 'note' => '(dd/mm/yyyy)'],
            ['text' => 'Giới tính', 'note' => '(Nam/Nữ)'],
            'Địa chỉ',
            ['text' => 'Trạng thái ', 'required' => true, 'note' => '(1: hoạt động, 0: khóa)']
        ];

        $col = 'A';
        foreach ($headers as $header) {
            if (is_array($header)) {
                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $richText->createText($header['text']);
                if ($header['required'] ?? false) {
                    $red = $richText->createTextRun('*');
                    $red->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFF0000'));
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
        $sheet->getStyle('A1:I1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:I1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // Sample data (thêm tài khoản)
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'nguyenvana');
        $sheet->setCellValue('C2', 'Nguyễn Văn A');
        $sheet->setCellValue('D2', 'nguyenvana@example.com');
        $sheet->setCellValue('E2', '0901234567');
        $sheet->setCellValue('F2', '15/05/1990');
        $sheet->setCellValue('G2', 'Nam');
        $sheet->setCellValue('H2', '123 Đường ABC, Q.1, TP.HCM');
        $sheet->setCellValue('I2', '1');

        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', 'tranthib');
        $sheet->setCellValue('C3', 'Trần Thị B');
        $sheet->setCellValue('D3', 'tranthib@example.com');
        $sheet->setCellValue('E3', '0907654321');
        $sheet->setCellValue('F3', '20/08/1995');
        $sheet->setCellValue('G3', 'Nữ');
        $sheet->setCellValue('H3', '456 Đường XYZ, Q.3, TP.HCM');
        $sheet->setCellValue('I3', '1');

        // Borders
        $sheet->getStyle('A1:I3')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_khach_hang.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/customers/import - Import Excel file */
    public function importExcel()
    {
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors in output

        try {
            require_once __DIR__ . '/../../../vendor/autoload.php';

            // Check file upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Không có file được tải lên';
                if (isset($_FILES['file']['error'])) {
                    $errorMsg .= ' (Error code: ' . $_FILES['file']['error'] . ')';
                }
                // Xác định status cho frontend
                $importStatus = 'success'; // xanh
                if (count($success) === 0 && count($errors) > 0) {
                    $importStatus = 'failed'; // đỏ
                } elseif (count($errors) > 0 && count($success) > 0) {
                    $importStatus = 'partial'; // vàng/cam
                }

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'status' => $importStatus, // ← THÊM DÒNG NÀY
                    'summary' => [
                        'total' => count($rows),
                        'success' => count($success),
                        'errors' => count($errors)
                    ]
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

                // Map columns: STT, Tài khoản*, Họ tên*, Email*, SĐT*, Ngày sinh, Giới tính, Địa chỉ, Trạng thái*
                $username = trim($row[1] ?? '');
                $full_name = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $phone = trim($row[4] ?? '');
                $date_of_birth = trim($row[5] ?? '');
                $gender = trim($row[6] ?? '');
                $address = trim($row[7] ?? '');
                $is_active = trim($row[8] ?? '');

                $rowErrors = [];

                // Validate username (optional)
                if (!empty($username) && !preg_match('/^[a-z0-9_]{6,32}$/', $username)) {
                    $rowErrors[] = 'Tài khoản phải không dấu, chỉ chứa chữ thường, số và gạch dưới, từ 6-32 ký tự';
                }

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

                // Validate is_active
                if (!in_array($is_active, ['0', '1', 0, 1], true)) {
                    $rowErrors[] = "Trạng thái phải là 0 hoặc 1";
                }

                // If errors exist, log and continue
                if (!empty($rowErrors)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'is_active' => $is_active,
                        'errors' => implode('; ', $rowErrors)
                    ];
                    continue;
                }

                // Create customer data
                $customerData = [
                    'username' => $username ?: null,
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'date_of_birth' => $date_of_birth ?: null,
                    'gender' => $gender ?: null,
                    'address' => $address ?: null,
                    'loyalty_points' => 0, // Mặc định 0
                    'is_active' => (int) $is_active,
                    'created_by' => $currentUser
                ];

                try {
                    $created = $this->repo->create($customerData);

                    // Check if create was successful
                    if ($created === false) {
                        throw new \Exception('Không thể tạo khách hàng trong database');
                    }

                    if (is_string($created)) {
                        throw new \Exception($created);
                    }

                    $success[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'loyalty_points' => 0,
                        'is_active' => $is_active
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'loyalty_points' => 0,
                        'is_active' => $is_active,
                        'errors' => $e->getMessage()
                    ];
                }
            }

            // Save import history
            $this->saveImportHistory($file['name'], count($success), count($errors), $success, $errors);

            $totalRows = count($rows);
            $successCount = count($success);
            $failedRows = count($errors);

            // Xác định status
            if ($successCount === 0 && $failedRows > 0) {
                $importStatus = 'failed';
            } elseif ($successCount > 0 && $failedRows > 0) {
                $importStatus = 'partial';
            } else {
                $importStatus = 'success';
            }

            // Tạo thông báo kết quả chi tiết - chỉ hiển thị 1 lỗi đầu tiên
            $message = '';
            if ($importStatus === 'success') {
                $message = "Nhập thành công $successCount/$totalRows bản ghi";
            } elseif ($importStatus === 'partial') {
                $message = "Nhập thành công $successCount/$totalRows bản ghi. Có $failedRows lỗi";
                if (!empty($errors)) {
                    // Chỉ hiển thị lỗi đầu tiên
                    $firstError = is_array($errors[0]) ? $errors[0]['errors'] : $errors[0];
                    $message .= ": " . $firstError;
                    if ($failedRows > 1) {
                        $message .= " (xem chi tiết trong lịch sử nhập)";
                    }
                }
            } else {
                $message = "Nhập thất bại. Có $failedRows lỗi";
                if (!empty($errors)) {
                    // Chỉ hiển thị lỗi đầu tiên
                    $firstError = is_array($errors[0]) ? $errors[0]['errors'] : $errors[0];
                    $message .= ": " . $firstError;
                    if ($failedRows > 1) {
                        $message .= " (xem chi tiết trong lịch sử nhập)";
                    }
                }
            }

            // ...phần trả về JSON giữ nguyên...
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'status' => $importStatus,
                'summary' => [
                    'total' => $totalRows,
                    'success' => $successCount,
                    'errors' => $failedRows
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Throwable $e) {
            // Log the error
            error_log("Import Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi xử lý file Excel: ' . $e->getMessage(),
                'detail' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
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

        // Prepare data matching ImportHistoryRepository::create expected fields
        $total = $successCount + $errorCount;
        $status = $errorCount > 0 ? 'partial' : 'completed';

        // Merge success and error rows, then sort by row number
        $allRows = array_merge($successRows, $errorRows);
        usort($allRows, function ($a, $b) {
            return ($a['row'] ?? 0) <=> ($b['row'] ?? 0);
        });

        $importHistoryRepo->create([
            'table_name' => 'customers',
            'file_name' => $filename,
            'total_rows' => $total,
            'success_rows' => $successCount,
            'failed_rows' => $errorCount,
            'status' => $status,
            'error_details' => !empty($errorRows) ? json_encode($errorRows, JSON_UNESCAPED_UNICODE) : null,
            'file_content' => !empty($allRows) ? json_encode($allRows, JSON_UNESCAPED_UNICODE) : null,
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
            $dates = array_filter(array_map(function ($item) {
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
