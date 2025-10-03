<?php
namespace App\Models;

use App\Core\Model;

/**
 * Manages staff assignments and internal roles.
 */
class StaffModel extends Model
{
    /**
     * Lists all staff members with their user accounts and branch allocations.
     */
    public function all(): array
    {
        $sql = 'SELECT sp.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM staff_profiles sp
                INNER JOIN users u ON u.id = sp.user_id
                LEFT JOIN branches b ON b.id = sp.branch_id
                ORDER BY u.first_name';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a staff profile by its identifier.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT sp.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name FROM staff_profiles sp INNER JOIN users u ON u.id = sp.user_id LEFT JOIN branches b ON b.id = sp.branch_id WHERE sp.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Creates a staff profile record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO staff_profiles (user_id, branch_id, position_title, employment_type, start_date, notes, created_at, updated_at)
                VALUES (:user_id, :branch_id, :position_title, :employment_type, :start_date, :notes, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'position_title' => $data['position_title'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'Full-time',
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates staff profile details.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE staff_profiles
                SET branch_id = :branch_id,
                    position_title = :position_title,
                    employment_type = :employment_type,
                    start_date = :start_date,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'branch_id' => $data['branch_id'] ?? null,
            'position_title' => $data['position_title'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'id' => $id,
        ]);
    }
}