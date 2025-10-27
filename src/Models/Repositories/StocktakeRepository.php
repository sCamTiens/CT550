<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Stocktake;

class StocktakeRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT st.id, st.created_by, u.full_name AS created_by_name, st.created_at, st.note
                FROM stocktakes st
                LEFT JOIN users u ON st.created_by = u.id
                ORDER BY st.created_at DESC, st.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $rows; // Trả về array thay vì Stocktake objects
    }

    /**
     * Tạo phiếu kiểm kê mới
     * @param string $note
     * @param int $userId
     * @param array $items [{ product_id, system_quantity, actual_quantity, difference }]
     * @return int stocktake_id
     */
    public static function create($note, $userId, $items)
    {
        $pdo = DB::pdo();
        
        try {
            $pdo->beginTransaction();
            
            // 1. Insert stocktake
            $stmt = $pdo->prepare("
                INSERT INTO stocktakes (note, created_by, created_at)
                VALUES (:note, :created_by, NOW())
            ");
            $stmt->execute([
                ':note' => $note,
                ':created_by' => $userId
            ]);
            $stocktakeId = (int) $pdo->lastInsertId();
            
            // 2. Insert stocktake_items
            $stmt = $pdo->prepare("
                INSERT INTO stocktake_items 
                (stocktake_id, product_id, system_qty, counted_qty, difference)
                VALUES (:stocktake_id, :product_id, :system_qty, :counted_qty, :difference)
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    ':stocktake_id' => $stocktakeId,
                    ':product_id' => $item['product_id'],
                    ':system_qty' => $item['system_quantity'],
                    ':counted_qty' => $item['actual_quantity'],
                    ':difference' => $item['difference']
                ]);
            }
            
            $pdo->commit();
            return $stocktakeId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy chi tiết phiếu kiểm kê
     * @param int $id
     * @return array|null
     */
    public static function findById($id)
    {
        $pdo = DB::pdo();
        
        error_log("StocktakeRepository::findById - Looking for ID: " . $id);
        
        // Get stocktake info
        $stmt = $pdo->prepare("
            SELECT st.id, st.note, st.created_by, u.full_name AS created_by_name, st.created_at
            FROM stocktakes st
            LEFT JOIN users u ON st.created_by = u.id
            WHERE st.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $stocktake = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        error_log("StocktakeRepository::findById - Found: " . ($stocktake ? 'YES' : 'NO'));
        
        if (!$stocktake) {
            return null;
        }
        
        // Get items
        $stmt = $pdo->prepare("
            SELECT 
                sti.product_id,
                p.name AS product_name,
                sti.system_qty AS system_quantity,
                sti.counted_qty AS actual_quantity,
                sti.difference
            FROM stocktake_items sti
            LEFT JOIN products p ON p.id = sti.product_id
            WHERE sti.stocktake_id = :stocktake_id
            ORDER BY sti.product_id
        ");
        $stmt->execute([':stocktake_id' => $id]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $stocktake['items'] = $items;
        return $stocktake;
    }
}
