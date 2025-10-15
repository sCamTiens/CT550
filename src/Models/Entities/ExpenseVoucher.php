<?php
namespace App\Models\Entities;

class ExpenseVoucher
{
    public $id;
    public $code;
    public $purchase_order_id;
    public $supplier_id;
    public $method;
    public $txn_ref;      // Mã giao dịch ngân hàng (nếu có)
    public $amount;
    public $paid_by;
    public $paid_at;
    public $bank_time;    // Thời gian giao dịch ngân hàng (nếu có)
    public $note;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    public $created_by_name;
    public $updated_by_name;

    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
