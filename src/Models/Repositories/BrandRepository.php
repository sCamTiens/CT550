<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Brand;
use App\Support\Auditable;

class BrandRepository
{
    use Auditable;

    public function all()
    {
     $pdo = DB::pdo();
     $rows = $pdo->query("SELECT b.id, b.name, b.slug, b.created_at, b.updated_at,
                b.created_by, b.updated_by,
                u1.full_name AS created_by_name,
                u2.full_name AS updated_by_name
            FROM brands b
            LEFT JOIN users u1 ON u1.id = b.created_by
            LEFT JOIN users u2 ON u2.id = b.updated_by
            ORDER BY b.id DESC")
         ->fetchAll(\PDO::FETCH_ASSOC);
     return array_map([$this, 'mapToEntity'], $rows);
    }

    public function find($id)
    {
     $pdo = DB::pdo();
     $st = $pdo->prepare("SELECT b.id, b.name, b.slug, b.created_at, b.updated_at,
                b.created_by, b.updated_by,
                u1.full_name AS created_by_name,
                u2.full_name AS updated_by_name
            FROM brands b
            LEFT JOIN users u1 ON u1.id = b.created_by
            LEFT JOIN users u2 ON u2.id = b.updated_by
            WHERE b.id=?");
     $st->execute([$id]);
     $row = $st->fetch(\PDO::FETCH_ASSOC);
     return $row ? $this->mapToEntity($row) : null;
    }

    public function create($name, $slug, $userId)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO brands(name,slug,created_by,updated_by)
                                   VALUES(:name,:slug,:created_by,:updated_by)");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug ?: null,
            ':created_by' => $userId,
            ':updated_by' => $userId
        ]);
        $id = $pdo->lastInsertId();
        
        // Log audit
        $this->logCreate('brands', (int)$id, [
            'name' => $name,
            'slug' => $slug,
            'created_by' => $userId,
            'updated_by' => $userId
        ]);
        
        return $this->find($id);
    }

    public function update($id, $name, $slug, $userId)
    {
        $pdo = DB::pdo();
        
        // Lấy dữ liệu trước khi update
        $beforeData = $this->find($id);
        $beforeArray = $beforeData ? (array)$beforeData : [];
        
        $stmt = $pdo->prepare("UPDATE brands 
                                   SET name=:name, slug=:slug, updated_by=:updated_by
                                   WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':slug' => $slug ?: null,
            ':updated_by' => $userId
        ]);
        
        // Log audit
        $afterArray = [
            'name' => $name,
            'slug' => $slug,
            'updated_by' => $userId
        ];
        $this->logUpdate('brands', (int)$id, $beforeArray, $afterArray);
        
        return $this->find($id);
    }

    /**
     * @param array $row
     * @return Brand
     */
    private function mapToEntity($row)
    {
        $brand = new Brand();
        foreach ($row as $k => $v) {
            if (property_exists($brand, $k)) $brand->$k = $v;
        }
        return $brand;
    }

    public function delete($id)
    {
        $pdo = DB::pdo();
        
        // Lấy dữ liệu trước khi xóa
        $beforeData = $this->find($id);
        $beforeArray = $beforeData ? (array)$beforeData : [];
        
        $pdo->prepare("DELETE FROM brands WHERE id=?")->execute([$id]);
        
        // Log audit
        $this->logDelete('brands', (int)$id, $beforeArray);
    }
}
