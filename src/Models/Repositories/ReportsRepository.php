<?php

namespace App\Models\Repositories;

use App\Core\DB;

class ReportsRepository
{
    private $db;

    public function __construct()
    {
        $this->db = DB::pdo();
    }

    /**
     * Convert dd/mm/yyyy to yyyy-mm-dd
     */
    private function convertDate($ddmmyyyy)
    {
        $parts = explode('/', $ddmmyyyy);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }

    // ==================== OVERVIEW METHODS ====================

    public function getTotalRevenue($fromDate, $toDate)
    {
        $sql = "SELECT SUM(grand_total) as total FROM orders WHERE status = 'Đã giao'";
        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float) ($row['total'] ?? 0);
    }

    public function getTotalOrders($fromDate, $toDate)
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'Đã giao'";
        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    public function getCountExpense($fromDate, $toDate)
    {
        $sql = "SELECT COUNT(*) as count FROM expense_vouchers WHERE 1=1";
        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    public function getTotalExpenses($fromDate, $toDate)
    {
        $sql = "SELECT SUM(amount) as total FROM expense_vouchers WHERE 1=1";
        if ($fromDate) $sql .= " AND DATE(COALESCE(paid_at, created_at)) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(COALESCE(paid_at, created_at)) <= '$toDate'";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float) ($row['total'] ?? 0);
    }

    public function getTotalProductsSold($fromDate, $toDate)
    {
        $sql = "SELECT SUM(oi.qty) as total 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'Đã giao'";
        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    // ==================== STAFF REPORTS ====================

    public function getStaffByOrders($fromDate, $toDate)
    {
        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY o.created_by
                  ORDER BY total_orders DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStaffByRevenue($fromDate, $toDate)
    {
        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY o.created_by
                  ORDER BY total_revenue DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function filterStaff($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId = null, $customerId = null)
    {
        $sql = "SELECT 
                    o.created_by as staff_id,
                    u.full_name,
                    sp.staff_role,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_revenue,
                    AVG(o.grand_total) as avg_order_value";

        // Luôn thêm tên sản phẩm để dùng cho biểu đồ tròn
        if ($productId) {
            $sql .= ", (SELECT p.name FROM products p WHERE p.id = $productId LIMIT 1) as product_name";
        } else {
            $sql .= ", GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as product_name";
        }

        // Luôn thêm tên khách hàng để dùng cho biểu đồ tròn
        if ($customerId) {
            $sql .= ", (SELECT u2.full_name FROM users u2 WHERE u2.id = $customerId LIMIT 1) as customer_name";
        } else {
            $sql .= ", u_customer.full_name as customer_name";
        }

        $sql .= " FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u_customer ON o.user_id = u_customer.id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

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

        $sql .= " GROUP BY o.created_by";

        // Filter by search text
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $sql .= " HAVING u.full_name LIKE '%$searchEscaped%'";
        }

        // Filter by value range
        if ($valueFrom !== null || $valueTo !== null) {
            $valueColumn = $criteria === 'revenue' ? 'total_revenue' : ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'revenue' ? 'total_revenue' : ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== PRODUCT REPORTS ====================

    public function getProductsByQuantity($fromDate, $toDate)
    {
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
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY p.id
                  ORDER BY total_quantity DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductsByRevenue($fromDate, $toDate)
    {
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
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY p.id
                  ORDER BY total_revenue DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function filterProducts($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $customerId = null, $supplierId = null)
    {
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku,
                    u.name as unit_name,
                    SUM(oi.qty) as total_quantity,
                    SUM(oi.line_total) as total_revenue,
                    COUNT(DISTINCT o.id) as total_orders";

        // Luôn thêm tên nhân viên để dùng cho biểu đồ tròn
        if ($staffId) {
            $sql .= ", (SELECT u2.full_name FROM users u2 WHERE u2.id = $staffId LIMIT 1) as staff_name";
        } else {
            $sql .= ", u_staff.full_name as staff_name";
        }

        // Luôn thêm tên khách hàng để dùng cho biểu đồ tròn
        if ($customerId) {
            $sql .= ", (SELECT u3.full_name FROM users u3 WHERE u3.id = $customerId LIMIT 1) as customer_name";
        } else {
            $sql .= ", u_customer.full_name as customer_name";
        }

        // Luôn thêm tên nhà cung cấp để dùng cho biểu đồ tròn
        if ($supplierId) {
            $sql .= ", (SELECT s.name FROM suppliers s WHERE s.id = $supplierId LIMIT 1) as supplier_name";
        } else {
            // Lấy nhà cung cấp từ purchase order gần nhất của sản phẩm
            $sql .= ", (
                SELECT s.name 
                FROM suppliers s
                JOIN purchase_orders po ON s.id = po.supplier_id
                JOIN product_batches pb ON po.id = pb.purchase_order_id
                WHERE pb.product_id = p.id
                ORDER BY po.created_at DESC
                LIMIT 1
            ) as supplier_name";
        }

        $sql .= " FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                LEFT JOIN users u_staff ON o.created_by = u_staff.id
                LEFT JOIN users u_customer ON o.user_id = u_customer.id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        // Filter by staff
        if ($staffId) {
            $sql .= " AND o.created_by = $staffId";
        }

        // Filter by customer
        if ($customerId) {
            $sql .= " AND o.user_id = $customerId";
        }

        // Filter by supplier
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
            $valueColumn = $criteria === 'revenue' ? 'total_revenue' : ($criteria === 'quantity' ? 'total_quantity' : 'total_orders');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'revenue' ? 'total_revenue' : ($criteria === 'quantity' ? 'total_quantity' : 'total_orders');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== CUSTOMER REPORTS ====================

    public function getCustomersBySpending($fromDate, $toDate)
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent
                FROM users u
                JOIN orders o ON u.id = o.user_id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY u.id
                  ORDER BY total_spent DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCustomersByOrders($fromDate, $toDate)
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent
                FROM users u
                JOIN orders o ON u.id = o.user_id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

        $sql .= " GROUP BY u.id
                  ORDER BY total_orders DESC
                  LIMIT 10";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function filterCustomers($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $productId = null)
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.grand_total) as total_spent,
                    AVG(o.grand_total) as avg_order_value";

        // Luôn thêm tên nhân viên để dùng cho biểu đồ tròn
        if ($staffId) {
            $sql .= ", (SELECT u2.full_name FROM users u2 WHERE u2.id = $staffId LIMIT 1) as staff_name";
        } else {
            $sql .= ", u_staff.full_name as staff_name";
        }

        // Luôn thêm tên sản phẩm để dùng cho biểu đồ tròn
        if ($productId) {
            $sql .= ", (SELECT p.name FROM products p WHERE p.id = $productId LIMIT 1) as product_name";
        } else {
            $sql .= ", GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as product_name";
        }

        $sql .= " FROM users u
                JOIN orders o ON u.id = o.user_id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u_staff ON o.created_by = u_staff.id
                WHERE o.status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

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
            $valueColumn = $criteria === 'total_spent' ? 'total_spent' : ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');

            if ($valueFrom !== null) {
                $sql .= (!empty($search) ? " AND" : " HAVING") . " $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'total_spent' ? 'total_spent' : ($criteria === 'orders' ? 'total_orders' : 'avg_order_value');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== SUPPLIER REPORTS ====================

    public function getSuppliers($fromDate, $toDate)
    {
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
                        WHERE o.status = 'Đã giao'
                        AND so.type = 'sale'
                        AND so.status IN ('approved', 'completed')"
            . $orderDateCondition . "
                        GROUP BY po_inner.supplier_id
                    ) sales ON s.id = sales.supplier_id
                  GROUP BY s.id
                  HAVING total_purchases > 0
                  ORDER BY total_purchase_value DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function filterSuppliers($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $productId = null)
    {
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
                    s.phone,
                    COUNT(DISTINCT po.id) as total_purchases,
                    COALESCE(SUM(po.total_amount), 0) as total_purchase_value,
                    COALESCE(SUM(sales.revenue), 0) as total_sales_value";

        // Thêm tên sản phẩm nếu filter theo sản phẩm cụ thể
        if ($productId) {
            $sql .= ", (SELECT p.name FROM products p WHERE p.id = $productId LIMIT 1) as product_name";
        } else {
            // Nếu không filter theo sản phẩm, trả về danh sách sản phẩm của nhà cung cấp
            $sql .= ", (
                SELECT GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ')
                FROM products p
                JOIN product_batches pb ON p.id = pb.product_id
                JOIN purchase_orders po_inner ON pb.purchase_order_id = po_inner.id
                WHERE po_inner.supplier_id = s.id
            ) as product_name";
        }

        $sql .= " FROM suppliers s
                LEFT JOIN purchase_orders po ON s.id = po.supplier_id" . $poDateCondition;

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
                        WHERE o.status = 'Đã giao'
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
            $valueColumn = $criteria === 'sales_value' ? 'total_sales_value' : ($criteria === 'purchase_value' ? 'total_purchase_value' : 'total_purchases');

            if ($valueFrom !== null) {
                $sql .= " AND $valueColumn >= $valueFrom";
            }
            if ($valueTo !== null) {
                $sql .= " AND $valueColumn <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'sales_value' ? 'total_sales_value' : ($criteria === 'purchase_value' ? 'total_purchase_value' : 'total_purchases');
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== ORDER REPORTS ====================

    public function filterOrders($criteria, $search, $valueFrom, $valueTo, $sortOrder, $fromDate, $toDate, $staffId = null, $productId = null, $customerId = null)
    {
        $sql = "SELECT 
                    o.id as order_id,
                    o.grand_total as total_amount,
                    o.status,
                    o.created_at,
                    u.full_name as customer_name,
                    u.email as customer_email,
                    o.created_by as staff_id,
                    u_staff.full_name as staff_name";

        // Thêm tên sản phẩm nếu filter theo sản phẩm cụ thể
        if ($productId) {
            $sql .= ", (SELECT p.name FROM products p WHERE p.id = $productId LIMIT 1) as product_name";
        }

        $sql .= " FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN users u_staff ON o.created_by = u_staff.id
            WHERE 1=1";

        if ($fromDate) $sql .= " AND DATE(o.created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(o.created_at) <= '$toDate'";

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
                if ($valueFrom !== null) $sql .= " AND o.grand_total >= $valueFrom";
                if ($valueTo !== null) $sql .= " AND o.grand_total <= $valueTo";
            }
        }

        // Sort
        $orderColumn = $criteria === 'total' ? 'o.grand_total' : 'o.created_at';
        $sql .= " ORDER BY $orderColumn " . strtoupper($sortOrder);

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== INVENTORY REPORTS ====================

    public function getLowStock()
    {
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

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHighStock()
    {
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

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function filterInventory($criteria, $search, $fromDate, $toDate, $productId = null)
    {
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

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== CHART DATA ====================

    public function getRevenueChartData($fromDate, $toDate)
    {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    SUM(grand_total) as revenue
                FROM orders
                WHERE status = 'Đã giao'";

        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";

        $sql .= " GROUP BY DATE(created_at)
                  ORDER BY date ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getOrderStatusData($fromDate, $toDate)
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM orders
                WHERE 1=1";

        if ($fromDate) $sql .= " AND DATE(created_at) >= '$fromDate'";
        if ($toDate) $sql .= " AND DATE(created_at) <= '$toDate'";

        $sql .= " GROUP BY status";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ==================== DROPDOWN LIST DATA ====================

    public function getStaffList()
    {
        $sql = "SELECT DISTINCT
                    u.id as staff_id,
                    u.full_name,
                    COALESCE(sp.staff_role, 'Nhân viên') as staff_role
                FROM orders o
                JOIN users u ON o.created_by = u.id
                LEFT JOIN staff_profiles sp ON u.id = sp.user_id
                WHERE o.status = 'Đã giao'
                ORDER BY u.full_name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductList()
    {
        $sql = "SELECT 
                    p.id as product_id,
                    p.name,
                    p.sku
                FROM products p
                WHERE p.is_active = 1
                ORDER BY p.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCustomerList()
    {
        $sql = "SELECT DISTINCT
                    u.id as customer_id,
                    u.full_name,
                    u.email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.status = 'Đã giao'
                ORDER BY u.full_name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSupplierList()
    {
        $sql = "SELECT 
                    s.id as supplier_id,
                    s.name as supplier_name
                FROM suppliers s
                ORDER BY s.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
