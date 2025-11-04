<?php
namespace App\Models\Entities;

class Attendance
{
    public $id;
    public $user_id;
    public $shift_id;
    public $attendance_date;
    public $check_in_time;
    public $check_out_time;
    public $status;
    public $notes;
    public $is_approved;
    public $approved_by;
    public $approved_at;
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
