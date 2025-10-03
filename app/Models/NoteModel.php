<?php
namespace App\Models;

use App\Core\Model;

/**
 * Records freeform notes against students, instructors, and staff profiles.
 */
class NoteModel extends Model
{
    /**
     * Adds a note entry.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO notes (related_type, related_id, author_user_id, content, created_at)
                VALUES (:related_type, :related_id, :author_user_id, :content, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'related_type' => $data['related_type'],
            'related_id' => $data['related_id'],
            'author_user_id' => $data['author_user_id'],
            'content' => $data['content'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Lists notes for a specific entity.
     */
    public function for(string $type, int $relatedId): array
    {
        $stmt = $this->db->prepare('SELECT n.*, CONCAT(u.first_name, " ", u.last_name) AS author_name
                                     FROM notes n
                                     LEFT JOIN users u ON u.id = n.author_user_id
                                     WHERE n.related_type = :related_type AND n.related_id = :related_id
                                     ORDER BY n.created_at DESC');
        $stmt->execute(['related_type' => $type, 'related_id' => $relatedId]);
        return $stmt->fetchAll();
    }
}