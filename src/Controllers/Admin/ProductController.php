<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class ProductController extends BaseAdminController
{
    /** GET /admin/products (trả về view) */
    public function index()
    {
        return $this->view('admin/products/product');
    }

    /** GET /admin/api/products (list) */
    public function apiIndex()
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
                    p.sale_price, p.cost_price, p.tax_rate, p.is_active,
                    p.brand_id, p.category_id, p.unit_id,
                    p.created_at, p.updated_at,
                    p.created_by, cu.full_name AS created_by_name,
                    p.updated_by, uu.full_name AS updated_by_name,
                    b.name AS brand_name, 
                    c.name AS category_name, 
                    u.name AS unit_name,
                    s.qty AS stock_qty 
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units u ON u.id = p.unit_id
                LEFT JOIN users cu ON cu.id = p.created_by
                LEFT JOIN users uu ON uu.id = p.updated_by
                LEFT JOIN stocks s ON s.product_id = p.id  
                ORDER BY p.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products (create) */
    public function store()
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');

        try {
            $pdo->beginTransaction();

            // 1. Thêm sản phẩm (KHÔNG include stock_qty trong products)
            $stmt = $pdo->prepare("INSERT INTO products
            (sku, name, slug, brand_id, category_id, pack_size, unit_id, barcode, description,
             sale_price, cost_price, tax_rate, is_active, created_by, updated_by, created_at, updated_at)
            VALUES
            (:sku,:name,:slug,:brand_id,:category_id,:pack_size,:unit_id,:barcode,:description,
             :sale_price,:cost_price,:tax_rate,:is_active,:created_by,:updated_by,NOW(),NOW())");
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
            $id = $pdo->lastInsertId();

            // 2. Tạo tồn kho mặc định (qty=0, safety_stock=0)
            $stmt2 = $pdo->prepare("INSERT INTO stocks (product_id, qty, safety_stock, updated_by)
                                VALUES (:pid, 0, 0, :uid)");
            $stmt2->execute([
                ':pid' => $id,
                ':uid' => $currentUser
            ]);

            $pdo->commit();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi tạo sản phẩm'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/products/{id} */
    public function update($id)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE products SET 
                sku=:sku, name=:name, slug=:slug,
                brand_id=:brand_id, category_id=:category_id, 
                pack_size=:pack_size, unit_id=:unit_id, barcode=:barcode, description=:description,
                sale_price=:sale_price, cost_price=:cost_price, tax_rate=:tax_rate, 
                is_active=:is_active, updated_by=:updated_by, updated_at=NOW()
                WHERE id=:id");
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
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi cập nhật sản phẩm'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/products/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = \App\Core\DB::pdo();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM stocks WHERE product_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            $pdo->commit();

            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
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
                WHERE p.id=?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-') ?: uniqid('sp-');
    }
}
