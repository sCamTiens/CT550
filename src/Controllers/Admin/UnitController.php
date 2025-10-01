<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class UnitController extends BaseAdminController
{
    public function index()
    {
        return $this->view('admin/units/unit', [
            'items' => $this->all()
        ]);
    }

    /** GET /admin/api/units */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $this->all()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function all()
    {
        $pdo = \App\Core\DB::pdo();
        return $pdo->query("SELECT * FROM units ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** POST /admin/units */
    public function store()
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("INSERT INTO units (name, slug, created_by) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $this->currentUserId()
        ]);

        $id = $pdo->lastInsertId();
        echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** PUT /admin/units/{id} */
    public function update($id)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("UPDATE units SET name=?, slug=?, updated_at=NOW(), updated_by=? WHERE id=?");
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $this->currentUserId(),
            $id
        ]);

        echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** DELETE /admin/units/{id} */
    public function destroy($id)
    {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM units WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT * FROM units WHERE id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
