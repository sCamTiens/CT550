<?php
namespace App\Models\Repositories;


use App\Core\DB;
use App\Models\Entities\Role;

class RoleRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT id, name FROM roles ORDER BY id ASC";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function($row) {
            $role = new Role();
            foreach ($row as $k => $v) $role->$k = $v;
            return $role;
        }, $rows);
    }

}
