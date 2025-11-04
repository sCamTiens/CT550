<?php
namespace App\Models\Entities;

class WorkShift
{
    public $id;
    public $name;
    public $start_time;
    public $end_time;
    public $wage_per_shift;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
