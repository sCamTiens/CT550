<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PurchaseOrderRepository;
use App\Models\Repositories\SupplierRepository;
use App\Models\Repositories\ProductRepository;
use App\Models\Repositories\ExpenseVoucherRepository;

class PurchaseOrderController extends BaseAdminController
{
    private PurchaseOrderRepository $repo;
    private SupplierRepository $supplierRepo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new PurchaseOrderRepository();
        $this->supplierRepo = new SupplierRepository();
        $this->productRepo = new ProductRepository();
    }

    public function index()
    {
        return $this->view('admin/purchase-orders/purchase-orders');
    }

    public function apiIndex()
    {
        $items = $this->repo->all();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /admin/api/purchase-orders/{id}
     * Lấy chi tiết phiếu nhập kèm các dòng sản phẩm
     */
    public function show($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $details = $this->repo->getDetailsWithLines($id);

        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($details, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $_SESSION['user']['id'] ?? null;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            $id = $this->repo->createReceipt($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            // Expose lỗi để debug
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi tạo phiếu nhập',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            exit;
        }
    }

    // API: GET /admin/api/suppliers
    public function apiSuppliers()
    {
        $items = $this->supplierRepo->all();

        // Nếu repo trả về array thuần (FETCH_ASSOC) thì không cần map nữa
        if (!empty($items) && is_object($items[0] ?? null)) {
            $items = array_map(function ($s) {
                return method_exists($s, 'toArray') ? $s->toArray() : (array) $s;
            }, $items);
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function update($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $_SESSION['user']['id'] ?? null;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            // Kiểm tra trạng thái thanh toán
            $po = $this->repo->findById($id);
            if (!$po) {
                http_response_code(404);
                echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
                exit;
            }

            // Không cho sửa nếu đã thanh toán một phần hoặc hết
            if ($po['payment_status'] == '0' || $po['payment_status'] == '2') {
                http_response_code(403);
                echo json_encode(['error' => 'Không thể sửa phiếu nhập đã thanh toán']);
                exit;
            }

            $this->repo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['id' => $id], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi cập nhật phiếu nhập',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function destroy($id)
    {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phiếu nhập']);
            exit;
        }

        $currentUser = $_SESSION['user']['id'] ?? null;
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Bạn chưa đăng nhập']);
            exit;
        }

        try {
            // Kiểm tra trạng thái thanh toán
            $po = $this->repo->findById($id);
            if (!$po) {
                http_response_code(404);
                echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
                exit;
            }

            // Không cho xóa nếu đã thanh toán một phần hoặc hết
            if ($po['payment_status'] == '0' || $po['payment_status'] == '2') {
                http_response_code(403);
                echo json_encode(['error' => 'Không thể xóa phiếu nhập đã thanh toán']);
                exit;
            }

            $this->repo->delete($id, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Có lỗi xảy ra khi xóa phiếu nhập',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET /admin/api/purchase_orders/unpaid
     * Trả về danh sách phiếu nhập chưa thanh toán hoặc thanh toán một phần
     */
    public function unpaid()
    {
        $repo = new ExpenseVoucherRepository();
        $items = $repo->getUnpaidPurchaseOrders();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        // Tự động tìm ngày nhỏ nhất và lớn nhất từ danh sách phiếu nhập
        $fromDate = '';
        $toDate = '';
        
        if (!empty($items)) {
            $dates = array_filter(array_map(function($item) {
                $date = $item['received_at'] ?? '';
                // Chỉ lấy phần ngày (loại bỏ giờ)
                if ($date && strpos($date, ' ') !== false) {
                    $date = explode(' ', $date)[0];
                }
                return $date;
            }, $items));
            
            if (!empty($dates)) {
                sort($dates);
                $fromDate = reset($dates); // Ngày nhỏ nhất
                $toDate = end($dates);     // Ngày lớn nhất
            }
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:P1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:P2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', 'Từ ngày: ' . $fromDate . ' - Đến ngày: ' . $toDate);
        $sheet->mergeCells('A3:P3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A5', 'DANH SÁCH PHIẾU NHẬP KHO');
        $sheet->mergeCells('A5:P5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Mã phiếu', 'Nhà cung cấp', 'Sản phẩm', 'Mã lô', 'Số lượng', 'Đơn giá', 'Tổng tiền', 'Đã thanh toán', 'Trạng thái thanh toán', 'Hạn thanh toán', 'Ghi chú', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '7', $h);
            $col++;
        }
        $sheet->getStyle('A7:P7')->getFont()->setBold(true);
        $sheet->getStyle('A7:P7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7:P7')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');
        $sheet->getStyle('A7:P7')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Data
        $currentRow = 8;
        $stt = 1;

        foreach ($items as $po) {
            $productItems = $po['items'] ?? [];

            $startRow = $currentRow; // Lưu dòng bắt đầu để merge cells

            if (empty($productItems)) {
                // Nếu không có sản phẩm
                $sheet->setCellValue('A' . $currentRow, $stt++);
                $sheet->setCellValue('B' . $currentRow, $po['code'] ?? '');
                $sheet->setCellValue('C' . $currentRow, $po['supplier_name'] ?? '');
                $sheet->setCellValue('D' . $currentRow, '');
                $sheet->setCellValue('E' . $currentRow, '');
                $sheet->setCellValue('F' . $currentRow, '');
                $sheet->setCellValue('G' . $currentRow, '');
                $sheet->setCellValue('H' . $currentRow, $po['total_amount'] ?? 0);
                $sheet->setCellValue('I' . $currentRow, $po['paid_amount'] ?? 0);
                $sheet->setCellValue('J' . $currentRow, $po['payment_status'] ?? '');
                $sheet->setCellValue('K' . $currentRow, $po['due_date'] ?? '');
                $sheet->setCellValue('L' . $currentRow, $po['note'] ?? '');
                $sheet->setCellValue('M' . $currentRow, $po['received_at'] ?? '');
                $sheet->setCellValue('N' . $currentRow, $po['created_by_name'] ?? '');
                $sheet->setCellValue('O' . $currentRow, $po['updated_at'] ?? '');
                $sheet->setCellValue('P' . $currentRow, $po['updated_by_name'] ?? '');

                $currentRow++;
            } else {
                // Xuất nhiều dòng cho mỗi sản phẩm
                foreach ($productItems as $idx => $item) {
                    // Chỉ ghi thông tin phiếu nhập ở dòng đầu tiên
                    if ($idx === 0) {
                        $sheet->setCellValue('A' . $currentRow, $stt);
                        $sheet->setCellValue('B' . $currentRow, $po['code'] ?? '');
                        $sheet->setCellValue('C' . $currentRow, $po['supplier_name'] ?? '');
                        $sheet->setCellValue('H' . $currentRow, $po['total_amount'] ?? 0);
                        $sheet->setCellValue('I' . $currentRow, $po['paid_amount'] ?? 0);
                        $sheet->setCellValue('J' . $currentRow, $po['payment_status'] ?? '');
                        $sheet->setCellValue('K' . $currentRow, $po['due_date'] ?? '');
                        $sheet->setCellValue('L' . $currentRow, $po['note'] ?? '');
                        $sheet->setCellValue('M' . $currentRow, $po['received_at'] ?? '');
                        $sheet->setCellValue('N' . $currentRow, $po['created_by_name'] ?? '');
                        $sheet->setCellValue('O' . $currentRow, $po['updated_at'] ?? '');
                        $sheet->setCellValue('P' . $currentRow, $po['updated_by_name'] ?? '');
                    }

                    // Thông tin sản phẩm (ghi ở mọi dòng)
                    $sheet->setCellValue('D' . $currentRow, $item['product_name'] ?? '');
                    $sheet->setCellValue('E' . $currentRow, $item['batch_code'] ?? '');
                    $sheet->setCellValue('F' . $currentRow, $item['quantity'] ?? 0);
                    $sheet->setCellValue('G' . $currentRow, $item['unit_cost'] ?? 0);

                    $currentRow++;
                }

                // Merge cells cho các cột thông tin phiếu nhập (nếu có nhiều hơn 1 sản phẩm)
                $endRow = $currentRow - 1;
                if ($endRow > $startRow) {
                    // Merge các cột: STT, Mã phiếu, Nhà cung cấp, Tổng tiền, Đã thanh toán, Trạng thái, Hạn thanh toán, Ghi chú, Ngày nhập, Người tạo
                    $mergeCols = ['A', 'B', 'C', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                    foreach ($mergeCols as $col) {
                        $sheet->mergeCells($col . $startRow . ':' . $col . $endRow);
                        // Căn giữa theo chiều dọc
                        $sheet->getStyle($col . $startRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }
                }

                $stt++;
            }
        }

        $lastRow = $currentRow - 1;

        // Format số có dấu phân cách nghìn
        $sheet->getStyle('F8:I' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // Borders
        $sheet->getStyle('A7:P' . $lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'P') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Phieu_nhap_kho.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
