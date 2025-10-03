<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Manages lesson and exam scheduling records including conflict detection.
 */
class ScheduleModel extends Model
{
    /**
     * Returns all upcoming schedule entries with contextual information.
     */
    public function upcoming(int $limit = 20, ?int $instructorId = null, ?int $studentId = null): array
    {
        $sql = 'SELECT sch.*, c.title AS course_title, CONCAT(us.first_name, " ", us.last_name) AS student_name,
                       CONCAT(ui.first_name, " ", ui.last_name) AS instructor_name, v.name AS vehicle_name
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                INNER JOIN students st ON st.id = e.student_id
                INNER JOIN users us ON us.id = st.user_id
                INNER JOIN courses c ON c.id = e.course_id
                INNER JOIN instructors ins ON ins.id = sch.instructor_id
                INNER JOIN users ui ON ui.id = ins.user_id
                LEFT JOIN vehicles v ON v.id = sch.vehicle_id
                WHERE sch.scheduled_date >= CURDATE()';
        $params = [];
        if ($instructorId !== null) {
            $sql .= ' AND sch.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }
        if ($studentId !== null) {
            $sql .= ' AND e.student_id = :student_id';
            $params['student_id'] = $studentId;
        }
        $sql .= ' ORDER BY sch.scheduled_date, sch.start_time LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    /**
     * Retrieves schedule entries for a particular instructor.
     */
    public function forInstructor(int $instructorId): array
    {
        $sql = 'SELECT sch.*, CONCAT(us.first_name, " ", us.last_name) AS student_name
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                INNER JOIN students st ON st.id = e.student_id
                INNER JOIN users us ON us.id = st.user_id
                INNER JOIN courses c ON c.id = e.course_id
                WHERE sch.instructor_id = :instructor_id
                ORDER BY sch.scheduled_date DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['instructor_id' => $instructorId]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves schedule entries for a specific student through enrolments.
     */
    public function forStudent(int $studentId): array
    {
        $sql = 'SELECT sch.*, CONCAT(ui.first_name, " ", ui.last_name) AS instructor_name
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                INNER JOIN instructors ins ON ins.id = sch.instructor_id
                INNER JOIN users ui ON ui.id = ins.user_id
                WHERE e.student_id = :student_id
                ORDER BY sch.scheduled_date DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Loads a schedule record with joined entities.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM schedules WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Creates a schedule record after ensuring there are no conflicts.
     */
    public function create(array $data): int
    {
        $studentId = $this->resolveStudentId((int) ($data['enrollment_id'] ?? 0));
        if ($this->hasUnavailabilityConflict((int) $data['instructor_id'], $data['scheduled_date'], $data['start_time'], $data['end_time'])) {
            throw new \RuntimeException('Instructor is unavailable for the selected time.');
        }
        if ($this->hasBookingConflict((int) $data['instructor_id'], $studentId, $data['scheduled_date'], $data['start_time'], $data['end_time'], $data['vehicle_id'] ?? null, null)) {
            throw new \RuntimeException('Schedule conflict detected.');
        }

        $sql = 'INSERT INTO schedules (enrollment_id, instructor_id, vehicle_id, branch_id, event_type, scheduled_date, start_time, end_time, status, lesson_topic, notes, student_rating, student_feedback, completion_marked_by, completion_marked_at, reminder_sent, created_at, updated_at)
                VALUES (:enrollment_id, :instructor_id, :vehicle_id, :branch_id, :event_type, :scheduled_date, :start_time, :end_time, :status, :lesson_topic, :notes, :student_rating, :student_feedback, :completion_marked_by, :completion_marked_at, :reminder_sent, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'enrollment_id' => $data['enrollment_id'],
            'instructor_id' => $data['instructor_id'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'event_type' => $data['event_type'] ?? 'lesson',
            'scheduled_date' => $data['scheduled_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'] ?? 'scheduled',
            'lesson_topic' => $data['lesson_topic'] ?? null,
            'notes' => $data['notes'] ?? null,
            'student_rating' => $data['student_rating'] ?? null,
            'student_feedback' => $data['student_feedback'] ?? null,
            'completion_marked_by' => $data['completion_marked_by'] ?? null,
            'completion_marked_at' => $data['completion_marked_at'] ?? null,
            'reminder_sent' => $data['reminder_sent'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates a schedule entry while enforcing conflict checks.
     */
    public function update(int $id, array $data): bool
    {
        $studentId = $this->resolveStudentId((int) ($data['enrollment_id'] ?? 0));
        if ($this->hasUnavailabilityConflict((int) $data['instructor_id'], $data['scheduled_date'], $data['start_time'], $data['end_time'])) {
            throw new \RuntimeException('Instructor is unavailable for the selected time.');
        }
        if ($this->hasBookingConflict((int) $data['instructor_id'], $studentId, $data['scheduled_date'], $data['start_time'], $data['end_time'], $data['vehicle_id'] ?? null, $id)) {
            throw new \RuntimeException('Schedule conflict detected.');
        }

        $sql = 'UPDATE schedules
                SET enrollment_id = :enrollment_id,
                    instructor_id = :instructor_id,
                    vehicle_id = :vehicle_id,
                    branch_id = :branch_id,
                    event_type = :event_type,
                    scheduled_date = :scheduled_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    status = :status,
                    lesson_topic = :lesson_topic,
                    notes = :notes,
                    student_rating = :student_rating,
                    student_feedback = :student_feedback,
                    completion_marked_by = :completion_marked_by,
                    completion_marked_at = :completion_marked_at,
                    reminder_sent = :reminder_sent,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'enrollment_id' => $data['enrollment_id'],
            'instructor_id' => $data['instructor_id'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'event_type' => $data['event_type'] ?? 'lesson',
            'scheduled_date' => $data['scheduled_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'] ?? 'scheduled',
            'lesson_topic' => $data['lesson_topic'] ?? null,
            'notes' => $data['notes'] ?? null,
            'student_rating' => $data['student_rating'] ?? null,
            'student_feedback' => $data['student_feedback'] ?? null,
            'completion_marked_by' => $data['completion_marked_by'] ?? null,
            'completion_marked_at' => $data['completion_marked_at'] ?? null,
            'reminder_sent' => $data['reminder_sent'] ?? 0,
            'id' => $id,
        ]);
    }

    /**
     * Marks a schedule entry as having sent reminders.
     */
    public function markReminderSent(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE schedules SET reminder_sent = 1, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Builds a calendar grid for a given month.
     */
    public function calendar(int $year, int $month, ?int $instructorId = null, ?int $studentId = null): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $sql = 'SELECT sch.*, c.title AS course_title, CONCAT(us.first_name, " ", us.last_name) AS student_name, CONCAT(ui.first_name, " ", ui.last_name) AS instructor_name
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                INNER JOIN students st ON st.id = e.student_id
                INNER JOIN users us ON us.id = st.user_id
                INNER JOIN courses c ON c.id = e.course_id
                INNER JOIN instructors ins ON ins.id = sch.instructor_id
                INNER JOIN users ui ON ui.id = ins.user_id
                WHERE sch.scheduled_date BETWEEN :start AND :end';
        $params = ['start' => $start, 'end' => $end];
        if ($instructorId !== null) {
            $sql .= ' AND sch.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }
        if ($studentId !== null) {
            $sql .= ' AND e.student_id = :student_id';
            $params['student_id'] = $studentId;
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, in_array($key, ['start', 'end'], true) ? PDO::PARAM_STR : PDO::PARAM_INT);
        }
        $stmt->execute();
        $items = $stmt->fetchAll();
        $calendar = [];
        foreach ($items as $item) {
            $calendar[$item['scheduled_date']][] = $item;
        }
        return $calendar;
    }
    /**
     * Checks whether a proposed schedule conflicts with existing instructor or vehicle bookings.
     */
    public function hasConflict(int $instructorId, string $date, string $startTime, string $endTime, ?int $vehicleId, ?int $ignoreId): bool
    {
        $params = [
            'scheduled_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'instructor_id' => $instructorId,
        ];
        $sql = 'SELECT COUNT(*) AS total FROM schedules WHERE scheduled_date = :scheduled_date AND (:start_time < end_time AND :end_time > start_time) AND (';
        $sql .= 'instructor_id = :instructor_id';
        if ($vehicleId) {
            $sql .= ' OR vehicle_id = :vehicle_id';
            $params['vehicle_id'] = $vehicleId;
        }
        $sql .= ')';
        if ($ignoreId) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }


    /**
     * Exposes instructor unavailability check for API consumers.
     */
    public function hasInstructorUnavailability(int $instructorId, string $date, string $startTime, string $endTime): bool
    {
        return $this->hasUnavailabilityConflict($instructorId, $date, $startTime, $endTime);
    }

    /**
     * Detects overlapping bookings for a student during a proposed window.
     */
    public function hasStudentOverlap(int $studentId, string $date, string $startTime, string $endTime, ?int $ignoreId): bool
    {
        if ($studentId <= 0) {
            return false;
        }

        $params = [
            'student_id' => $studentId,
            'scheduled_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $sql = 'SELECT COUNT(*) AS total
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                WHERE e.student_id = :student_id
                  AND sch.scheduled_date = :scheduled_date
                  AND (:start_time < sch.end_time AND :end_time > sch.start_time)';
        if ($ignoreId) {
            $sql .= ' AND sch.id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }


    /**
     * Applies status, rating, and feedback updates for a scheduled lesson.
     */
    public function setOutcome(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE schedules
                SET status = :status,
                    student_rating = :student_rating,
                    student_feedback = :student_feedback,
                    completion_marked_by = :completion_marked_by,
                    completion_marked_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id');
        return $stmt->execute([
            'status' => $data['status'],
            'student_rating' => $data['student_rating'],
            'student_feedback' => $data['student_feedback'],
            'completion_marked_by' => $data['completion_marked_by'],
            'id' => $id,
        ]);
    }


    /**
     * Checks whether an instructor has any schedules with the specified student.
     */
    public function instructorHasStudent(int $instructorId, int $studentId): bool
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                WHERE sch.instructor_id = :instructor_id AND e.student_id = :student_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'instructor_id' => $instructorId,
            'student_id' => $studentId,
        ]);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }

    /**
     * Ensures enrolment lookups can resolve the owning student for conflict checks.
     */
    private function resolveStudentId(int $enrollmentId): int
    {
        if ($enrollmentId <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare('SELECT student_id FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $enrollmentId]);
        $row = $stmt->fetch();
        return (int) ($row['student_id'] ?? 0);
    }

    /**
     * Validates that the instructor is available for the proposed lesson window.
     */
    private function hasUnavailabilityConflict(int $instructorId, string $date, string $startTime, string $endTime): bool
    {
        if ($instructorId <= 0) {
            return false;
        }
        $startDateTime = $date . ' ' . $startTime;
        $endDateTime = $date . ' ' . $endTime;
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total
                                     FROM instructor_unavailability
                                     WHERE instructor_id = :instructor_id
                                       AND :start_dt < end_datetime
                                       AND :end_dt > start_datetime');
        $stmt->execute([
            'instructor_id' => $instructorId,
            'start_dt' => $startDateTime,
            'end_dt' => $endDateTime,
        ]);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }

    /**
     * Aggregates instructor, vehicle, and student overlap detection for schedule creation/update.
     */
    private function hasBookingConflict(int $instructorId, int $studentId, string $date, string $startTime, string $endTime, ?int $vehicleId, ?int $ignoreId): bool
    {
        if ($this->hasConflict($instructorId, $date, $startTime, $endTime, $vehicleId, $ignoreId)) {
            return true;
        }

        if ($studentId <= 0) {
            return false;
        }

        $params = [
            'student_id' => $studentId,
            'scheduled_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $sql = 'SELECT COUNT(*) AS total
                FROM schedules sch
                INNER JOIN enrollments e ON e.id = sch.enrollment_id
                WHERE e.student_id = :student_id
                  AND sch.scheduled_date = :scheduled_date
                  AND (:start_time < sch.end_time AND :end_time > sch.start_time)';
        if ($ignoreId) {
            $sql .= ' AND sch.id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }

}












