<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Stock;

class StockRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT s.product_id, p.sku AS product_sku, p.name AS product_name, u.name AS unit_name, s.qty, s.min_qty, s.max_qty, s.updated_at
                FROM stocks s
                JOIN products p ON s.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                ORDER BY s.updated_at DESC, s.product_id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Stock($row), $rows);
    }
}
