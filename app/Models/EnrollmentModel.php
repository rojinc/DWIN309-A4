<?php
namespace App\Models;

use App\Core\Model;

/**
 * Manages student enrolments into driving courses.
 */
class EnrollmentModel extends Model
{
    /**
     * Retrieves enrolments with student and course context.
     */
    public function all(): array
    {
        $sql = 'SELECT e.*, c.title AS course_title, u.first_name, u.last_name
                FROM enrollments e
                INNER JOIN courses c ON c.id = e.course_id
                INNER JOIN students s ON s.id = e.student_id
                INNER JOIN users u ON u.id = s.user_id
                ORDER BY e.created_at DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Returns enrolments for a particular student.
     */
    public function forStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT e.*, c.title AS course_title FROM enrollments e INNER JOIN courses c ON c.id = e.course_id WHERE e.student_id = :student_id');
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Locates a single enrolment.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Finds an enrolment for a specific student and course combination.
     */
    public function findByStudentAndCourse(int $studentId, int $courseId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM enrollments WHERE student_id = :student_id AND course_id = :course_id LIMIT 1');
        $stmt->execute(['student_id' => $studentId, 'course_id' => $courseId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Creates a new enrolment record.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO enrollments (student_id, course_id, start_date, status, progress_percentage, notes, created_at, updated_at)
                VALUES (:student_id, :course_id, :start_date, :status, :progress_percentage, :notes, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'student_id' => $data['student_id'],
            'course_id' => $data['course_id'],
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'status' => $data['status'] ?? 'active',
            'progress_percentage' => $data['progress_percentage'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates enrolment fields.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE enrollments
                SET start_date = :start_date,
                    status = :status,
                    progress_percentage = :progress_percentage,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'start_date' => $data['start_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'progress_percentage' => $data['progress_percentage'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'id' => $id,
        ]);
    }

    /**
     * Updates the progress percentage for an enrolment.
     */
    public function setProgress(int $id, int $progress): bool
    {
        $stmt = $this->db->prepare('UPDATE enrollments SET progress_percentage = :progress, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'progress' => $progress,
            'id' => $id,
        ]);
    }
}