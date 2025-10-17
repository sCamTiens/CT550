<?php
namespace App\Models\Entities;

class ProductBatch
{
    public $id;
    public $product_id;
    public $batch_code;
    public $mfg_date;
    public $exp_date;
    public $initial_qty;
    public $current_qty;
    public $purchase_order_id;
    public $note;
    public $unit_cost;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    
    // Thêm các field từ JOIN với bảng products
    public $product_name;
    public $product_sku;

    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) $this->$k = $v;
        }
    }
}
