<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Provides aggregate reporting queries for dashboards and exports.
 */
class ReportModel extends Model
{
    /**
     * Gathers key dashboard metrics in a single payload.
     */
    public function dashboardSummary(): array
    {
        $totals = $this->db->query('SELECT COUNT(*) AS students FROM students')->fetch();
        $lessons = $this->db->query('SELECT COUNT(*) AS upcoming FROM schedules WHERE scheduled_date >= CURDATE()')->fetch();
        $overdue = $this->db->query('SELECT COUNT(*) AS overdue FROM invoices WHERE due_date < CURDATE() AND status <> "paid"')->fetch();
        $fleet = $this->db->query('SELECT SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available, COUNT(*) AS total FROM vehicles')->fetch();
        return [
            'students' => (int) ($totals['students'] ?? 0),
            'upcoming_lessons' => (int) ($lessons['upcoming'] ?? 0),
            'overdue_invoices' => (int) ($overdue['overdue'] ?? 0),
            'fleet_available' => (int) ($fleet['available'] ?? 0),
            'fleet_total' => (int) ($fleet['total'] ?? 0),
        ];
    }

    /**
     * Summarises key counts for instructor dashboards.
     */
    public function instructorDashboardSummary(int $instructorId): array
    {
        $studentsStmt = $this->db->prepare('SELECT COUNT(DISTINCT e.student_id) AS total_students
                                             FROM schedules sch
                                             INNER JOIN enrollments e ON e.id = sch.enrollment_id
                                             WHERE sch.instructor_id = :instructor_id');
        $studentsStmt->execute(['instructor_id' => $instructorId]);
        $students = (int) ($studentsStmt->fetch()['total_students'] ?? 0);

        $lessonsStmt = $this->db->prepare('SELECT COUNT(*) AS upcoming
                                            FROM schedules
                                            WHERE instructor_id = :instructor_id AND scheduled_date >= CURDATE()');
        $lessonsStmt->execute(['instructor_id' => $instructorId]);
        $upcoming = (int) ($lessonsStmt->fetch()['upcoming'] ?? 0);

        $overdueStmt = $this->db->prepare('SELECT COUNT(*) AS overdue
                                            FROM invoices i
                                            INNER JOIN enrollments e ON e.id = i.enrollment_id
                                            WHERE i.due_date < CURDATE()
                                              AND i.status <> "paid"
                                              AND e.id IN (
                                                  SELECT DISTINCT sch.enrollment_id
                                                  FROM schedules sch
                                                  WHERE sch.instructor_id = :instructor_id
                                              )');
        $overdueStmt->execute(['instructor_id' => $instructorId]);
        $overdue = (int) ($overdueStmt->fetch()['overdue'] ?? 0);

        return [
            'students' => $students,
            'upcoming_lessons' => $upcoming,
            'overdue_invoices' => $overdue,
            'fleet_available' => null,
            'fleet_total' => null,
        ];
    }

    /**
     * Calculates total revenue attributed to an instructor's schedules.
     */
    public function instructorRevenue(int $instructorId): float
    {
        $stmt = $this->db->prepare('SELECT SUM(p.amount) AS total
                                     FROM payments p
                                     INNER JOIN invoices i ON i.id = p.invoice_id
                                     WHERE i.enrollment_id IN (
                                         SELECT DISTINCT sch.enrollment_id
                                         FROM schedules sch
                                         WHERE sch.instructor_id = :instructor_id
                                     )');
        $stmt->execute(['instructor_id' => $instructorId]);
        return (float) ($stmt->fetch()['total'] ?? 0.0);
    }

    /**
     * Summaries used for student dashboards.
     */
    public function studentDashboardSummary(int $studentId): array
    {
        $enrolmentsStmt = $this->db->prepare('SELECT COUNT(*) AS total FROM enrollments WHERE student_id = :student_id');
        $enrolmentsStmt->execute(['student_id' => $studentId]);
        $enrolments = (int) ($enrolmentsStmt->fetch()['total'] ?? 0);

        $upcomingStmt = $this->db->prepare('SELECT COUNT(*) AS upcoming
                                            FROM schedules sch
                                            INNER JOIN enrollments e ON e.id = sch.enrollment_id
                                            WHERE e.student_id = :student_id AND sch.scheduled_date >= CURDATE()');
        $upcomingStmt->execute(['student_id' => $studentId]);
        $upcoming = (int) ($upcomingStmt->fetch()['upcoming'] ?? 0);

        $overdueStmt = $this->db->prepare('SELECT COUNT(*) AS overdue
                                            FROM invoices i
                                            INNER JOIN enrollments e ON e.id = i.enrollment_id
                                            WHERE e.student_id = :student_id AND i.due_date < CURDATE() AND i.status <> "paid"');
        $overdueStmt->execute(['student_id' => $studentId]);
        $overdue = (int) ($overdueStmt->fetch()['overdue'] ?? 0);

        return [
            'students' => $enrolments,
            'upcoming_lessons' => $upcoming,
            'overdue_invoices' => $overdue,
            'fleet_available' => null,
            'fleet_total' => null,
        ];
    }

    /**
     * Summarises revenue by month for charting.
     */
    public function monthlyRevenue(int $months = 12): array
    {
        $months = max(1, $months);
        $start = new \DateTime('first day of this month');
        if ($months > 1) {
            $start->modify('-' . ($months - 1) . ' months');
        }

        $stmt = $this->db->prepare('SELECT DATE_FORMAT(payment_date, "%Y-%m") AS period, SUM(amount) AS total
                                     FROM payments
                                     WHERE payment_date >= :start
                                     GROUP BY period ORDER BY period');
        $stmt->execute(['start' => $start->format('Y-m-d')]);
        $fetched = $stmt->fetchAll();
        $totals = [];
        foreach ($fetched as $row) {
            $totals[$row['period']] = (float) ($row['total'] ?? 0);
        }

        $series = [];
        $cursor = clone $start;
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $series[] = [
                'period' => $key,
                'total' => (float) ($totals[$key] ?? 0),
            ];
            $cursor->modify('+1 month');
        }

        return $series;
    }

    /**
     * Computes revenue grouped by course for financial trends.
     */
    public function revenueByCourse(): array
    {
        $sql = 'SELECT c.title AS course_title, SUM(p.amount) AS total_revenue
                FROM payments p
                INNER JOIN invoices i ON i.id = p.invoice_id
                INNER JOIN enrollments e ON e.id = i.enrollment_id
                INNER JOIN courses c ON c.id = e.course_id
                GROUP BY c.id, c.title
                ORDER BY total_revenue DESC';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Returns enrolment retention statistics.
     */
    public function retentionStats(): array
    {
        $totals = $this->db->query('SELECT COUNT(*) AS total FROM enrollments')->fetch();
        $completed = $this->db->query('SELECT COUNT(*) AS completed FROM enrollments WHERE status = "completed"')->fetch();
        $active = $this->db->query('SELECT COUNT(*) AS active FROM enrollments WHERE status IN ("active", "in_progress")')->fetch();
        return [
            'total_enrollments' => (int) ($totals['total'] ?? 0),
            'completed' => (int) ($completed['completed'] ?? 0),
            'active' => (int) ($active['active'] ?? 0),
        ];
    }

    /**
     * Instructor performance indicators.
     */
    public function instructorPerformance(): array
    {
        $sql = 'SELECT i.id,
                       CONCAT(u.first_name, " ", u.last_name) AS instructor_name,
                       b.name AS branch_name,
                       SUM(CASE WHEN s.status = "completed" THEN 1 ELSE 0 END) AS completed_lessons,
                       COUNT(s.id) AS total_lessons
                FROM instructors i
                INNER JOIN users u ON u.id = i.user_id
                LEFT JOIN branches b ON b.id = i.branch_id
                LEFT JOIN schedules s ON s.instructor_id = i.id
                GROUP BY i.id, instructor_name, branch_name
                ORDER BY completed_lessons DESC';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Returns student progress breakdown for analytics cards.
     */
    public function studentProgressBreakdown(): array
    {
        $sql = 'SELECT progress_summary, COUNT(*) AS total FROM students GROUP BY progress_summary';
        return $this->db->query($sql)->fetchAll();
    }
}

