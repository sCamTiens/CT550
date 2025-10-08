<?php
namespace App\Models\Repositories;

use App\Core\DB;

class CategoryRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.is_active, 
                       c.created_at, c.updated_at,
                       c.created_by, cu.full_name AS created_by_name,
                       c.updated_by, uu.full_name AS updated_by_name,
                       p.name AS parent_name
                FROM categories c
                LEFT JOIN categories p ON p.id = c.parent_id
                LEFT JOIN users cu ON cu.id = c.created_by
                LEFT JOIN users uu ON uu.id = c.updated_by
                ORDER BY c.id DESC";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.is_active, 
                                    c.created_at, c.updated_at,
                                    c.created_by, cu.full_name AS created_by_name,
                                    c.updated_by, uu.full_name AS updated_by_name
                             FROM categories c
                             LEFT JOIN users cu ON cu.id = c.created_by
                             LEFT JOIN users uu ON uu.id = c.updated_by
                             WHERE c.id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO categories
            (name, slug, parent_id, sort_order, is_active, created_by, updated_by, created_at, updated_at)
            VALUES (:name, :slug, :parent_id, :sort_order, :is_active, :created_by, :updated_by, NOW(), NOW())");
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':parent_id' => $data['parent_id'],
            ':sort_order' => $data['sort_order'],
            ':is_active' => $data['is_active'],
            ':created_by' => $data['created_by'],
            ':updated_by' => $data['updated_by'],
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("UPDATE categories
            SET name=:name, slug=:slug, parent_id=:parent_id, sort_order=:sort_order, 
                is_active=:is_active, updated_by=:updated_by, updated_at=NOW()
            WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':parent_id' => $data['parent_id'],
            ':sort_order' => $data['sort_order'],
            ':is_active' => $data['is_active'],
            ':updated_by' => $data['updated_by'],
        ]);
    }

    public static function canDelete($id)
    {
        $pdo = DB::pdo();
        // Check if is parent of another category
        $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $chk->execute([$id]);
        if ($chk->fetchColumn() > 0) return 'parent';
        // Check if any product uses this category
        $chk2 = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $chk2->execute([$id]);
        if ($chk2->fetchColumn() > 0) return 'product';
        return true;
    }

    public static function delete($id)
    {
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    }
}
