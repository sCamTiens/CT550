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
                cu.full_name AS created_by_name
            FROM expense_vouchers e
            LEFT JOIN users cu ON cu.id = e.created_by
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
            (paid_at, amount, receiver_name, note, is_active, txn_ref, bank_time, created_by, updated_by, created_at, updated_at)
            VALUES
            (:paid_at, :amount, :receiver_name, :note, :is_active, :txn_ref, :bank_time, :created_by, :updated_by, NOW(), NOW())
        ");
        $stmt->execute([
            ':paid_at' => $data['paid_at'],
            ':amount' => $data['amount'],
            ':receiver_name' => $data['receiver_name'],
            ':note' => $data['note'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
            ':created_by' => $currentUser,
            ':updated_by' => $currentUser,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE expense_vouchers SET 
                paid_at = :paid_at, amount = :amount, receiver_name = :receiver_name, note = :note, is_active = :is_active,
                txn_ref = :txn_ref, bank_time = :bank_time,
                updated_by = :updated_by, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':paid_at' => $data['paid_at'],
            ':amount' => $data['amount'],
            ':receiver_name' => $data['receiver_name'],
            ':note' => $data['note'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
            ':updated_by' => $currentUser,
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
