<?php
namespace App\Models\Entities;

class ReceiptVoucher
{
    public $id;
    public $code;
    public $received_at;
    public $amount;
    public $payer_name;
    public $note;
    public $is_active;
    public $txn_ref;
    public $bank_time;
    public $created_by;
    public $updated_by;
    public $created_at;
    public $updated_at;
    public $created_by_name;
    public $updated_by_name;

    public function __construct($data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
