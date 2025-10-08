<?php
namespace App\Models\Entities;

class PurchaseOrder
{
    public $id;
    public $code;
    public $supplier_id;
    public $total_amount;
    public $paid_amount;
    public $payment_status;
    public $due_date;
    public $note;
    public $received_at;
    public $created_by;
    public $created_at;
    public $updated_at;
}
