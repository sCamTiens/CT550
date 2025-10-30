<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Repositories\ProductBatchRepository;
use App\Support\Auditable;

class PurchaseOrderRepository
{
    use Auditable;

    /**
     * Convert date from d/m/Y to Y-m-d
     */
    private function convertDateFormat(?string $date): ?string
    {
        if (!$date)
            return null;

        // Nếu đã đúng format Y-m-d thì giữ nguyên
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Convert từ d/m/Y sang Y-m-d
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        return null;
    }

    /**
     * Create a purchase order (receipt) and for each line create a product_batch,
     * a stock_movement (Nhập kho) and update stocks. All in one transaction.
     */

    public function createReceipt(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        $batchRepo = new ProductBatchRepository();

        error_log("=== CREATE RECEIPT DEBUG ===");
        error_log("Data received: " . json_encode($data, JSON_UNESCAPED_UNICODE));
        error_log("Current user: " . $currentUser);
        try {
            $pdo->beginTransaction();

            $code = $data['code'] ?? ('PO-' . round(microtime(true) * 1000));

            // Convert ngày nhập từ d/m/Y sang Y-m-d
            $receivedAtDate = $this->convertDateFormat($data['created_at'] ?? null);
            $receivedAt = $receivedAtDate ? ($receivedAtDate . ' ' . date('H:i:s')) : date('Y-m-d H:i:s');

            error_log("Received date: " . ($data['created_at'] ?? 'NULL'));
            error_log("Converted date: " . ($receivedAt ?? 'NULL'));

            // Tính tổng tiền từ các dòng hàng
            $lines = $data['lines'] ?? [];
            $totalAmount = 0;
            foreach ($lines as $ln) {
                $qty = (int) ($ln['qty'] ?? 0);
                $unitCost = (float) ($ln['unit_cost'] ?? 0);
                $totalAmount += $qty * $unitCost;
            }

            // Xử lý trạng thái thanh toán và số tiền đã trả
            $paymentStatusText = $data['payment_status'] ?? 'Chưa đối soát';

            // Convert text sang số để lưu vào database
            // 1 = Chưa đối soát, 0 = Đã thanh toán một phần, 2 = Đã thanh toán hết
            switch ($paymentStatusText) {
                case 'Đã thanh toán một phần':
                    $paymentStatus = '0';
                    break;
                case 'Đã thanh toán hết':
                    $paymentStatus = '2';
                    break;
                default:
                    $paymentStatus = '1'; // Chưa đối soát
                    break;
            }

            // Xử lý số tiền đã trả
            $paidAmount = isset($data['paid_amount']) ? (float) $data['paid_amount'] : 0;
            
            // Nếu trạng thái là "Đã thanh toán hết", ưu tiên lấy tổng tiền
            if ($paymentStatusText === 'Đã thanh toán hết') {
                $paidAmount = $totalAmount;
            }

            // Convert due_date từ d/m/Y sang Y-m-d
            $dueDate = $this->convertDateFormat($data['due_date'] ?? null);

            $stmt = $pdo->prepare("
                INSERT INTO purchase_orders 
                (code, supplier_id, total_amount, paid_amount, payment_status, due_date, note, received_at, created_by, created_at, updated_at, updated_by) 
                VALUES (:code, :supplier_id, :total_amount, :paid_amount, :payment_status, :due_date, :note, :received_at, :created_by, NOW(), NOW(), :updated_by)
            ");
            $stmt->execute([
                ':code' => $code,
                ':supplier_id' => $data['supplier_id'] ?? null,
                ':total_amount' => $totalAmount,
                ':paid_amount' => $paidAmount,
                ':payment_status' => $paymentStatus,
                ':due_date' => $dueDate,
                ':note' => $data['note'] ?? null,
                ':received_at' => $receivedAt,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
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

                // Batches
                if (!empty($ln['batches']) && is_array($ln['batches'])) {
                    $sum = 0;
                    foreach ($ln['batches'] as $bline) {
                        $bqty = (int) ($bline['qty'] ?? 0);
                        $sum += $bqty;
                        $batchData = [
                            'product_id' => $productId,
                            'batch_code' => $ln['batch_code'] ?? ('BATCH-' . round(microtime(true) * 1000)),
                            'mfg_date' => $this->convertDateFormat($ln['mfg_date'] ?? null),
                            'exp_date' => $this->convertDateFormat($ln['exp_date'] ?? null),
                            'initial_qty' => $bqty,
                            'current_qty' => $bqty,
                            'purchase_order_id' => $poId,
                            'note' => ($bline['note'] ?? '') . " Tạo từ phiếu nhập #" . $code,
                            'unit_cost' => $bline['unit_cost'] ?? $unitCost,
                            'created_by' => $currentUser,
                            'created_at' => date('Y-m-d H:i:s')
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
                    // Single batch
                    $batchData = [
                        'product_id' => $productId,
                        'batch_code' => $ln['batch_code'] ?? null,
                        'mfg_date' => $this->convertDateFormat($ln['mfg_date'] ?? null),
                        'exp_date' => $this->convertDateFormat($ln['exp_date'] ?? null),
                        'initial_qty' => $qty,
                        'current_qty' => $qty,
                        'purchase_order_id' => $poId,
                        'note' => ($ln['note'] ?? '') . " Tạo từ phiếu nhập #" . $code,
                        'unit_cost' => $unitCost,
                        'created_by' => $currentUser,
                        'created_at' => date('Y-m-d H:i:s')
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
                    INSERT INTO expense_vouchers (
                        code, purchase_order_id, supplier_id, method, amount, paid_by, paid_at, note, created_by, created_at, updated_at
                    )
                    VALUES (
                        :code, :purchase_order_id, :supplier_id, 'Tiền mặt', :amount, :paid_by, NOW(), :note, :created_by, NOW(), NOW()
                    )
                ");
                $expenseCode = 'PC-' . time();
                $expenseStmt->execute([
                    ':code' => $expenseCode,
                    ':purchase_order_id' => $poId,
                    ':supplier_id' => $data['supplier_id'] ?? null,
                    ':amount' => $paidAmount,
                    ':paid_by' => $currentUser,
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

            // Log audit
            $this->logCreate('purchase_orders', $poId, [
                'code' => $code,
                'supplier_id' => $data['supplier_id'] ?? null,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus
            ]);

            return $poId;
        } catch (\Exception $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            error_log("ERROR creating receipt: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function all(int $limit = 200)
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
                    po.*, 
                    s.name AS supplier_name, 
                    u.full_name AS created_by_name,
                    u2.full_name AS updated_by_name
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id = po.supplier_id
                LEFT JOIN users u ON u.id = po.created_by
                LEFT JOIN users u2 ON u2.id = po.updated_by
                ORDER BY po.created_at DESC
                LIMIT ?";
        $st = $pdo->prepare($sql);
        $st->bindValue(1, (int) $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
                    po.*, 
                    s.name AS supplier_name, 
                    u.full_name AS created_by_name,
                    u2.full_name AS updated_by_name
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id = po.supplier_id
                LEFT JOIN users u ON u.id = po.created_by
                LEFT JOIN users u2 ON u2.id = po.updated_by
                WHERE po.id = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết phiếu nhập kèm các dòng sản phẩm
     */
    public function getDetailsWithLines(int $id)
    {
        $pdo = DB::pdo();

        // Lấy thông tin phiếu nhập
        $po = $this->findById($id);
        if (!$po) {
            return null;
        }

        // Convert date format từ Y-m-d sang d/m/Y cho frontend
        if ($po['received_at']) {
            $po['created_at'] = date('d/m/Y', strtotime($po['received_at']));
        }

        if ($po['due_date']) {
            $po['due_date'] = date('d/m/Y', strtotime($po['due_date']));
        }

        // Convert payment_status từ số sang text
        switch ($po['payment_status']) {
            case '0':
                $po['payment_status'] = 'Đã thanh toán một phần';
                break;
            case '2':
                $po['payment_status'] = 'Đã thanh toán hết';
                break;
            default:
                $po['payment_status'] = 'Chưa đối soát';
                break;
        }

        // Lấy các dòng sản phẩm từ product_batches
        $sql = "SELECT 
                    pb.product_id,
                    pb.batch_code,
                    pb.initial_qty AS quantity,
                    pb.unit_cost,
                    pb.mfg_date AS manufacture_date,
                    pb.exp_date AS expiry_date,
                    p.name AS product_name,
                    p.sku AS product_sku
                FROM product_batches pb
                JOIN products p ON pb.product_id = p.id
                WHERE pb.purchase_order_id = ?
                ORDER BY pb.id ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $lines = $st->fetchAll(\PDO::FETCH_ASSOC);

        // Convert date format cho từng dòng
        foreach ($lines as &$line) {
            if ($line['manufacture_date']) {
                $line['manufacture_date'] = date('d/m/Y', strtotime($line['manufacture_date']));
            }
            if ($line['expiry_date']) {
                $line['expiry_date'] = date('d/m/Y', strtotime($line['expiry_date']));
            }
        }

        $po['lines'] = $lines;

        return $po;
    }

    public function update(int $id, array $data, $currentUser)
    {
        $pdo = DB::pdo();

        // Lấy dữ liệu cũ trước khi update
        $po = $this->findById($id);

        // Kiểm tra xem phiếu nhập đã thanh toán một phần chưa
        if ($po && ($po['payment_status'] === '0' || $po['payment_status'] === '2')) {
            throw new \Exception("Không thể sửa phiếu nhập kho đã thanh toán một phần hoặc thanh toán hết");
        }

        // Chỉ cho phép cập nhật một số trường nhất định
        $sql = "
            UPDATE purchase_orders
            SET note = :note,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':note' => $data['note'] ?? null,
            ':updated_by' => $currentUser,
            ':id' => $id
        ]);

        // Audit log
        $this->logUpdate(
            'purchase_orders',
            $id,
            ['note' => $po['note'] ?? null],
            ['note' => $data['note'] ?? null],
            $currentUser
        );
    }

    public function delete(int $id, $currentUser)
    {
        $pdo = DB::pdo();

        try {
            $pdo->beginTransaction();

            // Lấy thông tin phiếu nhập trước khi xóa
            $po = $this->findById($id);
            if (!$po) {
                throw new \Exception('Không tìm thấy phiếu nhập');
            }

            // Kiểm tra xem phiếu nhập đã thanh toán hết chưa
            if ($po['payment_status'] === '2') {
                throw new \Exception('Không thể xóa phiếu nhập kho đã thanh toán hết');
            }

            // 1. Xóa các product_batch liên quan và cập nhật tồn kho
            $batches = $pdo->prepare("
                SELECT id, product_id, current_qty 
                FROM product_batches 
                WHERE purchase_order_id = ?
            ");
            $batches->execute([$id]);
            $batchList = $batches->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($batchList as $batch) {
                // Trừ tồn kho
                $updateStock = $pdo->prepare("
                    UPDATE stocks 
                    SET qty = qty - :qty, 
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                ");
                $updateStock->execute([
                    ':qty' => $batch['current_qty'],
                    ':product_id' => $batch['product_id'],
                    ':updated_by' => $currentUser
                ]);
            }

            // 2. Xóa stock_movements liên quan
            $deleteMovements = $pdo->prepare("
                DELETE FROM stock_movements 
                WHERE ref_type = 'Phiếu nhập' AND ref_id = ?
            ");
            $deleteMovements->execute([$id]);

            // 3. Xóa product_batches
            $deleteBatches = $pdo->prepare("
                DELETE FROM product_batches 
                WHERE purchase_order_id = ?
            ");
            $deleteBatches->execute([$id]);

            // 4. Xóa các dòng chi tiết phiếu nhập
            $deleteItems = $pdo->prepare("
                DELETE FROM purchase_order_items 
                WHERE purchase_order_id = ?
            ");
            $deleteItems->execute([$id]);

            // 5. Xóa phiếu chi nếu có
            $deleteExpense = $pdo->prepare("
                DELETE FROM expense_vouchers 
                WHERE purchase_order_id = ?
            ");
            $deleteExpense->execute([$id]);

            // 6. Xóa các bút toán công nợ
            $deleteLedger = $pdo->prepare("
                DELETE FROM ap_ledger 
                WHERE ref_type = 'Phiếu nhập' AND ref_id = ?
            ");
            $deleteLedger->execute([$id]);

            // 7. Xóa phiếu nhập
            $deletePO = $pdo->prepare("DELETE FROM purchase_orders WHERE id = ?");
            $deletePO->execute([$id]);

            // Audit log
            $this->logDelete('purchase_orders', $id, [
                'code' => $po['code'],
                'supplier_name' => $po['supplier_name'],
                'total_amount' => $po['total_amount']
            ], $currentUser);

            $pdo->commit();
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
