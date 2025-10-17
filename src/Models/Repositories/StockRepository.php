<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Stock;

use \PDO;

class StockRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
                    p.id AS product_id, 
                    p.sku AS product_sku, 
                    p.name AS product_name, 
                    u.name AS unit_name, 
                    COALESCE(s.qty, 0) AS qty, 
                    COALESCE(s.safety_stock, 0) AS safety_stock, 
                    s.updated_at
                FROM products p
                LEFT JOIN stocks s ON s.product_id = p.id
                LEFT JOIN units u ON p.unit_id = u.id
                WHERE p.is_active = 1
                ORDER BY p.name ASC
                LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Stock($row), $rows);
    }

    /**
     * Allocate product quantity from batches using FEFO (by exp_date) or FIFO.
     * This method is transaction-safe and uses SELECT ... FOR UPDATE to lock batches.
     * Returns ['total_cogs' => float, 'allocations' => [ ['batch_id'=>..., 'qty'=>..., 'unit_cost'=>...], ... ]]
     * Throws Exception if insufficient qty.
     */
    public static function allocateBatches(int $productId, int $qtyNeeded, ?int $orderId = null, ?int $orderItemId = null, string $policy = 'fefo')
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $sql = "SELECT id, current_qty, unit_cost FROM product_batches WHERE product_id = ? AND current_qty > 0 ";
            if (strtolower($policy) === 'fefo') {
                $sql .= "ORDER BY exp_date ASC, id ASC ";
            } else {
                $sql .= "ORDER BY created_at ASC, id ASC ";
            }
            $sql .= "FOR UPDATE";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $remaining = $qtyNeeded;
            $allocations = [];
            $totalCogs = 0.0;

            foreach ($batches as $b) {
                if ($remaining <= 0)
                    break;
                $available = (int) $b['current_qty'];
                if ($available <= 0)
                    continue;
                $take = min($remaining, $available);

                // decrement batch
                $upd = $pdo->prepare("UPDATE product_batches SET current_qty = current_qty - ? WHERE id = ?");
                $upd->execute([$take, $b['id']]);

                // insert stock movement (negative qty means out)
                $ins = $pdo->prepare(
                    "INSERT INTO stock_movements (product_id, type, ref_type, ref_id, qty, note, unit_cost) VALUES (?, 'Xuất kho', 'Đơn hàng', ?, ?, ?, ?)"
                );
                $note = $orderItemId ? "Allocated to order_item:{$orderItemId}" : null;
                // qty stored as negative for outflow
                $ins->execute([$productId, $orderId, -$take, $note, $b['unit_cost']]);

                $allocations[] = ['batch_id' => $b['id'], 'qty' => $take, 'unit_cost' => (float) $b['unit_cost']];
                $totalCogs += $take * (float) $b['unit_cost'];
                $remaining -= $take;
            }

            if ($remaining > 0) {
                $pdo->rollBack();
                throw new \Exception("Không đủ tồn cho product_id={$productId}. Thiếu={$remaining}");
            }

            // update aggregate stocks table
            $updStocks = $pdo->prepare("UPDATE stocks SET qty = qty - ? WHERE product_id = ?");
            $updStocks->execute([$qtyNeeded, $productId]);

            // Kiểm tra tồn kho sau khi xuất, tạo thông báo nếu thấp
            self::checkLowStock($productId);

            // store unit_cost / cogs on order_item if provided
            if ($orderItemId) {
                $avgUnitCost = $qtyNeeded ? ($totalCogs / $qtyNeeded) : 0;
                $updOI = $pdo->prepare("UPDATE order_items SET unit_cost = ?, line_cogs = ? WHERE id = ?");
                $updOI->execute([$avgUnitCost, $totalCogs, $orderItemId]);
            }

            $pdo->commit();
            return ['total_cogs' => $totalCogs, 'allocations' => $allocations];
        } catch (\Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Kiểm tra tồn kho thấp và tạo thông báo nếu cần
     */
    private static function checkLowStock(int $productId): void
    {
        try {
            $pdo = DB::pdo();
            
            // Lấy thông tin tồn kho và sản phẩm
            $sql = "SELECT s.qty, s.safety_stock, p.name 
                    FROM stocks s 
                    JOIN products p ON p.id = s.product_id 
                    WHERE s.product_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            $stock = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($stock && $stock['qty'] <= $stock['safety_stock']) {
                // Tồn kho đã thấp, tạo thông báo
                require_once __DIR__ . '/NotificationRepository.php';
                NotificationRepository::createLowStockAlert(
                    $productId,
                    $stock['name'],
                    $stock['qty'],
                    $stock['safety_stock']
                );
            }
        } catch (\Exception $e) {
            // Log error nhưng không throw để không ảnh hưởng flow chính
            error_log("Error checking low stock: " . $e->getMessage());
        }
    }
}
