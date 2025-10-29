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
