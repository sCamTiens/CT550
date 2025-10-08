<?php
namespace App\Models\Entities;

class Brand
{
    public $id;
    public $name;
    public $slug;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    // Optionally for joined data
    public $created_by_name;
    public $updated_by_name;
}
