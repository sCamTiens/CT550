<?php
namespace App\Models\Entities;

class Product
{
	public $id;
	public $sku;
	public $name;
	public $slug;
	public $brand_id;
	public $category_id;
	public $pack_size;
	public $unit_id;
	public $barcode;
	public $description;
	public $sale_price;
	public $cost_price;
	public $tax_rate;
	public $is_active;
	public $created_at;
	public $updated_at;
	public $created_by;
	public $updated_by;
}
