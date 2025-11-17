<?php
namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\LoyaltyRepository;

class LoyaltyController extends Controller
{
    private LoyaltyRepository $loyaltyRepo;

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

        $this->loyaltyRepo = new LoyaltyRepository();
    }

    /**
     * GET /loyalty - Hiển thị trang điểm tích lũy
     */
    public function index(): mixed
    {
        $customerId = $_SESSION['customer']['id'];
        
        // Lấy thông tin customer MỚI NHẤT từ database
        $customer = $this->loyaltyRepo->getCustomerInfo($customerId);
        
        if (!$customer) {
            $_SESSION['flash_error'] = 'Không tìm thấy thông tin khách hàng';
            header('Location: /');
            exit;
        }
        
        // Update session với loyalty_points mới nhất
        $_SESSION['customer']['loyalty_points'] = $customer['loyalty_points'];
        
        return $this->view('customer/loyalty/loyalty', ['customer' => $customer]);
    }

    /**
     * GET /api/loyalty/transactions - API lấy danh sách giao dịch điểm
     */
    public function apiTransactions(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $customerId = $_SESSION['customer']['id'];
        
        // Lấy danh sách giao dịch
        $transactions = $this->loyaltyRepo->getTransactions($customerId);
        
        // Lấy thống kê
        $stats = [
            'totalEarned' => $this->loyaltyRepo->getTotalEarned($customerId),
            'totalRedeemed' => $this->loyaltyRepo->getTotalRedeemed($customerId),
            'orderCount' => $this->loyaltyRepo->getOrderCount($customerId)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $transactions,
            'stats' => $stats
        ]);
        exit;
    }
}
