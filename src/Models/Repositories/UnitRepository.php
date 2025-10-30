<?php

namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Unit;
use App\Support\Auditable;

class UnitRepository
{
    use Auditable;

    // Kiểm tra có thể xóa không: nếu còn sản phẩm dùng đơn vị này thì không cho xóa
    public function canDelete($id): bool
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM products WHERE unit_id = ?");
        $st->execute([$id]);
        return $st->fetchColumn() == 0;
    }
    public function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT u.id, u.name, u.slug,
                       u.created_at, u.updated_at,
                       u.created_by, cu.full_name AS created_by_name,
                       u.updated_by, uu.full_name AS updated_by_name
                FROM units u
                LEFT JOIN users cu ON cu.id = u.created_by
                LEFT JOIN users uu ON uu.id = u.updated_by
                ORDER BY u.id DESC";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Unit($row), $rows);
    }

    public function findOne($id)
    {
        $pdo = DB::pdo();
        $sql = "SELECT u.id, u.name, u.slug,
                       u.created_at, u.updated_at,
                       u.created_by, cu.full_name AS created_by_name,
                       u.updated_by, uu.full_name AS updated_by_name
                FROM units u
                LEFT JOIN users cu ON cu.id = u.created_by
                LEFT JOIN users uu ON uu.id = u.updated_by
                WHERE u.id=?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Unit($row) : null;
    }

    public function create($name, $slug, $currentUserId)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO units (name, slug, created_by, updated_by, created_at, updated_at) VALUES (:name, :slug, :created_by, :updated_by, NOW(), NOW())");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug ?: null,
            ':created_by' => $currentUserId,
            ':updated_by' => $currentUserId,
        ]);
        $id = $pdo->lastInsertId();
        
        // Log audit
        $this->logCreate('units', (int)$id, [
            'name' => $name,
            'slug' => $slug ?: null
        ]);
        
        return $id;
    }

    public function update($id, $name, $slug, $currentUserId)
    {
        // Get before data
        $beforeUnit = $this->findOne($id);
        $beforeArray = null;
        if ($beforeUnit) {
            $beforeArray = [
                'name' => $beforeUnit->name,
                'slug' => $beforeUnit->slug
            ];
        }
        
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("UPDATE units SET name=:name, slug=:slug, updated_by=:updated_by, updated_at=NOW() WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':slug' => $slug ?: null,
            ':updated_by' => $currentUserId,
        ]);
        
        // Log audit
        if ($beforeArray) {
            $afterArray = [
                'name' => $name,
                'slug' => $slug ?: null
            ];
            $this->logUpdate('units', (int)$id, $beforeArray, $afterArray);
        }
    }

    public function findBySlug($slug)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT * FROM units WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Unit($row) : null;
    }

    public function delete($id)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException('Không thể xoá vì đơn vị này đang được ràng buộc với sản phẩm.');
        }
        
        // Get before data
        $beforeUnit = $this->findOne($id);
        $beforeArray = null;
        if ($beforeUnit) {
            $beforeArray = [
                'name' => $beforeUnit->name,
                'slug' => $beforeUnit->slug
            ];
        }
        
        $pdo = DB::pdo();
        $st = $pdo->prepare("DELETE FROM units WHERE id=?");
        $st->execute([$id]);
        
        // Log audit
        if ($beforeArray) {
            $this->logDelete('units', (int)$id, $beforeArray);
        }
    }
}
