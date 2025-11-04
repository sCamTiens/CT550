<?php
namespace App\Models\Entities;

class Payroll
{
    public $id;
    public $user_id;
    public $month;
    public $year;
    public $total_shifts_worked;
    public $required_shifts;
    public $base_salary;
    public $actual_salary;
    public $bonus;
    public $deduction;
    public $total_salary;
    public $status;
    public $notes;
    public $created_by;
    public $updated_by;
    public $approved_by;
    public $approved_at;
    public $paid_at;
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
