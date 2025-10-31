<?php
namespace App\Controllers\Admin;

use App\Core\DB;
use App\Controllers\Admin\AuthController;
use App\Models\Repositories\OrderRepository;
use App\Models\Repositories\CustomerRepository;
use App\Models\Repositories\ProductRepository;

class DashboardController extends BaseAdminController
{

    private $orderRepo;
    private $customerRepo;
    private $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->orderRepo = new OrderRepository();
        $this->customerRepo = new CustomerRepository();
        $this->productRepo = new ProductRepository();
    }

    public function index(): mixed
    {
        $pdo = DB::pdo();

        $today = date('Y-m-d');

        // 1. Đếm đơn hàng hôm nay
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $orders_today = (int) $stmt->fetchColumn();

        // 2. Tính doanh thu hôm nay
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(grand_total), 0) 
            FROM orders 
            WHERE DATE(created_at) = ? AND status = 'Hoàn tất'
        ");
        $stmt->execute([$today]);
        $revenue_today = (float) $stmt->fetchColumn();

        // 3. Đếm người dùng mới hôm nay
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ? AND role_id = 3");
        $stmt->execute([$today]);
        $customers_today = (int) $stmt->fetchColumn();

        // 4. Đếm sản phẩm sắp hết hàng
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM stocks s
            INNER JOIN products p ON p.id = s.product_id
            WHERE s.qty <= s.safety_stock 
              AND p.is_active = 1
        ");
        $low_stock = (int) $stmt->fetchColumn();

        // 5. Lấy 5 đơn hàng mới nhất
        $stmt = $pdo->query("
            SELECT o.id, o.code, o.grand_total as total_amount, o.status, o.created_at,
                   COALESCE(u.full_name, 'Khách lẻ') as customer_name
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recent_orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 6. Top 5 sản phẩm bán chạy
        $stmt = $pdo->query("
            SELECT p.id, p.name, 
                   COALESCE(SUM(oi.qty), 0) as total_sold,
                   COALESCE(SUM(oi.qty * oi.unit_price), 0) as total_revenue
            FROM products p
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id AND o.status = 'Hoàn tất'
            GROUP BY p.id, p.name
            HAVING total_sold > 0
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $top_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 7. Sản phẩm sắp hết hàng
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.sku, s.qty as stock, s.safety_stock
            FROM products p
            INNER JOIN stocks s ON s.product_id = p.id
            WHERE s.qty <= s.safety_stock 
              AND p.is_active = 1
            ORDER BY s.qty ASC, p.name ASC
            LIMIT 5
        ");
        $low_stock_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 8. Thu chi mặc định theo tháng hiện tại
        $currentMonth = date('Y-m');
        $revenueExpenseData = $this->getRevenueExpenseData('month', $currentMonth, 0);

        // 9. Doanh thu theo danh mục (Top 5)
        $stmt = $pdo->query("
            SELECT c.name, 
                   COALESCE(SUM(oi.qty * oi.unit_price), 0)/1000000 as revenue
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id AND o.status = 'Hoàn tất'
            GROUP BY c.id, c.name
            HAVING revenue > 0
            ORDER BY revenue DESC
            LIMIT 5
        ");
        $category_revenue = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 10. Trạng thái đơn hàng
        $stmt = $pdo->query("
            SELECT 
                SUM(CASE WHEN status = 'Hoàn tất' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Chờ xử lý' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled
            FROM orders
        ");
        $order_status = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $this->view('admin/index', [
            'orders_today' => $orders_today,
            'revenue_today' => $revenue_today,
            'customers_today' => $customers_today,
            'low_stock' => $low_stock,
            'recent_orders' => $recent_orders,
            'top_products' => $top_products,
            'low_stock_products' => $low_stock_products,
            'chart_data' => $revenueExpenseData,
            'category_revenue' => $category_revenue,
            'order_status' => $order_status
        ]);
    }

    /**
     * API: Lấy dữ liệu thu chi theo filter
     * GET /admin/api/dashboard/revenue-expense?type=week&period=2025-10&week=1
     */
    public function apiRevenueExpense()
    {
        $type = $_GET['type'] ?? 'week';
        $period = $_GET['period'] ?? date('Y-m');
        $week = isset($_GET['week']) ? (int) $_GET['week'] : 0; // 0 = tất cả, 1-4 = tuần cụ thể

        $data = $this->getRevenueExpenseData($type, $period, $week);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Lấy dữ liệu thu chi theo loại filter
     */
    private function getRevenueExpenseData(string $type, string $period, int $week = 0): array
    {
        $pdo = DB::pdo();

        if ($type === 'month') {
            // period là Y-m (ví dụ: "2025-10"), trả về dữ liệu theo ngày trong tháng
            return $this->getMonthlyData($pdo, $period);
        } else {
            // type === 'year', truyền $period (năm) vào getYearlyData
            return $this->getYearlyData($pdo, $period);
        }
    }

    /**
     * Dữ liệu thu chi theo tuần trong tháng
     * @param int $week 0 = tất cả tuần, 1-4 = tuần cụ thể
     */
    private function getWeeklyData($pdo, string $yearMonth, int $week = 0): array
    {
        $labels = ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'];
        $revenue = [0, 0, 0, 0];
        $expense = [0, 0, 0, 0];

        if ($week > 0 && $week <= 4) {
            // Doanh thu tuần cụ thể
            $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(grand_total),0)/1000000
            FROM orders
            WHERE DATE_FORMAT(created_at,'%Y-%m')=? 
              AND status='Hoàn tất'
              AND WEEK(created_at,1) - WEEK(DATE_SUB(created_at,INTERVAL DAYOFMONTH(created_at)-1 DAY),1)+1=?
        ");
            $stmt->execute([$yearMonth, $week]);
            $revenue[$week - 1] = round((float) $stmt->fetchColumn(), 1);

            // Chi phí tuần cụ thể
            $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount),0)/1000000
            FROM expense_vouchers
            WHERE DATE_FORMAT(COALESCE(paid_at, created_at),'%Y-%m')=? 
              AND WEEK(COALESCE(paid_at, created_at),1) - WEEK(DATE_SUB(COALESCE(paid_at, created_at),INTERVAL DAYOFMONTH(COALESCE(paid_at, created_at))-1 DAY),1)+1=?
        ");
            $stmt->execute([$yearMonth, $week]);
            $expense[$week - 1] = round((float) $stmt->fetchColumn(), 1);

            $labels = ['Tuần ' . $week];
            $revenue = [$revenue[$week - 1]];
            $expense = [$expense[$week - 1]];
        } else {
            // All weeks
            $stmt = $pdo->prepare("
            SELECT WEEK(created_at,1) - WEEK(DATE_SUB(created_at,INTERVAL DAYOFMONTH(created_at)-1 DAY),1)+1 as w,
                   COALESCE(SUM(grand_total),0)/1000000 as amt
            FROM orders
            WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status='Hoàn tất'
            GROUP BY w
        ");
            $stmt->execute([$yearMonth]);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                if ($r['w'] >= 1 && $r['w'] <= 4)
                    $revenue[$r['w'] - 1] = round($r['amt'], 1);
            }

            $stmt = $pdo->prepare("
            SELECT WEEK(COALESCE(paid_at, created_at),1) - WEEK(DATE_SUB(COALESCE(paid_at, created_at),INTERVAL DAYOFMONTH(COALESCE(paid_at, created_at))-1 DAY),1)+1 as w,
                   COALESCE(SUM(amount),0)/1000000 as amt
            FROM expense_vouchers
            WHERE DATE_FORMAT(COALESCE(paid_at, created_at),'%Y-%m')=?
            GROUP BY w
        ");
            $stmt->execute([$yearMonth]);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                if ($r['w'] >= 1 && $r['w'] <= 4)
                    $expense[$r['w'] - 1] = round($r['amt'], 1);
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue' => array_sum($revenue),
            'total_expense' => array_sum($expense),
            'profit' => array_sum($revenue) - array_sum($expense)
        ];
    }

    /**
     * Dữ liệu thu chi theo ngày trong 1 tháng
     * $period dạng YYYY-MM (vd: 2025-10)
     */
    private function getMonthlyData($pdo, string $yearMonth): array
    {
        [$year, $month] = explode('-', $yearMonth);
        $yearInt = (int) $year;
        $monthInt = (int) $month;

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthInt, $yearInt);

        $labels = [];
        $revenue = [];
        $expense = [];

        // Doanh thu theo ngày
        $stmt = $pdo->prepare("
        SELECT DAY(created_at) as d, COALESCE(SUM(grand_total),0)/1000000 as amt
        FROM orders
        WHERE YEAR(created_at)=? AND MONTH(created_at)=? AND status='Hoàn tất'
        GROUP BY d
    ");
        $stmt->execute([$yearInt, $monthInt]);
        $revenueData = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Chi phí theo ngày
        $stmt = $pdo->prepare("
        SELECT DAY(COALESCE(paid_at, created_at)) as d, COALESCE(SUM(amount),0)/1000000 as amt
        FROM expense_vouchers
        WHERE YEAR(COALESCE(paid_at, created_at))=? AND MONTH(COALESCE(paid_at, created_at))=?
        GROUP BY d
    ");
        $stmt->execute([$yearInt, $monthInt]);
        $expenseData = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Build mảng đầy đủ
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[] = "Ngày $d";
            $revenue[] = isset($revenueData[$d]) ? round($revenueData[$d], 1) : 0;
            $expense[] = isset($expenseData[$d]) ? round($expenseData[$d], 1) : 0;
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue' => array_sum($revenue),
            'total_expense' => array_sum($expense),
            'profit' => array_sum($revenue) - array_sum($expense)
        ];
    }

    /**
     * Dữ liệu thu chi theo năm (hiển thị 12 tháng trong năm được chọn)
     */
    private function getYearlyData($pdo, ?string $targetYear = null): array
    {
        $year = $targetYear ? (int) $targetYear : (int) date('Y');
        
        $labels = [];
        $revenue = array_fill(0, 12, 0);
        $expense = array_fill(0, 12, 0);

        // Tạo labels cho 12 tháng
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = 'Tháng ' . $m;
        }

        // Doanh thu theo tháng
        $stmt = $pdo->prepare("
        SELECT MONTH(created_at) as m, COALESCE(SUM(grand_total),0)/1000000 as amt
        FROM orders
        WHERE YEAR(created_at) = ? AND status='Hoàn tất'
        GROUP BY m
    ");
        $stmt->execute([$year]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $monthIndex = (int)$r['m'] - 1; // 0-indexed
            $revenue[$monthIndex] = round($r['amt'], 1);
        }

        // Chi phí theo tháng
        $stmt = $pdo->prepare("
        SELECT MONTH(COALESCE(paid_at, created_at)) as m, COALESCE(SUM(amount),0)/1000000 as amt
        FROM expense_vouchers
        WHERE YEAR(COALESCE(paid_at, created_at)) = ?
        GROUP BY m
    ");
        $stmt->execute([$year]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $monthIndex = (int)$r['m'] - 1; // 0-indexed
            $expense[$monthIndex] = round($r['amt'], 1);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue' => array_sum($revenue),
            'total_expense' => array_sum($expense),
            'profit' => array_sum($revenue) - array_sum($expense)
        ];
    }

}