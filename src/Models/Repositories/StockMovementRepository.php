<?php

namespace App\Models\Repositories;

use App\Core\DB;

class StockMovementRepository
{
    /**
     * Lấy tất cả lịch sử thay đổi tồn kho với filter
     */
    public function all(int $limit = 500): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT 
                sm.id,
                sm.product_id,
                p.name AS product_name,
                p.sku AS product_sku,
                sm.type AS movement_type,
                sm.qty AS quantity,
                sm.ref_type AS reference_type,
                sm.ref_id AS reference_id,
                sm.note,
                sm.created_at
            FROM stock_movements sm
            LEFT JOIN products p ON sm.product_id = p.id
            ORDER BY sm.created_at DESC, sm.id DESC
            LIMIT ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết một movement
     */
    public function findById(int $id): ?array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT 
                sm.id,
                sm.product_id,
                p.name AS product_name,
                p.sku AS product_sku,
                sm.type AS movement_type,
                sm.qty AS quantity,
                sm.ref_type AS reference_type,
                sm.ref_id AS reference_id,
                sm.note,
                sm.created_at
            FROM stock_movements sm
            LEFT JOIN products p ON sm.product_id = p.id
            WHERE sm.id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lấy tổng số movement
     */
    public function count(): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("SELECT COUNT(*) FROM stock_movements");
        return (int) $stmt->fetchColumn();
    }
}
