<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class SupplierController extends BaseAdminController
{
    public function index()
    {
        return $this->view('admin/suppliers/supplier');
    }

    /** GET /admin/api/suppliers */
    public function apiIndex()
    {
        $pdo = \App\Core\DB::pdo();
        $rows = $pdo->query("
        SELECT s.*,
               cu.full_name AS created_by_name,
               uu.full_name AS updated_by_name
        FROM suppliers s
        LEFT JOIN users cu ON cu.id = s.created_by
        LEFT JOIN users uu ON uu.id = s.updated_by
        ORDER BY s.id DESC 
        LIMIT 500
    ")->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/suppliers */
    public function store()
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("
            INSERT INTO suppliers (name, phone, email, address, created_by, updated_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $this->currentUserId(),
            $this->currentUserId()
        ]);

        $id = $pdo->lastInsertId();
        echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** PUT /admin/suppliers/{id} */
    public function update($id)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET name=?, phone=?, email=?, address=?, updated_at=NOW(), updated_by=? 
            WHERE id=?
        ");
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $this->currentUserId(),
            $id
        ]);

        echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** DELETE /admin/suppliers/{id} */
    public function destroy($id)
    {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("
        SELECT s.*,
               cu.full_name AS created_by_name,
               uu.full_name AS updated_by_name
        FROM suppliers s
        LEFT JOIN users cu ON cu.id = s.created_by
        LEFT JOIN users uu ON uu.id = s.updated_by
        WHERE s.id = ?
    ");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
