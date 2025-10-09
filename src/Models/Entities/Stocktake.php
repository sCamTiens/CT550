<?php
namespace App\Models\Entities;

class Stocktake
{
    public $id;
    public $note;
    public $created_by;
    public $created_by_name;
    public $updated_by;
    public $updated_by_name;
    public $created_at;
    public $updated_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
