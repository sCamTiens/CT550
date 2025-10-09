<?php
namespace App\Models\Entities;

class Staff
{
    public $user_id;
    public $username;
    public $full_name;
    public $email;
    public $phone;
    public $staff_role;
    public $hired_at;
    public $note;

    public function __construct($data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
