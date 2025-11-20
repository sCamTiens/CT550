<?php

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Repositories\ReportsRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportsController extends BaseAdminController
{
    private $reportsRepo;

    public function __construct()
    {
        parent::__construct();
        $this->reportsRepo = new ReportsRepository();
    }

    /** GET /admin/reports - View trang thống kê */
    public function index()
    {
        return $this->view('admin/reports/reports');
    }

    /** GET /admin/api/reports/overview - Tổng quan */
    public function apiOverview()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;

        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = [
            'totalRevenue' => $this->reportsRepo->getTotalRevenue($fromDate, $toDate),
            'totalOrders' => $this->reportsRepo->getTotalOrders($fromDate, $toDate),
            'totalCountExpenses' => $this->reportsRepo->getCountExpense($fromDate, $toDate),
            'avgOrderValue' => 0,
            'totalExpenses' => $this->reportsRepo->getTotalExpenses($fromDate, $toDate),
            'totalProductsSold' => $this->reportsRepo->getTotalProductsSold($fromDate, $toDate)
        ];

        if ($data['totalOrders'] > 0) {
            $data['avgOrderValue'] = $data['totalRevenue'] / $data['totalOrders'];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/reports/staff/orders */
    public function apiStaffByOrders()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getStaffByOrders($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/staff/revenue */
    public function apiStaffByRevenue()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getStaffByRevenue($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/products/quantity */
    public function apiProductsByQuantity()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getProductsByQuantity($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/products/revenue */
    public function apiProductsByRevenue()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getProductsByRevenue($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/suppliers */
    public function apiSuppliers()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getSuppliers($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/customers/spenders */
    public function apiCustomersBySpending()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getCustomersBySpending($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/customers/orders */
    public function apiCustomersByOrders()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = $this->reportsRepo->getCustomersByOrders($fromDate, $toDate);
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/inventory/low-stock */
    public function apiLowStock()
    {
        $data = $this->reportsRepo->getLowStock();
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/inventory/high-stock */
    public function apiHighStock()
    {
        $data = $this->reportsRepo->getHighStock();
        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/revenue-chart */
    public function apiRevenueChart()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $rows = $this->reportsRepo->getRevenueChartData($fromDate, $toDate);

        $labels = array_map(fn($r) => date('d/m', strtotime($r['date'])), $rows);
        $values = array_map(fn($r) => (float) $r['revenue'], $rows);

        $this->jsonResponse(['labels' => $labels, 'values' => $values]);
    }

    /** GET /admin/api/reports/order-status */
    public function apiOrderStatus()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $rows = $this->reportsRepo->getOrderStatusData($fromDate, $toDate);

        $statusMap = [
            'Chờ xử lý' => 'Chờ xử lý',
            'Đang xử lý' => 'Đang xử lý',
            'Hoàn tất' => 'Hoàn tất',
            'Đã hủy' => 'Đã hủy'
        ];

        $labels = array_map(fn($r) => $statusMap[$r['status']] ?? $r['status'], $rows);
        $values = array_map(fn($r) => (int) $r['count'], $rows);

        $this->jsonResponse(['labels' => $labels, 'values' => $values]);
    }

    /** GET /admin/api/reports/filter */
    public function apiFilter()
    {
        $reportType = $_GET['report_type'] ?? 'staff';
        $criteria = $_GET['criteria'] ?? 'revenue';
        $searchText = $_GET['search'] ?? '';

        $staffId = $_GET['staff_id'] ?? null;
        $productId = $_GET['product_id'] ?? null;
        $customerId = $_GET['customer_id'] ?? null;
        $supplierId = $_GET['supplier_id'] ?? null;

        $valueFrom = $_GET['value_from'] ?? null;
        $valueTo = $_GET['value_to'] ?? null;
        $sortOrder = $_GET['sort_order'] ?? 'desc';
        $fromDate = isset($_GET['from_date']) ? $this->convertDate($_GET['from_date']) : null;
        $toDate = isset($_GET['to_date']) ? $this->convertDate($_GET['to_date']) : null;

        $data = [];

        try {
            switch ($reportType) {
                case 'staff':
                    $data = $this->reportsRepo->filterStaff($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId, $customerId);
                    break;
                case 'products':
                    $data = $this->reportsRepo->filterProducts($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $customerId, $supplierId);
                    break;
                case 'customers':
                    $data = $this->reportsRepo->filterCustomers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId);
                    break;
                case 'suppliers':
                    // filterSuppliers() is declared void in the repository; do not assign its result.
                    // Call it to allow any internal processing, then provide a safe fallback.
                    $this->reportsRepo->filterSuppliers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId);
                    $data = [];
                    break;
                case 'orders':
                    $data = $this->reportsRepo->filterOrders($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId, $customerId);
                    break;
                case 'inventory':
                    $data = $this->reportsRepo->filterInventory($criteria, $searchText, $fromDate, $toDate, $productId);
                    break;
            }

            $responsePayload = [
                'success' => true,
                'data' => $data
            ];

            // Optional debug counts when client requests them (useful to verify why
            // aggregated endpoints return empty when filtering by product).
            // Call with `include_counts=1` and `product_id` in the query string.
            if (isset($_GET['include_counts']) && $_GET['include_counts'] == '1' && $productId) {
                try {
                    $pdo = DB::pdo();
                    $sql = "SELECT COUNT(*) AS items_count, COUNT(DISTINCT o.user_id) AS customers_count, COUNT(DISTINCT o.created_by) AS staff_count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = :pid AND o.status = 'Hoàn tất'";
                    if ($fromDate) $sql .= " AND DATE(o.created_at) >= '{$fromDate}'";
                    if ($toDate) $sql .= " AND DATE(o.created_at) <= '{$toDate}'";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':pid' => $productId]);
                    $counts = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $responsePayload['debug_counts'] = $counts ?: ['items_count' => 0, 'customers_count' => 0, 'staff_count' => 0];
                } catch (\Exception $e) {
                    // swallow debug errors but include a message
                    $responsePayload['debug_counts_error'] = $e->getMessage();
                }
            }

            // If client requested counts and there are matching order_items but
            // the main repository returned empty `data`, provide a fallback
            // grouped result so the frontend can still render pie charts.
            if (isset($responsePayload['debug_counts']) && $productId && empty($responsePayload['data'])) {
                $counts = $responsePayload['debug_counts'];
                if (!empty($counts['items_count']) && (int)$counts['items_count'] > 0) {
                    try {
                        $pdo = DB::pdo();
                        if ($reportType === 'customers') {
                            $sql = "SELECT u.id as user_id, u.full_name, u.email, COUNT(o.id) as total_orders, SUM(o.grand_total) as total_spent
                                    FROM users u
                                    JOIN orders o ON u.id = o.user_id
                                    JOIN order_items oi ON oi.order_id = o.id
                                    WHERE oi.product_id = :pid AND o.status = 'Hoàn tất'";
                            if ($fromDate) $sql .= " AND DATE(o.created_at) >= '{$fromDate}'";
                            if ($toDate) $sql .= " AND DATE(o.created_at) <= '{$toDate}'";
                            $sql .= " GROUP BY u.id ORDER BY total_spent DESC LIMIT 10";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':pid' => $productId]);
                            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                            if ($rows) {
                                $responsePayload['data'] = $rows;
                                $responsePayload['raw_rows'] = $rows;
                            }
                        } elseif ($reportType === 'staff') {
                            $sql = "SELECT o.created_by as staff_id, u.full_name, sp.staff_role, COUNT(o.id) as total_orders, SUM(o.grand_total) as total_revenue
                                    FROM orders o
                                    JOIN users u ON o.created_by = u.id
                                    LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                                    JOIN order_items oi ON oi.order_id = o.id
                                    WHERE oi.product_id = :pid AND o.status = 'Hoàn tất'";
                            if ($fromDate) $sql .= " AND DATE(o.created_at) >= '{$fromDate}'";
                            if ($toDate) $sql .= " AND DATE(o.created_at) <= '{$toDate}'";
                            $sql .= " GROUP BY o.created_by ORDER BY total_revenue DESC LIMIT 10";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':pid' => $productId]);
                            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                            if ($rows) {
                                $responsePayload['data'] = $rows;
                                $responsePayload['raw_rows'] = $rows;
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore fallback errors but include note
                        $responsePayload['fallback_error'] = $e->getMessage();
                    }
                }
            }

            $this->jsonResponse($responsePayload);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /** GET /admin/api/reports/export */
    public function apiExport()
    {
        $reportType = $_GET['report_type'] ?? 'staff';
        $criteria = $_GET['criteria'] ?? 'revenue';
        $searchText = $_GET['search'] ?? '';

        $staffId = $_GET['staff_id'] ?? null;
        $productId = $_GET['product_id'] ?? null;
        $customerId = $_GET['customer_id'] ?? null;
        $supplierId = $_GET['supplier_id'] ?? null;

        $valueFrom = $_GET['value_from'] ?? null;
        $valueTo = $_GET['value_to'] ?? null;
        $sortOrder = $_GET['sort_order'] ?? 'desc';
        $fromDate = isset($_GET['from_date']) ? $this->convertDate($_GET['from_date']) : null;
        $toDate = isset($_GET['to_date']) ? $this->convertDate($_GET['to_date']) : null;

        try {
            $data = [];
            switch ($reportType) {
                case 'staff':
                    $data = $this->reportsRepo->filterStaff($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId, $customerId);
                    break;
                case 'products':
                    $data = $this->reportsRepo->filterProducts($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $customerId, $supplierId);
                    break;
                case 'customers':
                    $data = $this->reportsRepo->filterCustomers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId);
                    break;
                case 'suppliers':
                    $data = $this->reportsRepo->filterSuppliers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId);
                    break;
                case 'orders':
                    $data = $this->reportsRepo->filterOrders($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId, $customerId);
                    break;
                case 'inventory':
                    $data = $this->reportsRepo->filterInventory($criteria, $searchText, $fromDate, $toDate, $productId);
                    break;
            }

            $this->exportToExcel($data, $reportType, $criteria, $_GET['from_date'] ?? null, $_GET['to_date'] ?? null, $productId, $customerId, $staffId, $supplierId);

        } catch (\Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi xuất Excel: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** GET /admin/api/reports/staff-list */
    public function apiStaffList()
    {
        try {
            $data = $this->reportsRepo->getStaffList();
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /** GET /admin/api/reports/product-list */
    public function apiProductList()
    {
        try {
            $data = $this->reportsRepo->getProductList();
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /** GET /admin/api/reports/customer-list */
    public function apiCustomerList()
    {
        try {
            $data = $this->reportsRepo->getCustomerList();
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /** GET /admin/api/reports/supplier-list */
    public function apiSupplierList()
    {
        try {
            $data = $this->reportsRepo->getSupplierList();
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'data' => []]);
        }
    }

    // ==================== HELPER METHODS ====================

    private function convertDate($ddmmyyyy)
    {
        $parts = explode('/', $ddmmyyyy);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }

    private function jsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function exportToExcel($data, $reportType, $criteria, $fromDateStr = null, $toDateStr = null, $productId = null, $customerId = null, $staffId = null, $supplierId = null)
    {
        $headers = $this->getExcelHeaders($reportType, $productId, $customerId, $staffId, $supplierId);
        $title = strtoupper($this->getReportTitle($reportType, $criteria));

        $dateRange = '';
        if ($fromDateStr || $toDateStr) {
            if ($fromDateStr && $toDateStr) {
                $dateRange = "TỪ NGÀY {$fromDateStr} ĐẾN NGÀY {$toDateStr}";
            } elseif ($fromDateStr) {
                $dateRange = "TỪ NGÀY {$fromDateStr}";
            } elseif ($toDateStr) {
                $dateRange = "ĐẾN NGÀY {$toDateStr}";
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Thống kê');

        $row = 1;
        $lastColumn = chr(64 + count($headers));

        // Title
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '002975']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;

        // Export date
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", 'Ngày xuất: ' . date('d/m/Y H:i:s'));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $row++;

        // Date range
        if (!empty($dateRange)) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", $dateRange);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $row++;
        }

        $row++;

        // Headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002975']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            $sheet->getColumnDimension($col)->setWidth(20);
            $col++;
        }
        $row++;

        // Data rows
        foreach ($data as $dataRow) {
            $col = 'A';
            foreach (array_keys($headers) as $key) {
                $value = $dataRow[$key] ?? '';

                if (in_array($key, ['total_revenue', 'total_spent', 'total_sales_value', 'total_purchase_value', 'avg_order_value', 'total_amount'])) {
                    if (is_numeric($value)) {
                        $sheet->setCellValue("{$col}{$row}", (float) $value);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0 ₫');
                    } else {
                        $sheet->setCellValue("{$col}{$row}", $value);
                    }
                } else if ($key === 'created_at' && !empty($value)) {
                    $sheet->setCellValue("{$col}{$row}", date('d/m/Y H:i', strtotime($value)));
                } else if (in_array($key, ['total_orders', 'total_quantity', 'total_purchases', 'current_stock'])) {
                    if (is_numeric($value)) {
                        $sheet->setCellValue("{$col}{$row}", (int) $value);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    } else {
                        $sheet->setCellValue("{$col}{$row}", $value);
                    }
                } else {
                    $sheet->setCellValue("{$col}{$row}", $value);
                }

                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);

                $col++;
            }
            $row++;
        }

        // Thêm dòng tổng
        if (count($data) > 0) {
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", 'TỔNG CỘNG');
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '002975']]]
            ]);

            $col = 'C';
            $headerKeys = array_keys($headers);
            for ($i = 2; $i < count($headerKeys); $i++) {
                $key = $headerKeys[$i];
                
                // Chỉ tính tổng cho các cột số
                if (in_array($key, ['total_revenue', 'total_spent', 'total_sales_value', 'total_purchase_value', 'total_amount', 'total_orders', 'total_quantity', 'total_purchases', 'current_stock'])) {
                    $total = array_sum(array_column($data, $key));
                    
                    if (in_array($key, ['total_revenue', 'total_spent', 'total_sales_value', 'total_purchase_value', 'total_amount'])) {
                        $sheet->setCellValue("{$col}{$row}", (float) $total);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0 ₫');
                    } else {
                        $sheet->setCellValue("{$col}{$row}", (int) $total);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    }
                    
                    $sheet->getStyle("{$col}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '002975']]]
                    ]);
                } else {
                    $sheet->setCellValue("{$col}{$row}", '');
                    $sheet->getStyle("{$col}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '002975']]]
                    ]);
                }
                
                $col++;
            }
        }

        foreach (range('A', chr(64 + count($headers))) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="thong-ke-' . $reportType . '-' . date('Y-m-d-His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function getExcelHeaders($reportType, $productId = null, $customerId = null, $staffId = null, $supplierId = null)
    {
        $baseHeaders = [
            'staff' => [
                'full_name' => 'Tên nhân viên',
                'staff_role' => 'Chức vụ',
                'total_revenue' => 'Doanh thu',
                'total_orders' => 'Số đơn',
                'avg_order_value' => 'Giá trị TB'
            ],
            'products' => [
                'name' => 'Tên sản phẩm',
                'sku' => 'SKU',
                'total_revenue' => 'Doanh thu',
                'total_quantity' => 'Số lượng',
                'unit_name' => 'Đơn vị'
            ],
            'customers' => [
                'full_name' => 'Tên khách hàng',
                'email' => 'Email',
                'total_spent' => 'Tổng chi tiêu',
                'total_orders' => 'Số đơn',
                'avg_order_value' => 'Giá trị TB'
            ],
            'suppliers' => [
                'supplier_name' => 'Tên nhà cung cấp',
                'total_sales_value' => 'Doanh thu bán',
                'total_purchase_value' => 'Giá trị nhập',
                'total_purchases' => 'Số lần nhập'
            ],
            'orders' => [
                'order_id' => 'Mã đơn',
                'customer_name' => 'Khách hàng',
                'total_amount' => 'Tổng tiền',
                'status' => 'Trạng thái',
                'created_at' => 'Ngày tạo'
            ],
            'inventory' => [
                'name' => 'Tên sản phẩm',
                'sku' => 'SKU',
                'current_stock' => 'Tồn kho',
                'unit_name' => 'Đơn vị'
            ]
        ];

        $headers = $baseHeaders[$reportType] ?? [];

        $additionalHeaders = [];

        if ($productId && $reportType !== 'products') {
            $additionalHeaders['product_name'] = 'Sản phẩm';
        }

        if ($customerId && $reportType !== 'customers') {
            $additionalHeaders['customer_name'] = 'Khách hàng';
        }

        if ($staffId && $reportType !== 'staff') {
            $additionalHeaders['staff_name'] = 'Nhân viên';
        }

        if ($supplierId && $reportType !== 'suppliers') {
            $additionalHeaders['supplier_name'] = 'Nhà cung cấp';
        }

        if (!empty($additionalHeaders)) {
            $firstKey = array_key_first($headers);
            $firstHeader = [$firstKey => $headers[$firstKey]];
            unset($headers[$firstKey]);
            $headers = array_merge($firstHeader, $additionalHeaders, $headers);
        }

        return $headers;
    }

    private function getReportTitle($reportType, $criteria)
    {
        $typeLabels = [
            'staff' => 'NHÂN VIÊN',
            'products' => 'SẢN PHẨM',
            'customers' => 'KHÁCH HÀNG',
            'suppliers' => 'NHÀ CUNG CẤP',
            'orders' => 'ĐƠN HÀNG',
            'inventory' => 'TỒN KHO'
        ];

        $criteriaLabels = [
            'revenue' => 'DOANH THU',
            'orders' => 'SỐ ĐƠN HÀNG',
            'quantity' => 'SỐ LƯỢNG BÁN',
            'total_spent' => 'TỔNG CHI TIÊU',
            'sales_value' => 'DOANH THU BÁN',
            'purchase_value' => 'GIÁ TRỊ NHẬP',
            'purchases' => 'SỐ LẦN NHẬP',
            'total' => 'TỔNG GIÁ TRỊ',
            'status' => 'THEO TRẠNG THÁI',
            'low_stock' => 'SẮP HẾT HÀNG',
            'high_stock' => 'TỒN KHO CAO',
            'out_of_stock' => 'HẾT HÀNG',
            'avg_order_value' => 'GIÁ TRỊ TB'
        ];

        return 'THỐNG KÊ ' . ($typeLabels[$reportType] ?? '') . ' - ' . ($criteriaLabels[$criteria] ?? '');
    }
}
