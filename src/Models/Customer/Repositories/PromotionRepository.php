<?php

namespace App\Models\Customer\Repositories;

use App\Core\DB;
use PDO;

class PromotionRepository
{
    /**
     * Lấy đường dẫn ảnh sản phẩm
     */
    private function getProductImage(int $productId): string
    {
        // Check if product image exists in filesystem
        $imagePath = __DIR__ . '/../../../../public/assets/images/products/' . $productId . '/1.png';

        if (file_exists($imagePath)) {
            return '/assets/images/products/' . $productId . '/1.png';
        }

        return '/assets/images/products/default.png';
    }

    /**
     * Lấy danh sách khuyến mãi đang active
     */
    public function getActivePromotions(int $limit = 10): array
    {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.promo_type,
                    p.discount_type,
                    p.discount_value,
                    p.combo_price,
                    p.starts_at,
                    p.ends_at,
                    p.priority
                FROM promotions p
                WHERE p.is_active = 1
                  AND p.starts_at <= NOW()
                  AND p.ends_at >= NOW()
                ORDER BY p.priority DESC, p.created_at DESC
                LIMIT :limit";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết khuyến mãi bao gồm sản phẩm
     */
    public function getPromotionDetail(int $promotionId): ?array
    {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.promo_type,
                    p.discount_type,
                    p.discount_value,
                    p.combo_price,
                    p.starts_at,
                    p.ends_at
                FROM promotions p
                WHERE p.id = :id
                  AND p.is_active = 1
                  AND p.starts_at <= NOW()
                  AND p.ends_at >= NOW()";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['id' => $promotionId]);
        
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$promotion) {
            return null;
        }

        // Lấy sản phẩm theo loại khuyến mãi
        $promotion['products'] = $this->getPromotionProducts($promotionId, $promotion['promo_type']);
        
        return $promotion;
    }

    /**
     * Lấy sản phẩm theo loại khuyến mãi
     */
    private function getPromotionProducts(int $promotionId, string $promoType): array
    {
        switch ($promoType) {
            case 'combo':
                return $this->getComboProducts($promotionId);
            case 'bundle':
                return $this->getBundleProducts($promotionId);
            case 'gift':
                return $this->getGiftProducts($promotionId);
            case 'discount':
            default:
                return $this->getDiscountProducts($promotionId);
        }
    }

    /**
     * Lấy sản phẩm cho combo
     */
    private function getComboProducts(int $promotionId): array
    {
        $sql = "SELECT 
                    pci.product_id,
                    pci.required_qty,
                    pr.name,
                    pr.slug,
                    pr.sale_price
                FROM promotion_combo_items pci
                JOIN products pr ON pr.id = pci.product_id
                WHERE pci.promotion_id = :promotion_id
                ORDER BY pci.id";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['promotion_id' => $promotionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['product_id']);
        }
        
        return $rows;
    }

    /**
     * Lấy sản phẩm cho bundle (mua kèm)
     */
    private function getBundleProducts(int $promotionId): array
    {
        $sql = "SELECT 
                    pbr.product_id,
                    pbr.required_qty,
                    pbr.bundle_price,
                    pr.name,
                    pr.slug,
                    pr.sale_price
                FROM promotion_bundle_rules pbr
                JOIN products pr ON pr.id = pbr.product_id
                WHERE pbr.promotion_id = :promotion_id
                ORDER BY pbr.id";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['promotion_id' => $promotionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['product_id']);
        }
        
        return $rows;
    }

    /**
     * Lấy sản phẩm cho quà tặng
     */
    private function getGiftProducts(int $promotionId): array
    {
        $sql = "SELECT 
                    pgr.trigger_product_id as product_id,
                    pgr.required_qty,
                    pgr.gift_product_id,
                    pgr.gift_qty,
                    pr.name,
                    pr.slug,
                    pr.sale_price,
                    gift.name as gift_name
                FROM promotion_gift_rules pgr
                JOIN products pr ON pr.id = pgr.trigger_product_id
                JOIN products gift ON gift.id = pgr.gift_product_id
                WHERE pgr.promotion_id = :promotion_id
                ORDER BY pgr.id";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['promotion_id' => $promotionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['product_id']);
            $row['gift_image_url'] = $this->getProductImage($row['gift_product_id']);
        }
        
        return $rows;
    }

    /**
     * Lấy sản phẩm cho giảm giá
     */
    private function getDiscountProducts(int $promotionId): array
    {
        $sql = "SELECT 
                    pp.product_id,
                    pr.name,
                    pr.slug,
                    pr.sale_price
                FROM promotion_products pp
                JOIN products pr ON pr.id = pp.product_id
                WHERE pp.promotion_id = :promotion_id
                  AND pr.is_active = 1
                ORDER BY pr.name
                LIMIT 20";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['promotion_id' => $promotionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['product_id']);
        }
        
        return $rows;
    }

    /**
     * Lấy hình ảnh đại diện cho khuyến mãi
     */
    public function getPromotionImages(int $promotionId, string $promoType): array
    {
        if ($promoType === 'combo') {
            // Lấy 2 ảnh đầu tiên của combo
            $sql = "SELECT pci.product_id
                    FROM promotion_combo_items pci
                    WHERE pci.promotion_id = :promotion_id
                    ORDER BY pci.id
                    LIMIT 2";
        } else {
            // Lấy ảnh từ promotion_products
            $sql = "SELECT pp.product_id
                    FROM promotion_products pp
                    WHERE pp.promotion_id = :promotion_id
                    ORDER BY pp.product_id
                    LIMIT 4";
        }
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute(['promotion_id' => $promotionId]);
        $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get image paths from filesystem
        $images = [];
        foreach ($productIds as $productId) {
            $images[] = $this->getProductImage($productId);
        }
        
        return array_filter($images);
    }
}
