<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\DB;

class ProductController extends BaseAdminController
{
    /** GET /admin/products */
    public function index()
    {
        return $this->view('admin/products/product');
    }

    public function apiIndex()
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT p.*, b.name AS brand_name, c.name AS category_name
          FROM products p
          LEFT JOIN brands b ON b.id=p.brand_id
          LEFT JOIN categories c ON c.id=p.category_id
          ORDER BY p.id DESC LIMIT 500";
        echo json_encode(['items' => $pdo->query($sql)->fetchAll()]);
        exit;
    }

    public function apiBrands()
    {
        $pdo = \App\Core\DB::pdo();
        echo json_encode($pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll());
        exit;
    }
    public function apiCategories()
    {
        $pdo = \App\Core\DB::pdo();
        echo json_encode($pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll());
        exit;
    }

    public function store(\App\Core\Request $req)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare("INSERT INTO products (sku,name,price,unit,brand_id,category_id,pack_size,barcode,description,is_active)
                         VALUES (:sku,:name,:price,:unit,:brand_id,:category_id,:pack_size,:barcode,:description,:is_active)");
        $stmt->execute([
            ':sku' => $data['sku'],
            ':name' => $data['name'],
            ':price' => $data['price'] ?? 0,
            ':unit' => $data['unit'] ?? null,
            ':brand_id' => $data['brand_id'] ?: null,
            ':category_id' => $data['category_id'] ?: null,
            ':pack_size' => $data['pack_size'] ?? null,
            ':barcode' => $data['barcode'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        $id = $pdo->lastInsertId();
        echo json_encode($this->findOne($id));
        exit;
    }

    public function update($id, \App\Core\Request $req)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare("UPDATE products SET sku=:sku,name=:name,price=:price,unit=:unit,
            brand_id=:brand_id,category_id=:category_id,pack_size=:pack_size,barcode=:barcode,
            description=:description,is_active=:is_active WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':sku' => $data['sku'],
            ':name' => $data['name'],
            ':price' => $data['price'] ?? 0,
            ':unit' => $data['unit'] ?? null,
            ':brand_id' => $data['brand_id'] ?: null,
            ':category_id' => $data['category_id'] ?: null,
            ':pack_size' => $data['pack_size'] ?? null,
            ':barcode' => $data['barcode'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        echo json_encode($this->findOne($id));
        exit;
    }

    public function destroy($id)
    {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT p.*, b.name AS brand_name, c.name AS category_name
          FROM products p
          LEFT JOIN brands b ON b.id=p.brand_id
          LEFT JOIN categories c ON c.id=p.category_id
          WHERE p.id=?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

}
