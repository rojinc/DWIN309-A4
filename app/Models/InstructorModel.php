<?php
namespace App\Models;

use App\Core\Model;

/**
 * Handles instructor profile persistence and scheduling statistics.
 */
class InstructorModel extends Model
{
    /**
     * Lists all instructors alongside aggregated metrics.
     */
    public function all(): array
    {
        $sql = 'SELECT i.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name,
                       (SELECT COUNT(*) FROM schedules s WHERE s.instructor_id = i.id AND s.event_type = "lesson") AS lesson_count,
                       (SELECT COUNT(*) FROM schedules s WHERE s.instructor_id = i.id AND s.status = "completed") AS completed_lessons
                FROM instructors i
                INNER JOIN users u ON u.id = i.user_id
                LEFT JOIN branches b ON b.id = i.branch_id
                ORDER BY u.first_name';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Loads a single instructor with branch and user metadata.
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT i.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM instructors i
                INNER JOIN users u ON u.id = i.user_id
                LEFT JOIN branches b ON b.id = i.branch_id
                WHERE i.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Locates an instructor by their owning user account.
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM instructors WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Creates an instructor profile entry.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO instructors (user_id, branch_id, certification_number, accreditation_expiry, experience_years, availability_notes, bio, created_at, updated_at)
                VALUES (:user_id, :branch_id, :certification_number, :accreditation_expiry, :experience_years, :availability_notes, :bio, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'certification_number' => $data['certification_number'] ?? null,
            'accreditation_expiry' => $data['accreditation_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'availability_notes' => $data['availability_notes'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates instructor profile information.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE instructors
                SET branch_id = :branch_id,
                    certification_number = :certification_number,
                    accreditation_expiry = :accreditation_expiry,
                    experience_years = :experience_years,
                    availability_notes = :availability_notes,
                    bio = :bio,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'branch_id' => $data['branch_id'] ?? null,
            'certification_number' => $data['certification_number'] ?? null,
            'accreditation_expiry' => $data['accreditation_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'availability_notes' => $data['availability_notes'] ?? null,
            'bio' => $data['bio'] ?? null,
            'id' => $id,
        ]);
    }

    /**
     * Captures instructor performance ratings.
     */
    public function setRating(int $id, float $rating): bool
    {
        $sql = 'UPDATE instructors SET rating = :rating, updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['rating' => $rating, 'id' => $id]);
    }

    /**
     * Retrieves instructors assigned to a specific branch.
     */
    public function forBranch(int $branchId): array
    {
        $stmt = $this->db->prepare('SELECT i.*, u.first_name, u.last_name FROM instructors i INNER JOIN users u ON u.id = i.user_id WHERE i.branch_id = :branch_id');
        $stmt->execute(['branch_id' => $branchId]);
        return $stmt->fetchAll();
    }
}
