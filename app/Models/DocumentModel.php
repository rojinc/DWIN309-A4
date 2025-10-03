<?php
namespace App\Models;

use App\Core\Model;

/**
 * Handles storage metadata for uploaded documents.
 */
class DocumentModel extends Model
{
    /**
     * Saves file metadata associated with a user profile.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO documents (user_id, file_name, file_path, mime_type, file_size, category, notes, created_at)
                VALUES (:user_id, :file_name, :file_path, :mime_type, :file_size, :category, :notes, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'] ?? 'application/octet-stream',
            'file_size' => $data['file_size'] ?? 0,
            'category' => $data['category'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Retrieves a document record by primary key.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Returns documents belonging to a user.
     */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
