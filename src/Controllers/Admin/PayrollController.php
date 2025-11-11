<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PayrollRepository;
use App\Models\Repositories\StaffRepository;
use App\Models\Repositories\AttendanceRepository;
use App\Services\EmailService;

class PayrollController extends BaseAdminController
{
    protected $repo;
    private $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new PayrollRepository();
        $this->emailService = new EmailService();
    }

    /**
     * Giao diện quản lý bảng lương
     * GET /admin/payroll
     */
    public function index()
    {
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        
        return $this->view('admin/payroll/payroll', [
            'month' => $month,
            'year' => $year
        ]);
    }

    /**
     * API: Lấy danh sách bảng lương theo tháng
     * GET /admin/api/payroll?month=X&year=Y
     */
    public function apiIndex()
    {
        try {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            
            $items = $this->repo->getByMonth((int)$month, (int)$year);
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Tính lương cho tất cả nhân viên trong tháng
     * POST /admin/api/payroll/calculate
     */
    public function calculate()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $month = $data['month'] ?? date('n');
        $year = $data['year'] ?? date('Y');
        $createdBy = $this->currentUserId();

        try {
            $staffRepo = new StaffRepository();
            $staffs = $staffRepo->all();
            
            $results = [];
            foreach ($staffs as $staff) {
                $payroll = $this->repo->calculatePayroll(
                    $staff['user_id'], 
                    (int)$month, 
                    (int)$year, 
                    $createdBy
                );
                $results[] = $payroll;
            }
            
            $this->json([
                'message' => 'Tính lương thành công cho ' . count($results) . ' nhân viên',
                'data' => $results
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Tính lương cho một nhân viên
     * POST /admin/api/payroll/calculate/{userId}
     */
    public function calculateOne($userId)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $month = $data['month'] ?? date('n');
        $year = $data['year'] ?? date('Y');
        $createdBy = $this->currentUserId();

        try {
            $payroll = $this->repo->calculatePayroll(
                (int)$userId, 
                (int)$month, 
                (int)$year, 
                $createdBy
            );
            
            $this->json([
                'message' => 'Tính lương thành công',
                'data' => $payroll
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Cập nhật thưởng/phạt
     * PUT /admin/api/payroll/{id}/bonus-deduction
     */
    public function updateBonusDeduction($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $bonus = $data['bonus'] ?? 0;
        $deduction = $data['deduction'] ?? 0;
        $updatedBy = $this->currentUserId();

        try {
            $this->repo->updateBonusDeduction($id, $bonus, $deduction, $updatedBy);
            $this->json(['message' => 'Cập nhật thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Phê duyệt bảng lương
     * POST /admin/api/payroll/{id}/approve
     */
    public function approve($id)
    {
        try {
            $approvedBy = $this->currentUserId();
            $this->repo->approve($id, $approvedBy);
            $this->json(['message' => 'Phê duyệt thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Đánh dấu đã trả lương
     * POST /admin/api/payroll/{id}/mark-paid
     */
    public function markAsPaid($id)
    {
        try {
            $this->repo->markAsPaid($id);
            $this->json(['message' => 'Đã đánh dấu đã trả lương']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Trả lương cho một nhân viên (tạo phiếu chi)
     * POST /admin/api/payroll/{id}/pay
     */
    public function pay($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $currentUser = $this->currentUserId();
            
            // Lấy thông tin bảng lương
            $payroll = $this->repo->find($id);
            if (!$payroll) {
                $this->json(['error' => 'Không tìm thấy bảng lương'], 404);
                return;
            }
            
            // Kiểm tra trạng thái: chỉ trả lương đã duyệt
            if ($payroll['status'] !== 'Đã duyệt') {
                $this->json(['error' => 'Chỉ có thể trả lương đã được duyệt'], 400);
                return;
            }
            
            // Tạo phiếu chi
            $expenseRepo = new \App\Models\Repositories\ExpenseVoucherRepository();
            $expenseCode = $expenseRepo->getNextCode();
            
            $expenseData = [
                'code' => $expenseCode,
                'type' => 'Lương nhân viên',
                'payroll_id' => $id,
                'staff_user_id' => $payroll['user_id'],
                'method' => $data['method'] ?? 'Tiền mặt',
                'amount' => $payroll['total_salary'],
                'paid_by' => $currentUser,
                'paid_at' => date('Y-m-d H:i:s'),
                'note' => 'Trả lương tháng ' . $payroll['month'] . '/' . $payroll['year'] . ' - ' . $payroll['full_name']
            ];
            
            $expenseId = $expenseRepo->create($expenseData, $currentUser);
            
            // Gửi email thông báo cho nhân viên
            try {
                // Lấy thông tin nhân viên
                $staffRepo = new StaffRepository();
                $staff = $staffRepo->find($payroll['user_id']); // find() chấp nhận user_id
                
                if ($staff && !empty($staff['email'])) {
                    // Lấy dữ liệu chấm công của nhân viên trong tháng
                    $attRepo = new AttendanceRepository();
                    $attendances = $attRepo->getByUserAndMonth(
                        $payroll['user_id'], 
                        (int)$payroll['month'], 
                        (int)$payroll['year']
                    );
                    
                    // Gửi email kèm PDF
                    $emailResult = $this->emailService->sendPayrollNotification(
                        $staff,
                        $payroll,
                        $attendances,
                        $payroll['month'],
                        $payroll['year']
                    );
                    
                    if (!$emailResult['success']) {
                        error_log("Failed to send payroll email to {$staff['email']}: {$emailResult['message']}");
                    }
                } else {
                    error_log("Staff user_id {$payroll['user_id']} has no email address");
                }
            } catch (\Throwable $emailError) {
                // Log lỗi nhưng không làm gián đoạn quá trình trả lương
                error_log("Email error for payroll {$id}: " . $emailError->getMessage());
            }
            
            $this->json([
                'message' => 'Đã tạo phiếu chi và trả lương thành công',
                'expense_id' => $expenseId
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Trả lương cho tất cả nhân viên đã duyệt (tạo nhiều phiếu chi)
     * POST /admin/api/payroll/pay-all
     */
    public function payAll()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $month = $data['month'] ?? date('n');
        $year = $data['year'] ?? date('Y');
        $method = $data['method'] ?? 'Tiền mặt';
        
        try {
            $currentUser = $this->currentUserId();
            
            // Lấy tất cả bảng lương đã duyệt trong tháng
            $allPayrolls = $this->repo->getByMonth((int)$month, (int)$year);
            $approvedPayrolls = array_filter($allPayrolls, function($p) {
                return $p['status'] === 'Đã duyệt';
            });
            
            if (empty($approvedPayrolls)) {
                $this->json(['error' => 'Không có bảng lương đã duyệt trong tháng này'], 400);
                return;
            }
            
            $expenseRepo = new \App\Models\Repositories\ExpenseVoucherRepository();
            $staffRepo = new StaffRepository();
            $attRepo = new AttendanceRepository();
            $successCount = 0;
            $emailCount = 0;
            $errors = [];
            
            foreach ($approvedPayrolls as $payroll) {
                try {
                    $expenseCode = $expenseRepo->getNextCode();
                    
                    $expenseData = [
                        'code' => $expenseCode,
                        'type' => 'Lương nhân viên',
                        'payroll_id' => $payroll['id'],
                        'staff_user_id' => $payroll['user_id'],
                        'method' => $method,
                        'amount' => $payroll['total_salary'],
                        'paid_by' => $currentUser,
                        'paid_at' => date('Y-m-d H:i:s'),
                        'note' => 'Trả lương tháng ' . $month . '/' . $year . ' - ' . $payroll['full_name']
                    ];
                    
                    $expenseRepo->create($expenseData, $currentUser);
                    $successCount++;
                    
                    // Gửi email cho nhân viên
                    try {
                        $staff = $staffRepo->find($payroll['user_id']);
                        
                        if ($staff && !empty($staff['email'])) {
                            $attendances = $attRepo->getByUserAndMonth(
                                $payroll['user_id'], 
                                (int)$month, 
                                (int)$year
                            );
                            
                            $emailResult = $this->emailService->sendPayrollNotification(
                                $staff,
                                $payroll,
                                $attendances,
                                $month,
                                $year
                            );
                            
                            if ($emailResult['success']) {
                                $emailCount++;
                            }
                        }
                    } catch (\Throwable $emailError) {
                        error_log("Email error for payroll {$payroll['id']}: " . $emailError->getMessage());
                    }
                    
                } catch (\Throwable $e) {
                    $errors[] = $payroll['full_name'] . ': ' . $e->getMessage();
                }
            }
            
            $total = count($approvedPayrolls);
            $message = "Đã tạo {$successCount}/{$total} phiếu chi thành công";
            if ($emailCount > 0) {
                $message .= " và gửi {$emailCount} email thông báo";
            }
            if (!empty($errors)) {
                $message .= '. Lỗi: ' . implode('; ', $errors);
            }
            
            $this->json([
                'message' => $message,
                'success_count' => $successCount,
                'total' => $total,
                'errors' => $errors
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xóa bảng lương
     * DELETE /admin/api/payroll/{id}
     */
    public function delete($id)
    {
        try {
            $this->repo->delete($id);
            $this->json(['message' => 'Xóa thành công']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xuất Excel bảng lương
     * GET /admin/api/payroll/export?month=X&year=Y&type=month|quarter|year|custom
     */
    public function export()
    {
        try {
            $type = $_GET['type'] ?? 'month';
            $items = [];
            $title = '';
            
            if ($type === 'quarter') {
                $quarter = $_GET['quarter'] ?? 1;
                $year = $_GET['year'] ?? date('Y');
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                
                for ($m = $startMonth; $m <= $endMonth; $m++) {
                    $monthItems = $this->repo->getByMonth((int)$m, (int)$year);
                    $items = array_merge($items, $monthItems);
                }
                $title = "Quý $quarter năm $year";
            } elseif ($type === 'year') {
                $year = $_GET['year'] ?? date('Y');
                
                for ($m = 1; $m <= 12; $m++) {
                    $monthItems = $this->repo->getByMonth((int)$m, (int)$year);
                    $items = array_merge($items, $monthItems);
                }
                $title = "Năm $year";
            } elseif ($type === 'custom') {
                $from = $_GET['from'] ?? null;
                $to = $_GET['to'] ?? null;
                
                if ($from && $to) {
                    $fromDate = new \DateTime($from);
                    $toDate = new \DateTime($to);
                    
                    $currentDate = clone $fromDate;
                    while ($currentDate <= $toDate) {
                        $m = (int)$currentDate->format('n');
                        $y = (int)$currentDate->format('Y');
                        $monthItems = $this->repo->getByMonth($m, $y);
                        $items = array_merge($items, $monthItems);
                        $currentDate->modify('+1 month');
                    }
                    $title = "Từ " . $fromDate->format('d/m/Y') . " đến " . $toDate->format('d/m/Y');
                } else {
                    $month = $_GET['month'] ?? date('n');
                    $year = $_GET['year'] ?? date('Y');
                    $items = $this->repo->getByMonth((int)$month, (int)$year);
                    $title = "Tháng $month năm $year";
                }
            } else {
                // Default: month
                $month = $_GET['month'] ?? date('n');
                $year = $_GET['year'] ?? date('Y');
                $items = $this->repo->getByMonth((int)$month, (int)$year);
                $title = "Tháng $month năm $year";
            }
            
            // Tạo file Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set tên sheet
            $sheet->setTitle('Bảng lương');
            
            // Tiêu đề
            $sheet->setCellValue('A1', 'BẢNG LƯƠNG NHÂN VIÊN');
            $sheet->mergeCells('A1:L1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Thông tin xuất file
            $sheet->setCellValue('A2', $title);
            $sheet->mergeCells('A2:L2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A3', 'Ngày xuất: ' . date('d/m/Y') . ' - Thời gian: ' . date('H:i:s'));
            $sheet->mergeCells('A3:L3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Header bảng
            $row = 5;
            $headers = ['STT', 'Nhân viên', 'Vai trò', 'Số ca làm', 'Ca yêu cầu', 'Lương cơ bản', 'Lương thực tế', 'Thưởng', 'Phạt', 'Khấu trừ đi muộn', 'Tổng lương', 'Trạng thái'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF002975');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            
            // Dữ liệu
            $row = 6;
            $stt = 1;
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $row, $stt++);
                $sheet->setCellValue('B' . $row, $item['full_name'] ?? '');
                $sheet->setCellValue('C' . $row, $item['staff_role'] ?? '');
                $sheet->setCellValue('D' . $row, $item['total_shifts_worked'] ?? 0);
                $sheet->setCellValue('E' . $row, $item['required_shifts'] ?? 0);
                $sheet->setCellValue('F' . $row, $item['base_salary'] ?? 0);
                $sheet->setCellValue('G' . $row, $item['actual_salary'] ?? 0);
                $sheet->setCellValue('H' . $row, $item['bonus'] ?? 0);
                $sheet->setCellValue('I' . $row, $item['deduction'] ?? 0);
                $sheet->setCellValue('J' . $row, $item['late_deduction'] ?? 0);
                $sheet->setCellValue('K' . $row, $item['total_salary'] ?? 0);
                $sheet->setCellValue('L' . $row, $item['status'] ?? '');
                $row++;
            }
            
            // Format số tiền
            $moneyColumns = ['F', 'G', 'H', 'I', 'J', 'K'];
            foreach ($moneyColumns as $col) {
                $sheet->getStyle($col . '6:' . $col . ($row - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }
            
            // Tổng cộng
            if (!empty($items)) {
                $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('F' . $row, '=SUM(F6:F' . ($row - 1) . ')');
                $sheet->setCellValue('G' . $row, '=SUM(G6:G' . ($row - 1) . ')');
                $sheet->setCellValue('H' . $row, '=SUM(H6:H' . ($row - 1) . ')');
                $sheet->setCellValue('I' . $row, '=SUM(I6:I' . ($row - 1) . ')');
                $sheet->setCellValue('J' . $row, '=SUM(J6:J' . ($row - 1) . ')');
                $sheet->setCellValue('K' . $row, '=SUM(K6:K' . ($row - 1) . ')');
                
                $sheet->getStyle('F' . $row . ':K' . $row)->getFont()->setBold(true);
                $sheet->getStyle('F' . $row . ':K' . $row)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }
            
            // Auto size columns
            foreach (range('A', 'L') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Border cho bảng
            $lastRow = $row;
            $sheet->getStyle('A5:L' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            
            // Download file
            $filenamePart = str_replace(' ', '_', $title);
            $filename = 'BangLuong_' . $filenamePart . '_' . date('dmY_His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Lấy lịch sử thay đổi lương của nhân viên
     * GET /admin/api/payroll/salary-history?user_id=X
     */
    public function getSalaryHistory()
    {
        try {
            $userId = $_GET['user_id'] ?? null;
            
            if (!$userId) {
                $this->json(['success' => false, 'error' => 'Thiếu user_id'], 400);
                return;
            }

            $history = $this->repo->getSalaryHistory((int)$userId);
            
            $this->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Xuất Excel lịch sử thay đổi lương
     * GET /admin/api/payroll/salary-history/export?user_id=X
     */
    public function exportSalaryHistory()
    {
        try {
            $userId = $_GET['user_id'] ?? null;
            
            if (!$userId) {
                $this->json(['error' => 'Thiếu user_id'], 400);
                return;
            }

            $history = $this->repo->getSalaryHistory((int)$userId);
            
            if (empty($history)) {
                $this->json(['error' => 'Không có dữ liệu'], 404);
                return;
            }

            // Lấy thông tin nhân viên
            $staffInfo = $history[0] ?? [];
            $staffName = $staffInfo['full_name'] ?? 'Nhân viên';
            
            // Tạo file Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $sheet->setCellValue('A1', 'LỊCH SỬ THAY ĐỔI LƯƠNG');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A2', 'Nhân viên: ' . $staffName);
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            
            $sheet->setCellValue('A3', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
            $sheet->mergeCells('A3:F3');
            
            // Column headers
            $headers = ['STT', 'Từ ngày', 'Đến ngày', 'Lương cơ bản', 'Ghi chú', 'Ngày tạo'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '5', $header);
                $col++;
            }
            
            // Style headers
            $sheet->getStyle('A5:F5')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF002975']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]);
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(40);
            $sheet->getColumnDimension('F')->setWidth(20);
            
            // Data rows
            $row = 6;
            $stt = 1;
            foreach ($history as $item) {
                $sheet->setCellValue('A' . $row, $stt++);
                $sheet->setCellValue('B' . $row, $item['from_date'] ?? '-');
                $sheet->setCellValue('C' . $row, $item['to_date'] ?? 'Hiện tại');
                $sheet->setCellValue('D' . $row, number_format($item['salary'] ?? 0, 0, ',', '.') . ' đ');
                $sheet->setCellValue('E' . $row, $item['note'] ?? '-');
                $sheet->setCellValue('F' . $row, $item['created_at'] ?? '-');
                
                // Center align STT
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // Right align salary
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                
                $row++;
            }
            
            // Borders
            $lastRow = $row - 1;
            $sheet->getStyle('A5:F' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            
            // Download file
            $filename = 'LichSuLuong_' . str_replace(' ', '_', $staffName) . '_' . date('dmY_His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
