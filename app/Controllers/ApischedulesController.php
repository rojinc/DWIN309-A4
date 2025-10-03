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
        $this->reminders = new ReminderService();
        $this->audit = new AuditService();
    }

    public function eventsAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
        $calendar = $this->schedules->calendar($year, $month);
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
            $this->jsonError('CSRF validation failed.', 419);
            return;
        }
        $validation = Validation::make($payload, [
            'enrollment_id' => ['required'],
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
        try {
            $scheduleId = $this->schedules->create([
                'enrollment_id' => (int) $data['enrollment_id'],
                'instructor_id' => (int) $data['instructor_id'],
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
            $this->jsonError($exception->getMessage(), 409);
            return;
        }
        $this->queueReminder((int) $data['enrollment_id'], $scheduleId, $data);
        $this->audit->log($this->auth->user()['id'] ?? null, 'schedule_created', 'schedule', $scheduleId);
        $this->json(['message' => 'Schedule created successfully.', 'id' => $scheduleId], 201);
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
        $vehicleId = isset($payload['vehicle_id']) && $payload['vehicle_id'] !== '' ? (int) $payload['vehicle_id'] : null;
        $ignoreId = isset($payload['ignore_id']) ? (int) $payload['ignore_id'] : null;
        $conflict = $this->schedules->hasConflict((int) $data['instructor_id'], $data['scheduled_date'], $data['start_time'], $data['end_time'], $vehicleId, $ignoreId);
        $this->json(['conflict' => $conflict]);
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

    private function jsonError(string $message, int $status): void
    {
        $this->json(['error' => $message], $status);
    }
}