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
                AND category_id = ? 
                AND id != ?
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $productId, $limit]);
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
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1 AND category_id IN ($placeholderStr)
            ORDER BY created_at DESC
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
            SELECT id, slug, name, sale_price AS price, updated_at
            FROM products 
            WHERE is_active = 1
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
}

