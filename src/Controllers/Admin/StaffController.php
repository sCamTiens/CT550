<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\StaffRepository;
use App\Models\Repositories\RoleRepository;

class StaffController extends BaseAdminController
{
    protected $repo;
    public function __construct()
    {
        parent::__construct();
        $this->repo = new StaffRepository();
    }

    /**
     * API: Đổi mật khẩu nhân viên
     * PUT /admin/api/staff/{id}/password
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
                return $this->json(['ok' => true]);
            } else {
                return $this->json(['error' => 'Không thể đổi mật khẩu'], 500);
            }
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Lỗi khi đổi mật khẩu', 'detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Giao diện trang quản lý nhân viên (View)
     * GET /admin/staff
     */
    public function index()
    {
        return $this->view('admin/staff/staff');
    }

    /**
     * API: Danh sách nhân viên (JSON)
     * GET /admin/api/staff
     */
    public function apiIndex()
    {
        try {
            $items = $this->repo->all();
            // Đảm bảo mỗi phần tử là mảng (không phải object) và đủ trường
            $data = array_map(function ($r) {
                // Nếu là object chuyển thành mảng
                if (is_object($r))
                    $r = (array) $r;
                return [
                    'user_id' => $r['user_id'] ?? $r['id'] ?? null,
                    'username' => $r['username'] ?? '',
                    'full_name' => $r['full_name'] ?? '',
                    'email' => $r['email'] ?? '',
                    'phone' => $r['phone'] ?? '',
                    'staff_role' => $r['staff_role'] ?? '',
                    'hired_at' => $r['hired_at'] ?? null,
                    'is_active' => $r['is_active'] ?? 1,
                    'note' => $r['note'] ?? '',
                    'avatar_url' => $r['avatar_url'] ?? '',
                    'created_by_name' => $r['created_by_name'] ?? '',
                    'updated_by_name' => $r['updated_by_name'] ?? '',
                    'created_at' => $r['created_at'] ?? '',
                    'updated_at' => $r['updated_at'] ?? '',
                ];
            }, $items);
            $this->json(['items' => $data]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải danh sách nhân viên',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Danh sách vai trò (roles)
     * GET /admin/api/staff/roles
     */
    public function apiRoles()
    {
        try {
            $repo = new RoleRepository();
            $roles = $repo->all();
            $data = array_map(fn($r) => ['id' => $r->id, 'name' => $r->name], $roles);
            $this->json(['roles' => $data]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Không thể tải danh sách vai trò', 'detail' => $e->getMessage()], 500);
        }
    }

    /** API: Tạo mới nhân viên */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate cơ bản
        if (empty(trim($data['username'] ?? ''))) {
            return $this->json(['error' => 'Tên tài khoản không được để trống'], 422);
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email không hợp lệ'], 422);
        }
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            return $this->json(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số'], 422);
        }

        try {
            $data['created_by'] = $this->currentUserId();
            $result = $this->repo->create($data);
            if ($result === false) {
                return $this->json(['error' => 'Không thể tạo nhân viên'], 500);
            }
            // Nếu repository trả về chuỗi lỗi (VD: "Email đã tồn tại trong hệ thống")
            if (is_string($result)) {
                return $this->json(['error' => $result], 422);
            }
            $this->json($result);
        } catch (\PDOException $e) {
            $this->json(['error' => 'Lỗi cơ sở dữ liệu khi tạo nhân viên', 'detail' => $e->getMessage()], 500);
        }
    }

    /** API: Cập nhật nhân viên */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty(trim($data['full_name'] ?? ''))) {
            return $this->json(['error' => 'Họ tên không được để trống'], 422);
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email không hợp lệ'], 422);
        }
        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            return $this->json(['error' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số'], 422);
        }

        try {
            $data['updated_by'] = $this->currentUserId();
            $result = $this->repo->update($id, $data);
            if ($result === false) {
                return $this->json(['error' => 'Không thể cập nhật nhân viên'], 500);
            }
            // Nếu repository trả về chuỗi lỗi (VD: "Email đã tồn tại trong hệ thống")
            if (is_string($result)) {
                return $this->json(['error' => $result], 422);
            }
            $this->json($result);
        } catch (\PDOException $e) {
            $this->json(['error' => 'Lỗi cơ sở dữ liệu khi cập nhật nhân viên', 'detail' => $e->getMessage()], 500);
        }
    }

    /** API: Xóa nhân viên */
    public function delete($id)
    {
        try {
            $ok = $this->repo->delete($id);
            return $ok
                ? $this->json(['ok' => true])
                : $this->json(['error' => 'Không thể xoá nhân viên'], 500);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\PDOException $e) {
            return $this->json(['error' => 'Lỗi cơ sở dữ liệu khi xoá', 'detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Lấy ID người dùng hiện tại trong session
     */
    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /** GET /admin/api/staff/template - Download Excel template */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define headers
        $headers = [
            'STT',
            'Tài khoản*',
            'Mật khẩu*',
            'Họ tên*',
            'Vai trò*',
            'Email*',
            'Số điện thoại*',
            'Ngày vào làm',
            'Ghi chú',
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
                'nhanvien01',
                'Password123',
                'Nguyễn Văn A',
                'Nhân viên bán hàng',
                'nguyenvana@example.com',
                '0901234567',
                '01/01/2024',
                'Nhân viên chính thức',
                1
            ],
            [
                2,
                'thukho01',
                'Password456',
                'Trần Thị B',
                'Kho',
                'tranthib@example.com',
                '0907654321',
                '15/02/2024',
                '',
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
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_nhanvien.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/staff/import - Import Excel file */
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

                // Map columns: STT, Tài khoản*, Mật khẩu*, Họ tên*, Vai trò*, Email*, SĐT*, Ngày vào làm, Ghi chú, Trạng thái*
                $username = trim($row[1] ?? '');
                $password = trim($row[2] ?? '');
                $full_name = trim($row[3] ?? '');
                $staff_role = trim($row[4] ?? '');
                $email = trim($row[5] ?? '');
                $phone = trim($row[6] ?? '');
                $hired_at = trim($row[7] ?? '');
                $note = trim($row[8] ?? '');
                $is_active = trim($row[9] ?? '');

                $rowErrors = [];

                // Validate required fields
                if (empty($username)) {
                    $rowErrors[] = 'Tài khoản không được để trống';
                } elseif (strlen($username) < 6) {
                    $rowErrors[] = 'Tài khoản phải có ít nhất 6 ký tự';
                } elseif (!preg_match('/^[a-zA-Z_.]+$/', $username)) {
                    $rowErrors[] = 'Tài khoản chỉ chứa chữ cái không dấu, dấu chấm, gạch dưới';
                }

                // Check if username exists
                if (!empty($username)) {
                    $existingStaff = $this->repo->findByUsername($username);
                    if ($existingStaff) {
                        $rowErrors[] = "Tài khoản '$username' đã tồn tại";
                    }
                }

                // Validate password
                if (empty($password)) {
                    $rowErrors[] = 'Mật khẩu không được để trống';
                } elseif (strlen($password) < 8) {
                    $rowErrors[] = 'Mật khẩu phải có ít nhất 8 ký tự';
                }

                // Validate full_name
                if (empty($full_name)) {
                    $rowErrors[] = 'Họ tên không được để trống';
                } elseif (strlen($full_name) < 3) {
                    $rowErrors[] = 'Họ tên phải có ít nhất 3 ký tự';
                }

                // Validate staff_role
                $validRoles = ['Admin', 'Kho', 'Nhân viên bán hàng', 'Hỗ trợ trực tuyến'];
                if (empty($staff_role)) {
                    $rowErrors[] = 'Vai trò không được để trống';
                } elseif (!in_array($staff_role, $validRoles)) {
                    $rowErrors[] = "Vai trò phải là: " . implode(', ', $validRoles);
                }

                // Validate email
                if (empty($email)) {
                    $rowErrors[] = 'Email không được để trống';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Email không hợp lệ';
                }

                // Validate phone
                if (empty($phone)) {
                    $rowErrors[] = 'Số điện thoại không được để trống';
                } elseif (!preg_match('/^0\d{9}$/', $phone)) {
                    $rowErrors[] = 'Số điện thoại phải bắt đầu bằng 0 và có 10 chữ số';
                }

                // Validate hired_at (optional)
                if (!empty($hired_at)) {
                    $hired_at = $this->convertDateFormat($hired_at);
                    if (!$hired_at) {
                        $rowErrors[] = 'Ngày vào làm không đúng định dạng (dd/mm/yyyy)';
                    }
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
                        'staff_role' => $staff_role,
                        'email' => $email,
                        'phone' => $phone,
                        'is_active' => $is_active,
                        'errors' => implode('; ', $rowErrors)
                    ];
                    continue;
                }

                // Create staff data
                $staffData = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'full_name' => $full_name,
                    'staff_role' => $staff_role,
                    'email' => $email,
                    'phone' => $phone,
                    'hired_at' => $hired_at ?: null,
                    'note' => $note ?: null,
                    'is_active' => (int)$is_active,
                    'created_by' => $currentUser
                ];

                try {
                    $this->repo->create($staffData);
                    $success[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'staff_role' => $staff_role,
                        'email' => $email,
                        'phone' => $phone,
                        'is_active' => $is_active
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'staff_role' => $staff_role,
                        'email' => $email,
                        'phone' => $phone,
                        'is_active' => $is_active,
                        'errors' => $e->getMessage()
                    ];
                }
            }

            // Save import history
            $this->saveImportHistory($file['name'], count($success), count($errors), $success, $errors);

            // Response
            $message = "Nhập thành công " . count($success) . " nhân viên";
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
            'module' => 'staff',
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

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

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

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH NHÂN VIÊN');
        $sheet->mergeCells('A5:M5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Tên đăng nhập', 'Họ tên', 'Vai trò', 'Email', 'SĐT', 'Trạng thái', 'Ngày vào làm', 'Ghi chú', 'Ngày tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }
        $sheet->getStyle('A6:M6')->getFont()->setBold(true);
        $sheet->getStyle('A6:M6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 7;
        $stt = 1;
        foreach ($items as $s) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $s['username'] ?? '');
            $sheet->setCellValue('C' . $row, $s['full_name'] ?? '');
            $sheet->setCellValue('D' . $row, $s['staff_role'] ?? '');
            $sheet->setCellValue('E' . $row, $s['email'] ?? '');
            $sheet->setCellValue('F' . $row, $s['phone'] ?? '');
            $sheet->setCellValue('G' . $row, $s['is_active'] ?? '');
            $sheet->setCellValue('H' . $row, $s['hired_at'] ?? '');
            $sheet->setCellValue('I' . $row, $s['note'] ?? '');
            $sheet->setCellValue('J' . $row, $s['created_at'] ?? '');
            $sheet->setCellValue('K' . $row, $s['created_by_name'] ?? '');
            $sheet->setCellValue('L' . $row, $s['updated_at'] ?? '');
            $sheet->setCellValue('M' . $row, $s['updated_by_name'] ?? '');
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
        $sheet->getStyle('A6:M' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'M') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Nhan_vien.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
