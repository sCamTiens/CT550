<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\ReceiptVoucher;

class ReceiptVoucherRepository
{
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT r.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
            FROM receipt_vouchers r
            LEFT JOIN users cu ON cu.id = r.created_by
            LEFT JOIN users uu ON uu.id = r.updated_by
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
            SELECT r.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
            FROM receipt_vouchers r
            LEFT JOIN users cu ON cu.id = r.created_by
            LEFT JOIN users uu ON uu.id = r.updated_by
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
            (received_at, amount, payer_name, note, is_active, txn_ref, bank_time, created_by, updated_by, created_at, updated_at)
            VALUES
            (:received_at, :amount, :payer_name, :note, :is_active, :txn_ref, :bank_time, :created_by, :updated_by, NOW(), NOW())
        ");
        $stmt->execute([
            ':received_at' => $data['received_at'],
            ':amount' => $data['amount'],
            ':payer_name' => $data['payer_name'],
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
            UPDATE receipt_vouchers SET 
                received_at = :received_at, amount = :amount, payer_name = :payer_name, note = :note, is_active = :is_active,
                txn_ref = :txn_ref, bank_time = :bank_time,
                updated_by = :updated_by, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':received_at' => $data['received_at'],
            ':amount' => $data['amount'],
            ':payer_name' => $data['payer_name'],
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
