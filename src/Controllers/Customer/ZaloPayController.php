<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;

class ZaloPayController extends Controller
{
    /**
     * Create ZaloPay payment
     */
    public function createPayment(Request $req): mixed
    {
        header('Content-Type: application/json');

        try {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);

            $customerId = $req->user['id'] ?? null;
            if (!$customerId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }

            // Get ZaloPay config
            $appId = getenv('ZALOPAY_APP_ID');
            $key1 = getenv('ZALOPAY_KEY1');
            $key2 = getenv('ZALOPAY_KEY2');
            $endpoint = getenv('ZALOPAY_ENDPOINT');
            $callbackUrl = getenv('ZALOPAY_CALLBACK_URL');

            if (!$appId || !$key1 || !$key2 || !$endpoint) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cấu hình ZaloPay chưa đầy đủ'
                ]);
                exit;
            }

            // Create order data
            $transID = time(); // Transaction ID
            $appTransID = date('ymd') . '_' . $transID; // Format: yymmdd_timestamp
            $amount = (int)$data['amount'];

            $embedData = json_encode([
                'redirecturl' => $callbackUrl,
                'customer_id' => $customerId
            ]);

            $items = json_encode([]);

            $order = [
                'app_id' => $appId,
                'app_trans_id' => $appTransID,
                'app_user' => 'user_' . $customerId,
                'app_time' => round(microtime(true) * 1000),
                'amount' => $amount,
                'item' => $items,
                'embed_data' => $embedData,
                'description' => 'Thanh toán đơn hàng MiniGo #' . $appTransID,
                'bank_code' => '',
                'callback_url' => $callbackUrl
            ];

            // Create MAC
            $macData = $appId . '|' . $order['app_trans_id'] . '|' . $order['app_user'] . '|' .
                $order['amount'] . '|' . $order['app_time'] . '|' . $order['embed_data'] . '|' .
                $order['item'];
            $order['mac'] = hash_hmac('sha256', $macData, $key1);

            // Call ZaloPay API
            $context = stream_context_create([
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($order)
                ]
            ]);

            $response = file_get_contents($endpoint, false, $context);
            $result = json_decode($response, true);

            if ($result['return_code'] == 1) {
                // Save pending order to database
                $pdo = \App\Core\DB::pdo();
                $pendingData = json_encode([
                    'customer_id' => $customerId,
                    'address_id' => $data['address_id'],
                    'cart_items' => $data['cart_items'],
                    'subtotal' => $data['amount'],
                    'selected_item_ids' => $data['selected_item_ids'] ?? []
                ]);

                $stmt = $pdo->prepare("
                    INSERT INTO pending_orders (txn_ref, customer_id, order_data, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE order_data = ?, created_at = NOW()
                ");
                $stmt->execute([$appTransID, $customerId, $pendingData, $pendingData]);

                echo json_encode([
                    'success' => true,
                    'payment_url' => $result['order_url'],
                    'app_trans_id' => $appTransID
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể tạo thanh toán ZaloPay: ' . ($result['return_message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            error_log('[ZaloPay] Create payment error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * ZaloPay callback
     */
    public function callback(Request $req): mixed
    {
        try {
            $result = [];
            $key2 = getenv('ZALOPAY_KEY2');

            $postData = file_get_contents('php://input');
            $postDataArr = json_decode($postData, true);

            $mac = hash_hmac('sha256', $postDataArr['data'], $key2);

            if (strcmp($mac, $postDataArr['mac']) != 0) {
                $result['return_code'] = -1;
                $result['return_message'] = 'mac not equal';
            } else {
                $dataJson = json_decode($postDataArr['data'], true);

                // Payment successful
                $appTransID = $dataJson['app_trans_id'];

                // Get pending order
                $pdo = \App\Core\DB::pdo();
                $stmt = $pdo->prepare("SELECT * FROM pending_orders WHERE txn_ref = ?");
                $stmt->execute([$appTransID]);
                $pendingOrder = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$pendingOrder) {
                    $result['return_code'] = 0;
                    $result['return_message'] = 'Order not found';
                    echo json_encode($result);
                    exit;
                }

                $orderData = json_decode($pendingOrder['order_data'], true);
                $customerId = $orderData['customer_id'];

                // Create order
                $pdo->beginTransaction();

                try {
                    $orderCode = 'ORD' . date('YmdHis') . rand(100, 999);

                    $stmt = $pdo->prepare("
                        INSERT INTO orders (
                            code, user_id, order_type, status, subtotal, grand_total,
                            payment_method, payment_status, shipping_address_id,
                            created_at, updated_at
                        )
                        VALUES (?, ?, 'Online', 'Chờ xử lý', ?, ?, 'ZaloPay', 'Đã thanh toán', ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $orderCode,
                        $customerId,
                        $orderData['subtotal'],
                        $orderData['subtotal'],
                        $orderData['address_id']
                    ]);

                    $orderId = $pdo->lastInsertId();

                    // Insert order items
                    $stmtItem = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, qty, unit_price, line_total)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    foreach ($orderData['cart_items'] as $item) {
                        $stmtItem->execute([
                            $orderId,
                            $item['id'],
                            $item['quantity'],
                            $item['price'],
                            $item['subtotal']
                        ]);

                        // Trừ kho
                        $stmtStock = $pdo->prepare("
                            UPDATE stocks 
                            SET qty = qty - ?, updated_at = NOW() 
                            WHERE product_id = ?
                        ");
                        $stmtStock->execute([$item['quantity'], $item['id']]);
                    }

                    // Tạo payment record
                    $paymentMeta = json_encode([
                        'app_trans_id' => $appTransID,
                        'zp_trans_id' => $dataJson['zp_trans_id'] ?? '',
                        'server_time' => $dataJson['server_time'] ?? '',
                        'amount' => $dataJson['amount'] ?? 0,
                        'discount_amount' => $dataJson['discount_amount'] ?? 0,
                        'channel' => $dataJson['channel'] ?? '',
                        'merchant_user_id' => $dataJson['merchant_user_id'] ?? ''
                    ]);

                    $stmtPayment = $pdo->prepare("
                        INSERT INTO payments (amount, method, txn_ref, paid_at, meta, created_at)
                        VALUES (?, 'ZaloPay', ?, NOW(), ?, NOW())
                    ");
                    $stmtPayment->execute([
                        $orderData['subtotal'],
                        $appTransID,
                        $paymentMeta
                    ]);

                    $paymentId = $pdo->lastInsertId();

                    // Update order với payment_id
                    $stmtUpdateOrder = $pdo->prepare("
                        UPDATE orders SET payment_id = ? WHERE id = ?
                    ");
                    $stmtUpdateOrder->execute([$paymentId, $orderId]);

                    // Clear cart
                    $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
                    if (!empty($orderData['selected_item_ids'])) {
                        foreach ($orderData['selected_item_ids'] as $productId) {
                            $cartRepo->removeItemDB($customerId, $productId);
                        }
                    } else {
                        $cartRepo->clearCartDB($customerId);
                    }

                    // Delete pending order
                    $stmt = $pdo->prepare("DELETE FROM pending_orders WHERE txn_ref = ?");
                    $stmt->execute([$appTransID]);

                    $pdo->commit();

                    $result['return_code'] = 1;
                    $result['return_message'] = 'success';
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    error_log('[ZaloPay] Callback error: ' . $e->getMessage());
                    $result['return_code'] = 0;
                    $result['return_message'] = $e->getMessage();
                }
            }

            echo json_encode($result);
        } catch (\Exception $e) {
            error_log('[ZaloPay] Callback exception: ' . $e->getMessage());
            echo json_encode(['return_code' => 0, 'return_message' => $e->getMessage()]);
        }
        exit;
    }
}
