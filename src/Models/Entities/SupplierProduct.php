<?php
namespace App\Models\Entities;

class SupplierProduct
{
    public $supplier_id;
    public $product_id;
    public $supplier_sku;
    public $default_cost;
    public $moq;
    public $lead_time_days;
    public $preference_score;
    public $is_active;
    public $updated_at;
    public $created_by;
}
