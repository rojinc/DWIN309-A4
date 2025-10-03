<?php
namespace App\Models;

use App\Core\Model;

/**
 * Persists audit trail entries for compliance and troubleshooting.
 */
class AuditModel extends Model
{
    /**
     * Records an audit event describing a key action.
     */
    public function record(array $data): int
    {
        $sql = 'INSERT INTO audit_trail (user_id, action, entity_type, entity_id, details, created_at)
                VALUES (:user_id, :action, :entity_type, :entity_id, :details, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'details' => $data['details'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Lists audit entries with most recent first.
     */
    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS user_name
                                     FROM audit_trail a
                                     LEFT JOIN users u ON u.id = a.user_id
                                     ORDER BY a.created_at DESC
                                     LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}