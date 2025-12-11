<?php

namespace App\Controllers\Admin;

use App\Models\Repositories\OrderRepository;
use App\Controllers\Admin\AuthController;
use App\Services\EmailService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrderController extends BaseAdminController
{
    private $orderRepo;
    private $emailService;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->orderRepo = new OrderRepository();
        $this->emailService = new EmailService();
    }

    /** GET /admin/orders (trả về view) */
    public function index()
    {
        // Load items để truyền vào view
        $items = $this->orderRepo->all();
        return $this->view('admin/orders/order', ['items' => $items]);
    }

    /** GET /admin/api/orders (list) */
    public function apiIndex()
    {
        $items = $this->orderRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/orders/next-code */
    public function nextCode()
    {
        $code = $this->orderRepo->generateCode();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/orders (create) */
    public function store()
    {
        // Log request data để debug
        $rawInput = file_get_contents('php://input');
        error_log("=== ORDER CREATE REQUEST ===");
        error_log("Raw input: " . $rawInput);

        $data = json_decode($rawInput, true) ?? [];
        error_log("Decoded data: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $currentUser = $this->currentUserId();
        error_log("Current user ID: " . $currentUser);

        try {
            $id = $this->orderRepo->create($data, $currentUser);
            error_log("Order created successfully with ID: " . $id);

            // Lấy thông tin đơn hàng vừa tạo
            $order = $this->orderRepo->findOne($id);

            // Gửi email nếu khách hàng có email
            if (!empty($data['customer_id'])) {
                try {
                    // Lấy thông tin khách hàng
                    $customer = $this->orderRepo->getCustomerInfo($data['customer_id']);

                    if (!empty($customer['email'])) {
                        // Lấy danh sách sản phẩm trong đơn hàng
                        $items = $this->orderRepo->getOrderItems($id);

                        // Convert Order object to array for email
                        $orderArray = json_decode(json_encode($order), true);

                        // Gửi email
                        $emailResult = $this->emailService->sendOrderInvoice($orderArray, $customer, $items);
                        error_log("Email send result: " . json_encode($emailResult));
                    } else {
                        error_log("Customer has no email address");
                    }
                } catch (\Exception $e) {
                    // Log lỗi nhưng không làm fail request
                    error_log("Failed to send email: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            // Convert Order object to array before JSON encode
            $orderArray = $order ? json_decode(json_encode($order), true) : ['id' => $id];
            echo json_encode($orderArray, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            error_log("=== PDO EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi cơ sở dữ liệu',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            error_log("=== GENERAL EXCEPTION ===");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** PUT /admin/orders/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $this->orderRepo->update($id, $data, $currentUser);
            $order = $this->orderRepo->findOne($id);
            // Convert Order object to array before JSON encode
            $orderArray = $order ? json_decode(json_encode($order), true) : ['id' => $id];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($orderArray, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật đơn hàng: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** DELETE /admin/orders/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->orderRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** GET /admin/api/orders/unpaid */
    public function unpaid()
    {
        $items = $this->orderRepo->unpaid();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/orders/{id}/items */
    public function getItems($id)
    {
        $items = $this->orderRepo->getOrderItems($id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/orders/{id}/print - In hóa đơn */
    public function print($id)
    {
        $orderObj = $this->orderRepo->findOne($id);
        if (!$orderObj) {
            http_response_code(404);
            echo "Đơn hàng không tồn tại";
            exit;
        }

        // Convert object sang array để sử dụng trong view
        $order = json_decode(json_encode($orderObj), true);

        // Debug: Log order data
        error_log("Order data: " . print_r($order, true));

        $items = $this->orderRepo->getOrderItems($id);

        // Gán items vào order
        $order['items'] = $items;

        return $this->view('admin/orders/invoice-template', [
            'order' => $order
        ]);
    }

    /** POST /admin/api/orders/export - Xuất Excel */
    public function export()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['orders'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Không có dữ liệu để xuất']);
            exit;
        }

        $orders = $data['orders'];
        $fromDate = $data['from_date'] ?? '';
        $toDate = $data['to_date'] ?? '';

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $exportDate = $data['export_date'] ?? $vietnamTime->format('d/m/Y H:i:s');
        $filename = $data['filename'] ?? 'Don_hang.xlsx';

        // Tạo file Excel với PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ===== HEADER =====
        // MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:P1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', "Ngày xuất file: $exportDate");
        $sheet->mergeCells('A2:P2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Khoảng thời gian
        $sheet->setCellValue('A3', "Từ ngày: $fromDate - Đến ngày: $toDate");
        $sheet->mergeCells('A3:P3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Dòng trống
        $currentRow = 4;

        // Tiêu đề bảng
        $sheet->setCellValue('A5', 'DANH SÁCH ĐƠN HÀNG');
        $sheet->mergeCells('A5:P5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== TIÊU ĐỀ CỘT =====
        $currentRow = 7;
        $headers = [
            'STT',
            'Mã đơn',
            'Khách hàng',
            'Sản phẩm',
            'Số lượng',
            'Đơn giá',
            'Trạng thái',
            'Tạm tính',
            'Chương trình khuyến mãi',
            'Giảm giá',
            'Tổng tiền',
            'PT thanh toán',
            'Địa chỉ giao',
            'Ghi chú',
            'Thời gian tạo',
            'Người tạo'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $col++;
        }

        // Style cho header
        $sheet->getStyle('A7:P7')->getFont()->setBold(true);
        $sheet->getStyle('A7:P7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7:P7')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A7:P7')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ===== DỮ LIỆU =====
        $currentRow = 8;
        $stt = 1;

        foreach ($orders as $order) {
            $items = $order['items'] ?? [];

            $startRow = $currentRow; // Lưu dòng bắt đầu để merge cells

            if (empty($items)) {
                // Nếu không có sản phẩm
                $sheet->setCellValue('A' . $currentRow, $stt++);
                $sheet->setCellValue('B' . $currentRow, $order['code'] ?? '');
                $sheet->setCellValue('C' . $currentRow, $order['customer_name'] ?? '');
                $sheet->setCellValue('D' . $currentRow, '');
                $sheet->setCellValue('E' . $currentRow, '');
                $sheet->setCellValue('F' . $currentRow, '');
                $sheet->setCellValue('G' . $currentRow, $order['status'] ?? '');
                $sheet->setCellValue('H' . $currentRow, $order['subtotal'] ?? 0);
                $sheet->setCellValue('I' . $currentRow, $order['promotion_discount'] ?? 0);
                $sheet->setCellValue('J' . $currentRow, $order['discount_amount'] ?? 0);
                $sheet->setCellValue('K' . $currentRow, $order['total_amount'] ?? 0);
                $sheet->setCellValue('L' . $currentRow, $order['payment_method'] ?? '');
                $sheet->setCellValue('M' . $currentRow, $order['shipping_address'] ?? '');
                $sheet->setCellValue('N' . $currentRow, $order['note'] ?? '');
                $sheet->setCellValue('O' . $currentRow, $order['created_at'] ?? '');
                $sheet->setCellValue('P' . $currentRow, $order['created_by_name'] ?? '');

                $currentRow++;
            } else {
                // Xuất nhiều dòng cho mỗi sản phẩm
                foreach ($items as $idx => $item) {
                    // Chỉ ghi thông tin đơn hàng ở dòng đầu tiên
                    if ($idx === 0) {
                        $sheet->setCellValue('A' . $currentRow, $stt);
                        $sheet->setCellValue('B' . $currentRow, $order['code'] ?? '');
                        $sheet->setCellValue('C' . $currentRow, $order['customer_name'] ?? '');
                        $sheet->setCellValue('G' . $currentRow, $order['status'] ?? '');
                        $sheet->setCellValue('H' . $currentRow, $order['subtotal'] ?? 0);
                        $sheet->setCellValue('I' . $currentRow, $order['promotion_discount'] ?? 0);
                        $sheet->setCellValue('J' . $currentRow, $order['discount_amount'] ?? 0);
                        $sheet->setCellValue('K' . $currentRow, $order['total_amount'] ?? 0);
                        $sheet->setCellValue('L' . $currentRow, $order['payment_method'] ?? '');
                        $sheet->setCellValue('M' . $currentRow, $order['shipping_address'] ?? '');
                        $sheet->setCellValue('N' . $currentRow, $order['note'] ?? '');
                        $sheet->setCellValue('O' . $currentRow, $order['created_at'] ?? '');
                        $sheet->setCellValue('P' . $currentRow, $order['created_by_name'] ?? '');
                    }

                    // Thông tin sản phẩm (ghi ở mọi dòng)
                    $sheet->setCellValue('D' . $currentRow, $item['product_name'] ?? '');
                    $sheet->setCellValue('E' . $currentRow, $item['qty'] ?? 0);
                    $sheet->setCellValue('F' . $currentRow, $item['unit_price'] ?? 0);

                    $currentRow++;
                }

                // Merge cells cho các cột thông tin đơn hàng (nếu có nhiều hơn 1 sản phẩm)
                $endRow = $currentRow - 1;
                if ($endRow > $startRow) {
                    // Merge các cột: STT, Mã đơn, Khách hàng, Trạng thái, Tạm tính, Chương trình khuyến mãi, Giảm giá, Tổng tiền, PT thanh toán, Địa chỉ, Ghi chú, Thời gian, Người tạo
                    $mergeCols = ['A', 'B', 'C', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                    foreach ($mergeCols as $col) {
                        $sheet->mergeCells($col . $startRow . ':' . $col . $endRow);
                        // Căn giữa theo chiều dọc
                        $sheet->getStyle($col . $startRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    }
                }

                $stt++;
            }
        }

        // ===== FORMAT SỐ =====
        $lastRow = $currentRow - 1;
        $sheet->getStyle('E8:F' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');
        $sheet->getStyle('H8:K' . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0');

        // ===== BORDERS =====
        $sheet->getStyle('A7:P' . $lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ===== AUTO SIZE =====
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== XUẤT FILE =====
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** POST /admin/api/orders/{id}/process - Xử lý đơn hàng */
    public function processOrder($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $ghnOrderService = new \App\Support\GHNOrderService();
            $result = $ghnOrderService->processOrder((int)$id, $this->currentUserId());

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đang xử lý');

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** POST /admin/api/orders/{id}/ship-with-ghn - Giao hàng GHN */
    public function shipWithGHN($id)
    {
        // Suppress all output
        ob_start();

        header('Content-Type: application/json; charset=utf-8');
        try {
            error_log("=== SHIP WITH GHN START === Order ID: $id");

            // Clear any previous output
            ob_clean();

            $ghnOrderService = new \App\Support\GHNOrderService();
            $result = $ghnOrderService->shipWithGHN((int)$id, $this->currentUserId());

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đang giao hàng');

            error_log("=== SHIP WITH GHN SUCCESS ===");

            // Ensure clean output
            ob_clean();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log("=== SHIP WITH GHN ERROR ===");
            error_log("Message: " . $e->getMessage());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());

            http_response_code(400);

            // Clear any output before error
            ob_clean();
            echo json_encode([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], JSON_UNESCAPED_UNICODE);
        }

        ob_end_flush();
        exit;
    }

    /** POST /admin/api/orders/{id}/cancel - Hủy đơn hàng */
    public function cancelOrder($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $reason = $data['reason'] ?? '';

        try {
            $ghnOrderService = new \App\Support\GHNOrderService();
            $result = $ghnOrderService->cancelOrder((int)$id, $this->currentUserId(), $reason);

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đã hủy');

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** GET /admin/api/orders/{id}/tracking - Tracking GHN */
    public function getTracking($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $ghnOrderService = new \App\Support\GHNOrderService();
            $result = $ghnOrderService->getTrackingInfo((int)$id);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** POST /admin/api/orders/{id}/manual-ship - Chuyển sang Đang giao thủ công */
    public function manualShip($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = \App\Core\DB::pdo()->prepare("
                UPDATE orders 
                SET status = 'Đang giao', 
                    updated_at = NOW(), 
                    updated_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$this->currentUserId(), $id]);

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đang giao hàng');

            echo json_encode([
                'success' => true,
                'message' => 'Đã chuyển trạng thái sang "Đang giao" (Thủ công)'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    private function sendOrderStatusNotification($orderId, $statusText)
    {
        try {
            $stmt = \App\Core\DB::pdo()->prepare("SELECT user_id, code FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if ($order && $order['user_id']) {
                \App\Support\NotificationHelper::send(
                    (int)$order['user_id'],
                    'Cập nhật trạng thái đơn hàng',
                    "Đơn hàng {$order['code']} của bạn đã được chuyển sang trạng thái: $statusText",
                    '/profile?tab=orders&open=' . $orderId,
                    'order_status'
                );
            }
        } catch (\Throwable $e) {
            error_log("Notification Error: " . $e->getMessage());
        }
    }

    /** POST /admin/api/orders/{id}/manual-complete - Hoàn tất thủ công */
    public function manualComplete($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = $this->currentUserId();
            $pdo = \App\Core\DB::pdo();

            // 1. Get Order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new \Exception('Đơn hàng không tồn tại');
            }

            if ($order['status'] === 'Hoàn thành' || $order['status'] === 'Đã hủy') {
                throw new \Exception('Không thể thay đổi trạng thái đơn hàng đã kết thúc');
            }

            $pdo->beginTransaction();

            // 2. Update Order Status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'Đã giao', 
                    ghn_status = 'delivered',
                    payment_status = 'Đã thanh toán', 
                    updated_at = NOW(), 
                    updated_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $id]);

            // 3. Create Receipt Voucher (Phiếu thu)
            // Generate Code: RC + Date + Random
            $receiptCode = 'RC' . date('ymd') . rand(1000, 9999);

            $stmt = $pdo->prepare("
                INSERT INTO receipt_vouchers (
                    code, order_id, payment_id, payer_user_id, payer_name, 
                    method, amount, received_by, received_at, note, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, NOW(), ?, ?
                )
            ");

            // Get payer name
            $payerName = $order['delivery_name'] ?? 'Khách lẻ';

            $stmt->execute([
                $receiptCode,
                $id,
                $order['payment_id'],
                $order['user_id'],
                $payerName,
                $order['payment_method'], // Ensure method matches enum or fallback
                $order['grand_total'],
                $userId,
                'Hoàn tất thủ công bởi ' . $_SESSION['user']['username'],
                $userId
            ]);

            $pdo->commit();

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đã giao hàng (Hoàn tất)');

            echo json_encode(['success' => true, 'message' => 'Đã hoàn tất đơn hàng và tạo phiếu thu']);
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /** POST /admin/api/orders/{id}/manual-cancel - Hủy thủ công & Hoàn kho */
    public function manualCancel($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = $this->currentUserId();
            $pdo = \App\Core\DB::pdo();

            // 1. Get Order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new \Exception('Đơn hàng không tồn tại');
            }

            if ($order['status'] === 'Hoàn thành' || $order['status'] === 'Đã hủy') {
                throw new \Exception('Không thể hủy đơn hàng đã kết thúc');
            }

            $pdo->beginTransaction();

            // 2. Update Order Status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'Đã hủy', 
                    ghn_status = 'cancel',
                    updated_at = NOW(), 
                    updated_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $id]);

            // 3. Restock Products (Hoàn kho)
            // Get Order Items
            $stmtItem = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
            $stmtItem->execute([$id]);
            $items = $stmtItem->fetchAll();

            foreach ($items as $item) {
                // Update Stock
                $stmtStock = $pdo->prepare("
                    UPDATE stocks 
                    SET qty = qty + ?, updated_at = NOW(), updated_by = ? 
                    WHERE product_id = ?
                ");
                $stmtStock->execute([$item['qty'], $userId, $item['product_id']]);

                // Create Stock Movement Log (Trả hàng)
                $stmtLog = $pdo->prepare("
                    INSERT INTO stock_movements (
                        product_id, type, ref_type, ref_id, qty, note, created_at
                    ) VALUES (
                        ?, 'Trả hàng', 'Đơn hàng', ?, ?, ?, NOW()
                    )
                ");
                $stmtLog->execute([
                    $item['product_id'],
                    $id,
                    $item['qty'],
                    'Hủy đơn hàng thủ công'
                ]);
            }

            // Note: If GHN order exists, calling API to cancel is optional here (manual override mode)
            // But we should try if ghn_order_code exists
            if (!empty($order['ghn_order_code'])) {
                try {
                    $ghnService = new \App\Support\GHNOrderService();
                    $ghnService->cancelOrder($id, $userId, 'Hủy thủ công bởi Admin');
                } catch (\Exception $e) {
                    // Ignore GHN error in manual mode
                }
            }

            $pdo->commit();

            // Gửi thông báo
            $this->sendOrderStatusNotification($id, 'Đã hủy');

            echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng và hoàn kho']);
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /admin/api/orders/calculate-with-promotions
     * Tính toán giá trị đơn hàng với khuyến mãi tự động
     */
    public function calculateWithPromotions()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $items = $data['items'] ?? [];

            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Không có sản phẩm']);
                exit;
            }

            // Tính toán với khuyến mãi
            $result = \App\Support\PromotionHelper::calculateOrderTotal($items);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
