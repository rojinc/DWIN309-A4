<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\BranchModel;
use App\Models\EnrollmentModel;
use App\Models\InstructorModel;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Models\VehicleModel;
use App\Services\AuditService;
use App\Services\ReminderService;

/**
 * JSON endpoints to support interactive scheduling calendar.
 */
class ApischedulesController extends Controller
{
    private ScheduleModel $schedules;
    private EnrollmentModel $enrollments;
    private InstructorModel $instructors;
    private StudentModel $students;
    private VehicleModel $vehicles;
    private BranchModel $branches;
    private ReminderService $reminders;
    private AuditService $audit;

    public function __construct()
    {
        parent::__construct();
        $this->schedules = new ScheduleModel();
        $this->enrollments = new EnrollmentModel();
        $this->instructors = new InstructorModel();
        $this->vehicles = new VehicleModel();
        $this->branches = new BranchModel();
        $this->students = new StudentModel();
        $this->reminders = new ReminderService();
        $this->audit = new AuditService();
    }

    public function eventsAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
        $user = $this->auth->user();
        $role = $user['role'] ?? '';
        $instructorId = null;
        $studentId = null;
        if ($role === 'instructor') {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0) ?: null;
        } elseif ($role === 'student') {
            $student = $this->students->findByUserId((int) ($user['id'] ?? 0));
            $studentId = (int) ($student['id'] ?? 0) ?: null;
        }

        $calendar = $this->schedules->calendar($year, $month, $instructorId, $studentId);
        $events = [];
        foreach ($calendar as $date => $items) {
            foreach ($items as $event) {
                $events[] = [
                    'id' => (int) $event['id'],
                    'title' => $event['lesson_topic'] ?: $event['student_name'],
                    'start' => $date . 'T' . substr($event['start_time'], 0, 5),
                    'end' => $date . 'T' . substr($event['end_time'], 0, 5),
                    'student' => $event['student_name'],
                    'instructor' => $event['instructor_name'],
                    'status' => $event['status'],
                    'course' => $event['course_title'],
                    'lesson_topic' => $event['lesson_topic'] ?? null,
                    'notes' => $event['notes'] ?? null,
                ];
            }
        }
        $this->json($events);
    }

    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Invalid request method.', 405);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $payload['csrf_token'] = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify('schedule_ajax', $payload['csrf_token'])) {
            $newToken = Csrf::token('schedule_ajax');
            $this->jsonError('CSRF validation failed.', 419, ['csrf_token' => $newToken]);
            return;
        }

        $newToken = Csrf::token('schedule_ajax');
        $validation = Validation::make($payload, [
            'student_id' => ['required'],
            'course_id' => ['required'],
            'instructor_id' => ['required'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'time'],
            'end_time' => ['required', 'time'],
        ]);
        if ($validation['errors']) {
            $this->jsonError(implode(' ', $validation['errors']), 422, ['csrf_token' => $newToken]);
            return;
        }

        $data = $validation['data'];
        $studentId = (int) $data['student_id'];
        $courseId = (int) $data['course_id'];
        $instructorId = (int) $data['instructor_id'];

        try {
            $enrollmentId = $this->resolveEnrollmentId($studentId, $courseId);
            $scheduleId = $this->schedules->create([
                'enrollment_id' => $enrollmentId,
                'instructor_id' => $instructorId,
                'vehicle_id' => !empty($payload['vehicle_id']) ? (int) $payload['vehicle_id'] : null,
                'branch_id' => !empty($payload['branch_id']) ? (int) $payload['branch_id'] : null,
                'event_type' => $payload['event_type'] ?? 'lesson',
                'scheduled_date' => $data['scheduled_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => $payload['status'] ?? 'scheduled',
                'lesson_topic' => $payload['lesson_topic'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'reminder_sent' => 0,
            ]);
        } catch (\RuntimeException $exception) {
            $this->jsonError($exception->getMessage(), 409, ['csrf_token' => $newToken]);
            return;
        }

        $this->queueReminder($enrollmentId, $scheduleId, $data);
        $this->audit->log($this->auth->user()['id'] ?? null, 'schedule_created', 'schedule', $scheduleId);
        $this->json([
            'message' => 'Schedule created successfully.',
            'id' => $scheduleId,
            'csrf_token' => $newToken
        ], 201);
    }

    public function checkConflictAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $payload = $_GET;
        $validation = Validation::make($payload, [
            'instructor_id' => ['required'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'time'],
            'end_time' => ['required', 'time'],
        ]);
        if ($validation['errors']) {
            $this->jsonError(implode(' ', $validation['errors']), 422);
            return;
        }

        $data = $validation['data'];
        $instructorId = (int) $data['instructor_id'];
        $vehicleId = isset($payload['vehicle_id']) && $payload['vehicle_id'] !== '' ? (int) $payload['vehicle_id'] : null;
        $ignoreId = isset($payload['ignore_id']) ? (int) $payload['ignore_id'] : null;
        $studentId = isset($payload['student_id']) && $payload['student_id'] !== '' ? (int) $payload['student_id'] : 0;

        $conflict = $this->schedules->hasConflict(
            $instructorId,
            $data['scheduled_date'],
            $data['start_time'],
            $data['end_time'],
            $vehicleId,
            $ignoreId
        );
        $unavailable = $this->schedules->hasInstructorUnavailability(
            $instructorId,
            $data['scheduled_date'],
            $data['start_time'],
            $data['end_time']
        );
        $studentOverlap = $studentId > 0
            ? $this->schedules->hasStudentOverlap($studentId, $data['scheduled_date'], $data['start_time'], $data['end_time'], $ignoreId)
            : false;

        $this->json([
            'conflict' => $conflict || $unavailable || $studentOverlap,
            'reason' => $unavailable
                ? 'Instructor is unavailable for the selected time.'
                : ($conflict
                    ? 'Instructor or vehicle already booked.'
                    : ($studentOverlap ? 'Student already has a booking in this time window.' : null)),
        ]);
    }

    public function updateStatusAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Invalid request method.', 405);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Csrf::verify('schedule_status', $payload['csrf_token'] ?? null)) {
            $token = Csrf::token('schedule_status');
            $this->jsonError('CSRF validation failed.', 419, ['csrf_token' => $token]);
            return;
        }

        $token = Csrf::token('schedule_status');

        $scheduleId = isset($payload['schedule_id']) ? (int) $payload['schedule_id'] : 0;
        $status = (string) ($payload['status'] ?? '');
        $ratingRaw = $payload['student_rating'] ?? null;
        $feedback = trim((string) ($payload['student_feedback'] ?? ''));

        $user = $this->auth->user();
        $role = $user['role'] ?? '';
        $allowedStatuses = ['completed', 'not_completed'];
        if (in_array($role, ['admin', 'staff'], true)) {
            $allowedStatuses[] = 'cancelled';
        }

        if ($scheduleId <= 0) {
            $this->jsonError('Invalid schedule reference.', 422, ['csrf_token' => $token]);
            return;
        }
        if (!in_array($status, $allowedStatuses, true)) {
            $this->jsonError('Unsupported lesson status selected.', 422, ['csrf_token' => $token]);
            return;
        }

        $rating = null;
        if ($ratingRaw !== null && $ratingRaw !== '') {
            if (!is_numeric($ratingRaw)) {
                $this->jsonError('Rating must be a number between 0 and 10.', 422, ['csrf_token' => $token]);
                return;
            }
            $rating = max(0, min(10, (int) $ratingRaw));
        }

        if (strlen($feedback) > 600) {
            $feedback = substr($feedback, 0, 600);
        }

        $schedule = $this->schedules->find($scheduleId);
        if (!$schedule) {
            $this->jsonError('Schedule entry not found.', 404, ['csrf_token' => $token]);
            return;
        }

        if ($role === 'instructor') {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0);
            if ($instructorId <= 0 || (int) $schedule['instructor_id'] !== $instructorId) {
                $this->jsonError('You are not authorised to update this lesson.', 403, ['csrf_token' => $token]);
                return;
            }
        }

        $success = $this->schedules->setOutcome($scheduleId, [
            'status' => $status,
            'student_rating' => $rating,
            'student_feedback' => $feedback !== '' ? $feedback : null,
            'completion_marked_by' => $user['id'] ?? null,
        ]);

        if (!$success) {
            $this->jsonError('Unable to update lesson outcome.', 500, ['csrf_token' => $token]);
            return;
        }

        $this->audit->log($user['id'] ?? null, 'schedule_status_updated', 'schedule', $scheduleId);

        $this->json([
            'message' => 'Lesson updated successfully.',
            'csrf_token' => $token,
        ]);
    }

    /**
     * Ensures a student-course pairing has an enrolment ready for scheduling.
     */
    private function resolveEnrollmentId(int $studentId, int $courseId): int
    {
        $enrollment = $this->enrollments->findByStudentAndCourse($studentId, $courseId);
        if ($enrollment) {
            return (int) $enrollment['id'];
        }

        return $this->enrollments->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'start_date' => date('Y-m-d'),
            'status' => 'active',
            'progress_percentage' => 0,
            'notes' => 'Auto-created from quick booking',
        ]);
    }

    private function queueReminder(int $enrollmentId, int $scheduleId, array $data): void
    {
        $studentUserId = $this->resolveStudentUserId($enrollmentId);
        if (!$studentUserId) {
            return;
        }

        $this->reminders->queue([
            'related_type' => 'schedule',
            'related_id' => $scheduleId,
            'recipient_user_id' => $studentUserId,
            'channel' => 'sms',
            'reminder_type' => 'Upcoming Lesson',
            'message' => 'Lesson booked for ' . date('d M Y', strtotime($data['scheduled_date'])) . ' at ' . $data['start_time'],
            'send_on' => date('Y-m-d', strtotime($data['scheduled_date'] . ' -1 day')),
            'status' => 'pending',
        ]);
    }

    private function resolveStudentUserId(int $enrollmentId): int
    {
        $enrollment = $this->enrollments->find($enrollmentId);
        if (!$enrollment) {
            return 0;
        }

        $studentModel = new StudentModel();
        $student = $studentModel->find((int) $enrollment['student_id']);
        return (int) ($student['user_id'] ?? 0);
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function jsonError(string $message, int $status, array $extra = []): void
    {
        $this->json(array_merge(['error' => $message], $extra), $status);
    }
}

