<?php

namespace App\Models\Entities;

/**
 * Entity: Lịch làm việc nhân viên
 * Quản lý việc sắp ca làm việc theo ngày cho từng nhân viên
 */
class StaffShiftSchedule
{
    public ?int $id = null;
    public int $staff_id;
    public int $shift_id;
    public string $work_date;
    public string $status = 'Làm việc';
    public ?string $note = null;
    public ?int $created_by = null;
    public ?int $updated_by = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Thông tin mở rộng (từ JOIN)
    public ?string $staff_name = null;
    public ?string $shift_name = null;
    public ?string $start_time = null;
    public ?string $end_time = null;
}
