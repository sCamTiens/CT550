<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\ExpenseVoucher;

class ExpenseVoucherRepository
{
    /**
     * Convert date from d/m/Y to Y-m-d (hoặc d/m/Y H:i sang Y-m-d H:i)
     */
    private function convertDateFormat(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        // Nếu đã đúng format Y-m-d hoặc Y-m-d H:i:s thì giữ nguyên
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) {
            return $date;
        }

        // Convert từ d/m/Y sang Y-m-d
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // Convert từ d/m/Y H:i sang Y-m-d H:i
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5];
        }

        return null;
    }

    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT 
                e.id, e.code, e.type, e.purchase_order_id, e.supplier_id, e.payroll_id, e.staff_user_id,
                e.method, e.txn_ref, e.amount, e.paid_by, e.paid_at, e.bank_time, e.note, 
                e.created_at, e.updated_at, e.created_by,
                cu.full_name AS created_by_name,
                s.name AS supplier_name,
                po.code AS purchase_order_code,
                po.payment_status AS payment_status,
                pb.full_name AS paid_by_name,
                su.full_name AS staff_name
            FROM expense_vouchers e
            LEFT JOIN users cu ON cu.id = e.created_by
            LEFT JOIN suppliers s ON s.id = e.supplier_id
            LEFT JOIN purchase_orders po ON po.id = e.purchase_order_id
            LEFT JOIN users pb ON pb.id = e.paid_by
            LEFT JOIN users su ON su.id = e.staff_user_id
            ORDER BY e.id DESC
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new ExpenseVoucher($row), $rows);
    }

    public function findOne(int $id): ?ExpenseVoucher
    {
        $pdo = DB::pdo();
        $sql = "
        SELECT 
            e.*, 
            cu.full_name AS created_by_name, 
            s.name AS supplier_name,
            po.code AS purchase_order_code,
            pb.full_name AS paid_by_name,
            su.full_name AS staff_name
        FROM expense_vouchers e
        LEFT JOIN users cu ON cu.id = e.created_by
        LEFT JOIN suppliers s ON s.id = e.supplier_id
        LEFT JOIN purchase_orders po ON po.id = e.purchase_order_id
        LEFT JOIN users pb ON pb.id = e.paid_by
        LEFT JOIN users su ON su.id = e.staff_user_id
        WHERE e.id = ?
    ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new ExpenseVoucher($row) : null;
    }

    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();

        error_log("=== CREATE EXPENSE VOUCHER DEBUG ===");
        error_log("Data received: " . json_encode($data, JSON_UNESCAPED_UNICODE));
        error_log("Current user: " . $currentUser);

        try {
            $pdo->beginTransaction();

            // 1. Convert date format
            $paidAt = $this->convertDateFormat($data['paid_at'] ?? null);
            $bankTime = $this->convertDateFormat($data['bank_time'] ?? null);

            error_log("Original paid_at: " . ($data['paid_at'] ?? 'NULL'));
            error_log("Converted paid_at: " . ($paidAt ?? 'NULL'));
            error_log("Original bank_time: " . ($data['bank_time'] ?? 'NULL'));
            error_log("Converted bank_time: " . ($bankTime ?? 'NULL'));

            // 2. Tạo phiếu chi
            $stmt = $pdo->prepare("
                INSERT INTO expense_vouchers
                (code, type, purchase_order_id, supplier_id, payroll_id, staff_user_id, method, txn_ref, amount, paid_by, paid_at, bank_time, note, created_by, created_at)
                VALUES
                (:code, :type, :purchase_order_id, :supplier_id, :payroll_id, :staff_user_id, :method, :txn_ref, :amount, :paid_by, :paid_at, :bank_time, :note, :created_by, NOW())
            ");
            $stmt->execute([
                ':code' => $data['code'] ?? null,
                ':type' => $data['type'] ?? 'Nhà cung cấp',
                ':purchase_order_id' => $data['purchase_order_id'] ?? null,
                ':supplier_id' => $data['supplier_id'] ?? null,
                ':payroll_id' => $data['payroll_id'] ?? null,
                ':staff_user_id' => $data['staff_user_id'] ?? null,
                ':method' => $data['method'] ?? null,
                ':txn_ref' => $data['txn_ref'] ?? null,
                ':amount' => $data['amount'] ?? 0,
                ':paid_by' => $data['paid_by'] ?? null,
                ':paid_at' => $paidAt,
                ':bank_time' => $bankTime,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
            ]);

            error_log("Expense voucher inserted successfully");

            $expenseId = (int) $pdo->lastInsertId();
            $amount = (float) ($data['amount'] ?? 0);
            $type = $data['type'] ?? 'Nhà cung cấp';
            $purchaseOrderId = $data['purchase_order_id'] ?? null;
            $supplierId = $data['supplier_id'] ?? null;
            $payrollId = $data['payroll_id'] ?? null;

            // 3. Xử lý theo loại phiếu chi
            if ($type === 'Nhà cung cấp') {
                // 3.1. Cập nhật paid_amount và payment_status của phiếu nhập
                if ($purchaseOrderId) {
                    // Lấy thông tin phiếu nhập hiện tại
                    $poStmt = $pdo->prepare("SELECT total_amount, paid_amount FROM purchase_orders WHERE id = ?");
                    $poStmt->execute([$purchaseOrderId]);
                    $po = $poStmt->fetch(\PDO::FETCH_ASSOC);

                    if ($po) {
                        $newPaidAmount = $po['paid_amount'] + $amount;
                        $totalAmount = $po['total_amount'];

                        // Xác định payment_status mới
                        // 1 = Chưa đối soát, 0 = Đã thanh toán một phần, 2 = Đã thanh toán hết
                        if ($newPaidAmount >= $totalAmount) {
                            $newStatus = '2'; // Đã thanh toán hết
                        } elseif ($newPaidAmount > 0) {
                            $newStatus = '0'; // Đã thanh toán một phần
                        } else {
                            $newStatus = '1'; // Chưa đối soát
                        }

                        // Cập nhật phiếu nhập
                        $updatePO = $pdo->prepare("
                            UPDATE purchase_orders 
                            SET paid_amount = :paid_amount, 
                                payment_status = :payment_status
                            WHERE id = :id
                        ");
                        $updatePO->execute([
                            ':paid_amount' => $newPaidAmount,
                            ':payment_status' => $newStatus,
                            ':id' => $purchaseOrderId
                        ]);
                    }
                }

                // 3.2. Tạo bút toán giảm công nợ (credit) trong ap_ledger
                if ($supplierId && $amount > 0) {
                    $apStmt = $pdo->prepare("
                        INSERT INTO ap_ledger (supplier_id, ref_type, ref_id, debit, credit, note, created_by, created_at)
                        VALUES (:supplier_id, 'Phiếu chi', :ref_id, 0, :credit, :note, :created_by, NOW())
                    ");
                    $apStmt->execute([
                        ':supplier_id' => $supplierId,
                        ':ref_id' => $expenseId,
                        ':credit' => $amount,
                        ':note' => 'Chi tiền cho nhà cung cấp - Phiếu ' . ($data['code'] ?? ''),
                        ':created_by' => $currentUser
                    ]);
                }
            } elseif ($type === 'Lương nhân viên') {
                // 3.3. Cập nhật trạng thái bảng lương thành "Đã trả"
                if ($payrollId) {
                    $updatePayroll = $pdo->prepare("
                        UPDATE payrolls 
                        SET status = 'Đã trả'
                        WHERE id = :id
                    ");
                    $updatePayroll->execute([':id' => $payrollId]);
                }
            }

            // 4. Commit transaction
            $pdo->commit();
            error_log("Transaction committed successfully. Expense ID: " . $expenseId);
            return $expenseId;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("ERROR creating expense voucher: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        throw new \Exception("Không được phép sửa phiếu chi");
    }

    public function delete(int $id): void
    {
        $pdo = DB::pdo();

        try {
            $pdo->beginTransaction();

            // 1. Lấy thông tin phiếu chi trước khi xóa
            $stmt = $pdo->prepare("SELECT purchase_order_id, supplier_id, amount FROM expense_vouchers WHERE id = ?");
            $stmt->execute([$id]);
            $expense = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$expense) {
                throw new \Exception("Không tìm thấy phiếu chi #" . $id);
            }

            $purchaseOrderId = $expense['purchase_order_id'];
            $supplierId = $expense['supplier_id'];
            $amount = (float) $expense['amount'];

            // 2. Kiểm tra xem phiếu nhập đã thanh toán hết chưa
            if ($purchaseOrderId) {
                $poStmt = $pdo->prepare("SELECT payment_status FROM purchase_orders WHERE id = ?");
                $poStmt->execute([$purchaseOrderId]);
                $po = $poStmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($po && $po['payment_status'] === '2') {
                    throw new \Exception("Không thể xóa phiếu chi vì phiếu nhập kho đã thanh toán hết");
                }
            }

            // 3. Xóa phiếu chi
            $pdo->prepare("DELETE FROM expense_vouchers WHERE id = ?")->execute([$id]);

            // 4. Trừ lại paid_amount của phiếu nhập và cập nhật payment_status
            if ($purchaseOrderId && $amount > 0) {
                // Lấy thông tin phiếu nhập hiện tại
                $poStmt = $pdo->prepare("SELECT total_amount, paid_amount FROM purchase_orders WHERE id = ?");
                $poStmt->execute([$purchaseOrderId]);
                $po = $poStmt->fetch(\PDO::FETCH_ASSOC);

                if ($po) {
                    $newPaidAmount = max(0, $po['paid_amount'] - $amount); // Không cho âm
                    $totalAmount = $po['total_amount'];

                    // Xác định payment_status mới
                    if ($newPaidAmount >= $totalAmount) {
                        $newStatus = '2'; // Đã thanh toán hết
                    } elseif ($newPaidAmount > 0) {
                        $newStatus = '0'; // Đã thanh toán một phần
                    } else {
                        $newStatus = '1'; // Chưa đối soát
                    }

                    // Cập nhật phiếu nhập
                    $updatePO = $pdo->prepare("
                        UPDATE purchase_orders 
                        SET paid_amount = :paid_amount, 
                            payment_status = :payment_status
                        WHERE id = :id
                    ");
                    $updatePO->execute([
                        ':paid_amount' => $newPaidAmount,
                        ':payment_status' => $newStatus,
                        ':id' => $purchaseOrderId
                    ]);
                }
            }

            // 5. Xóa bút toán công nợ trong ap_ledger
            if ($supplierId) {
                $pdo->prepare("DELETE FROM ap_ledger WHERE ref_type = 'Phiếu chi' AND ref_id = ?")->execute([$id]);
            }

            $pdo->commit();

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("ERROR deleting expense voucher: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sinh mã phiếu chi tiếp theo dạng PC-001
     * Dựa trên mã CODE lớn nhất, không phải ID
     */
    public function getNextCode(): string
    {
        $pdo = DB::pdo();

        // Lấy mã code lớn nhất có dạng PC-XXX
        $sql = "SELECT code FROM expense_vouchers 
            WHERE code REGEXP '^PC-[0-9]+$' 
            ORDER BY CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC 
            LIMIT 1";

        $row = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);

        if ($row && $row['code']) {
            // Lấy phần số từ mã code (VD: PC-001 -> 001)
            $lastNum = (int) substr($row['code'], 3);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return 'PC-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
    // Lấy id lớn nhất của phiếu chi
    public function getLastId(): ?int
    {
        $pdo = DB::pdo();
        $row = $pdo->query("SELECT MAX(id) AS max_id FROM expense_vouchers")->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['max_id'] ? (int) $row['max_id'] : null;
    }

    /**
     * Lấy danh sách phiếu nhập chưa thanh toán hoặc thanh toán một phần
     * Trả về mảng ['id' => ..., 'code' => ...]
     */
    /**
     * Lấy danh sách phiếu nhập chưa thanh toán hoặc thanh toán một phần
     * Chỉ trả về các phiếu có trạng thái 'Chưa đối soát' hoặc 'Đã thanh toán một phần'
     * Trả về mảng ['id' => ..., 'code' => ..., 'supplier_id' => ...]
     */
    public function getUnpaidPurchaseOrders(): array
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
                    id, 
                    code, 
                    supplier_id,
                    total_amount,
                    paid_amount,
                    (total_amount - paid_amount) as remaining_debt
                FROM purchase_orders 
                WHERE (total_amount - paid_amount) > 0
                ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
