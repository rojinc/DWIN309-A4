<?php
namespace App\Models;

use App\Core\Model;

/**
 * Stores broadcast or targeted communications sent via email/SMS.
 */
class CommunicationModel extends Model
{
    /**
     * Creates a new outbound communication record.
     */
    public function create(array $data, array $recipientIds): int
    {
        $sql = 'INSERT INTO communications (sender_user_id, audience_scope, channel, subject, message, created_at)
                VALUES (:sender_user_id, :audience_scope, :channel, :subject, :message, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sender_user_id' => $data['sender_user_id'],
            'audience_scope' => $data['audience_scope'],
            'channel' => $data['channel'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ]);
        $communicationId = (int) $this->db->lastInsertId();
        $recStmt = $this->db->prepare('INSERT INTO communication_recipients (communication_id, user_id) VALUES (:communication_id, :user_id)');
        foreach ($recipientIds as $recipientId) {
            $recStmt->execute(['communication_id' => $communicationId, 'user_id' => $recipientId]);
        }
        return $communicationId;
    }

    /**
     * Lists communications for audit purposes.
     */
    public function all(): array
    {
        $sql = 'SELECT c.*, CONCAT(u.first_name, " ", u.last_name) AS sender_name
                FROM communications c
                LEFT JOIN users u ON u.id = c.sender_user_id
                ORDER BY c.created_at DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves recipient list for a given communication.
     */
    public function recipients(int $communicationId): array
    {
        $stmt = $this->db->prepare('SELECT cr.*, CONCAT(u.first_name, " ", u.last_name) AS recipient_name, u.email
                                     FROM communication_recipients cr
                                     INNER JOIN users u ON u.id = cr.user_id
                                     WHERE cr.communication_id = :communication_id');
        $stmt->execute(['communication_id' => $communicationId]);
        return $stmt->fetchAll();
    }
}