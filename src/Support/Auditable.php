<?php
namespace App\Support;

use App\Models\Repositories\AuditLogRepository;

/**
 * Trait để thêm tính năng audit log vào các Repository
 * 
 * Sử dụng:
 * 1. use Auditable trong Repository class
 * 2. Gọi $this->logCreate(), $this->logUpdate(), $this->logDelete()
 */
trait Auditable
{
    private ?AuditLogRepository $auditRepo = null;

    /**
     * Lấy instance của AuditLogRepository
     */
    private function getAuditRepo(): AuditLogRepository
    {
        if (!$this->auditRepo) {
            $this->auditRepo = new AuditLogRepository();
        }
        return $this->auditRepo;
    }

    /**
     * Log hành động CREATE
     */
    protected function logCreate(
        string $entityType,
        int $entityId,
        array $afterData,
        ?int $actorUserId = null
    ): void {
        $this->getAuditRepo()->log(
            $actorUserId,
            $entityType,
            $entityId,
            'create',
            null,
            $afterData
        );
    }

    /**
     * Log hành động UPDATE
     */
    protected function logUpdate(
        string $entityType,
        int $entityId,
        array $beforeData,
        array $afterData,
        ?int $actorUserId = null
    ): void {
        // Chỉ log nếu có thay đổi
        if ($beforeData === $afterData) {
            return;
        }

        $this->getAuditRepo()->log(
            $actorUserId,
            $entityType,
            $entityId,
            'update',
            $beforeData,
            $afterData
        );
    }

    /**
     * Log hành động DELETE
     */
    protected function logDelete(
        string $entityType,
        int $entityId,
        array $beforeData,
        ?int $actorUserId = null
    ): void {
        $this->getAuditRepo()->log(
            $actorUserId,
            $entityType,
            $entityId,
            'delete',
            $beforeData,
            null
        );
    }

    /**
     * Log hành động RESTORE
     */
    protected function logRestore(
        string $entityType,
        int $entityId,
        array $afterData,
        ?int $actorUserId = null
    ): void {
        $this->getAuditRepo()->log(
            $actorUserId,
            $entityType,
            $entityId,
            'restore',
            null,
            $afterData
        );
    }

    /**
     * Log hành động thay đổi trạng thái
     */
    protected function logStatusChange(
        string $entityType,
        int $entityId,
        string $oldStatus,
        string $newStatus,
        ?int $actorUserId = null
    ): void {
        $this->getAuditRepo()->log(
            $actorUserId,
            $entityType,
            $entityId,
            'status_change',
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }
}
