<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Support\JWTHelper;

class VNPayController extends Controller
{
    /**
     * Tạo URL thanh toán VNPay
     */
    public function createPayment(Request $req)
    {
        header('Content-Type: application/json');

        // Get customer ID from JWT token
        $customerId = $req->user['id'] ?? null;

        if (!$customerId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $orderId = $data['order_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $orderInfo = $data['order_info'] ?? 'Thanh toán đơn hàng MiniGo';

        if (!$orderId || $amount <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thông tin đơn hàng không hợp lệ'
            ]);
            exit;
        }

        // VNPay config
        $vnp_TmnCode = getenv('VNPAY_TMN_CODE');
        $vnp_HashSecret = getenv('VNPAY_HASH_SECRET');
        $vnp_Url = getenv('VNPAY_URL');
        $vnp_ReturnUrl = getenv('VNPAY_RETURN_URL');

        if (!$vnp_TmnCode || !$vnp_HashSecret || !$vnp_Url) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Cấu hình VNPay chưa đầy đủ'
            ]);
            exit;
        }

        // Build payment data
        $vnp_TxnRef = $orderId . '_' . time();
        $vnp_OrderInfo = $orderInfo;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = (int)($amount * 100); // VNPay requires amount in VND * 100
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

        echo json_encode([
            'success' => true,
            'payment_url' => $vnp_Url,
            'txn_ref' => $vnp_TxnRef
        ]);
        exit;
    }

    /**
     * Xử lý callback từ VNPay
     */
    public function callback(Request $req)
    {
        $vnp_HashSecret = getenv('VNPAY_HASH_SECRET');

        $vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
        $inputData = [];

        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Verify signature
        if ($secureHash !== $vnp_SecureHash) {
            return $this->view('customer/payment/vnpay_error', [
                'message' => 'Chữ ký không hợp lệ'
            ]);
        }

        $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
        $vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
        $vnp_Amount = $_GET['vnp_Amount'] ?? 0;
        $vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
        $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';

        // Extract customer ID and txn_ref
        $vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';

        if ($vnp_ResponseCode == '00') {
            // Payment success - Tạo đơn hàng
            if (empty($vnp_TxnRef)) {
                return $this->view('customer/payment/vnpay_error', [
                    'message' => 'Không tìm thấy mã giao dịch'
                ]);
            }

            // Load pending order từ database
            $pdo = \App\Core\DB::pdo();
            $stmt = $pdo->prepare("SELECT * FROM pending_orders WHERE txn_ref = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $stmt->execute([$vnp_TxnRef]);
            $pendingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pendingRecord) {
                return $this->view('customer/payment/vnpay_error', [
                    'message' => 'Không tìm thấy thông tin đơn hàng hoặc phiên đã hết hạn'
                ]);
            }

            $pendingOrder = json_decode($pendingRecord['order_data'], true);
            $customerId = $pendingOrder['customer_id'];

            // Tạo đơn hàng
            $pdo->beginTransaction();

            try {
                // Generate order code
                $orderCode = 'ORD' . date('YmdHis') . rand(100, 999);

                // Insert order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        code, user_id, order_type, status, subtotal, grand_total, 
                        payment_method, payment_status, shipping_address_id, 
                        created_at, updated_at
                    )
                    VALUES (?, ?, 'Online', 'Chờ xử lý', ?, ?, 'VNPay', 'Đã thanh toán', ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $orderCode,
                    $customerId,
                    $pendingOrder['subtotal'],
                    $pendingOrder['subtotal'],
                    $pendingOrder['address_id']
                ]);

                $orderId = $pdo->lastInsertId();

                // Insert order items
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, qty, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($pendingOrder['cart_items'] as $item) {
                    $stmtItem->execute([
                        $orderId,
                        $item['id'],
                        $item['quantity'],
                        $item['price'],
                        $item['subtotal']
                    ]);

                    // Trừ số lượng trong kho
                    $stmtStock = $pdo->prepare("
                        UPDATE stocks 
                        SET qty = qty - ?, 
                            updated_at = NOW() 
                        WHERE product_id = ?
                    ");
                    $stmtStock->execute([$item['quantity'], $item['id']]);
                }

                // Tạo payment record
                $paymentMeta = json_encode([
                    'vnp_Amount' => $vnp_Amount,
                    'vnp_BankCode' => $vnp_BankCode,
                    'vnp_BankTranNo' => $_GET['vnp_BankTranNo'] ?? '',
                    'vnp_CardType' => $_GET['vnp_CardType'] ?? '',
                    'vnp_OrderInfo' => $_GET['vnp_OrderInfo'] ?? '',
                    'vnp_PayDate' => $_GET['vnp_PayDate'] ?? '',
                    'vnp_TransactionNo' => $vnp_TransactionNo,
                    'vnp_TxnRef' => $vnp_TxnRef,
                    'vnp_ResponseCode' => $_GET['vnp_ResponseCode'] ?? ''
                ]);

                $stmtPayment = $pdo->prepare("
                    INSERT INTO payments (amount, method, txn_ref, paid_at, meta, created_at)
                    VALUES (?, 'VNPay', ?, NOW(), ?, NOW())
                ");
                $stmtPayment->execute([
                    $pendingOrder['subtotal'],
                    $vnp_TxnRef,
                    $paymentMeta
                ]);

                $paymentId = $pdo->lastInsertId();

                // Update order với payment_id
                $stmtUpdateOrder = $pdo->prepare("
                    UPDATE orders SET payment_id = ? WHERE id = ?
                ");
                $stmtUpdateOrder->execute([$paymentId, $orderId]);

                // Xóa items khỏi giỏ hàng
                $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
                if (!empty($pendingOrder['selected_item_ids'])) {
                    foreach ($pendingOrder['selected_item_ids'] as $productId) {
                        $cartRepo->removeItemDB($customerId, $productId);
                    }
                } else {
                    $cartRepo->clearCartDB($customerId);
                }

                // Xóa pending order
                $stmt = $pdo->prepare("DELETE FROM pending_orders WHERE txn_ref = ?");
                $stmt->execute([$vnp_TxnRef]);

                $pdo->commit();

                // === QUAN TRỌNG: Khôi phục session cho user ===
                // Load customer info và tạo session để user có thể tiếp tục sử dụng
                $stmt = $pdo->prepare("
                    SELECT u.* 
                    FROM users u
                    INNER JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ? AND r.name = 'Khách hàng'
                ");
                $stmt->execute([$customerId]);
                $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($customer) {
                    // Tạo JWT tokens
                    $accessToken = JWTHelper::generateToken([
                        'id' => $customer['id'],
                        'username' => $customer['username'],
                        'email' => $customer['email'],
                        'role' => 'customer'
                    ]);
                    $refreshToken = JWTHelper::generateRefreshToken([
                        'id' => $customer['id'],
                        'username' => $customer['username'],
                        'email' => $customer['email'],
                        'role' => 'customer'
                    ]);

                    // Khôi phục session với đầy đủ thông tin
                    $_SESSION['customer'] = [
                        'id' => $customer['id'],
                        'username' => $customer['username'],
                        'full_name' => $customer['full_name'],
                        'email' => $customer['email'],
                        'phone' => $customer['phone'],
                        'gender' => $customer['gender'],
                        'date_of_birth' => $customer['date_of_birth'],
                        'avatar_url' => $customer['avatar_url'],
                        'loyalty_points' => $customer['loyalty_points'],
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken
                    ];
                }

                return $this->view('customer/payment/vnpay_success', [
                    'order_id' => $orderId,
                    'order_code' => $orderCode,
                    'amount' => $vnp_Amount / 100,
                    'transaction_no' => $vnp_TransactionNo,
                    'bank_code' => $vnp_BankCode
                ]);
            } catch (\Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('VNPay order creation error: ' . $e->getMessage());
                return $this->view('customer/payment/vnpay_error', [
                    'message' => 'Không thể tạo đơn hàng: ' . $e->getMessage()
                ]);
            }
        } else {
            // Payment failed - Xóa pending data
            if (!empty($vnp_TxnRef)) {
                $pdo = \App\Core\DB::pdo();
                $stmt = $pdo->prepare("DELETE FROM pending_orders WHERE txn_ref = ?");
                $stmt->execute([$vnp_TxnRef]);
            }

            return $this->view('customer/payment/vnpay_error', [
                'message' => $this->getResponseMessage($vnp_ResponseCode)
            ]);
        }
    }

    /**
     * Get VNPay response message
     */
    private function getResponseMessage($responseCode)
    {
        $messages = [
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường).',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.',
            '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.',
            '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.',
            '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP).',
            '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
            '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.',
            '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định.',
            '99' => 'Các lỗi khác'
        ];

        return $messages[$responseCode] ?? 'Lỗi không xác định';
    }
}
