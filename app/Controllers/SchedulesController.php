<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\ScheduleModel;
use App\Models\EnrollmentModel;
use App\Models\InstructorModel;
use App\Models\VehicleModel;
use App\Models\BranchModel;
use App\Models\StudentModel;
use App\Services\ReminderService;
use App\Services\AuditService;

/**
 * Covers lesson and exam scheduling workflows including calendar display.
 */
class SchedulesController extends Controller
{
    private ScheduleModel $schedules;
    private EnrollmentModel $enrollments;
    private InstructorModel $instructors;
    private VehicleModel $vehicles;
    private BranchModel $branches;
    private StudentModel $students;
    private ReminderService $reminders;
    private AuditService $audit;

    /**
     * Prepares schedule controller dependencies.
     */
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

    /**
     * Shows the scheduling calendar for a selected month.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
        $user = $this->auth->user();
        $canManage = in_array($user['role'], ['admin', 'staff'], true);

        $this->render('schedules/index', [
            'pageTitle' => 'Scheduling Calendar',
            'year' => $year,
            'month' => $month,
            'canManage' => $canManage,
            'enrollments' => $canManage ? $this->enrollments->all() : [],
            'instructors' => $canManage ? $this->instructors->all() : [],
            'vehicles' => $canManage ? $this->vehicles->all() : [],
            'branches' => $canManage ? $this->branches->all() : [],
            'csrfAjaxToken' => $canManage ? Csrf::token('schedule_ajax') : null,
        ]);
    }

    /**
     * Displays the create schedule form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('schedule_create');
        $this->render('schedules/create', [
            'pageTitle' => 'Book Lesson',
            'enrollments' => $this->enrollments->all(),
            'instructors' => $this->instructors->all(),
            'vehicles' => $this->vehicles->all(),
            'branches' => $this->branches->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Stores a schedule entry with conflict detection and reminder queueing.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('schedules', 'index'));
        }
        if (!Csrf::verify('schedule_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('schedules', 'create'));
        }

        $validation = Validation::make($_POST, [
            'enrollment_id' => ['required'],
            'instructor_id' => ['required'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'time'],
            'end_time' => ['required', 'time'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('schedules', 'create'));
        }

        $data = $validation['data'];
        try {
            $scheduleId = $this->schedules->create([
                'enrollment_id' => (int) $data['enrollment_id'],
                'instructor_id' => (int) $data['instructor_id'],
                'vehicle_id' => post('vehicle_id') ? (int) post('vehicle_id') : null,
                'branch_id' => post('branch_id') ? (int) post('branch_id') : null,
                'event_type' => post('event_type', 'lesson'),
                'scheduled_date' => $data['scheduled_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => post('status', 'scheduled'),
                'lesson_topic' => post('lesson_topic'),
                'notes' => post('notes'),
                'reminder_sent' => 0,
            ]);
        } catch (\RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect(route('schedules', 'create'));
        }

        $studentUserId = $this->resolveStudentUserId((int) $data['enrollment_id']);
        if ($studentUserId) {
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

        $this->audit->log($this->auth->user()['id'] ?? null, 'schedule_created', 'schedule', $scheduleId);
        $this->flash('success', 'Lesson scheduled successfully.');
        $this->redirect(route('schedules', 'index'));
    }

    /**
     * Helper to find the student user id for reminder delivery.
     */
    private function resolveStudentUserId(int $enrollmentId): int
    {
        $enrollment = $this->enrollments->find($enrollmentId);
        if (!$enrollment) {
            return 0;
        }

        $student = $this->students->find((int) ($enrollment['student_id'] ?? 0));
        return (int) ($student['user_id'] ?? 0);
    }
}
