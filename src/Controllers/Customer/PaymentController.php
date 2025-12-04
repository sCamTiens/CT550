<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;

class PaymentController extends Controller
{
    /**
     * COD payment success page
     */
    public function codSuccess(Request $req): mixed
    {
        // Get order info from session
        $orderInfo = $_SESSION['order_success'] ?? null;

        if (!$orderInfo) {
            // No order info - redirect to home
            header('Location: /');
            exit;
        }

        // Clear session data after displaying
        unset($_SESSION['order_success']);

        return $this->view('customer/payment/cod_success', [
            'order_id' => $orderInfo['order_id'],
            'order_code' => $orderInfo['order_code'],
            'amount' => $orderInfo['amount'],
            'payment_method' => $orderInfo['payment_method']
        ]);
    }
}
