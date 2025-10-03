<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Data access for student-specific profile details and lookups.
 */
class StudentModel extends Model
{
    /**
     * Returns all students with their linked user metadata.
     */
    public function all(): array
    {
        $sql = 'SELECT s.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                ORDER BY u.first_name';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Fetches a single student record with associated entities.
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT s.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                WHERE s.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Locates a student by the owning user account.
     */
    public function findByUserId(int $userId): ?array
    {
        $sql = 'SELECT s.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                WHERE s.user_id = :user_id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Returns students who have scheduled lessons with the given instructor.
     */
    public function forInstructor(int $instructorId, ?string $term = null): array
    {
        $sql = 'SELECT DISTINCT s.*, u.first_name, u.last_name, u.email, u.phone, b.name AS branch_name
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                INNER JOIN students s ON s.id = e.student_id
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                WHERE sch.instructor_id = :instructor_id';
        $params = ['instructor_id' => $instructorId];
        if ($term !== null && $term !== '') {
            $sql .= ' AND (u.first_name LIKE :term OR u.last_name LIKE :term OR s.license_number LIKE :term)';
            $params['term'] = '%' . $term . '%';
        }
        $sql .= ' ORDER BY u.first_name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Creates a student profile and returns the new identifier.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO students (user_id, branch_id, license_number, license_status, license_expiry, emergency_contact_name, emergency_contact_phone, address_line, city, postcode, progress_summary, created_at, updated_at)
                VALUES (:user_id, :branch_id, :license_number, :license_status, :license_expiry, :emergency_contact_name, :emergency_contact_phone, :address_line, :city, :postcode, :progress_summary, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'license_status' => $data['license_status'] ?? 'Learner',
            'license_expiry' => $data['license_expiry'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address_line' => $data['address_line'] ?? null,
            'city' => $data['city'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'progress_summary' => $data['progress_summary'] ?? 'Not yet started.'
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates student profile data.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE students
                SET branch_id = :branch_id,
                    license_number = :license_number,
                    license_status = :license_status,
                    license_expiry = :license_expiry,
                    emergency_contact_name = :emergency_contact_name,
                    emergency_contact_phone = :emergency_contact_phone,
                    address_line = :address_line,
                    city = :city,
                    postcode = :postcode,
                    progress_summary = :progress_summary,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'branch_id' => $data['branch_id'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'license_status' => $data['license_status'] ?? 'Learner',
            'license_expiry' => $data['license_expiry'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address_line' => $data['address_line'] ?? null,
            'city' => $data['city'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'progress_summary' => $data['progress_summary'] ?? null,
            'id' => $id,
        ]);
    }

    /**
     * Retrieves students that match a search query across names and license numbers.
     */
    public function search(string $term): array
    {
        $pattern = '%' . $term . '%';
        $sql = 'SELECT s.*, u.first_name, u.last_name, b.name AS branch_name
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                WHERE u.first_name LIKE :first_term OR u.last_name LIKE :last_term OR s.license_number LIKE :license_term
                ORDER BY u.first_name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'first_term' => $pattern,
            'last_term' => $pattern,
            'license_term' => $pattern,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Generates progress statistics for dashboard cards.
     */
    public function progressStats(): array
    {
        $sql = 'SELECT
                    SUM(CASE WHEN progress_summary LIKE "%completed%" THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN progress_summary LIKE "%in progress%" THEN 1 ELSE 0 END) AS in_progress,
                    COUNT(*) AS total
                FROM students';
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return [
            'completed' => (int) ($row['completed'] ?? 0),
            'in_progress' => (int) ($row['in_progress'] ?? 0),
            'total' => (int) ($row['total'] ?? 0)
        ];
    }
}

