<?php

namespace App\Models\Customer\Repositories;

use App\Core\DB;

class ProductRepository
{
    /**
     * Lấy đường dẫn ảnh sản phẩm
     */
    public function getProductImage(int $productId): string
    {
        // Check if product image exists in filesystem
        $imagePath = __DIR__ . '/../../../../public/assets/images/products/' . $productId . '/1.png';

        if (file_exists($imagePath)) {
            return '/assets/images/products/' . $productId . '/1.png';
        }

        return '/assets/images/products/default.png';
    }

    /**
     * Lấy sản phẩm mới nhất
     */
    public function latest(int $limit = 12): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 
            ORDER BY id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int) $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return $rows;
    }

    /**
     * Lấy danh sách sản phẩm với phân trang
     */
    public function paginate(int $page = 1, int $perPage = 12, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $pdo = DB::pdo();

        // Build WHERE conditions
        $where = ['is_active = 1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $where[] = 'brand_id = :brand_id';
            $params[':brand_id'] = $filters['brand_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :search OR sku LIKE :search OR barcode LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Get products
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS 
                id, slug, name, sku, sale_price AS price, description, updated_at
            FROM products
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($products as &$product) {
            $product['image_url'] = $this->getProductImage($product['id']);
        }

        // Get total count
        $total = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage))
        ];
    }

    /**
     * Tìm sản phẩm theo slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sku, barcode, description, 
                   sale_price AS price, cost_price, tax_rate, 
                   brand_id, category_id, unit_id, updated_at
            FROM products 
            WHERE slug = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['image_url'] = $this->getProductImage($row['id']);
            return $row;
        }

        return null;
    }

    /**
     * Tìm sản phẩm theo ID
     */
    public function findById(int $id): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sku, barcode, description, 
                   sale_price AS price, cost_price, tax_rate, 
                   brand_id, category_id, unit_id, updated_at
            FROM products 
            WHERE id = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['image_url'] = $this->getProductImage($row['id']);
            return $row;
        }

        return null;
    }

    /**
     * Lấy sản phẩm liên quan
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 
                AND category_id = :category_id 
                AND id != :product_id
            ORDER BY RAND()
            LIMIT :limit
        ");
        $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return $rows;
    }

    /**
     * Tìm kiếm sản phẩm
     */
    public function search(string $keyword, int $limit = 20): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 
                AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?)
            ORDER BY name ASC
            LIMIT ?
        ");
        $searchTerm = '%' . $keyword . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return $rows;
    }

    /**
     * Lấy sản phẩm theo category
     */
    public function getByCategory(int $categoryId, int $limit = 12): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 AND category_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $limit]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return $rows;
    }

    /**
     * Lấy sản phẩm theo brand
     */
    public function getByBrand(int $brandId, int $limit = 12): array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 AND brand_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$brandId, $limit]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return $rows;
    }

    /**
     * Lấy sản phẩm theo danh mục (bao gồm danh mục con)
     */
    public function getByCategories(array $categoryIds, int $page = 1, int $perPage = 20): array
    {
        if (empty($categoryIds)) {
            return ['data' => [], 'total' => 0, 'pages' => 0];
        }

        $offset = ($page - 1) * $perPage;
        $pdo = DB::pdo();

        // Create named placeholders for IN clause
        $placeholders = [];
        $params = [];
        foreach ($categoryIds as $i => $id) {
            $key = ":cat_id_$i";
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $placeholderStr = implode(',', $placeholders);

        // Count total
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM products 
            WHERE is_active = 1 AND category_id IN ($placeholderStr)
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Get products
        $stmt = $pdo->prepare("
            SELECT p.id, p.slug, p.name, p.sale_price AS price, p.updated_at,
                   COALESCE(s.qty, 0) AS stock_qty
            FROM products p
            LEFT JOIN stocks s ON p.id = s.product_id
            WHERE p.is_active = 1 AND p.category_id IN ($placeholderStr)
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        // Bind all parameters
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return [
            'data' => $rows,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'page' => $page
        ];
    }

    /**
     * Lấy sản phẩm ngẫu nhiên (cho trang "Tất cả")
     */
    public function getRandomProducts(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $pdo = DB::pdo();

        // Count total
        $total = (int) $pdo->query("
            SELECT COUNT(*) FROM products WHERE is_active = 1
        ")->fetchColumn();

        // Get random products
        $stmt = $pdo->prepare("
            SELECT p.id, p.slug, p.name, p.sale_price AS price, p.updated_at,
                   COALESCE(s.qty, 0) AS stock_qty
            FROM products p
            LEFT JOIN stocks s ON p.id = s.product_id
            WHERE p.is_active = 1
            ORDER BY RAND()
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return [
            'data' => $rows,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'page' => $page
        ];
    }

    /**
     * Lấy sản phẩm với filters và sorting
     */
    public function getProductsWithFilters(array $filters, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $pdo = DB::pdo();

        // Build WHERE conditions
        $where = ['p.is_active = 1'];
        $params = [];

        // Category filter
        if (!empty($filters['category_ids'])) {
            $placeholders = [];
            foreach ($filters['category_ids'] as $i => $id) {
                $key = ":cat_id_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $where[] = 'p.category_id IN (' . implode(',', $placeholders) . ')';
        }

        // Brand filter
        if (!empty($filters['brand_ids'])) {
            $placeholders = [];
            foreach ($filters['brand_ids'] as $i => $id) {
                $key = ":brand_id_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $where[] = 'p.brand_id IN (' . implode(',', $placeholders) . ')';
        }

        // Price range filter
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $where[] = 'p.sale_price >= :min_price';
            $params[':min_price'] = $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $where[] = 'p.sale_price <= :max_price';
            $params[':max_price'] = $filters['max_price'];
        }

        $whereClause = implode(' AND ', $where);

        // Determine ORDER BY clause
        $orderBy = 'p.created_at DESC'; // default: newest
        switch ($filters['sort'] ?? 'newest') {
            case 'price_asc':
                $orderBy = 'p.sale_price ASC';
                break;
            case 'price_desc':
                $orderBy = 'p.sale_price DESC';
                break;
            case 'best_selling':
                // Join with order_items to get best selling
                $orderBy = 'total_sold DESC, p.created_at DESC';
                break;
            case 'newest':
            default:
                $orderBy = 'p.created_at DESC';
                break;
        }

        // Count total
        $countSql = "SELECT COUNT(*) FROM products p WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Get products
        if (($filters['sort'] ?? 'newest') === 'best_selling') {
            // For best selling, join with order_items
            $sql = "
                SELECT p.id, p.slug, p.name, p.sale_price AS price, p.updated_at,
                       COALESCE(s.qty, 0) AS stock_qty,
                       COALESCE(SUM(oi.quantity), 0) AS total_sold
                FROM products p
                LEFT JOIN stocks s ON p.id = s.product_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset
            ";
        } else {
            $sql = "
                SELECT p.id, p.slug, p.name, p.sale_price AS price, p.updated_at,
                       COALESCE(s.qty, 0) AS stock_qty
                FROM products p
                LEFT JOIN stocks s ON p.id = s.product_id
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset
            ";
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return [
            'data' => $rows,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'page' => $page
        ];
    }
}
