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
        $this->loyaltyRepo = new LoyaltyRepository();
    }

    /**
     * GET /loyalty - Hiển thị trang điểm tích lũy
     */
    public function index(?Request $req = null): mixed
    {
        // Get customer ID from JWT token (priority 1) or session (fallback)
        $customerId = $req->user['id'] ?? $_SESSION['customer']['id'] ?? null;

        if (!$customerId) {
            header('Location: /login');
            exit;
        }

        // Lấy thông tin customer MỚI NHẤT từ database
        $customer = $this->loyaltyRepo->getCustomerInfo($customerId);

        if (!$customer) {
            $_SESSION['flash_error'] = 'Không tìm thấy thông tin khách hàng';
            header('Location: /');
            exit;
        }

        // DO NOT update session here - just use $customer data for display
        // Session already has user info + tokens from login

        return $this->view('customer/loyalty/loyalty', ['customer' => $customer]);
    }

    /**
     * GET /api/loyalty/transactions - API lấy danh sách giao dịch điểm
     */
    public function apiTransactions(Request $req): mixed
    {
        header('Content-Type: application/json');

        // Get customer ID from JWT
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

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
