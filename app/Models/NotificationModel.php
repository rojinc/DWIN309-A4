<?php
namespace App\Models;

use App\Core\Model;

/**
 * Persists user-facing notifications inside the application.
 */
class NotificationModel extends Model
{
    /**
     * Adds a notification targeted at a specific user.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO notifications (user_id, title, message, level, is_read, created_at)
                VALUES (:user_id, :title, :message, :level, 0, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'level' => $data['level'] ?? 'info',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Retrieves notifications for a user.
     */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Marks a notification as read.
     */
    public function markRead(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}