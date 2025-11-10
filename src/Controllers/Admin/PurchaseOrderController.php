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
     * GET /admin/purchase-orders/{id}/print
     * In phiếu nhập kho
     */
    public function print($id)
    {
        $poDetails = $this->repo->getDetailsWithLines($id);
        if (!$poDetails) {
            http_response_code(404);
            echo "Phiếu nhập không tồn tại";
            exit;
        }

        // Convert object sang array nếu cần
        $po = is_object($poDetails) ? json_decode(json_encode($poDetails), true) : $poDetails;

        return $this->view('admin/purchase-orders/invoice-template', [
            'po' => $po
        ]);
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

    /** GET /admin/api/purchase-orders/template - Tải file mẫu Excel */
    public function downloadTemplate()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu nhập');

        // Get data for reference sheets
        $pdo = \App\Core\DB::pdo();
        $suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $products = $pdo->query("SELECT id, name, sku, cost_price FROM products WHERE is_active = 1 ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);

        // ========== SHEET 1: Mẫu nhập phiếu nhập kho ==========
        $headers = [
            'STT',
            ['text' => 'ID Nhà cung cấp ', 'required' => true, 'note' => '(xem sheet NCC)'],
            ['text' => 'Ngày nhập ', 'required' => true, 'note' => '(dd/mm/yyyy)'],
            'Hạn thanh toán (dd/mm/yyyy)',
            'Số tiền đã trả',
            'Ghi chú',
            ['text' => 'ID Sản phẩm ', 'required' => true, 'note' => '(xem sheet SP)'],
            ['text' => 'Số lượng ', 'required' => true],
            ['text' => 'Đơn giá ', 'required' => true],
            'Ngày sản xuất (dd/mm/yyyy)',
            'Hạn sử dụng (dd/mm/yyyy)'
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
                    $note->getFont()->setSize(9)->getColor()->setRGB('666666');
                }
                $sheet->setCellValue($col . '1', $richText);
            } else {
                $sheet->setCellValue($col . '1', $header);
            }
            $col++;
        }

        // Style header
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('002975');
        $sheet->getStyle('A1:K1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:K1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // Sample data
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', '1');
        $sheet->setCellValue('C2', '30/10/2025');
        $sheet->setCellValue('D2', '15/11/2025');
        $sheet->setCellValue('E2', '0');
        $sheet->setCellValue('F2', 'Nhập kho tháng 10');
        $sheet->setCellValue('G2', '1');
        $sheet->setCellValue('H2', '100');
        $sheet->setCellValue('I2', '50000');
        $sheet->setCellValue('J2', '01/10/2025');
        $sheet->setCellValue('K2', '01/10/2026');

        // Borders
        $sheet->getStyle('A1:K2')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto-size columns
        foreach (range('A', 'K') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // ========== SHEET 2: Danh sách Nhà cung cấp ==========
        $supplierSheet = $spreadsheet->createSheet();
        $supplierSheet->setTitle('Nhà cung cấp');

        $supplierSheet->setCellValue('A1', 'ID');
        $supplierSheet->setCellValue('B1', 'Tên nhà cung cấp');
        $supplierSheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $supplierSheet->getStyle('A1:B1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');

        $row = 2;
        foreach ($suppliers as $supplier) {
            $supplierSheet->setCellValue('A' . $row, $supplier['id']);
            $supplierSheet->setCellValue('B' . $row, $supplier['name']);
            $row++;
        }

        $supplierSheet->getStyle('A1:B' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'B') as $c) {
            $supplierSheet->getColumnDimension($c)->setAutoSize(true);
        }

        // ========== SHEET 3: Danh sách Sản phẩm ==========
        $productSheet = $spreadsheet->createSheet();
        $productSheet->setTitle('Sản phẩm');

        $productSheet->setCellValue('A1', 'ID');
        $productSheet->setCellValue('B1', 'Mã SKU');
        $productSheet->setCellValue('C1', 'Tên sản phẩm');
        $productSheet->setCellValue('D1', 'Giá nhập');
        $productSheet->getStyle('A1:D1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $productSheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2196F3');

        $row = 2;
        foreach ($products as $product) {
            $productSheet->setCellValue('A' . $row, $product['id']);
            $productSheet->setCellValue('B' . $row, $product['sku']);
            $productSheet->setCellValue('C' . $row, $product['name']);
            $productSheet->setCellValue('D' . $row, $product['cost_price'] ?? 0);
            $row++;
        }

        $productSheet->getStyle('A1:D' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'D') as $c) {
            $productSheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Set active sheet back to first
        $spreadsheet->setActiveSheetIndex(0);

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_phieu_nhap_kho.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/purchase-orders/import - Nhập Excel */
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
            if (count($data) > 10001) {
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

            $currentUserId = $_SESSION['user']['id'] ?? null;
            $currentUserName = $_SESSION['user']['full_name'] ?? null;

            if (!$currentUserId) {
                http_response_code(401);
                echo json_encode(['error' => 'Bạn chưa đăng nhập'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Group rows by purchase order (same supplier_id, created_at, due_date)
            $purchaseOrders = [];
            
            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;

                // Skip empty rows
                $supplierId = $row[1] ?? null;
                $productId = $row[6] ?? null;
                if (!$supplierId || !$productId) continue;

                // Group key (payment_status bỏ, chỉ dựa vào paid_amount)
                $createdAt = trim($row[2] ?? '');
                $dueDate = trim($row[3] ?? '');
                $paidAmount = trim($row[4] ?? '0');
                $note = trim($row[5] ?? '');
                
                $groupKey = $supplierId . '|' . $createdAt . '|' . $dueDate . '|' . $paidAmount . '|' . $note;

                if (!isset($purchaseOrders[$groupKey])) {
                    $purchaseOrders[$groupKey] = [
                        'supplier_id' => $supplierId,
                        'created_at' => $createdAt,
                        'due_date' => $dueDate,
                        'paid_amount' => $paidAmount,
                        'note' => $note,
                        'lines' => [],
                        'rows' => []
                    ];
                }

                // Add product line
                $qty = trim($row[7] ?? '0');
                $unitCost = trim($row[8] ?? '0');
                $mfgDate = trim($row[9] ?? '');
                $expDate = trim($row[10] ?? '');

                $purchaseOrders[$groupKey]['lines'][] = [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'mfg_date' => $mfgDate,
                    'exp_date' => $expDate,
                    'row_number' => $rowNumber
                ];

                $purchaseOrders[$groupKey]['rows'][] = $rowNumber;
            }

            // Process each purchase order
            foreach ($purchaseOrders as $groupKey => $poData) {
                $rowNumbers = implode(', ', $poData['rows']);
                $rowData = [
                    'rows' => $rowNumbers,
                    'supplier_id' => $poData['supplier_id'],
                    'created_at' => $poData['created_at'],
                    'due_date' => $poData['due_date'],
                    'paid_amount' => $poData['paid_amount'],
                    'note' => $poData['note'],
                    'products_count' => count($poData['lines'])
                ];

                // Validate
                $rowErrors = [];

                // 1. Supplier ID bắt buộc và phải tồn tại
                if (!$poData['supplier_id']) {
                    $rowErrors[] = 'ID Nhà cung cấp là bắt buộc';
                } else {
                    $supplier = $this->supplierRepo->findOne($poData['supplier_id']);
                    if (!$supplier) {
                        $rowErrors[] = "Nhà cung cấp ID {$poData['supplier_id']} không tồn tại";
                    }
                }

                // 2. Ngày nhập bắt buộc và đúng định dạng dd/mm/yyyy
                if (!$poData['created_at']) {
                    $rowErrors[] = 'Ngày nhập là bắt buộc';
                } elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $poData['created_at'])) {
                    $rowErrors[] = 'Ngày nhập phải có định dạng dd/mm/yyyy';
                }

                // 3. Hạn thanh toán (nếu có) phải đúng định dạng
                if ($poData['due_date'] && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $poData['due_date'])) {
                    $rowErrors[] = 'Hạn thanh toán phải có định dạng dd/mm/yyyy';
                }

                // 4. Số tiền đã trả phải là số
                if (!is_numeric($poData['paid_amount']) || $poData['paid_amount'] < 0) {
                    $rowErrors[] = 'Số tiền đã trả phải là số >= 0';
                }

                // 5. Tính tổng tiền và validate số tiền đã trả
                $totalAmount = 0;
                foreach ($poData['lines'] as $line) {
                    $totalAmount += (float)$line['qty'] * (float)$line['unit_cost'];
                }
                
                if ($poData['paid_amount'] > $totalAmount) {
                    $rowErrors[] = 'Số tiền đã trả (' . number_format($poData['paid_amount'], 0, ',', '.') . ' đ) không được lớn hơn tổng tiền (' . number_format($totalAmount, 0, ',', '.') . ' đ)';
                }

                // 6. Validate từng dòng sản phẩm
                foreach ($poData['lines'] as $lineIdx => $line) {
                    $lineErrors = [];

                    if (!$line['product_id']) {
                        $lineErrors[] = 'ID Sản phẩm là bắt buộc';
                    } else {
                        $product = $this->productRepo->findOne($line['product_id']);
                        if (!$product) {
                            $lineErrors[] = "Sản phẩm ID {$line['product_id']} không tồn tại";
                        }
                    }

                    if (!is_numeric($line['qty']) || $line['qty'] <= 0) {
                        $lineErrors[] = 'Số lượng phải là số > 0';
                    }

                    if (!is_numeric($line['unit_cost']) || $line['unit_cost'] < 0) {
                        $lineErrors[] = 'Đơn giá phải là số >= 0';
                    }

                    if ($line['mfg_date'] && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $line['mfg_date'])) {
                        $lineErrors[] = 'Ngày sản xuất phải có định dạng dd/mm/yyyy';
                    }

                    if ($line['exp_date'] && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $line['exp_date'])) {
                        $lineErrors[] = 'Hạn sử dụng phải có định dạng dd/mm/yyyy';
                    }

                    if (!empty($lineErrors)) {
                        $rowErrors[] = "Dòng " . $line['row_number'] . " - Sản phẩm: " . implode('; ', $lineErrors);
                    }
                }

                // Nếu có lỗi
                if (!empty($rowErrors)) {
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = implode('; ', $rowErrors);
                    $errors[] = "Dòng $rowNumbers: " . implode('; ', $rowErrors);
                    $fileContent[] = $rowData;
                    $failedCount++;
                    continue;
                }

                // Tạo purchase order
                try {
                    // Prepare lines data for createReceipt
                    $lines = [];
                    foreach ($poData['lines'] as $line) {
                        $lines[] = [
                            'product_id' => $line['product_id'],
                            'qty' => (int)$line['qty'],
                            'unit_cost' => (float)$line['unit_cost'],
                            'mfg_date' => $line['mfg_date'],
                            'exp_date' => $line['exp_date'],
                            'batches' => [[
                                'qty' => (int)$line['qty'],
                                'unit_cost' => (float)$line['unit_cost'],
                                'note' => 'Nhập từ Excel'
                            ]]
                        ];
                    }

                    $receiptData = [
                        'supplier_id' => $poData['supplier_id'],
                        'created_at' => $poData['created_at'],
                        'due_date' => $poData['due_date'] ?: null,
                        'paid_amount' => (float)$poData['paid_amount'],
                        'note' => $poData['note'],
                        'lines' => $lines
                    ];

                    $poId = $this->repo->createReceipt($receiptData, $currentUserId);

                    $rowData['status'] = 'success';
                    $rowData['id'] = $poId;
                    $fileContent[] = $rowData;
                    $successCount++;
                } catch (\PDOException $e) {
                    $errorMessage = 'Lỗi database: ' . $e->getMessage();
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $errorMessage;
                    $errors[] = "Dòng $rowNumbers: " . $errorMessage;
                    $fileContent[] = $rowData;
                    $failedCount++;
                } catch (\Exception $e) {
                    $errorMessage = 'Lỗi: ' . $e->getMessage();
                    $rowData['status'] = 'failed';
                    $rowData['errors'] = $errorMessage;
                    $errors[] = "Dòng $rowNumbers: " . $errorMessage;
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
                'purchase_orders',
                $file['name'],
                $successCount,
                $failedCount,
                $status,
                $errors,
                $fileContent
            );

            // Tạo message
            $message = "Nhập thành công $successCount phiếu nhập kho";
            if ($failedCount > 0) {
                $firstError = !empty($errors) ? $errors[0] : '';
                $message = "Nhập thành công $successCount/" . ($successCount + $failedCount) . " phiếu nhập kho. Lỗi đầu tiên: $firstError";
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
            'imported_by' => $_SESSION['user']['id'] ?? null,
            'imported_by_name' => $_SESSION['user']['full_name'] ?? null
        ]);
    }
}
