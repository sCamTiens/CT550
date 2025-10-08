<?php
namespace App\Models\Entities;

class StocktakeItem
{
    public $id;
    public $stocktake_id;
    public $product_id;
    public $system_qty;
    public $counted_qty;
    public $difference;
    public $created_at;
    public $updated_at;
    public $created_by;
}
