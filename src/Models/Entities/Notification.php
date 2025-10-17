<?php
namespace App\Models\Entities;

class Notification
{
    public $id;
    public $user_id;
    public $type;
    public $title;
    public $message;
    public $link;
    public $is_read;
    public $read_at;
    public $created_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
