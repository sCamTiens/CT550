<?php
namespace App\Models\Repositories;

use App\Core\DB;

class AuditLogRepository
{
    /**
     * Ghi log hành động
     */
    public function log(
        ?int $actorUserId,
        string $entityType,
        int $entityId,
        string $action,
        ?array $beforeData = null,
        ?array $afterData = null
    ): int {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (
                actor_user_id, entity_type, entity_id, action,
                before_data, after_data, created_at
            ) VALUES (
                :actor_user_id, :entity_type, :entity_id, :action,
                :before_data, :after_data, NOW()
            )
        ");

        $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':action' => $action,
            ':before_data' => $beforeData ? json_encode($beforeData, JSON_UNESCAPED_UNICODE) : null,
            ':after_data' => $afterData ? json_encode($afterData, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Lấy toàn bộ logs với filter và phân trang
     */
    public function all(array $filters = []): array
    {
        $pdo = DB::pdo();

        $where = ['1=1'];
        $params = [];

        // Filter theo user
        if (!empty($filters['user_id'])) {
            $where[] = 'al.actor_user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }

        // FILTER 1: Theo NGƯỜI THỰC HIỆN (actor_type)
        if (!empty($filters['actor_type'])) {
            if ($filters['actor_type'] === 'staff') {
                $where[] = 'u.role_id = 2';
            } elseif ($filters['actor_type'] === 'customer') {
                $where[] = 'u.role_id = 1';
            }
        }

        // FILTER 2: Theo LOẠI ĐỐI TƯỢNG (entity_type)
        if (!empty($filters['entity_type'])) {
            if ($filters['entity_type'] === 'staff') {
                // Lọc logs về thao tác trên NHÂN VIÊN
                $where[] = 'al.entity_type = "staff"';
            } elseif ($filters['entity_type'] === 'customer') {
                // Lọc logs về thao tác trên KHÁCH HÀNG
                $where[] = 'al.entity_type = "customers"';
            } else {
                // Lọc theo entity bình thường
                $where[] = 'al.entity_type = :entity_type';
                $params[':entity_type'] = $filters['entity_type'];
            }
        }

        // Filter theo action
        if (!empty($filters['action'])) {
            $where[] = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }

        // Filter theo khoảng thời gian
        if (!empty($filters['from_date'])) {
            $where[] = 'DATE(al.created_at) >= :from_date';
            $params[':from_date'] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[] = 'DATE(al.created_at) <= :to_date';
            $params[':to_date'] = $filters['to_date'];
        }

        // Search
        if (!empty($filters['search'])) {
            $where[] = "(
                al.before_data LIKE :search 
                OR al.after_data LIKE :search
                OR u.full_name LIKE :search
            )";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    al.*,
                    u.full_name AS actor_name,
                    u.username AS actor_username
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.actor_user_id
                WHERE {$whereClause}
                ORDER BY al.created_at DESC
            ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy log theo entity
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT 
                al.*,
                u.full_name AS actor_name,
                u.username AS actor_username
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.actor_user_id
            WHERE al.entity_type = :entity_type 
              AND al.entity_id = :entity_id
            ORDER BY al.created_at DESC
        ");

        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê logs theo action
     */
    public function statsByAction(?string $fromDate = null, ?string $toDate = null): array
    {
        $pdo = DB::pdo();

        $where = '1=1';
        $params = [];

        if ($fromDate) {
            $where .= ' AND DATE(created_at) >= :from_date';
            $params[':from_date'] = $fromDate;
        }
        if ($toDate) {
            $where .= ' AND DATE(created_at) <= :to_date';
            $params[':to_date'] = $toDate;
        }

        $stmt = $pdo->prepare("
            SELECT 
                action,
                COUNT(*) as count
            FROM audit_logs
            WHERE {$where}
            GROUP BY action
            ORDER BY count DESC
        ");

        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê logs theo entity_type
     */
    public function statsByEntity(?string $fromDate = null, ?string $toDate = null): array
    {
        $pdo = DB::pdo();

        $where = '1=1';
        $params = [];

        if ($fromDate) {
            $where .= ' AND DATE(created_at) >= :from_date';
            $params[':from_date'] = $fromDate;
        }
        if ($toDate) {
            $where .= ' AND DATE(created_at) <= :to_date';
            $params[':to_date'] = $toDate;
        }

        $stmt = $pdo->prepare("
            SELECT 
                entity_type,
                COUNT(*) as count
            FROM audit_logs
            WHERE {$where}
            GROUP BY entity_type
            ORDER BY count DESC
        ");

        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê hoạt động theo Staff (role_id = 2)
     */
    public function statsByStaff(?string $fromDate = null, ?string $toDate = null, int $limit = 20): array
    {
        $pdo = DB::pdo();

        $where = 'u.role_id = 2';
        $params = [];

        if ($fromDate) {
            $where .= ' AND DATE(al.created_at) >= :from_date';
            $params[':from_date'] = $fromDate;
        }
        if ($toDate) {
            $where .= ' AND DATE(al.created_at) <= :to_date';
            $params[':to_date'] = $toDate;
        }

        $stmt = $pdo->prepare("  SELECT 
                                            al.actor_user_id,
                                            u.full_name,
                                            u.username,
                                            sp.staff_role,
                                            COUNT(*) as total_actions,
                                            SUM(CASE WHEN al.action = 'create' THEN 1 ELSE 0 END) as creates,
                                            SUM(CASE WHEN al.action = 'update' THEN 1 ELSE 0 END) as updates,
                                            SUM(CASE WHEN al.action = 'delete' THEN 1 ELSE 0 END) as deletes,
                                            MAX(al.created_at) as last_action_at
                                        FROM audit_logs al
                                        INNER JOIN users u ON u.id = al.actor_user_id
                                        LEFT JOIN staff_profiles sp ON sp.user_id = u.id
                                        WHERE {$where}
                                        GROUP BY al.actor_user_id, u.full_name, u.username, sp.staff_role
                                        ORDER BY total_actions DESC
                                        LIMIT :limit
                                ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê hoạt động theo Customer (role_id = 1)
     */
    public function statsByCustomer(?string $fromDate = null, ?string $toDate = null, int $limit = 20): array
    {
        $pdo = DB::pdo();

        $where = 'u.role_id = 1';
        $params = [];

        if ($fromDate) {
            $where .= ' AND DATE(al.created_at) >= :from_date';
            $params[':from_date'] = $fromDate;
        }
        if ($toDate) {
            $where .= ' AND DATE(al.created_at) <= :to_date';
            $params[':to_date'] = $toDate;
        }

        $stmt = $pdo->prepare("  SELECT 
                                            al.actor_user_id,
                                            u.full_name,
                                            u.username,
                                            COUNT(*) as total_actions,
                                            SUM(CASE WHEN al.action = 'create' THEN 1 ELSE 0 END) as creates,
                                            SUM(CASE WHEN al.action = 'update' THEN 1 ELSE 0 END) as updates,
                                            SUM(CASE WHEN al.action = 'delete' THEN 1 ELSE 0 END) as deletes,
                                            MAX(al.created_at) as last_action_at
                                        FROM audit_logs al
                                        INNER JOIN users u ON u.id = al.actor_user_id
                                        WHERE {$where}
                                        GROUP BY al.actor_user_id, u.full_name, u.username
                                        ORDER BY total_actions DESC
                                        LIMIT :limit
                                    ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách nhân viên đã có hoạt động
     */
    public function getActiveStaff(): array
    {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT DISTINCT
                u.id,
                u.full_name,
                u.username,
                sp.staff_role
            FROM audit_logs al
            INNER JOIN users u ON u.id = al.actor_user_id
            LEFT JOIN staff_profiles sp ON sp.user_id = u.id
            WHERE u.role_id = 2
            ORDER BY u.full_name ASC
        ");

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
