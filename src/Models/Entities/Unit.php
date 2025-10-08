<?php
namespace App\Models\Entities;

class Unit
{
    public $id;
    public $name;
    public $slug;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    public $created_by_name;
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
