<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Supplier;
use App\Support\Auditable;

class SupplierRepository
{
    use Auditable;

    // Kiểm tra có thể xóa không: nếu còn sản phẩm liên kết qua supplier_products thì không cho xóa
    public function canDelete($id): bool
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM supplier_products WHERE supplier_id = ?");
        $st->execute([$id]);
        return $st->fetchColumn() == 0;
    }
    public function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT s.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
                FROM suppliers s
                LEFT JOIN users cu ON cu.id = s.created_by
                LEFT JOIN users uu ON uu.id = s.updated_by
                ORDER BY s.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Supplier($row), $rows);
    }

    public function findOne($id)
    {
        $pdo = DB::pdo();
        $sql = "SELECT s.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
                FROM suppliers s
                LEFT JOIN users cu ON cu.id = s.created_by
                LEFT JOIN users uu ON uu.id = s.updated_by
                WHERE s.id = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Supplier($row) : null;
    }

    public function create($data, $currentUser)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, email, address, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $currentUser,
            $currentUser
        ]);
        $id = $pdo->lastInsertId();
        
        // Log audit
        $this->logCreate('suppliers', (int)$id, [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null
        ]);
        
        return $id;
    }

    public function update($id, $data, $currentUser)
    {
        // Get before data
        $beforeSupplier = $this->findOne($id);
        $beforeArray = null;
        if ($beforeSupplier) {
            $beforeArray = [
                'name' => $beforeSupplier->name,
                'phone' => $beforeSupplier->phone,
                'email' => $beforeSupplier->email,
                'address' => $beforeSupplier->address
            ];
        }
        
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=?, updated_at=NOW(), updated_by=? WHERE id=?");
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $currentUser,
            $id
        ]);
        
        // Log audit
        if ($beforeArray) {
            $afterArray = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null
            ];
            $this->logUpdate('suppliers', (int)$id, $beforeArray, $afterArray);
        }
    }

    public function delete($id)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException('Không thể xoá: nhà cung cấp này đang được sử dụng bởi sản phẩm.');
        }
        
        // Get before data
        $beforeSupplier = $this->findOne($id);
        $beforeArray = null;
        if ($beforeSupplier) {
            $beforeArray = [
                'name' => $beforeSupplier->name,
                'phone' => $beforeSupplier->phone,
                'email' => $beforeSupplier->email,
                'address' => $beforeSupplier->address
            ];
        }
        
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
        
        // Log audit
        if ($beforeArray) {
            $this->logDelete('suppliers', (int)$id, $beforeArray);
        }
    }
}
