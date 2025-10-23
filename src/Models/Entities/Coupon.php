<?php
namespace App\Models\Entities;

class Coupon
{
    public $id;
    public $code;
    public $name;
    public $description;
    public $discount_type;
    public $discount_value;
    public $min_order_value;
    public $max_discount;
    public $max_uses;
    public $used_count;
    public $starts_at;
    public $ends_at;
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
