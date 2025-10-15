<?php
namespace App\Models\Entities;

class Order
{
    public $id;
    public $code;
    public $customer_id;
    public $customer_name;
    public $customer_phone;
    public $customer_email;
    public $shipping_address;
    public $province_code;
    public $province_name;
    public $commune_code;
    public $commune_name;
    public $status;
    public $payment_id;
    public $payment_method;
    public $payment_status;
    public $subtotal;
    public $discount_amount;
    public $shipping_fee;
    public $tax_amount;
    public $total_amount;
    public $note;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $created_by_name;
    public $updated_by;
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
