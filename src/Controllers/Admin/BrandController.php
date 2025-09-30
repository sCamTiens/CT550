<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class BrandController extends Controller
{
    /** GET /admin/brands (view) */
    public function index() {
        return $this->view('admin/brands/brand');
    }

    /** GET /admin/api/brands (list JSON) */
    public function apiIndex() {
        $pdo = \App\Core\DB::pdo();
        $rows = $pdo->query("SELECT id,name,slug,created_at FROM brands ORDER BY id DESC")->fetchAll();
        echo json_encode(['items'=>$rows]); exit;
    }

    /** POST /admin/brands (create) */
    public function store() {
        $pdo  = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("INSERT INTO brands(name,slug) VALUES(:name,:slug)");
        $stmt->execute([
            ':name'=>$data['name'] ?? '',
            ':slug'=>($data['slug'] ?? null) ?: null,
        ]);
        $id = $pdo->lastInsertId();
        echo json_encode($this->findOne($id)); exit;
    }

    /** POST /admin/brands/{id} (update) */
    public function update($id) {
        $pdo  = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("UPDATE brands SET name=:name, slug=:slug WHERE id=:id");
        $stmt->execute([
            ':id'=>$id,
            ':name'=>$data['name'] ?? '',
            ':slug'=>($data['slug'] ?? null) ?: null,
        ]);
        echo json_encode($this->findOne($id)); exit;
    }

    /** POST /admin/brands/{id}/delete (delete) */
    public function destroy($id) {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM brands WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }

    private function findOne($id){
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT id,name,slug,created_at FROM brands WHERE id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }
}
