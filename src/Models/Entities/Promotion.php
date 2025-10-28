<?php
namespace App\Models\Entities;

class Promotion
{
    public $id;
    public $name;
    public $description;
    public $promo_type;
    public $discount_type;
    public $discount_value;
    public $apply_to;
    public $priority;
    public $starts_at;
    public $ends_at;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    public $created_by_name;
    public $updated_by_name;
    public $category_ids;
    public $product_ids;
    public $bundle_rules;
    public $gift_rules;
    public $combo_price;
    public $combo_items;

    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
