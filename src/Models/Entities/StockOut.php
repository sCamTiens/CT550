<?php
namespace App\Models\Entities;

class StockOut
{
    public $id;
    public $code;
    public $type; // sale, return, damage, other
    public $order_id;
    public $status; // pending, approved, completed, cancelled
    public $out_date;
    public $total_amount;
    public $note;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $created_by_name;
    public $updated_by;
    public $updated_by_name;
    public $customer_name;
    public $order_code;

    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
