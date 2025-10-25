<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\ReceiptVoucher;

class ReceiptVoucherRepository
{
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "SELECT 
                    r.*,
                    cu.full_name AS created_by_name, 
                    pu.full_name AS payer_user_name,
                    ru.full_name AS received_by_name,
                    o.code AS order_code
                FROM receipt_vouchers r
                LEFT JOIN users cu ON cu.id = r.created_by
                LEFT JOIN users pu ON pu.id = r.payer_user_id
                LEFT JOIN users ru ON ru.id = r.received_by
                LEFT JOIN orders o ON o.id = r.order_id
                ORDER BY r.id DESC
                LIMIT 500
            ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new ReceiptVoucher($row), $rows);
    }

    public function findOne(int $id): ?ReceiptVoucher
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT r.*,
                cu.full_name AS created_by_name, 
                pu.full_name AS payer_user_name,
                ru.full_name AS received_by_name
            FROM receipt_vouchers r
            LEFT JOIN users cu ON cu.id = r.created_by
            LEFT JOIN users pu ON pu.id = r.payer_user_id
            LEFT JOIN users ru ON ru.id = r.received_by
            WHERE r.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new ReceiptVoucher($row) : null;
    }

    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO receipt_vouchers
            (code, payer_user_id, order_id, payment_id, method, amount, received_by, received_at, 
             note, txn_ref, bank_time, created_by, created_at)
            VALUES
            (:code, :payer_user_id, :order_id, :payment_id, :method, :amount, :received_by, :received_at,
             :note, :txn_ref, :bank_time, :created_by, NOW())
        ");
        $stmt->execute([
            ':code' => $data['code'] ?? '',
            ':payer_user_id' => $data['customer_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':payment_id' => $data['payment_id'] ?? null,
            ':method' => $data['method'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':received_by' => $data['received_by'] ?? $currentUser,
            ':received_at' => $data['received_at'] ?? null,
            ':note' => $data['note'] ?? null,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
            ':created_by' => $currentUser,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE receipt_vouchers SET 
                code = :code,
                payer_user_id = :payer_user_id,
                order_id = :order_id,
                payment_id = :payment_id,
                method = :method,
                amount = :amount,
                received_by = :received_by,
                received_at = :received_at,
                note = :note,
                txn_ref = :txn_ref,
                bank_time = :bank_time,
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':code' => $data['code'] ?? '',
            ':payer_user_id' => $data['customer_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':payment_id' => $data['payment_id'] ?? null,
            ':method' => $data['method'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':received_by' => $data['received_by'] ?? null,
            ':received_at' => $data['received_at'] ?? null,
            ':note' => $data['note'] ?? null,
            ':txn_ref' => $data['txn_ref'] ?? null,
            ':bank_time' => $data['bank_time'] ?? null,
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM receipt_vouchers WHERE id = ?")->execute([$id]);
    }

    /**
     * Sinh mã phiếu thu tiếp theo dạng PT-001
     */
    public function getNextCode(): string
    {
        $pdo = DB::pdo();
        $row = $pdo->query("SELECT MAX(id) AS max_id FROM receipt_vouchers")->fetch(\PDO::FETCH_ASSOC);
        $nextNum = $row && $row['max_id'] ? ((int) $row['max_id'] + 1) : 1;
        return 'PT-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
    // Lấy id lớn nhất của phiếu thu
    public function getLastId(): ?int
    {
        $pdo = DB::pdo();
        $row = $pdo->query("SELECT MAX(id) AS max_id FROM receipt_vouchers")->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['max_id'] ? (int) $row['max_id'] : null;
    }
}
