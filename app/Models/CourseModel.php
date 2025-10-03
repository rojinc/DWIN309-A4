<?php
namespace App\Models;

use App\Core\Model;

/**
 * Handles course catalogue persistence and instructor associations.
 */
class CourseModel extends Model
{
    /**
     * Returns all courses with counts of active students.
     */
    public function all(): array
    {
        $sql = 'SELECT c.*, (
                    SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status IN ("active", "in_progress")
                ) AS active_students
                FROM courses c
                ORDER BY c.title';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a specific course with assigned instructors.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $course = $stmt->fetch();
        if ($course === false) {
            return null;
        }
        $course['instructors'] = $this->assignedInstructors($id);
        return $course;
    }

    /**
     * Creates a new course record and manages instructor links.
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO courses (title, description, price, lesson_count, category, status, created_at, updated_at)
                VALUES (:title, :description, :price, :lesson_count, :category, :status, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'lesson_count' => $data['lesson_count'],
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
        $courseId = (int) $this->db->lastInsertId();
        if (!empty($data['instructor_ids']) && is_array($data['instructor_ids'])) {
            $this->syncInstructors($courseId, $data['instructor_ids']);
        }
        return $courseId;
    }

    /**
     * Updates course details and optionally reassigns instructors.
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE courses
                SET title = :title,
                    description = :description,
                    price = :price,
                    lesson_count = :lesson_count,
                    category = :category,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'lesson_count' => $data['lesson_count'],
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? 'active',
            'id' => $id,
        ]);
        if (!empty($data['instructor_ids']) && is_array($data['instructor_ids'])) {
            $this->syncInstructors($id, $data['instructor_ids']);
        }
        return $result;
    }

    /**
     * Returns instructors assigned to a course.
     */
    public function assignedInstructors(int $courseId): array
    {
        $stmt = $this->db->prepare('SELECT ci.instructor_id, u.first_name, u.last_name
                                     FROM course_instructor ci
                                     INNER JOIN instructors i ON i.id = ci.instructor_id
                                     INNER JOIN users u ON u.id = i.user_id
                                     WHERE ci.course_id = :course_id');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Synchronises instructor assignments to match the supplied list.
     */
    private function syncInstructors(int $courseId, array $instructorIds): void
    {
        $this->db->prepare('DELETE FROM course_instructor WHERE course_id = :course_id')->execute(['course_id' => $courseId]);
        $stmt = $this->db->prepare('INSERT INTO course_instructor (course_id, instructor_id) VALUES (:course_id, :instructor_id)');
        foreach ($instructorIds as $instructorId) {
            $stmt->execute(['course_id' => $courseId, 'instructor_id' => $instructorId]);
        }
    }
}