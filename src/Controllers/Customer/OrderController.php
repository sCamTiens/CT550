<?php
namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\OrderRepository;

class OrderController extends Controller
{
    private OrderRepository $orderRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['customer'])) {
            header('Location: /login');
            exit;
        }

        $this->orderRepo = new OrderRepository();
    }

    /**
     * GET /orders - Hiển thị trang đơn hàng (deprecated, giờ dùng tab trong profile)
     */
    public function index(): mixed
    {
        $customerId = $_SESSION['customer']['id'];
        
        $orders = $this->orderRepo->getOrders($customerId);
        $totalOrders = $this->orderRepo->countOrders($customerId);
        
        return $this->view('customer/orders/orders', [
            'orders' => $orders,
            'totalOrders' => $totalOrders
        ]);
    }

    /**
     * GET /api/orders - API lấy danh sách đơn hàng
     */
    public function apiOrders(): mixed
    {
        header('Content-Type: application/json');
        
        $customerId = $_SESSION['customer']['id'];
        
        try {
            $orders = $this->orderRepo->getOrders($customerId);
            $totalOrders = $this->orderRepo->countOrders($customerId);
            
            echo json_encode([
                'success' => true,
                'data' => $orders,
                'total' => $totalOrders
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải dữ liệu'
            ]);
        }
        
        exit;
    }

    /**
     * GET /orders/{id} - Xem chi tiết đơn hàng
     */
    public function detail(Request $req): mixed
    {
        $customerId = $_SESSION['customer']['id'];
        $orderId = $_GET['id'] ?? null;
        
        if (!$orderId) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại';
            header('Location: /profile?tab=orders');
            exit;
        }
        
        $order = $this->orderRepo->getOrderById((int)$orderId, $customerId);
        
        if (!$order) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại hoặc không thuộc về bạn';
            header('Location: /profile?tab=orders');
            exit;
        }
        
        return $this->view('customer/orders/detail', ['order' => $order]);
    }

    /**
     * POST /orders/{id}/cancel - Hủy đơn hàng (chỉ cho phép khi status = pending)
     */
    public function cancel(Request $req): mixed
    {
        $customerId = $_SESSION['customer']['id'];
        $orderId = $_POST['order_id'] ?? null;
        
        if (!$orderId) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại';
            header('Location: /profile?tab=orders');
            exit;
        }
        
        $order = $this->orderRepo->getOrderById((int)$orderId, $customerId);
        
        if (!$order) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại hoặc không thuộc về bạn';
            header('Location: /profile?tab=orders');
            exit;
        }
        
        if ($order['status'] !== 'pending') {
            $_SESSION['flash_error'] = 'Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xác nhận"';
            header('Location: /profile?tab=orders');
            exit;
        }
        
        // TODO: Implement cancel order logic
        $_SESSION['profile_success'] = 'Đơn hàng đã được hủy thành công';
        header('Location: /profile?tab=orders');
        exit;
    }
}
