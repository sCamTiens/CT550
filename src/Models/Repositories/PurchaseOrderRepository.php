<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Repositories\ProductBatchRepository;

class PurchaseOrderRepository
{
    /**
     * Create a purchase order (receipt) and for each line create a product_batch,
     * a stock_movement (Nhập kho) and update stocks. All in one transaction.
     */
    public function createReceipt(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        $batchRepo = new ProductBatchRepository();
        try {
            $pdo->beginTransaction();

            $code = $data['code'] ?? ('PO-' . time());

            // Tính tổng tiền từ các dòng hàng
            $lines = $data['lines'] ?? [];
            $totalAmount = 0;
            foreach ($lines as $ln) {
                $qty = (int) ($ln['qty'] ?? 0);
                $unitCost = (float) ($ln['unit_cost'] ?? 0);
                $totalAmount += $qty * $unitCost;
            }

            // Insert purchase order (payment_status mặc định = 1 = "Chưa đối soát")
            $stmt = $pdo->prepare("
                INSERT INTO purchase_orders 
                (code, supplier_id, total_amount, paid_amount, payment_status, due_date, note, received_at, created_by, created_at, updated_at) 
                VALUES (:code, :supplier_id, :total_amount, 0, 1, :due_date, :note, NOW(), :created_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':code' => $code,
                ':supplier_id' => $data['supplier_id'] ?? null,
                ':total_amount' => $totalAmount,
                ':due_date' => $data['due_date'] ?? null,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
            ]);

            $poId = (int) $pdo->lastInsertId();

            // 👉 Insert items
            $poiStmt = $pdo->prepare("
                INSERT INTO purchase_order_items (purchase_order_id, product_id, qty, unit_cost, line_total) 
                VALUES (:po, :product_id, :qty, :unit_cost, :line_total)
            ");

            foreach ($lines as $ln) {
                $productId = (int) ($ln['product_id'] ?? 0);
                $qty = (int) ($ln['qty'] ?? 0);
                $unitCost = (float) ($ln['unit_cost'] ?? 0);
                $lineTotal = $qty * $unitCost;

                $poiStmt->execute([
                    ':po' => $poId,
                    ':product_id' => $productId,
                    ':qty' => $qty,
                    ':unit_cost' => $unitCost,
                    ':line_total' => $lineTotal,
                ]);

                // 👉 Batches
                if (!empty($ln['batches']) && is_array($ln['batches'])) {
                    $sum = 0;
                    foreach ($ln['batches'] as $bline) {
                        $bqty = (int) ($bline['qty'] ?? 0);
                        $sum += $bqty;
                        $batchData = [
                            'product_id' => $productId,
                            'batch_code' => $bline['batch_code'] ?? ($ln['batch_code'] ?? null),
                            'mfg_date' => $bline['mfg_date'] ?? ($ln['mfg_date'] ?? null),
                            'exp_date' => $bline['exp_date'] ?? ($ln['exp_date'] ?? null),
                            'initial_qty' => $bqty,
                            'current_qty' => $bqty,
                            'purchase_order_id' => $poId,
                            'note' => $bline['note'] ?? ($ln['note'] ?? null),
                            'unit_cost' => $bline['unit_cost'] ?? $unitCost,
                        ];
                        $batchId = $batchRepo->create($batchData, $currentUser);

                        $ins = $pdo->prepare("
                            INSERT INTO stock_movements (product_id, type, ref_type, ref_id, qty, note) 
                            VALUES (?, 'Nhập kho', 'Phiếu nhập', ?, ?, ?)
                        ");
                        $note = 'Batch:' . $batchId;
                        $ins->execute([$productId, $poId, $bqty, $note]);

                        $up = $pdo->prepare("
                            INSERT INTO stocks (product_id, qty, updated_by) 
                            VALUES (:product_id, :qty, :updated_by) 
                            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_by = :updated_by2, updated_at = NOW()
                        ");
                        $up->execute([
                            ':product_id' => $productId,
                            ':qty' => $bqty,
                            ':updated_by' => $currentUser,
                            ':updated_by2' => $currentUser,
                        ]);
                    }
                    // Bắt buộc tổng batch = line qty
                    if ($sum !== $qty) {
                        throw new \Exception("Sum of batches ($sum) does not equal line qty ($qty)");
                    }
                } else {
                    // 👉 Single batch
                    $batchData = [
                        'product_id' => $productId,
                        'batch_code' => $ln['batch_code'] ?? null,
                        'mfg_date' => $ln['mfg_date'] ?? null,
                        'exp_date' => $ln['exp_date'] ?? null,
                        'initial_qty' => $qty,
                        'current_qty' => $qty,
                        'purchase_order_id' => $poId,
                        'note' => $ln['note'] ?? null,
                        'unit_cost' => $unitCost,
                    ];
                    $batchId = $batchRepo->create($batchData, $currentUser);

                    $ins = $pdo->prepare("
                        INSERT INTO stock_movements (product_id, type, ref_type, ref_id, qty, note) 
                        VALUES (?, 'Nhập kho', 'Phiếu nhập', ?, ?, ?)
                    ");
                    $note = 'Batch:' . $batchId;
                    $ins->execute([$productId, $poId, $qty, $note]);

                    $up = $pdo->prepare("
                        INSERT INTO stocks (product_id, qty, updated_by) 
                        VALUES (:product_id, :qty, :updated_by) 
                        ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_by = :updated_by2, updated_at = NOW()
                    ");
                    $up->execute([
                        ':product_id' => $productId,
                        ':qty' => $qty,
                        ':updated_by' => $currentUser,
                        ':updated_by2' => $currentUser,
                    ]);
                }
            }

            $pdo->commit();
            return $poId;
        } catch (\Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }

    public function all(int $limit = 200)
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT po.*, s.name AS supplier_name, u.full_name AS created_by_name
            FROM purchase_orders po
            JOIN suppliers s ON s.id = po.supplier_id
            LEFT JOIN users u ON u.id = po.created_by
            ORDER BY po.created_at DESC
            LIMIT ?
        ";
        $st = $pdo->prepare($sql);
        $st->bindValue(1, (int) $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
