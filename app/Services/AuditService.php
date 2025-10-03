<?php
namespace App\Services;

use App\Models\AuditModel;

/**
 * Simplifies writing audit trail entries.
 */
class AuditService
{
    private AuditModel $audits;

    /**
     * Instantiates the audit model for downstream use.
     */
    public function __construct()
    {
        $this->audits = new AuditModel();
    }

    /**
     * Records an action performed by the current user.
     */
    public function log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        $this->audits->record([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
        ]);
    }
}