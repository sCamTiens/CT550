<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class ProductController extends BaseAdminController
{
    /** GET /admin/products */
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
                       p.created_at, p.updated_at,
                       p.created_by, cu.full_name AS created_by_name,
                       p.updated_by, uu.full_name AS updated_by_name,
                       b.name AS brand_name, 
                       c.name AS category_name, 
                       u.name AS unit_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units u ON u.id = p.unit_id
                LEFT JOIN users cu ON cu.id = p.created_by
                LEFT JOIN users uu ON uu.id = p.updated_by
                ORDER BY p.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function apiBrands()
    {
        $pdo = \App\Core\DB::pdo();
        echo json_encode($pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function apiCategories()
    {
        $pdo = \App\Core\DB::pdo();
        echo json_encode($pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function apiUnits()
    {
        $pdo = \App\Core\DB::pdo();
        echo json_encode($pdo->query("SELECT id,name FROM units ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    /** POST /admin/products (create) */
    public function store()
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $stmt = $pdo->prepare("INSERT INTO products
                (sku, name, brand_id, category_id, pack_size, unit_id, barcode, description,
                 sale_price, cost_price, tax_rate, is_active, created_by, updated_by, created_at, updated_at)
                VALUES
                (:sku,:name,:brand_id,:category_id,:pack_size,:unit_id,:barcode,:description,
                 :sale_price,:cost_price,:tax_rate,:is_active,:created_by,:updated_by,NOW(),NOW())");
            $stmt->execute([
                ':sku' => $data['sku'],
                ':name' => $data['name'],
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
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'SKU hoặc slug đã tồn tại']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo sản phẩm']);
            exit;
        }
    }

    /** PUT /admin/products/{id} (update) */
    public function update($id)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        try {
            $stmt = $pdo->prepare("UPDATE products SET 
                sku=:sku, name=:name, brand_id=:brand_id, category_id=:category_id, 
                pack_size=:pack_size, unit_id=:unit_id, barcode=:barcode, description=:description,
                sale_price=:sale_price, cost_price=:cost_price, tax_rate=:tax_rate, 
                is_active=:is_active, updated_by=:updated_by, updated_at=NOW()
                WHERE id=:id");
            $stmt->execute([
                ':id' => $id,
                ':sku' => $data['sku'],
                ':name' => $data['name'],
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
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'SKU hoặc slug đã tồn tại']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật sản phẩm']);
            exit;
        }
    }

    /** DELETE /admin/products/{id} */
    public function destroy($id)
    {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT p.id, p.sku, p.name, p.slug, p.pack_size, p.barcode, p.description,
                       p.sale_price, p.cost_price, p.tax_rate, p.is_active,
                       p.created_at, p.updated_at,
                       p.created_by, cu.full_name AS created_by_name,
                       p.updated_by, uu.full_name AS updated_by_name,
                       b.name AS brand_name, c.name AS category_name, u.name AS unit_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN units u ON u.id = p.unit_id
                LEFT JOIN users cu ON cu.id = p.created_by
                LEFT JOIN users uu ON uu.id = p.updated_by
                WHERE p.id=?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    /** Helper: user hiện tại */
    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
