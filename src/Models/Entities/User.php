<?php
namespace App\Models\Entities;

class User
{
	public $id;
	public $username;
	public $role_id;
	public $email;
	public $phone;
	public $password_hash;
	public $full_name;
	public $avatar_url;
	public $gender;
	public $date_of_birth;
	public $is_active;
	public $created_at;
	public $updated_at;
	public $created_by;
	public $updated_by;
	public $role_name; // for joined role name
}
