<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\ProductBatch;

class ProductBatchRepository
{
    public function all(int $limit = 500): array
    {
        $pdo = DB::pdo();
        $sql = "SELECT pb.*, p.name AS product_name, p.sku AS product_sku FROM product_batches pb JOIN products p ON pb.product_id = p.id WHERE pb.is_active = 1 ORDER BY pb.exp_date ASC, pb.id DESC LIMIT ?";
        $st = $pdo->prepare($sql);
        $st->bindValue(1, (int)$limit, \PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new ProductBatch($r), $rows);
    }

    public function findOne(int $id): ?ProductBatch
    {
        $st = DB::pdo()->prepare("SELECT * FROM product_batches WHERE id = ? AND is_active = 1");
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new ProductBatch($row) : null;
    }

    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO product_batches (product_id, batch_code, mfg_date, exp_date, initial_qty, current_qty, purchase_order_id, note, unit_cost, is_active, created_by, updated_by, created_at, updated_at) VALUES (:product_id, :batch_code, :mfg_date, :exp_date, :initial_qty, :current_qty, :purchase_order_id, :note, :unit_cost, :is_active, :created_by, :updated_by, NOW(), NOW())");
        $stmt->execute([
            ':product_id' => $data['product_id'],
            ':batch_code' => $data['batch_code'] ?? uniqid('B-'),
            ':mfg_date' => $data['mfg_date'] ?: null,
            ':exp_date' => $data['exp_date'] ?: null,
            ':initial_qty' => $data['initial_qty'] ?? 0,
            ':current_qty' => $data['current_qty'] ?? ($data['initial_qty'] ?? 0),
            ':purchase_order_id' => $data['purchase_order_id'] ?: null,
            ':note' => $data['note'] ?? null,
            ':unit_cost' => $data['unit_cost'] ?? 0,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ':created_by' => $currentUser,
            ':updated_by' => $currentUser,
        ]);
        return (int)DB::pdo()->lastInsertId();
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("UPDATE product_batches SET product_id = :product_id, batch_code = :batch_code, mfg_date = :mfg_date, exp_date = :exp_date, initial_qty = :initial_qty, current_qty = :current_qty, purchase_order_id = :purchase_order_id, note = :note, unit_cost = :unit_cost, updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':product_id' => $data['product_id'],
            ':batch_code' => $data['batch_code'],
            ':mfg_date' => $data['mfg_date'] ?: null,
            ':exp_date' => $data['exp_date'] ?: null,
            ':initial_qty' => $data['initial_qty'] ?? 0,
            ':current_qty' => $data['current_qty'] ?? 0,
            ':purchase_order_id' => $data['purchase_order_id'] ?: null,
            ':note' => $data['note'] ?? null,
            ':unit_cost' => $data['unit_cost'] ?? 0,
            ':updated_by' => $currentUser,
        ]);
    }

    public function delete(int $id): void
    {
        // soft-delete
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE product_batches SET is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public function restore(int $id): void
    {
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE product_batches SET is_active = 1, updated_at = NOW() WHERE id = ?")->execute([$id]);
    }
}
