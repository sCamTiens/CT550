<?php
namespace App\Models\Entities;

class Stock
{
    public $product_id;
    public $product_sku;
    public $product_name;
    public $unit_name;
    public $qty;
    public $safety_stock;
    public $min_qty;
    public $max_qty;
    public $updated_at;
    public $updated_by;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
