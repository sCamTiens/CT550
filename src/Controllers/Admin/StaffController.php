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
                    'gender' => $r['gender'] ?? '',
                    'staff_role' => $r['staff_role'] ?? '',
                    'hired_at' => $r['hired_at'] ?? null,
                    'is_active' => $r['is_active'] ?? 1,
                    'base_salary' => $r['base_salary'] ?? 0,
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
        if (empty(trim($data['full_name'] ?? ''))) {
            return $this->json(['error' => 'Họ tên không được để trống'], 422);
        }
        if (empty(trim($data['staff_role'] ?? ''))) {
            return $this->json(['error' => 'Vai trò không được để trống'], 422);
        }
        if (!isset($data['base_salary']) || $data['base_salary'] <= 0) {
            return $this->json(['error' => 'Lương tháng phải lớn hơn 0'], 422);
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
            if (is_string($result)) {
                return $this->json(['error' => $result], 409);
            }
            $this->json($result);
        } catch (\PDOException $e) {
            $this->json(['error' => 'Lỗi khi thêm nhân viên', 'detail' => $e->getMessage()], 500);
        }
    }

        /** API: Cập nhật nhân viên */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty(trim($data['full_name'] ?? ''))) {
            return $this->json(['error' => 'Họ tên không được để trống'], 422);
        }
        if (empty(trim($data['staff_role'] ?? ''))) {
            return $this->json(['error' => 'Vai trò không được để trống'], 422);
        }
        if (!isset($data['base_salary']) || $data['base_salary'] <= 0) {
            return $this->json(['error' => 'Lương tháng phải lớn hơn 0'], 422);
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
            if (is_string($result)) {
                return $this->json(['error' => $result], 409);
            }
            if (!$result) {
                return $this->json(['error' => 'Không tìm thấy nhân viên'], 404);
            }
            $this->json($result);
        } catch (\PDOException $e) {
            $this->json(['error' => 'Lỗi khi cập nhật nhân viên', 'detail' => $e->getMessage()], 500);
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

        // Headers (thêm cột Tài khoản)
        $headers = [
            'STT',
            ['text' => 'Tài khoản ', 'required' => true, 'note' => '(Không dấu, 6-32 ký tự)'],
            ['text' => 'Mật khẩu ', 'required' => true, 'note' => '(Tối thiểu 8 ký tự)'],
            ['text' => 'Họ tên ', 'required' => true],
            ['text' => 'Vai trò ', 'required' => true, 'note' => '(Admin/Kho/Nhân viên bán hàng/Hỗ trợ trực tuyến)'],
            ['text' => 'Email ', 'required' => true],
            ['text' => 'Số điện thoại ', 'required' => true, 'note' => '(0xxxxxxxxx)'],
            ['text' => 'Ngày sinh', 'note' => '(dd/mm/yyyy)'],
            ['text' => 'Ngày vào làm', 'note' => '(dd/mm/yyyy)'],
            ['text' => 'Giới tính', 'note' => '(Nam/Nữ)'],
            'Ghi chú',
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
        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:L1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:L1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // Sample data (thêm tài khoản) - Sử dụng timestamp để tránh trùng lặp
        $timestamp = time();
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'nhanvien' . $timestamp);
        $sheet->setCellValue('C2', '12345678');
        $sheet->setCellValue('D2', 'Nguyễn Văn A');
        $sheet->setCellValue('E2', 'Kho');
        $sheet->setCellValue('F2', 'nhanvien' . $timestamp . '@example.com');
        $sheet->setCellValue('G2', '0901234567');
        $sheet->setCellValue('H2', '15/05/1990');
        $sheet->setCellValue('I2', '01/10/2025');
        $sheet->setCellValue('J2', 'Nam');
        $sheet->setCellValue('K2', 'Nhân viên mẫu 1');
        $sheet->setCellValue('L2', '1');

        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', 'nhanvien' . ($timestamp + 1));
        $sheet->setCellValue('C3', 'matkhau123');
        $sheet->setCellValue('D3', 'Trần Thị B');
        $sheet->setCellValue('E3', 'Nhân viên bán hàng');
        $sheet->setCellValue('F3', 'nhanvien' . ($timestamp + 1) . '@example.com');
        $sheet->setCellValue('G3', '0907654321');
        $sheet->setCellValue('H3', '20/08/1995');
        $sheet->setCellValue('I3', '01/10/2025');
        $sheet->setCellValue('J3', 'Nữ');
        $sheet->setCellValue('K3', 'Nhân viên mẫu 2');
        $sheet->setCellValue('L3', '1');

        // Borders
        $sheet->getStyle('A1:L3')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'L') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_nhan_vien.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/staff/import - Import Excel file */
    public function importExcel()
    {
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors in output

        try {
            require_once __DIR__ . '/../../../vendor/autoload.php';

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

                // Map columns: STT, Tài khoản*, Mật khẩu*, Họ tên*, Vai trò*, Email*, SĐT*, Ngày sinh, Ngày vào làm, Giới tính, Ghi chú, Trạng thái*
                $username = trim($row[1] ?? '');
                $password = trim($row[2] ?? '');
                $full_name = trim($row[3] ?? '');
                $staff_role = trim($row[4] ?? '');
                $email = trim($row[5] ?? '');
                $phone = trim($row[6] ?? '');
                $date_of_birth = trim($row[7] ?? '');
                $hired_at = trim($row[8] ?? '');
                $gender = trim($row[9] ?? '');
                $note = trim($row[10] ?? '');
                $is_active = trim($row[11] ?? '');

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

                // Validate date_of_birth (optional)
                if (!empty($date_of_birth)) {
                    $date_of_birth = $this->convertDateFormat($date_of_birth);
                    if (!$date_of_birth) {
                        $rowErrors[] = 'Ngày sinh không đúng định dạng (dd/mm/yyyy)';
                    }
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

                if (!empty($gender) && !in_array($gender, ['Nam', 'Nữ'])) {
                    $rowErrors[] = "Giới tính phải là 'Nam' hoặc 'Nữ'";
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
                    'date_of_birth' => $date_of_birth ?: null,
                    'gender' => $gender ?: null,
                    'hired_at' => $hired_at ?: null,
                    'note' => $note ?: null,
                    'is_active' => (int) $is_active,
                    'created_by' => $currentUser
                ];

                try {
                    $result = $this->repo->create($staffData);

                    // Check if create() returned an error string
                    if (is_string($result)) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'username' => $username,
                            'full_name' => $full_name,
                            'staff_role' => $staff_role,
                            'email' => $email,
                            'phone' => $phone,
                            'gender' => $gender,
                            'is_active' => $is_active,
                            'errors' => $result
                        ];
                    } elseif ($result === false) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'username' => $username,
                            'full_name' => $full_name,
                            'staff_role' => $staff_role,
                            'email' => $email,
                            'phone' => $phone,
                            'gender' => $gender,
                            'is_active' => $is_active,
                            'errors' => 'Không thể tạo nhân viên'
                        ];
                    } else {
                        // Success - result is an array
                        $success[] = [
                            'row' => $rowNumber,
                            'username' => $username,
                            'full_name' => $full_name,
                            'staff_role' => $staff_role,
                            'email' => $email,
                            'phone' => $phone,
                            'gender' => $gender,
                            'is_active' => $is_active
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'username' => $username,
                        'full_name' => $full_name,
                        'staff_role' => $staff_role,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
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

        // Determine status based on results
        $status = 'success';
        if ($errorCount > 0 && $successCount === 0) {
            $status = 'failed';
        } elseif ($errorCount > 0 && $successCount > 0) {
            $status = 'partial';
        }

        // Merge success and error rows into one array for display
        $allRows = array_merge($successRows, $errorRows);
        // Sort by row number
        usort($allRows, function($a, $b) {
            return ($a['row'] ?? 0) - ($b['row'] ?? 0);
        });

        $importHistoryRepo->create([
            'table_name' => 'staff',
            'file_name' => $filename,
            'total_rows' => $successCount + $errorCount,
            'success_rows' => $successCount,
            'failed_rows' => $errorCount,
            'status' => $status,
            'error_details' => !empty($errorRows) ? json_encode($errorRows, JSON_UNESCAPED_UNICODE) : null,
            'file_content' => json_encode($allRows, JSON_UNESCAPED_UNICODE),
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
