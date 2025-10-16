<?php
namespace App\Models\Repositories;

use App\Core\DB;

class PromotionRepository
{
    /**
     * Lấy toàn bộ danh sách chương trình khuyến mãi
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.*, 
                cu.full_name AS created_by_name,
                uu.full_name AS updated_by_name
            FROM promotions p
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            ORDER BY p.priority DESC, p.id DESC
        ";
        $items = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Load category_ids và product_ids cho mỗi promotion
        foreach ($items as &$item) {
            $item['category_ids'] = $this->getCategoryIds($item['id']);
            $item['product_ids'] = $this->getProductIds($item['id']);
        }

        return $items;
    }

    /**
     * Tìm chương trình khuyến mãi theo ID
     */
    public function findOne(int $id): ?array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.*, 
                cu.full_name AS created_by_name,
                uu.full_name AS updated_by_name
            FROM promotions p
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            WHERE p.id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($item) {
            $item['category_ids'] = $this->getCategoryIds($id);
            $item['product_ids'] = $this->getProductIds($id);
        }

        return $item ?: null;
    }

    /**
     * Lấy danh sách category_ids cho promotion
     */
    private function getCategoryIds(int $promotionId): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT category_id FROM promotion_categories WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'category_id');
    }

    /**
     * Lấy danh sách product_ids cho promotion
     */
    private function getProductIds(int $promotionId): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT product_id FROM promotion_products WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'product_id');
    }

    /**
     * Tạo chương trình khuyến mãi mới
     */
    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        
        try {
            $pdo->beginTransaction();

            // Tạo promotion
            $stmt = $pdo->prepare("
                INSERT INTO promotions (
                    name, description, discount_type, discount_value,
                    apply_to, priority, starts_at, ends_at, is_active,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :name, :description, :discount_type, :discount_value,
                    :apply_to, :priority, :starts_at, :ends_at, :is_active,
                    :created_by, :updated_by, NOW(), NOW()
                )
            ");

            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':discount_type' => $data['discount_type'] ?? 'percentage',
                ':discount_value' => $data['discount_value'] ?? 0,
                ':apply_to' => $data['apply_to'] ?? 'all',
                ':priority' => $data['priority'] ?? 0,
                ':starts_at' => $data['starts_at'] ?? null,
                ':ends_at' => $data['ends_at'] ?? null,
                ':is_active' => $data['is_active'] ?? 1,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

            // Lưu category_ids nếu có
            if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
                $stmtCat = $pdo->prepare("INSERT INTO promotion_categories (promotion_id, category_id) VALUES (?, ?)");
                foreach ($data['category_ids'] as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            // Lưu product_ids nếu có
            if (!empty($data['product_ids']) && is_array($data['product_ids'])) {
                $stmtProd = $pdo->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                foreach ($data['product_ids'] as $prodId) {
                    $stmtProd->execute([$id, $prodId]);
                }
            }

            $pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật chương trình khuyến mãi
     */
    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        
        try {
            $pdo->beginTransaction();

            // Cập nhật promotion
            $stmt = $pdo->prepare("
                UPDATE promotions SET
                    name = :name,
                    description = :description,
                    discount_type = :discount_type,
                    discount_value = :discount_value,
                    apply_to = :apply_to,
                    priority = :priority,
                    starts_at = :starts_at,
                    ends_at = :ends_at,
                    is_active = :is_active,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':discount_type' => $data['discount_type'] ?? 'percentage',
                ':discount_value' => $data['discount_value'] ?? 0,
                ':apply_to' => $data['apply_to'] ?? 'all',
                ':priority' => $data['priority'] ?? 0,
                ':starts_at' => $data['starts_at'] ?? null,
                ':ends_at' => $data['ends_at'] ?? null,
                ':is_active' => $data['is_active'] ?? 1,
                ':updated_by' => $currentUser,
                ':id' => $id,
            ]);

            // Xóa và tạo lại category_ids
            $pdo->prepare("DELETE FROM promotion_categories WHERE promotion_id = ?")->execute([$id]);
            if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
                $stmtCat = $pdo->prepare("INSERT INTO promotion_categories (promotion_id, category_id) VALUES (?, ?)");
                foreach ($data['category_ids'] as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            // Xóa và tạo lại product_ids
            $pdo->prepare("DELETE FROM promotion_products WHERE promotion_id = ?")->execute([$id]);
            if (!empty($data['product_ids']) && is_array($data['product_ids'])) {
                $stmtProd = $pdo->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                foreach ($data['product_ids'] as $prodId) {
                    $stmtProd->execute([$id, $prodId]);
                }
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa chương trình khuyến mãi
     */
    public function delete(int $id): void
    {
        $pdo = DB::pdo();
        
        try {
            $pdo->beginTransaction();

            // Xóa liên kết categories
            $pdo->prepare("DELETE FROM promotion_categories WHERE promotion_id = ?")->execute([$id]);

            // Xóa liên kết products
            $pdo->prepare("DELETE FROM promotion_products WHERE promotion_id = ?")->execute([$id]);

            // Xóa promotion
            $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
