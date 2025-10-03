<?php
namespace App\Models;

use App\Core\Model;

/**
 * Stores automated reminders for lessons and invoices.
 */
class ReminderModel extends Model
{
    /**
     * Creates a reminder record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO reminders (related_type, related_id, recipient_user_id, channel, reminder_type, message, send_on, status, created_at, updated_at)
                VALUES (:related_type, :related_id, :recipient_user_id, :channel, :reminder_type, :message, :send_on, :status, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'related_type' => $data['related_type'],
            'related_id' => $data['related_id'],
            'recipient_user_id' => $data['recipient_user_id'],
            'channel' => $data['channel'] ?? 'sms',
            'reminder_type' => $data['reminder_type'],
            'message' => $data['message'],
            'send_on' => $data['send_on'],
            'status' => $data['status'] ?? 'pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Retrieves reminders due for sending.
     */
    public function due(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM reminders WHERE send_on <= CURDATE() AND status = "pending"');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates reminder status.
     */
    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE reminders SET status = :status, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Lists reminders for reporting.
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM reminders ORDER BY send_on DESC');
        return $stmt->fetchAll();
    }
}