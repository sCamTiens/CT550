<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\ExpenseVoucher;

class ExpenseVoucherRepository
{
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT 
                e.id, e.code, e.purchase_order_id, e.supplier_id, e.method, e.txn_ref, e.amount, e.paid_by, e.paid_at, e.bank_time, e.note, 
                e.created_at, e.updated_at, e.created_by,
                cu.full_name AS created_by_name,
                s.name AS supplier_name,
                po.code AS purchase_order_code,
                pb.full_name AS paid_by_name
            FROM expense_vouchers e
            LEFT JOIN users cu ON cu.id = e.created_by
            LEFT JOIN suppliers s ON s.id = e.supplier_id
            LEFT JOIN purchase_orders po ON po.id = e.purchase_order_id
            LEFT JOIN users pb ON pb.id = e.paid_by
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
            SELECT e.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
            FROM expense_vouchers e
            LEFT JOIN users cu ON cu.id = e.created_by
            LEFT JOIN users uu ON uu.id = e.updated_by
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
        $stmt = $pdo->prepare("
            INSERT INTO expense_vouchers
            (code, purchase_order_id, supplier_id, method, txn_ref, amount, paid_by, paid_at, bank_time, note, created_by, created_at, updated_at)
            VALUES
            (:code, :purchase_order_id, :supplier_id, :method, :txn_ref, :amount, :paid_by, :paid_at, :bank_time, :note, :created_by, NOW(), NOW())
        ");
        $stmt->execute([
            ':code' => $data['code'] ?? null,
            ':purchase_order_id' => $data['purchase_order_id'] ?? null,
            ':supplier_id' => $data['supplier_id'] ?? null,
            ':method' => $data['method'] ?? null,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':paid_by' => $data['paid_by'] ?? null,
            ':paid_at' => $data['paid_at'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
            ':note' => $data['note'] ?? null,
            ':created_by' => $currentUser,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE expense_vouchers SET 
                purchase_order_id = :purchase_order_id,
                supplier_id = :supplier_id,
                method = :method,
                txn_ref = :txn_ref,
                amount = :amount,
                paid_by = :paid_by,
                paid_at = :paid_at,
                bank_time = :bank_time,
                note = :note,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':purchase_order_id' => $data['purchase_order_id'] ?? null,
            ':supplier_id' => $data['supplier_id'] ?? null,
            ':method' => $data['method'] ?? null,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':paid_by' => $data['paid_by'] ?? null,
            ':paid_at' => $data['paid_at'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
            ':note' => $data['note'] ?? null,
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM expense_vouchers WHERE id = ?")->execute([$id]);
    }

    /**
     * Sinh mã phiếu chi tiếp theo dạng PC-001
     */
    public function getNextCode(): string
    {
        $pdo = DB::pdo();
        $row = $pdo->query("SELECT MAX(id) AS max_id FROM expense_vouchers")->fetch(\PDO::FETCH_ASSOC);
        $nextNum = $row && $row['max_id'] ? ((int) $row['max_id'] + 1) : 1;
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
        $sql = "SELECT id, code, supplier_id FROM purchase_orders WHERE payment_status IN ('Chưa đối soát', 'Đã thanh toán một phần') ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
