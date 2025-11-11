<?php

namespace App\Controllers\Admin;

use App\Core\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReportsController extends BaseAdminController
{
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

        // Convert dd/mm/yyyy to yyyy-mm-dd
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $data = [
            'totalRevenue' => $this->getTotalRevenue($fromDate, $toDate),
            'totalOrders' => $this->getTotalOrders($fromDate, $toDate),
            'totalCountExpenses' => $this->getCountExpense($fromDate, $toDate),
            'avgOrderValue' => 0,
            'totalExpenses' => $this->getTotalExpenses($fromDate, $toDate),
            'totalProductsSold' => $this->getTotalProductsSold($fromDate, $toDate)
        ];

        if ($data['totalOrders'] > 0) {
            $data['avgOrderValue'] = $data['totalRevenue'] / $data['totalOrders'];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/reports/staff/orders - Top nhân viên theo số đơn */
    public function apiStaffByOrders()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY o.created_by
                  ORDER BY total_orders DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/staff/revenue - Top nhân viên theo doanh thu */
    public function apiStaffByRevenue()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY o.created_by
                  ORDER BY total_revenue DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/products/quantity - Top sản phẩm bán chạy */
    public function apiProductsByQuantity()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    u.name as unit_name,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.line_total) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY p.id
                  ORDER BY total_quantity DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/products/revenue - Top sản phẩm theo doanh thu */
    public function apiProductsByRevenue()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    u.name as unit_name,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.line_total) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY p.id
                  ORDER BY total_revenue DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/suppliers - Thống kê nhà cung cấp */
    public function apiSuppliers()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();

        // Build WHERE conditions for date filtering
        $poDateCondition = "";
        if ($fromDate || $toDate) {
            $conditions = [];
            if ($fromDate)
                $conditions[] = "DATE(po.created_at) >= '$fromDate'";
            if ($toDate)
                $conditions[] = "DATE(po.created_at) <= '$toDate'";
            if (!empty($conditions)) {
                $poDateCondition = " AND (" . implode(" AND ", $conditions) . ")";
            }
        }

        $sql = "SELECT 
                    s.id as supplier_id,
                    s.name as supplier_name,
                    s.phone as contact_person,
                    COUNT(DISTINCT po.id) as total_purchases,
                    COALESCE(SUM(po.total_amount), 0) as total_purchase_value,
                    COALESCE(SUM(sales.revenue), 0) as total_sales_value,
                    CASE 
                        WHEN SUM(po.total_amount) > 0 
                        THEN ROUND((COALESCE(SUM(sales.revenue), 0) / SUM(po.total_amount)) * 100, 2)
                        ELSE 0 
                    END as efficiency
                FROM suppliers s
                LEFT JOIN purchase_orders po ON s.id = po.supplier_id" . $poDateCondition;

        // Doanh thu từ sản phẩm: order_items -> stock_out_items -> product_batches -> purchase_orders -> suppliers
        $orderDateCondition = "";
        if ($fromDate || $toDate) {
            $conditions = [];
            if ($fromDate)
                $conditions[] = "DATE(o.created_at) >= '$fromDate'";
            if ($toDate)
                $conditions[] = "DATE(o.created_at) <= '$toDate'";
            if (!empty($conditions)) {
                $orderDateCondition = " AND " . implode(" AND ", $conditions);
            }
        }

        $sql .= " LEFT JOIN (
                        SELECT 
                            po_inner.supplier_id,
                            SUM(oi.line_total) as revenue
                        FROM order_items oi
                        JOIN orders o ON oi.order_id = o.id
                        JOIN stock_outs so ON o.id = so.order_id
                        JOIN stock_out_items soi ON so.id = soi.stock_out_id 
                            AND soi.product_id = oi.product_id
                        LEFT JOIN product_batches pb ON soi.batch_id = pb.id
                        LEFT JOIN purchase_orders po_inner ON pb.purchase_order_id = po_inner.id
                        WHERE o.status = 'Hoàn tất'
                        AND so.type = 'sale'
                        AND so.status IN ('approved', 'completed')"
            . $orderDateCondition . "
                        GROUP BY po_inner.supplier_id
                    ) sales ON s.id = sales.supplier_id
                  GROUP BY s.id
                  HAVING total_purchases > 0
                  ORDER BY total_purchase_value DESC";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/customers/spenders - Top khách hàng chi tiêu */
    public function apiCustomersBySpending()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent
                FROM users u
                JOIN orders o ON u.id = o.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY u.id
                  ORDER BY total_spent DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/customers/orders - Top khách hàng theo đơn hàng */
    public function apiCustomersByOrders()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent
                FROM users u
                JOIN orders o ON u.id = o.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY u.id
                  ORDER BY total_orders DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/inventory/low-stock - Sản phẩm sắp hết */
    public function apiLowStock()
    {
        $db = $this->getDB();
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    COALESCE(s.safety_stock, 10) as min_stock,
                    s.qty as current_stock,
                    u.name as unit_name
                FROM products p
                JOIN stocks s ON p.id = s.product_id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE s.qty <= COALESCE(s.safety_stock, 10)
                ORDER BY s.qty ASC
                LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/inventory/high-stock - Sản phẩm tồn kho cao */
    public function apiHighStock()
    {
        $db = $this->getDB();
        // High stock: qty >= safety_stock * 5 (nếu không có safety_stock thì dùng 10 * 5 = 50)
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    COALESCE(s.safety_stock, 10) * 5 as max_stock,
                    s.qty as current_stock,
                    u.name as unit_name
                FROM products p
                JOIN stocks s ON p.id = s.product_id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE s.qty >= COALESCE(s.safety_stock, 10) * 5
                AND s.qty > 0
                ORDER BY s.qty DESC
                LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        $this->jsonResponse(['data' => $data]);
    }

    /** GET /admin/api/reports/revenue-chart - Dữ liệu biểu đồ doanh thu */
    public function apiRevenueChart()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    DATE(created_at) as date,
                    SUM(grand_total) as revenue
                FROM orders
                WHERE status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(created_at) <= '$toDate'";

        $sql .= " GROUP BY DATE(created_at)
                  ORDER BY date ASC";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $labels = array_map(function ($r) {
            return date('d/m', strtotime($r['date']));
        }, $rows);
        $values = array_map(function ($r) {
            return (float) $r['revenue'];
        }, $rows);

        $this->jsonResponse(['labels' => $labels, 'values' => $values]);
    }

    /** GET /admin/api/reports/order-status - Thống kê trạng thái đơn hàng */
    public function apiOrderStatus()
    {
        $from = $_GET['from_date'] ?? null;
        $to = $_GET['to_date'] ?? null;
        $fromDate = $from ? $this->convertDate($from) : null;
        $toDate = $to ? $this->convertDate($to) : null;

        $db = $this->getDB();
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM orders
                WHERE 1=1";

        if ($fromDate)
            $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(created_at) <= '$toDate'";

        $sql .= " GROUP BY status";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $statusMap = [
            'Chờ xử lý' => 'Chờ xử lý',
            'Đang xử lý' => 'Đang xử lý',
            'Hoàn tất' => 'Hoàn tất',
            'Đã hủy' => 'Đã hủy'
        ];

        $labels = array_map(function ($r) use ($statusMap) {
            return $statusMap[$r['status']] ?? $r['status'];
        }, $rows);
        $values = array_map(function ($r) {
            return (int) $r['count'];
        }, $rows);

        $this->jsonResponse(['labels' => $labels, 'values' => $values]);
    }

    // Helper methods
    private function getTotalRevenue($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(grand_total) as total FROM orders WHERE status = 'Hoàn tất'";
        if ($from)
            $sql .= " AND DATE(created_at) >= '$from'";
        if ($to)
            $sql .= " AND DATE(created_at) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float) ($row['total'] ?? 0);
    }

    private function getTotalOrders($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'Hoàn tất'";
        if ($from)
            $sql .= " AND DATE(created_at) >= '$from'";
        if ($to)
            $sql .= " AND DATE(created_at) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    private function getCountExpense($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM expense_vouchers WHERE 1=1";
        if ($from)
            $sql .= " AND DATE(created_at) >= '$from'";
        if ($to)
            $sql .= " AND DATE(created_at) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    private function getNewCustomers($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM users WHERE role_id = 3";
        if ($from)
            $sql .= " AND DATE(created_at) >= '$from'";
        if ($to)
            $sql .= " AND DATE(created_at) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    private function getTotalExpenses($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(amount) as total FROM expense_vouchers WHERE 1=1";
        if ($from)
            $sql .= " AND DATE(COALESCE(paid_at, created_at)) >= '$from'";
        if ($to)
            $sql .= " AND DATE(COALESCE(paid_at, created_at)) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float) ($row['total'] ?? 0);
    }

    private function getTotalProductsSold($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(oi.qty) as total 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'Hoàn tất'";
        if ($from)
            $sql .= " AND DATE(o.created_at) >= '$from'";
        if ($to)
            $sql .= " AND DATE(o.created_at) <= '$to'";

        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    private function convertDate($ddmmyyyy)
    {
        // Convert dd/mm/yyyy to yyyy-mm-dd
        $parts = explode('/', $ddmmyyyy);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }

    private function getDB()
    {
        return DB::pdo();
    }

    private function getProductImage($productId)
    {
        // Check if product image exists in filesystem
        $imagePath = __DIR__ . '/../../../public/assets/images/products/' . $productId . '/1.png';

        if (file_exists($imagePath)) {
            return '/assets/images/products/' . $productId . '/1.png';
        }

        return '/assets/images/products/default.png';
    }

    private function jsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin/api/reports/filter - Lọc dữ liệu theo nhiều tiêu chí */
    public function apiFilter()
    {
        $reportType = $_GET['report_type'] ?? 'staff';
        $criteria = $_GET['criteria'] ?? 'revenue';
        $searchText = $_GET['search'] ?? '';
        
        // Các filter mới
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
                    $data = $this->filterStaff($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId, $customerId);
                    break;
                case 'products':
                    $data = $this->filterProducts($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $customerId, $supplierId);
                    break;
                case 'customers':
                    $data = $this->filterCustomers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId);
                    break;
                case 'suppliers':
                    $data = $this->filterSuppliers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId);
                    break;
                case 'orders':
                    $data = $this->filterOrders($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId, $customerId);
                    break;
                case 'inventory':
                    $data = $this->filterInventory($criteria, $searchText, $fromDate, $toDate, $productId);
                    break;
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /** GET /admin/api/reports/export - Xuất Excel */
    public function apiExport()
    {
        $reportType = $_GET['report_type'] ?? 'staff';
        $criteria = $_GET['criteria'] ?? 'revenue';
        $searchText = $_GET['search'] ?? '';
        
        // Các filter mới
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
            // Get data same as filter
            $data = [];
            switch ($reportType) {
                case 'staff':
                    $data = $this->filterStaff($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId, $customerId);
                    break;
                case 'products':
                    $data = $this->filterProducts($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $customerId, $supplierId);
                    break;
                case 'customers':
                    $data = $this->filterCustomers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId);
                    break;
                case 'suppliers':
                    $data = $this->filterSuppliers($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId);
                    break;
                case 'orders':
                    $data = $this->filterOrders($criteria, $searchText, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId, $productId, $customerId);
                    break;
                case 'inventory':
                    $data = $this->filterInventory($criteria, $searchText, $fromDate, $toDate, $productId);
                    break;
            }

            // Generate Excel with date range
            $this->exportToExcel($data, $reportType, $criteria, $_GET['from_date'] ?? null, $_GET['to_date'] ?? null);

        } catch (\Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi xuất Excel: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Filter methods for each report type
    private function filterStaff($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId = null, $customerId = null)
    {
        $db = $this->getDB();

        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue,
                    AVG(o.grand_total) as avg_order_value
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        // Filter by customer
        if ($customerId) {
            $sql .= " AND o.user_id = $customerId";
        }
        
        // Filter by product (thông qua order_items)
        if ($productId) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM order_items oi 
                WHERE oi.order_id = o.id 
                AND oi.product_id = $productId
            )";
        }

        $sql .= " GROUP BY o.created_by";

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " HAVING u.full_name LIKE '%$searchEscaped%'";
        }

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            $valueColumn = $criteria === 'revenue' ? 'total_revenue' :
                ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'revenue' ? 'total_revenue' :
            ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function filterProducts($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $customerId = null, $supplierId = null)
    {
        $db = $this->getDB();

        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    u.name as unit_name,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.line_total) as total_revenue,
                    COUNT(DISTINCT o.id) as total_orders
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        // Filter by staff (nhân viên tạo đơn)
        if ($staffId) {
            $sql .= " AND o.created_by = $staffId";
        }
        
        // Filter by customer (khách hàng mua)
        if ($customerId) {
            $sql .= " AND o.user_id = $customerId";
        }
        
        // Filter by supplier (nhà cung cấp - dựa vào lô hàng)
        if ($supplierId) {
            $sql .= " AND EXISTS (
                SELECT 1 
                FROM stock_outs so
                JOIN stock_out_items soi ON so.id = soi.stock_out_id
                JOIN product_batches pb ON soi.batch_id = pb.id
                JOIN purchase_orders po ON pb.purchase_order_id = po.id
                WHERE so.order_id = o.id 
                AND soi.product_id = p.id
                AND po.supplier_id = $supplierId
            )";
        }

        $sql .= " GROUP BY p.id";

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " HAVING p.name LIKE '%$searchEscaped%' OR p.sku LIKE '%$searchEscaped%'";
        }

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            $valueColumn = $criteria === 'revenue' ? 'total_revenue' :
                ($criteria === 'quantity' ? 'total_quantity' : 'total_orders');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'revenue' ? 'total_revenue' :
            ($criteria === 'quantity' ? 'total_quantity' : 'total_orders');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        return $data;
    }

    private function filterCustomers($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $productId = null)
    {
        $db = $this->getDB();

        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent,
                    AVG(o.grand_total) as avg_order_value
                FROM users u
                JOIN orders o ON u.id = o.user_id
                WHERE o.status = 'Hoàn tất'";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        // Filter by staff
        if ($staffId) {
            $sql .= " AND o.created_by = $staffId";
        }
        
        // Filter by product
        if ($productId) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM order_items oi 
                WHERE oi.order_id = o.id 
                AND oi.product_id = $productId
            )";
        }

        $sql .= " GROUP BY u.id";

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " HAVING u.full_name LIKE '%$searchEscaped%' OR u.email LIKE '%$searchEscaped%'";
        }

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            $valueColumn = $criteria === 'total_spent' ? 'total_spent' :
                ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'total_spent' ? 'total_spent' :
            ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function filterSuppliers($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId = null)
    {
        $db = $this->getDB();

        $poDateCondition = "";
        if ($fromDate || $toDate) {
            $conditions = [];
            if ($fromDate)
                $conditions[] = "DATE(po.created_at) >= '$fromDate'";
            if ($toDate)
                $conditions[] = "DATE(po.created_at) <= '$toDate'";
            if (!empty($conditions)) {
                $poDateCondition = " AND (" . implode(" AND ", $conditions) . ")";
            }
        }

        $sql = "SELECT 
                    s.id as supplier_id,
                    s.name as supplier_name,
                    s.phone,
                    COUNT(DISTINCT po.id) as total_purchases,
                    COALESCE(SUM(po.total_amount), 0) as total_purchase_value,
                    COALESCE(SUM(sales.revenue), 0) as total_sales_value
                FROM suppliers s
                LEFT JOIN purchase_orders po ON s.id = po.supplier_id" . $poDateCondition;

        $orderDateCondition = "";
        if ($fromDate || $toDate) {
            $conditions = [];
            if ($fromDate)
                $conditions[] = "DATE(o.created_at) >= '$fromDate'";
            if ($toDate)
                $conditions[] = "DATE(o.created_at) <= '$toDate'";
            if (!empty($conditions)) {
                $orderDateCondition = " AND " . implode(" AND ", $conditions);
            }
        }

        $sql .= " LEFT JOIN (
                        SELECT 
                            po_inner.supplier_id,
                            SUM(oi.line_total) as revenue
                        FROM order_items oi
                        JOIN orders o ON oi.order_id = o.id
                        JOIN stock_outs so ON o.id = so.order_id
                        JOIN stock_out_items soi ON so.id = soi.stock_out_id 
                            AND soi.product_id = oi.product_id
                        LEFT JOIN product_batches pb ON soi.batch_id = pb.id
                        LEFT JOIN purchase_orders po_inner ON pb.purchase_order_id = po_inner.id
                        WHERE o.status = 'Hoàn tất'
                        AND so.type = 'sale'
                        AND so.status IN ('approved', 'completed')"
            . $orderDateCondition;
        
        // Filter by product trong sales subquery
        if ($productId) {
            $sql .= " AND oi.product_id = $productId";
        }
        
        $sql .= " GROUP BY po_inner.supplier_id
                    ) sales ON s.id = sales.supplier_id
                  WHERE 1=1";

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " AND s.name LIKE '%$searchEscaped%'";
        }

        $sql .= " GROUP BY s.id HAVING total_purchases > 0";

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            $valueColumn = $criteria === 'sales_value' ? 'total_sales_value' :
                ($criteria === 'purchase_value' ? 'total_purchase_value' : 'total_purchases');

            if ($valueFrom !== null) {
                $sql .= " AND $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'sales_value' ? 'total_sales_value' :
            ($criteria === 'purchase_value' ? 'total_purchase_value' : 'total_purchases');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function filterOrders($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $productId = null, $customerId = null)
    {
        $db = $this->getDB();

        $sql = "SELECT 
                    o.id as order_id,
                    o.grand_total as total_amount,
                    o.status,
                    o.created_at,
                    u.full_name as customer_name,
                    u.email as customer_email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE 1=1";

        if ($fromDate)
            $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate)
            $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        // Filter by staff
        if ($staffId) {
            $sql .= " AND o.created_by = $staffId";
        }
        
        // Filter by customer
        if ($customerId) {
            $sql .= " AND o.user_id = $customerId";
        }
        
        // Filter by product
        if ($productId) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM order_items oi 
                WHERE oi.order_id = o.id 
                AND oi.product_id = $productId
            )";
        }

        // Filter by search text (customer name)
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " AND (u.full_name LIKE '%$searchEscaped%' OR u.email LIKE '%$searchEscaped%')";
        }

        // Filter by criteria
        if ($criteria === 'status' && !empty($search)) {
            $sql .= " AND o.status LIKE '%$search%'";
        }

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            if ($criteria === 'total') {
                if ($valueFrom !== null)
                    $sql .= " AND o.grand_total >= $valueFrom";
                if ($valueTo !== null)
                    $sql .= " AND o.grand_total <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'total' ? 'o.grand_total' : 'o.created_at';
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function filterInventory($criteria, $search, $fromDate, $toDate, $productId = null)
    {
        $db = $this->getDB();

        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    s.qty as current_stock,
                    COALESCE(s.safety_stock, 10) as safety_stock,
                    u.name as unit_name
                FROM products p
                JOIN stocks s ON p.id = s.product_id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE 1=1";

        // Filter by product
        if ($productId) {
            $sql .= " AND p.id = $productId";
        }

        // Filter by criteria
        if ($criteria === 'low_stock') {
            $sql .= " AND s.qty <= COALESCE(s.safety_stock, 10)";
        } elseif ($criteria === 'high_stock') {
            $sql .= " AND s.qty >= COALESCE(s.safety_stock, 10) * 5";
        } elseif ($criteria === 'out_of_stock') {
            $sql .= " AND s.qty = 0";
        }

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " AND (p.name LIKE '%$searchEscaped%' OR p.sku LIKE '%$searchEscaped%')";
        }

        $sql .= " ORDER BY s.qty ASC";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path
        // foreach ($data as &$item) {
        //     $item['image_url'] = $this->getProductImage($item['product_id']);
        // }

        return $data;
    }

    private function exportToExcel($data, $reportType, $criteria, $fromDateStr = null, $toDateStr = null)
    {
        // Prepare headers based on report type
        $headers = $this->getExcelHeaders($reportType);
        $title = strtoupper($this->getReportTitle($reportType, $criteria));

        // Create date range text (sẽ hiển thị dưới "Ngày xuất")
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

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set sheet title
        $sheet->setTitle('Thống kê');

        $row = 1;

        // Add title (merge cells)
        $lastColumn = chr(64 + count($headers)); // A + số cột
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '002975']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;

        // Add export date
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", 'Ngày xuất: ' . date('d/m/Y H:i:s'));

        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $row++;

        // Add date range (if available)
        if (!empty($dateRange)) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", $dateRange);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $row++;
        }

        // Empty row
        $row++;

        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002975']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            $sheet->getColumnDimension($col)->setWidth(20);
            $col++;
        }
        $row++;

        // Write data rows
        foreach ($data as $dataRow) {
            $col = 'A';
            foreach (array_keys($headers) as $key) {
                $value = $dataRow[$key] ?? '';

                // Format money values
                if (in_array($key, ['total_revenue', 'total_spent', 'total_sales_value', 'total_purchase_value', 'avg_order_value', 'total_amount'])) {
                    if (is_numeric($value)) {
                        $sheet->setCellValue("{$col}{$row}", (float) $value);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0 ₫');
                    } else {
                        $sheet->setCellValue("{$col}{$row}", $value);
                    }
                }
                // Format date values
                else if ($key === 'created_at' && !empty($value)) {
                    $sheet->setCellValue("{$col}{$row}", date('d/m/Y H:i', strtotime($value)));
                }
                // Format number values
                else if (in_array($key, ['total_orders', 'total_quantity', 'total_purchases', 'current_stock', 'order_count'])) {
                    if (is_numeric($value)) {
                        $sheet->setCellValue("{$col}{$row}", (int) $value);
                        $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    } else {
                        $sheet->setCellValue("{$col}{$row}", $value);
                    }
                }
                // Regular text
                else {
                    $sheet->setCellValue("{$col}{$row}", $value);
                }

                // Apply borders
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);

                $col++;
            }
            $row++;
        }

        // Auto-size columns (optional, can be slow for large files)
        foreach (range('A', chr(64 + count($headers))) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="thong-ke-' . $reportType . '-' . date('Y-m-d-His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Write file to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function getExcelHeaders($reportType)
    {
        $headers = [
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

        return $headers[$reportType] ?? [];
    }

    private function getReportTitle($reportType, $criteria)
    {
        $typeLabels = [
            'staff' => 'NHÂN VIÊN',
            'products' => 'SẢN PHẨM',
            'customers' => 'KHÁCH HÀNG',
            'suppliers' => 'NHÀ CUNG CẤP',
            'orders' => 'ĐỠN HÀNG',
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
            'out_of_stock' => 'HẾT HÀNG'
        ];

        return 'THỐNG KÊ ' . ($typeLabels[$reportType] ?? '') . ' - ' . ($criteriaLabels[$criteria] ?? '');
    }

    /** GET /admin/api/reports/staff-list - Danh sách nhân viên */
    public function apiStaffList()
    {
        try {
            $db = $this->getDB();
            // Lấy danh sách nhân viên đã tạo đơn hàng (created_by)
            $sql = "SELECT DISTINCT
                        u.id as staff_id,
                        u.full_name,
                        COALESCE(sp.staff_role, 'Nhân viên') as staff_role
                    FROM orders o
                    JOIN users u ON o.created_by = u.id
                    LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                    WHERE o.status = 'Hoàn tất'
                    ORDER BY u.full_name ASC";

            $stmt = $db->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /** GET /admin/api/reports/product-list - Danh sách sản phẩm */
    public function apiProductList()
    {
        try {
            $db = $this->getDB();
            $sql = "SELECT 
                        p.id as product_id,
                        p.name,
                        p.sku
                    FROM products p
                    WHERE p.is_active = 1
                    ORDER BY p.name ASC";

            $stmt = $db->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /** GET /admin/api/reports/customer-list - Danh sách khách hàng */
    public function apiCustomerList()
    {
        try {
            $db = $this->getDB();
            // Lấy danh sách khách hàng đã mua hàng (user_id trong orders)
            $sql = "SELECT DISTINCT
                        u.id as customer_id,
                        u.full_name,
                        u.email
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    WHERE o.status = 'Hoàn tất'
                    ORDER BY u.full_name ASC";

            $stmt = $db->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /** GET /admin/api/reports/supplier-list - Danh sách nhà cung cấp */
    public function apiSupplierList()
    {
        try {
            $db = $this->getDB();
            $sql = "SELECT 
                        s.id as supplier_id,
                        s.name as supplier_name
                    FROM suppliers s
                    ORDER BY s.name ASC";

            $stmt = $db->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
