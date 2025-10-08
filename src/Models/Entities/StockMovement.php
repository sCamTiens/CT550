<?php
namespace App\Models\Entities;

class StockMovement
{
    public $id;
    public $product_id;
    public $type;
    public $ref_type;
    public $ref_id;
    public $qty;
    public $note;
    public $created_at;
}
