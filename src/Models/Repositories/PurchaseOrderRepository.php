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

            // Xử lý trạng thái thanh toán và số tiền đã trả
            $paymentStatus = $data['payment_status'] ?? 'Chưa đối soát';
            $paidAmount = 0;
            if ($paymentStatus === 'Đã thanh toán một phần' || $paymentStatus === 'Đã thanh toán hết') {
                $paidAmount = isset($data['paid_amount']) ? (float)$data['paid_amount'] : 0;
                if ($paymentStatus === 'Đã thanh toán hết') {
                    $paidAmount = $totalAmount;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO purchase_orders 
                (code, supplier_id, total_amount, paid_amount, payment_status, due_date, note, received_at, created_by, created_at, updated_at) 
                VALUES (:code, :supplier_id, :total_amount, :paid_amount, :payment_status, :due_date, :note, NOW(), :created_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':code' => $code,
                ':supplier_id' => $data['supplier_id'] ?? null,
                ':total_amount' => $totalAmount,
                ':paid_amount' => $paidAmount,
                ':payment_status' => $paymentStatus,
                ':due_date' => $data['due_date'] ?? null,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
            ]);

            $poId = (int) $pdo->lastInsertId();

            // Insert items
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
                        
                        // Cập nhật tồn kho trong bảng products
                        $upProduct = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :product_id");
                        $upProduct->execute([
                            ':qty' => $bqty,
                            ':product_id' => $productId
                        ]);
                    }
                    // Bắt buộc tổng batch = line qty
                    if ($sum !== $qty) {
                        throw new \Exception("Sum of batches ($sum) does not equal line qty ($qty)");
                    }
                } else {
                    // Single batch
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
                    
                    // Cập nhật tồn kho trong bảng products
                    $upProduct = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :product_id");
                    $upProduct->execute([
                        ':qty' => $qty,
                        ':product_id' => $productId
                    ]);
                }
            }

            // Tạo bút toán công nợ: tăng công nợ (debit) cho tổng tiền phiếu nhập
            $apDebitStmt = $pdo->prepare("
                INSERT INTO ap_ledger (supplier_id, ref_type, ref_id, debit, credit, note, created_by, created_at)
                VALUES (:supplier_id, 'Phiếu nhập', :ref_id, :debit, 0, :note, :created_by, NOW())
            ");
            $apDebitStmt->execute([
                ':supplier_id' => $data['supplier_id'],
                ':ref_id' => $poId,
                ':debit' => $totalAmount,
                ':note' => 'Phát sinh công nợ từ phiếu nhập #' . $poId,
                ':created_by' => $currentUser
            ]);

            // Nếu có thanh toán, tự động tạo phiếu chi và giảm công nợ (credit)
            if ($paidAmount > 0) {
                // 1. Tạo phiếu chi
                $expenseStmt = $pdo->prepare("
                    INSERT INTO expense_vouchers (code, purchase_order_id, supplier_id, amount, paid_at, is_active, note, created_by, created_at, updated_by, updated_at)
                    VALUES (:code, :purchase_order_id, :supplier_id, :amount, NOW(), 1, :note, :created_by, NOW(), :created_by, NOW())
                ");
                $expenseCode = 'PC-' . time();
                $expenseStmt->execute([
                    ':code' => $expenseCode,
                    ':purchase_order_id' => $poId,
                    ':supplier_id' => $data['supplier_id'] ?? null,
                    ':amount' => $paidAmount,
                    ':note' => 'Tự động tạo từ phiếu nhập',
                    ':created_by' => $currentUser,
                ]);

                $expenseId = $pdo->lastInsertId();

                // 2. Giảm công nợ (credit) cho số tiền đã thanh toán
                $apCreditStmt = $pdo->prepare("
                    INSERT INTO ap_ledger (supplier_id, ref_type, ref_id, debit, credit, note, created_by, created_at)
                    VALUES (:supplier_id, 'Phiếu chi', :ref_id, 0, :credit, :note, :created_by, NOW())
                ");
                $apCreditStmt->execute([
                    ':supplier_id' => $data['supplier_id'],
                    ':ref_id' => $expenseId,
                    ':credit' => $paidAmount,
                    ':note' => 'Thanh toán phiếu chi #' . $expenseCode,
                    ':created_by' => $currentUser
                ]);
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
