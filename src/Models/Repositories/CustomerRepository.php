<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Customer;

class CustomerRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
            u.id, u.username, u.full_name, u.email, u.phone, u.gender, u.date_of_birth, u.is_active,
            u.created_at, u.updated_at,
            cu.full_name AS created_by_name,
            uu.full_name AS updated_by_name
        FROM users u
        LEFT JOIN users cu ON cu.id = u.created_by
        LEFT JOIN users uu ON uu.id = u.updated_by
        WHERE u.role_id IN (1,2)
        ORDER BY u.created_at DESC, u.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Customer($row), $rows);
    }

    public static function create($data)
    {
        $pdo = DB::pdo();
        $sql = "INSERT INTO users (role_id, username, full_name, email, phone, gender, date_of_birth, is_active, created_by)
                VALUES (2, :username, :full_name, :email, :phone, :gender, :date_of_birth, :is_active, :created_by)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $data['username'],
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':gender' => $data['gender'],
            ':date_of_birth' => $data['date_of_birth'],
            ':is_active' => $data['is_active'] ?? 1,
            ':created_by' => $data['created_by'] ?? null
        ]);
        $id = $pdo->lastInsertId();
        return self::find($id);
    }

    public static function update($id, $data)
    {
        $pdo = DB::pdo();
        $sql = "UPDATE users SET username=:username, full_name=:full_name, email=:email, phone=:phone, gender=:gender, date_of_birth=:date_of_birth, is_active=:is_active, updated_by=:updated_by WHERE id=:id AND role_id IN (1,2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $data['username'],
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':gender' => $data['gender'],
            ':date_of_birth' => $data['date_of_birth'],
            ':is_active' => $data['is_active'] ?? 1,
            ':updated_by' => $data['updated_by'] ?? null,
            ':id' => $id
        ]);
        return self::find($id);
    }

    public static function delete($id)
    {
        $pdo = DB::pdo();
        $sql = "DELETE FROM users WHERE id=:id AND role_id IN (1,2)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public static function find($id)
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
            u.id, u.username, u.full_name, u.email, u.phone, u.gender, u.date_of_birth, u.is_active,
            u.created_at, u.updated_at,
            cu.full_name AS created_by_name,
            uu.full_name AS updated_by_name
        FROM users u
        LEFT JOIN users cu ON cu.id = u.created_by
        LEFT JOIN users uu ON uu.id = u.updated_by
        WHERE u.id = :id AND u.role_id IN (1,2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Customer($row) : null;
    }
}
