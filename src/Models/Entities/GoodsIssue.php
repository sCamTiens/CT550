<?php
namespace App\Models\Entities;

class GoodsIssue
{
    public $id;
    public $order_id;
    public $code;
    public $status;
    public $total_weight;
    public $total_volume;
    public $note;
    public $packed_by;
    public $created_at;
    public $updated_at;
    public $created_by;
}
