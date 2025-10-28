<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Promotion;
use App\Support\Auditable;

class PromotionRepository
{
    use Auditable;

    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.id, p.name, p.description, p.promo_type,
                   p.discount_type, p.discount_value, p.apply_to,
                   p.priority, p.starts_at, p.ends_at, p.is_active,
                   p.created_at, p.updated_at,
                   p.created_by, cu.full_name AS created_by_name,
                   p.updated_by, uu.full_name AS updated_by_name
            FROM promotions p
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            ORDER BY p.priority DESC, p.id DESC
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['category_ids'] = []; // Tạm thời bỏ category
            $row['product_ids'] = $this->getProductIds($row['id']);
            $row['bundle_rules'] = $this->getBundleRules($row['id']);
            $row['gift_rules'] = $this->getGiftRules($row['id']);
            $row['combo_price'] = $this->getComboPrice($row['id']);
            $row['combo_items'] = $this->getComboItems($row['id']);
        }
        return array_map(fn($row) => new Promotion($row), $rows);
    }

    public function findOne(int $id): ?Promotion
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.id, p.name, p.description, p.promo_type,
                   p.discount_type, p.discount_value, p.apply_to,
                   p.priority, p.starts_at, p.ends_at, p.is_active,
                   p.created_at, p.updated_at,
                   p.created_by, cu.full_name AS created_by_name,
                   p.updated_by, uu.full_name AS updated_by_name
            FROM promotions p
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            WHERE p.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['category_ids'] = []; // Tạm thời bỏ category
            $row['product_ids'] = $this->getProductIds($id);
            $row['bundle_rules'] = $this->getBundleRules($id);
            $row['gift_rules'] = $this->getGiftRules($id);
            $row['combo_price'] = $this->getComboPrice($id);
            $row['combo_items'] = $this->getComboItems($id);
            return new Promotion($row);
        }
        return null;
    }
    // Bundle rules
    private function getBundleRules(int $promotionId): array
    {
        $stmt = DB::pdo()->prepare("SELECT product_id, required_qty AS qty, bundle_price AS price FROM promotion_bundle_rules WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Gift rules
    private function getGiftRules(int $promotionId): array
    {
        $stmt = DB::pdo()->prepare("SELECT trigger_product_id, required_qty AS trigger_qty, gift_product_id, gift_qty FROM promotion_gift_rules WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Combo price
    private function getComboPrice(int $promotionId): float
    {
        $stmt = DB::pdo()->prepare("SELECT combo_price FROM promotion_combo_rules WHERE promotion_id = ? LIMIT 1");
        $stmt->execute([$promotionId]);
        return (float) $stmt->fetchColumn();
    }

    // Combo items
    private function getComboItems(int $promotionId): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT pci.product_id, pci.required_qty AS qty
            FROM promotion_combo_items pci
            JOIN promotion_combo_rules pcr ON pcr.id = pci.combo_rule_id
            WHERE pcr.promotion_id = ?
        ");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO promotions
                (name, description, promo_type, discount_type, discount_value, apply_to,
                 priority, starts_at, ends_at, is_active, created_by, updated_by, created_at, updated_at)
                VALUES
                (:name, :description, :promo_type, :discount_type, :discount_value, :apply_to,
                 :priority, :starts_at, :ends_at, :is_active, :created_by, :updated_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':promo_type' => $data['promo_type'] ?? 'discount',
                ':discount_type' => $data['discount_type'] ?? 'percentage',
                ':discount_value' => $data['discount_value'] ?? 0,
                ':apply_to' => $data['apply_to'] ?? 'all',
                ':priority' => $data['priority'] ?? 0,
                ':starts_at' => $data['starts_at'] ?? null,
                ':ends_at' => $data['ends_at'] ?? null,
                ':is_active' => !empty($data['is_active']) ? 1 : 0,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

            // Lưu product (bỏ category vì bảng không hỗ trợ)
            if (!empty($data['product_ids']) && is_array($data['product_ids'])) {
                foreach ($data['product_ids'] as $productId) {
                    $pdo->prepare("
                        INSERT INTO promotion_products (promotion_id, product_id, created_by, updated_by)
                        VALUES (?, ?, ?, ?)
                    ")->execute([$id, $productId, $currentUser, $currentUser]);
                }
            }

            // Lưu bundle rules
            if (!empty($data['bundle_rules']) && is_array($data['bundle_rules'])) {
                foreach ($data['bundle_rules'] as $rule) {
                    $pdo->prepare("
                        INSERT INTO promotion_bundle_rules 
                        (promotion_id, product_id, required_qty, bundle_price, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $id, 
                        $rule['product_id'], 
                        $rule['qty'], 
                        $rule['price'], 
                        $currentUser, 
                        $currentUser
                    ]);
                }
            }

            // Lưu gift rules
            if (!empty($data['gift_rules']) && is_array($data['gift_rules'])) {
                foreach ($data['gift_rules'] as $rule) {
                    $pdo->prepare("
                        INSERT INTO promotion_gift_rules 
                        (promotion_id, trigger_product_id, required_qty, gift_product_id, gift_qty, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $id,
                        $rule['trigger_product_id'],
                        $rule['trigger_qty'],
                        $rule['gift_product_id'],
                        $rule['gift_qty'],
                        $currentUser,
                        $currentUser
                    ]);
                }
            }

            // Lưu combo
            if (!empty($data['combo_items']) && is_array($data['combo_items'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO promotion_combo_rules (promotion_id, combo_price, created_by, updated_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id, $data['combo_price'] ?? 0, $currentUser, $currentUser]);
                $comboRuleId = (int) $pdo->lastInsertId();

                foreach ($data['combo_items'] as $item) {
                    $pdo->prepare("
                        INSERT INTO promotion_combo_items (combo_rule_id, product_id, required_qty)
                        VALUES (?, ?, ?)
                    ")->execute([
                        $comboRuleId,
                        $item['product_id'],
                        $item['qty']
                    ]);
                }
            }

            $pdo->commit();

            
            $this->logCreate('promotions', $id, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'promo_type' => $data['promo_type'] ?? 'discount',
                'discount_type' => $data['discount_type'] ?? 'percentage',
                'discount_value' => $data['discount_value'] ?? 0,
                'apply_to' => $data['apply_to'] ?? 'all',
                'priority' => $data['priority'] ?? 0,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => !empty($data['is_active']) ? 1 : 0
            ]);            return $id;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $beforePromotion = $this->findOne($id);
        $beforeArray = null;
        if ($beforePromotion) {
            $beforeArray = [
                'name' => $beforePromotion->name,
                'description' => $beforePromotion->description,
                'promo_type' => $beforePromotion->promo_type,
                'discount_type' => $beforePromotion->discount_type,
                'discount_value' => $beforePromotion->discount_value,
                'apply_to' => $beforePromotion->apply_to,
                'priority' => $beforePromotion->priority,
                'starts_at' => $beforePromotion->starts_at,
                'ends_at' => $beforePromotion->ends_at,
                'is_active' => $beforePromotion->is_active
            ];
        }
        
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE promotions SET 
                    name = :name, description = :description, promo_type = :promo_type,
                    discount_type = :discount_type, discount_value = :discount_value, apply_to = :apply_to,
                    priority = :priority, starts_at = :starts_at, ends_at = :ends_at,
                    is_active = :is_active, updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':promo_type' => $data['promo_type'] ?? 'discount',
                ':discount_type' => $data['discount_type'] ?? 'percentage',
                ':discount_value' => $data['discount_value'] ?? 0,
                ':apply_to' => $data['apply_to'] ?? 'all',
                ':priority' => $data['priority'] ?? 0,
                ':starts_at' => $data['starts_at'] ?? null,
                ':ends_at' => $data['ends_at'] ?? null,
                ':is_active' => !empty($data['is_active']) ? 1 : 0,
                ':updated_by' => $currentUser,
            ]);

            // Xóa các liên kết cũ
            $pdo->prepare("DELETE FROM promotion_products WHERE promotion_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM promotion_bundle_rules WHERE promotion_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM promotion_gift_rules WHERE promotion_id = ?")->execute([$id]);
            
            // Xóa combo cũ
            $stmt = $pdo->prepare("SELECT id FROM promotion_combo_rules WHERE promotion_id = ?");
            $stmt->execute([$id]);
            $comboRuleIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($comboRuleIds as $comboRuleId) {
                $pdo->prepare("DELETE FROM promotion_combo_items WHERE combo_rule_id = ?")->execute([$comboRuleId]);
            }
            $pdo->prepare("DELETE FROM promotion_combo_rules WHERE promotion_id = ?")->execute([$id]);

            // Lưu lại product (bỏ category)
            if (!empty($data['product_ids']) && is_array($data['product_ids'])) {
                foreach ($data['product_ids'] as $productId) {
                    $pdo->prepare("
                        INSERT INTO promotion_products (promotion_id, product_id, created_by, updated_by)
                        VALUES (?, ?, ?, ?)
                    ")->execute([$id, $productId, $currentUser, $currentUser]);
                }
            }

            // Lưu lại bundle rules
            if (!empty($data['bundle_rules']) && is_array($data['bundle_rules'])) {
                foreach ($data['bundle_rules'] as $rule) {
                    $pdo->prepare("
                        INSERT INTO promotion_bundle_rules 
                        (promotion_id, product_id, required_qty, bundle_price, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $id, 
                        $rule['product_id'], 
                        $rule['qty'], 
                        $rule['price'], 
                        $currentUser, 
                        $currentUser
                    ]);
                }
            }

            // Lưu lại gift rules
            if (!empty($data['gift_rules']) && is_array($data['gift_rules'])) {
                foreach ($data['gift_rules'] as $rule) {
                    $pdo->prepare("
                        INSERT INTO promotion_gift_rules 
                        (promotion_id, trigger_product_id, required_qty, gift_product_id, gift_qty, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $id,
                        $rule['trigger_product_id'],
                        $rule['trigger_qty'],
                        $rule['gift_product_id'],
                        $rule['gift_qty'],
                        $currentUser,
                        $currentUser
                    ]);
                }
            }

            // Lưu lại combo
            if (!empty($data['combo_items']) && is_array($data['combo_items'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO promotion_combo_rules (promotion_id, combo_price, created_by, updated_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id, $data['combo_price'] ?? 0, $currentUser, $currentUser]);
                $comboRuleId = (int) $pdo->lastInsertId();

                foreach ($data['combo_items'] as $item) {
                    $pdo->prepare("
                        INSERT INTO promotion_combo_items (combo_rule_id, product_id, required_qty)
                        VALUES (?, ?, ?)
                    ")->execute([
                        $comboRuleId,
                        $item['product_id'],
                        $item['qty']
                    ]);
                }
            }

            $pdo->commit();            if ($beforeArray) {
                $afterArray = [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'discount_type' => $data['discount_type'] ?? 'percentage',
                    'discount_value' => $data['discount_value'] ?? 0,
                    'apply_to' => $data['apply_to'] ?? 'all',
                    'priority' => $data['priority'] ?? 0,
                    'starts_at' => $data['starts_at'] ?? null,
                    'ends_at' => $data['ends_at'] ?? null,
                    'is_active' => !empty($data['is_active']) ? 1 : 0
                ];
                $this->logUpdate('promotions', $id, $beforeArray, $afterArray);
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $beforePromotion = $this->findOne($id);
        $beforeArray = null;
        if ($beforePromotion) {
            $beforeArray = [
                'name' => $beforePromotion->name,
                'description' => $beforePromotion->description,
                'discount_type' => $beforePromotion->discount_type,
                'discount_value' => $beforePromotion->discount_value,
                'apply_to' => $beforePromotion->apply_to,
                'priority' => $beforePromotion->priority,
                'starts_at' => $beforePromotion->starts_at,
                'ends_at' => $beforePromotion->ends_at,
                'is_active' => $beforePromotion->is_active
            ];
        }

        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();
            
            // Delete promotion_products
            $pdo->prepare("DELETE FROM promotion_products WHERE promotion_id = ?")->execute([$id]);
            
            // Delete bundle rules
            $pdo->prepare("DELETE FROM promotion_bundle_rules WHERE promotion_id = ?")->execute([$id]);
            
            // Delete gift rules
            $pdo->prepare("DELETE FROM promotion_gift_rules WHERE promotion_id = ?")->execute([$id]);
            
            // Delete combo: first get combo_rule ids, delete items, then delete rules
            $stmtCombo = $pdo->prepare("SELECT id FROM promotion_combo_rules WHERE promotion_id = ?");
            $stmtCombo->execute([$id]);
            $comboRuleIds = $stmtCombo->fetchAll(\PDO::FETCH_COLUMN);
            if (!empty($comboRuleIds)) {
                $placeholders = implode(',', array_fill(0, count($comboRuleIds), '?'));
                $pdo->prepare("DELETE FROM promotion_combo_items WHERE combo_rule_id IN ($placeholders)")->execute($comboRuleIds);
                $pdo->prepare("DELETE FROM promotion_combo_rules WHERE promotion_id = ?")->execute([$id]);
            }
            
            // Finally delete promotion
            $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
            
            $pdo->commit();

            if ($beforeArray) {
                $this->logDelete('promotions', $id, $beforeArray);
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function getCategoryIds(int $promotionId): array
    {
        // Tạm thời không hỗ trợ category vì bảng không có cột này
        return [];
    }

    private function getProductIds(int $promotionId): array
    {
        $stmt = DB::pdo()->prepare("SELECT product_id FROM promotion_products WHERE promotion_id = ? AND product_id IS NOT NULL");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}

