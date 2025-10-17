<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\StockOut;
use App\Support\Auditable;

class StockOutRepository
{
    use Auditable;

    /**
     * Lấy toàn bộ danh sách phiếu xuất kho
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT so.id, so.code, so.type, so.order_id, so.status, so.out_date, 
                   so.total_amount, so.note,
                   so.created_at, so.updated_at,
                   so.created_by, cu.full_name AS created_by_name,
                   so.updated_by, uu.full_name AS updated_by_name,
                   o.code AS order_code, c.full_name AS customer_name
            FROM stock_outs so
            LEFT JOIN users cu ON cu.id = so.created_by
            LEFT JOIN users uu ON uu.id = so.updated_by
            LEFT JOIN orders o ON o.id = so.order_id
            LEFT JOIN users c ON c.id = o.user_id
            ORDER BY so.id DESC
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new StockOut($row), $rows);
    }

    /**
     * Tìm phiếu xuất kho theo ID
     */
    public function findOne(int $id): ?StockOut
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT so.id, so.code, so.type, so.order_id, so.status, so.out_date, 
                   so.total_amount, so.note,
                   so.created_at, so.updated_at,
                   so.created_by, cu.full_name AS created_by_name,
                   so.updated_by, uu.full_name AS updated_by_name,
                   o.code AS order_code, c.full_name AS customer_name
            FROM stock_outs so
            LEFT JOIN users cu ON cu.id = so.created_by
            LEFT JOIN users uu ON uu.id = so.updated_by
            LEFT JOIN orders o ON o.id = so.order_id
            LEFT JOIN users c ON c.id = o.user_id
            WHERE so.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new StockOut($row) : null;
    }

    /**
     * Tạo mã phiếu xuất kho tự động
     */
    public function generateCode(): string
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM stock_outs");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'XK' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo phiếu xuất kho mới
     */
    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Tạo phiếu xuất kho
            $stmt = $pdo->prepare("
                INSERT INTO stock_outs
                (code, type, order_id, status, out_date, total_amount, note,
                 created_by, updated_by, created_at, updated_at)
                VALUES
                (:code, :type, :order_id, :status, :out_date, :total_amount, :note,
                 :created_by, :updated_by, NOW(), NOW())
            ");
            $stmt->execute([
                ':code' => $data['code'],
                ':type' => $data['type'] ?? 'sale',
                ':order_id' => $data['order_id'] ?: null,
                ':status' => $data['status'] ?? 'pending',
                ':out_date' => $data['out_date'] ?? date('Y-m-d H:i:s'),
                ':total_amount' => $data['total_amount'] ?? 0,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser,
            ]);

            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
            return $id;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật phiếu xuất kho
     */
    public function update(int $id, array $data, int $currentUser): void
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE stock_outs SET 
                    type = :type,
                    order_id = :order_id,
                    status = :status,
                    out_date = :out_date,
                    total_amount = :total_amount,
                    note = :note,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':type' => $data['type'] ?? 'sale',
                ':order_id' => $data['order_id'] ?: null,
                ':status' => $data['status'] ?? 'pending',
                ':out_date' => $data['out_date'] ?? date('Y-m-d H:i:s'),
                ':total_amount' => $data['total_amount'] ?? 0,
                ':note' => $data['note'] ?? null,
                ':updated_by' => $currentUser,
            ]);

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa phiếu xuất kho
     */
    public function delete(int $id): void
    {
        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Xóa chi tiết phiếu xuất
            $pdo->prepare("DELETE FROM stock_out_items WHERE stock_out_id = ?")->execute([$id]);
            
            // Xóa phiếu xuất
            $pdo->prepare("DELETE FROM stock_outs WHERE id = ?")->execute([$id]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lấy danh sách phiếu xuất chờ duyệt
     */
    public function pending(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT so.id, so.code, so.type, so.out_date, so.total_amount
            FROM stock_outs so
            WHERE so.status = 'pending'
            ORDER BY so.id DESC
        ";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Duyệt phiếu xuất kho
     */
    public function approve(int $id, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE stock_outs 
            SET status = 'approved', updated_by = :user, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id, ':user' => $currentUser]);
    }

    /**
     * Hoàn thành phiếu xuất kho
     */
    public function complete(int $id, int $currentUser): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("
            UPDATE stock_outs 
            SET status = 'completed', updated_by = :user, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id, ':user' => $currentUser]);
    }
}
