<?php
namespace App\Models\Entities;

class ReceiptVoucher
{
    public $id;
    public $code;
    public $payer_user_id;
    public $payer_user_name;
    public $order_id;
    public $order_code;
    public $payment_id;
    public $method;
    public $amount;
    public $received_by;
    public $received_by_name;
    public $received_at;
    public $txn_ref;
    public $bank_time;
    public $note;
    public $created_by;
    public $created_by_name;
    public $updated_by;
    public $updated_by_name;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
