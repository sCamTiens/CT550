<?php
namespace App\Models\Entities;

class PurchaseOrderItem
{
    public $id;
    public $purchase_order_id;
    public $product_id;
    public $qty;
    public $unit_cost;
    public $line_total;
}
