<?php

namespace App\Services;

use PDO;

class RecommendationService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Lấy sản phẩm gợi ý cho khách hàng dựa trên lịch sử mua hàng và tìm kiếm
     * 
     * @param int|null $userId ID của người dùng (NULL nếu là guest)
     * @param int $limit Số lượng sản phẩm gợi ý
     * @return array Danh sách sản phẩm gợi ý
     */
    public function getPersonalizedRecommendations(?int $userId, int $limit = 12): array
    {
        if (!$userId) {
            // Nếu chưa đăng nhập, trả về sản phẩm phổ biến
            return $this->getPopularProducts($limit);
        }

        // Lấy danh mục từ sản phẩm đã mua
        $purchasedCategories = $this->getPurchasedCategories($userId);

        // Lấy từ khóa từ lịch sử tìm kiếm
        $searchKeywords = $this->getSearchKeywords($userId);

        // Lấy sản phẩm đã mua để loại trừ
        $purchasedProductIds = $this->getPurchasedProductIds($userId);

        // Kết hợp các nguồn để tạo danh sách gợi ý
        $recommendations = [];

        // 1. Sản phẩm cùng danh mục với sản phẩm đã mua (60%)
        if (!empty($purchasedCategories)) {
            $categoryProducts = $this->getProductsByCategories(
                $purchasedCategories,
                $purchasedProductIds,
                (int)ceil($limit * 0.6)
            );
            $recommendations = array_merge($recommendations, $categoryProducts);
        }

        // 2. Sản phẩm liên quan đến từ khóa tìm kiếm (30%)
        if (!empty($searchKeywords)) {
            $searchProducts = $this->getProductsByKeywords(
                $searchKeywords,
                $purchasedProductIds,
                (int)ceil($limit * 0.3)
            );
            $recommendations = array_merge($recommendations, $searchProducts);
        }

        // 3. Sản phẩm phổ biến để fill thêm (10%)
        if (count($recommendations) < $limit) {
            $popularProducts = $this->getPopularProducts(
                $limit - count($recommendations),
                array_merge($purchasedProductIds, array_column($recommendations, 'id'))
            );
            $recommendations = array_merge($recommendations, $popularProducts);
        }

        // Loại bỏ trùng lặp và giới hạn số lượng
        $recommendations = $this->deduplicateProducts($recommendations);
        $recommendations = array_slice($recommendations, 0, $limit);

        return $recommendations;
    }

    /**
     * Lấy danh mục từ 5 đơn hàng gần nhất của khách hàng
     */
    private function getPurchasedCategories(int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.category_id, c.name
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            INNER JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.user_id = :user_id
                AND o.status NOT IN ('Đã hủy')
                AND p.category_id IS NOT NULL
            ORDER BY o.created_at DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lấy từ khóa từ lịch sử tìm kiếm gần đây
     */
    private function getSearchKeywords(int $userId, int $limit = 10): array
    {
        $sql = "
            SELECT DISTINCT search_query
            FROM search_history
            WHERE user_id = :user_id
                AND results_count > 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lấy ID của sản phẩm đã mua để loại trừ
     */
    private function getPurchasedProductIds(int $userId): array
    {
        $sql = "
            SELECT DISTINCT oi.product_id
            FROM orders o
            INNER JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = :user_id
                AND o.status NOT IN ('Đã hủy')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lấy sản phẩm theo danh mục
     */
    private function getProductsByCategories(array $categoryIds, array $excludeIds, int $limit): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        // Build named placeholders for category IDs
        $categoryPlaceholders = [];
        $params = [];
        foreach ($categoryIds as $i => $catId) {
            $key = "cat_{$i}";
            $categoryPlaceholders[] = ":{$key}";
            $params[$key] = $catId;
        }
        $categoryIn = implode(',', $categoryPlaceholders);

        // Build named placeholders for exclude IDs
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $excludePlaceholders = [];
            foreach ($excludeIds as $i => $exId) {
                $key = "ex_{$i}";
                $excludePlaceholders[] = ":{$key}";
                $params[$key] = $exId;
            }
            $excludeClause = 'AND p.id NOT IN (' . implode(',', $excludePlaceholders) . ')';
        }

        $sql = "
            SELECT p.*, pi.image_url, s.qty as stock_quantity,
                   p.sale_price as final_price
            FROM products p
            LEFT JOIN (
                SELECT product_id, image_url
                FROM product_images
                WHERE is_primary = 1
                GROUP BY product_id
            ) pi ON p.id = pi.product_id
            LEFT JOIN stocks s ON p.id = s.product_id
            WHERE p.category_id IN ($categoryIn)
                AND p.is_active = 1
                $excludeClause
            ORDER BY RAND()
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm theo từ khóa tìm kiếm
     */
    private function getProductsByKeywords(array $keywords, array $excludeIds, int $limit): array
    {
        if (empty($keywords)) {
            return [];
        }

        // Tạo search conditions
        $searchConditions = [];
        $params = [];
        foreach ($keywords as $i => $keyword) {
            $searchConditions[] = "(p.name LIKE :keyword{$i} OR p.description LIKE :keyword{$i})";
            $params["keyword{$i}"] = '%' . $keyword . '%';
        }

        // Build named placeholders for exclude IDs  
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $excludePlaceholders = [];
            foreach ($excludeIds as $i => $exId) {
                $key = "kw_ex_{$i}";
                $excludePlaceholders[] = ":{$key}";
                $params[$key] = $exId;
            }
            $excludeClause = 'AND p.id NOT IN (' . implode(',', $excludePlaceholders) . ')';
        }

        $sql = "
            SELECT p.*, pi.image_url, s.qty as stock_quantity,
                   p.sale_price as final_price
            FROM products p
            LEFT JOIN (
                SELECT product_id, image_url
                FROM product_images
                WHERE is_primary = 1
                GROUP BY product_id
            ) pi ON p.id = pi.product_id
            LEFT JOIN stocks s ON p.id = s.product_id
            WHERE (" . implode(' OR ', $searchConditions) . ")
                AND p.is_active = 1
                $excludeClause
            ORDER BY RAND()
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm phổ biến (fallback)
     */
    private function getPopularProducts(int $limit, array $excludeIds = []): array
    {
        // Build named placeholders for exclude IDs
        $params = [];
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $excludePlaceholders = [];
            foreach ($excludeIds as $i => $exId) {
                $key = "pop_ex_{$i}";
                $excludePlaceholders[] = ":{$key}";
                $params[$key] = $exId;
            }
            $excludeClause = 'AND p.id NOT IN (' . implode(',', $excludePlaceholders) . ')';
        }

        $sql = "
            SELECT p.*, pi.image_url, s.qty as stock_quantity,
                   p.sale_price as final_price,
                   COALESCE(SUM(oi.qty), 0) as total_sold
            FROM products p
            LEFT JOIN (
                SELECT product_id, image_url
                FROM product_images
                WHERE is_primary = 1
                GROUP BY product_id
            ) pi ON p.id = pi.product_id
            LEFT JOIN stocks s ON p.id = s.product_id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status NOT IN ('Đã hủy')
            WHERE p.is_active = 1
                $excludeClause
            GROUP BY p.id
            ORDER BY total_sold DESC, p.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Loại bỏ sản phẩm trùng lặp
     */
    private function deduplicateProducts(array $products): array
    {
        $seen = [];
        $result = [];

        foreach ($products as $product) {
            if (!isset($seen[$product['id']])) {
                $seen[$product['id']] = true;
                $result[] = $product;
            }
        }

        return $result;
    }

    /**
     * Lưu lịch sử tìm kiếm
     */
    public function saveSearchHistory(
        ?int $userId,
        string $searchQuery,
        int $resultsCount,
        string $searchType = 'product'
    ): void {
        $sql = "
            INSERT INTO search_history 
            (user_id, search_query, search_type, results_count, ip_address, user_agent, created_at)
            VALUES (:user_id, :search_query, :search_type, :results_count, :ip_address, :user_agent, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'search_query' => trim($searchQuery),
            'search_type' => $searchType,
            'results_count' => $resultsCount,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
