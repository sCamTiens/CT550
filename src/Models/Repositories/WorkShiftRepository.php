<?php
namespace App\Models\Repositories;

use App\Core\DB;
use PDO;

class WorkShiftRepository
{
    protected $table = 'work_shifts';

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY start_time ASC";
        return DB::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): array|false
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): array|false
    {
        $sql = "INSERT INTO {$this->table} (name, start_time, end_time, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['start_time'],
            $data['end_time'],
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null
        ]);
        
        $id = DB::pdo()->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data): array|false
    {
        $sql = "UPDATE {$this->table} SET 
                name = ?, 
                start_time = ?, 
                end_time = ?, 
                is_active = ?,
                updated_by = ?
                WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['start_time'],
            $data['end_time'],
            $data['is_active'],
            $data['updated_by'] ?? null,
            $id
        ]);
        
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = DB::pdo()->prepare($sql);
        return $stmt->execute([$id]);
    }
}
