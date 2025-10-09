<?php
namespace App\Models\Entities;

class Customer
{
    public $id;
    public $username;
    public $full_name;
    public $email;
    public $phone;
    public $gender;
    public $date_of_birth;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $created_by_name;
    public $updated_by_name;

    public function __construct($data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) $this->$k = $v;
        }
    }
}
