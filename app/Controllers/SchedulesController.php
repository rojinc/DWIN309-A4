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
use App\Models\CourseModel;
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
    private CourseModel $courses;
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
        $this->courses = new CourseModel();
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
        $role = $user['role'] ?? '';
        $canManage = in_array($role, ['admin', 'staff'], true);
        $canRate = $role === 'instructor';
        $instructorId = null;
        if ($canRate) {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0);
        }

        $this->render('schedules/index', [
            'pageTitle' => 'Scheduling Calendar',
            'year' => $year,
            'month' => $month,
            'canManage' => $canManage,
            'canRate' => $canRate,
            'instructorId' => $instructorId,
            'students' => $canManage ? $this->students->all() : [],
            'courses' => $canManage ? $this->courses->all() : [],
            'instructors' => $canManage ? $this->instructors->all() : [],
            'vehicles' => $canManage ? $this->vehicles->all() : [],
            'branches' => $canManage ? $this->branches->all() : [],
            'csrfAjaxToken' => $canManage ? Csrf::token('schedule_ajax') : null,
            'statusCsrfToken' => $canRate ? Csrf::token('schedule_status') : null,
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
            'students' => $this->students->all(),
            'courses' => $this->courses->all(),
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
            'student_id' => ['required'],
            'course_id' => ['required'],
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
        $studentId = (int) $data['student_id'];
        $courseId = (int) $data['course_id'];
        $instructorId = (int) $data['instructor_id'];

        try {
            $enrollmentId = $this->resolveEnrollmentId($studentId, $courseId);
            $scheduleId = $this->schedules->create([
                'enrollment_id' => $enrollmentId,
                'instructor_id' => $instructorId,
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

        $studentUserId = $this->resolveStudentUserId($enrollmentId);
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

    /**
     * Ensures a student-course pairing has an enrolment for scheduling.
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
}



