<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Product;
use App\Support\Auditable;

class ProductRepository
{
    use Auditable;

    /**
     * Kiểm tra xem có thể xóa sản phẩm hay không
     * → Nếu sản phẩm đang được ràng buộc với các bảng khác thì KHÔNG cho xóa
     */
    public function canDelete(int $id): bool
    {
        $pdo = DB::pdo();

        // Các bảng có thể chứa khóa ngoại hoặc liên kết tới sản phẩm
        $relations = [
            'order_items' => 'product_id',
            'purchase_order_items' => 'product_id',
            'product_batches' => 'product_id',
            'product_images' => 'product_id',
            'promotion_products' => 'product_id',
            'promotion_bundle_rules' => 'product_id',
            'promotion_gift_rules' => 'trigger_product_id',
            'promotion_gift_rules_2' => 'gift_product_id',
            'stock_movements' => 'product_id',
            'stocks' => 'product_id',
            'cart_items' => 'product_id',
            'product_reviews' => 'product_id',
            'similar_items' => 'product_id',
            'similar_items_2' => 'similar_id',
        ];

        foreach ($relations as $table => $column) {
            $realTable = $table;
            if ($table === 'promotion_gift_rules_2')
                $realTable = 'promotion_gift_rules';
            if ($table === 'similar_items_2')
                $realTable = 'similar_items';

            $sql = "SELECT COUNT(*) FROM {$realTable} WHERE {$column} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {
                return false; // Có ràng buộc → không cho xóa
            }
        }

        return true;
    }

    /**
     * Lấy đường dẫn ảnh sản phẩm
     */
    private function getProductImage(int $productId): string
    {
        // Check if product image exists in filesystem
        $imagePath = __DIR__ . '/../../../public/assets/images/products/' . $productId . '/1.png';

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
            SELECT id, slug, name, sale_price AS price
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
     * Tìm sản phẩm theo slug
     */
    public function findBySlug(string $slug): ?array
    {
        $st = DB::pdo()->prepare("SELECT * FROM products WHERE slug = ?");
        $st->execute([$slug]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['image_url'] = $this->getProductImage($row['id']);
            return $row;
        }

        return null;
    }

    /**
     * Tìm sản phẩm theo SKU
     */
    public function findBySku(string $sku): ?array
    {
        $st = DB::pdo()->prepare("SELECT * FROM products WHERE sku = ?");
        $st->execute([$sku]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['image_url'] = $this->getProductImage($row['id']);
            return $row;
        }

        return null;
    }

    /**
     * Lấy toàn bộ danh sách sản phẩm (cho admin)
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
                   p.sale_price, p.cost_price, p.tax_rate, p.is_active,
                   p.brand_id, p.category_id, p.unit_id,
                   p.created_at, p.updated_at,
                   p.created_by, cu.full_name AS created_by_name,
                   p.updated_by, uu.full_name AS updated_by_name,
                   b.name AS brand_name, c.name AS category_name, u.name AS unit_name,
                   s.qty AS stock_qty 
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN units u ON u.id = p.unit_id
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            LEFT JOIN stocks s ON s.product_id = p.id
            WHERE p.is_active = 1
            ORDER BY p.id DESC 
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return array_map(fn($row) => new Product($row), $rows);
    }

    /**
     * Lấy tất cả sản phẩm (bao gồm cả không hoạt động) - dùng cho quà tặng
     */
    public function allIncludingInactive(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
                   p.sale_price, p.cost_price, p.tax_rate, p.is_active,
                   p.brand_id, p.category_id, p.unit_id,
                   p.created_at, p.updated_at,
                   p.created_by, cu.full_name AS created_by_name,
                   p.updated_by, uu.full_name AS updated_by_name,
                   b.name AS brand_name, c.name AS category_name, u.name AS unit_name,
                   s.qty AS stock_qty 
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN units u ON u.id = p.unit_id
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            LEFT JOIN stocks s ON s.product_id = p.id
            ORDER BY p.is_active DESC, p.id DESC 
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Add image path for each product
        foreach ($rows as &$row) {
            $row['image_url'] = $this->getProductImage($row['id']);
        }

        return array_map(fn($row) => new Product($row), $rows);
    }

    /**
     * Tìm sản phẩm theo ID
     */
    public function findOne(int $id): ?Product
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
                   p.sale_price, p.cost_price, p.tax_rate, p.is_active,
                   p.brand_id, p.category_id, p.unit_id,
                   p.created_at, p.updated_at,
                   p.created_by, cu.full_name AS created_by_name,
                   p.updated_by, uu.full_name AS updated_by_name,
                   b.name AS brand_name, c.name AS category_name, u.name AS unit_name,
                   s.qty AS stock_qty 
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN units u ON u.id = p.unit_id
            LEFT JOIN stocks s ON s.product_id = p.id  
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            WHERE p.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $row['image_url'] = $this->getProductImage($row['id']);
            return new Product($row);
        }

        return null;
    }

    /**
     * Tạo sản phẩm mới
     */
    public function create(array $data, int $currentUser, string $slug): int
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO products
                (sku, name, slug, brand_id, category_id, pack_size, unit_id, barcode, description,
                 sale_price, cost_price, tax_rate, is_active, created_by, updated_by, created_at, updated_at)
                VALUES
                (:sku, :name, :slug, :brand_id, :category_id, :pack_size, :unit_id, :barcode, :description,
                 :sale_price, :cost_price, :tax_rate, :is_active, :created_by, :updated_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':sku' => $data['sku'],
                ':name' => $data['name'],
                ':slug' => $slug,
                ':brand_id' => $data['brand_id'] ?: null,
                ':category_id' => $data['category_id'] ?: null,
                ':pack_size' => $data['pack_size'] ?? null,
                ':unit_id' => $data['unit_id'] ?: null,
                ':barcode' => $data['barcode'] ?? null,
                ':description' => $data['description'] ?? null,
                ':sale_price' => $data['sale_price'] ?? 0,
                ':cost_price' => $data['cost_price'] ?? 0,
                ':tax_rate' => $data['tax_rate'] ?? 0,
                ':is_active' => !empty($data['is_active']) ? 1 : 0,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

            // tạo dòng trong bảng stocks tương ứng
            $pdo->prepare("
                INSERT INTO stocks (product_id, qty, safety_stock, updated_by)
                VALUES (:pid, 0, 0, :uid)
            ")->execute([
                        ':pid' => $id,
                        ':uid' => $currentUser,
                    ]);

            $pdo->commit();

            // Log audit
            $this->logCreate('products', $id, [
                'sku' => $data['sku'],
                'name' => $data['name'],
                'slug' => $slug,
                'brand_id' => $data['brand_id'] ?: null,
                'category_id' => $data['category_id'] ?: null,
                'pack_size' => $data['pack_size'] ?? null,
                'unit_id' => $data['unit_id'] ?: null,
                'barcode' => $data['barcode'] ?? null,
                'sale_price' => $data['sale_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'is_active' => !empty($data['is_active']) ? 1 : 0
            ]);

            return $id;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(int $id, array $data, int $currentUser, string $slug): void
    {
        // Get before data
        $beforeProduct = $this->findOne($id);
        if ($beforeProduct) {
            $beforeArray = [
                'sku' => $beforeProduct->sku,
                'name' => $beforeProduct->name,
                'slug' => $beforeProduct->slug,
                'brand_id' => $beforeProduct->brand_id,
                'category_id' => $beforeProduct->category_id,
                'pack_size' => $beforeProduct->pack_size,
                'unit_id' => $beforeProduct->unit_id,
                'barcode' => $beforeProduct->barcode,
                'sale_price' => $beforeProduct->sale_price,
                'cost_price' => $beforeProduct->cost_price,
                'tax_rate' => $beforeProduct->tax_rate,
                'is_active' => $beforeProduct->is_active
            ];
        }

        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE products SET 
                sku = :sku, name = :name, slug = :slug,
                brand_id = :brand_id, category_id = :category_id, 
                pack_size = :pack_size, unit_id = :unit_id, barcode = :barcode, description = :description,
                sale_price = :sale_price, cost_price = :cost_price, tax_rate = :tax_rate, 
                is_active = :is_active, updated_by = :updated_by, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':sku' => $data['sku'],
            ':name' => $data['name'],
            ':slug' => $slug,
            ':brand_id' => $data['brand_id'] ?: null,
            ':category_id' => $data['category_id'] ?: null,
            ':pack_size' => $data['pack_size'] ?? null,
            ':unit_id' => $data['unit_id'] ?: null,
            ':barcode' => $data['barcode'] ?? null,
            ':description' => $data['description'] ?? null,
            ':sale_price' => $data['sale_price'] ?? 0,
            ':cost_price' => $data['cost_price'] ?? 0,
            ':tax_rate' => $data['tax_rate'] ?? 0,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':updated_by' => $currentUser,
        ]);

        // Log audit
        if (isset($beforeArray)) {
            $afterArray = [
                'sku' => $data['sku'],
                'name' => $data['name'],
                'slug' => $slug,
                'brand_id' => $data['brand_id'] ?: null,
                'category_id' => $data['category_id'] ?: null,
                'pack_size' => $data['pack_size'] ?? null,
                'unit_id' => $data['unit_id'] ?: null,
                'barcode' => $data['barcode'] ?? null,
                'sale_price' => $data['sale_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'is_active' => !empty($data['is_active']) ? 1 : 0
            ];
            $this->logUpdate('products', $id, $beforeArray, $afterArray);
        }
    }

    /**
     * Xóa sản phẩm và tồn kho
     */
    public function delete(int $id): void
    {
        // Get before data
        $beforeProduct = $this->findOne($id);
        $beforeArray = null;
        if ($beforeProduct) {
            $beforeArray = [
                'sku' => $beforeProduct->sku,
                'name' => $beforeProduct->name,
                'slug' => $beforeProduct->slug,
                'brand_id' => $beforeProduct->brand_id,
                'category_id' => $beforeProduct->category_id,
                'pack_size' => $beforeProduct->pack_size,
                'unit_id' => $beforeProduct->unit_id,
                'barcode' => $beforeProduct->barcode,
                'sale_price' => $beforeProduct->sale_price,
                'cost_price' => $beforeProduct->cost_price,
                'tax_rate' => $beforeProduct->tax_rate,
                'is_active' => $beforeProduct->is_active
            ];
        }

        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM stocks WHERE product_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

            $pdo->commit();

            // Log audit
            if ($beforeArray) {
                $this->logDelete('products', $id, $beforeArray);
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}