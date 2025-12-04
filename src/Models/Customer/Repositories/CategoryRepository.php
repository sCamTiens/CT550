<?php

namespace App\Models\Customer\Repositories;

use App\Core\DB;

class CategoryRepository
{
    /**
     * Lấy tất cả danh mục cha (parent_id = NULL)
     */
    public function getParentCategories(): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, name, slug, sort_order
            FROM categories
            WHERE parent_id IS NULL AND is_active = 1 AND slug != 'qua-tang'
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh mục con theo parent_id
     */
    public function getChildCategories(int $parentId): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, name, slug, sort_order
            FROM categories
            WHERE parent_id = :parent_id AND is_active = 1 AND slug != 'qua-tang'
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả danh mục với cấu trúc cha-con
     */
    public function getCategoriesTree(): array
    {
        $parents = $this->getParentCategories();

        foreach ($parents as &$parent) {
            $parent['children'] = $this->getChildCategories($parent['id']);
        }

        return $parents;
    }

    /**
     * Lấy danh mục theo slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, parent_id, name, slug
            FROM categories
            WHERE slug = :slug AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Lấy danh mục theo ID
     */
    public function findById(int $id): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, parent_id, name, slug
            FROM categories
            WHERE id = :id AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Lấy tất cả ID con (bao gồm cả cháu, chắt...) của một danh mục
     */
    public function getAllChildIds(int $parentId): array
    {
        $ids = [$parentId];
        $children = $this->getChildCategories($parentId);

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getAllChildIds($child['id']));
        }

        return $ids;
    }
}
