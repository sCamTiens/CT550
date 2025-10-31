<?php

namespace App\Controllers\Admin;

use App\Core\DB;

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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        $sql .= " GROUP BY p.id
                  ORDER BY total_quantity DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($data as &$item) {
            $item['image_url'] = $this->getProductImage($item['product_id']);
        }

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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
        $sql .= " GROUP BY p.id
                  ORDER BY total_revenue DESC
                  LIMIT 10";

        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($data as &$item) {
            $item['image_url'] = $this->getProductImage($item['product_id']);
        }

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
            if ($fromDate) $conditions[] = "DATE(po.created_at) >= '$fromDate'";
            if ($toDate) $conditions[] = "DATE(po.created_at) <= '$toDate'";
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
            if ($fromDate) $conditions[] = "DATE(o.created_at) >= '$fromDate'";
            if ($toDate) $conditions[] = "DATE(o.created_at) <= '$toDate'";
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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
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
        
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";
        
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
        foreach ($data as &$item) {
            $item['image_url'] = $this->getProductImage($item['product_id']);
        }

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
        foreach ($data as &$item) {
            $item['image_url'] = $this->getProductImage($item['product_id']);
        }

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
        
        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";
        
        $sql .= " GROUP BY DATE(created_at)
                  ORDER BY date ASC";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $labels = array_map(function($r) { 
            return date('d/m', strtotime($r['date'])); 
        }, $rows);
        $values = array_map(function($r) { 
            return (float)$r['revenue']; 
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
        
        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";
        
        $sql .= " GROUP BY status";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $statusMap = [
            'Chờ xử lý' => 'Chờ xử lý',
            'Đang xử lý' => 'Đang xử lý',
            'Hoàn tất' => 'Hoàn tất',
            'Đã hủy' => 'Đã hủy'
        ];

        $labels = array_map(function($r) use ($statusMap) { 
            return $statusMap[$r['status']] ?? $r['status']; 
        }, $rows);
        $values = array_map(function($r) { 
            return (int)$r['count']; 
        }, $rows);

        $this->jsonResponse(['labels' => $labels, 'values' => $values]);
    }

    // Helper methods
    private function getTotalRevenue($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(grand_total) as total FROM orders WHERE status = 'Hoàn tất'";
        if ($from) $sql .= " AND DATE(created_at) >= '$from'";
        if ($to) $sql .= " AND DATE(created_at) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }

    private function getTotalOrders($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'Hoàn tất'";
        if ($from) $sql .= " AND DATE(created_at) >= '$from'";
        if ($to) $sql .= " AND DATE(created_at) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }

    private function getCountExpense($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM expense_vouchers WHERE 1=1";
        if ($from) $sql .= " AND DATE(created_at) >= '$from'";
        if ($to) $sql .= " AND DATE(created_at) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }

    private function getNewCustomers($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT COUNT(*) as count FROM users WHERE role_id = 3";
        if ($from) $sql .= " AND DATE(created_at) >= '$from'";
        if ($to) $sql .= " AND DATE(created_at) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['count'] ?? 0);
    }

    private function getTotalExpenses($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(amount) as total FROM expense_vouchers WHERE 1=1";
        if ($from) $sql .= " AND DATE(COALESCE(paid_at, created_at)) >= '$from'";
        if ($to) $sql .= " AND DATE(COALESCE(paid_at, created_at)) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }

    private function getTotalProductsSold($from, $to)
    {
        $db = $this->getDB();
        $sql = "SELECT SUM(oi.qty) as total 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'Hoàn tất'";
        if ($from) $sql .= " AND DATE(o.created_at) >= '$from'";
        if ($to) $sql .= " AND DATE(o.created_at) <= '$to'";
        
        $stmt = $db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
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
}
