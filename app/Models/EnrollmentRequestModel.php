<?php
namespace App\Models;

use App\Core\Model;

/**
 * Handles persistence for student self-service enrollment requests.
 */
class EnrollmentRequestModel extends Model
{
    /**
     * Stores a new enrollment request record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO enrollment_requests (
                    student_id,
                    course_id,
                    preferred_date,
                    preferred_time,
                    status,
                    instructor_id,
                    student_notes,
                    admin_notes,
                    decision_by,
                    decision_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :student_id,
                    :course_id,
                    :preferred_date,
                    :preferred_time,
                    :status,
                    :instructor_id,
                    :student_notes,
                    :admin_notes,
                    :decision_by,
                    :decision_at,
                    NOW(),
                    NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'student_id' => $data['student_id'],
            'course_id' => $data['course_id'],
            'preferred_date' => $data['preferred_date'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'instructor_id' => $data['instructor_id'] ?? null,
            'student_notes' => $data['student_notes'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'decision_by' => $data['decision_by'] ?? null,
            'decision_at' => $data['decision_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Returns enrollment requests with associated user and course data.
     */
    public function all(?string $status = null): array
    {
        $sql = $this->baseSelect();
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE er.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY er.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetches requests assigned to a specific instructor, optionally filtered by status.
     */
    public function forInstructor(int $instructorId, ?string $status = null): array
    {
        $conditions = ['er.instructor_id = :instructor_id'];
        $params = ['instructor_id' => $instructorId];
        if ($status !== null) {
            $conditions[] = 'er.status = :status';
            $params['status'] = $status;
        }
        $sql = $this->baseSelect(implode(' AND ', $conditions)) . ' ORDER BY er.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves requests for a particular student.
     */
    public function forStudent(int $studentId): array
    {
        $sql = $this->baseSelect('er.student_id = :student_id') . ' ORDER BY er.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Loads a single request with joined metadata.
     */
    public function find(int $id): ?array
    {
        $sql = $this->baseSelect('er.id = :id') . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Applies updates to a request record, typically during approval/decline.
     */
    public function updateStatus(int $id, array $data): bool
    {
        $sql = 'UPDATE enrollment_requests
                SET status = :status,
                    instructor_id = :instructor_id,
                    admin_notes = :admin_notes,
                    decision_by = :decision_by,
                    decision_at = :decision_at,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $data['status'],
            'instructor_id' => $data['instructor_id'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'decision_by' => $data['decision_by'] ?? null,
            'decision_at' => $data['decision_at'] ?? null,
            'id' => $id,
        ]);
    }

    /**
     * Builds the base select query with joins to related tables.
     */
    private function baseSelect(string $extraWhere = ''): string
    {
        $sql = 'SELECT er.*, c.title AS course_title,
                       ui.first_name AS instructor_first_name,
                       ui.last_name AS instructor_last_name,
                       us.first_name AS student_first_name,
                       us.last_name AS student_last_name,
                       us.email AS student_email,
                       us.phone AS student_phone
                FROM enrollment_requests er
                INNER JOIN students s ON s.id = er.student_id
                INNER JOIN users us ON us.id = s.user_id
                INNER JOIN courses c ON c.id = er.course_id
                LEFT JOIN instructors i ON i.id = er.instructor_id
                LEFT JOIN users ui ON ui.id = i.user_id';
        if ($extraWhere !== '') {
            $sql .= ' WHERE ' . $extraWhere;
        }
        return $sql;
    }
}
