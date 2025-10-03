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
    private ScheduleModel ;
    private EnrollmentModel ;
    private InstructorModel ;
    private VehicleModel ;
    private BranchModel ;
    private ReminderService ;
    private AuditService ;

    /**
     * Prepares schedule controller dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        ->schedules = new ScheduleModel();
        ->enrollments = new EnrollmentModel();
        ->instructors = new InstructorModel();
        ->vehicles = new VehicleModel();
        ->branches = new BranchModel();
        ->reminders = new ReminderService();
        ->audit = new AuditService();
    }

    /**
     * Shows the scheduling calendar for a selected month.
     */
    public function indexAction(): void
    {
        ->requireRole(['admin', 'staff', 'instructor']);
         = isset(['year']) ? (int) ['year'] : (int) date('Y');
         = isset(['month']) ? (int) ['month'] : (int) date('n');
         = ->auth->user();
         = in_array(['role'], ['admin', 'staff'], true);

        ->render('schedules/index', [
            'pageTitle' => 'Scheduling Calendar',
            'year' => ,
            'month' => ,
            'canManage' => ,
            'enrollments' =>  ? ->enrollments->all() : [],
            'instructors' =>  ? ->instructors->all() : [],
            'vehicles' =>  ? ->vehicles->all() : [],
            'branches' =>  ? ->branches->all() : [],
            'csrfAjaxToken' =>  ? Csrf::token('schedule_ajax') : null,
        ]);
    }

    /**
     * Displays the create schedule form.
     */
    public function createAction(): void
    {
        ->requireRole(['admin', 'staff']);
         = Csrf::token('schedule_create');
        ->render('schedules/create', [
            'pageTitle' => 'Book Lesson',
            'enrollments' => ->enrollments->all(),
            'instructors' => ->instructors->all(),
            'vehicles' => ->vehicles->all(),
            'branches' => ->branches->all(),
            'csrfToken' => ,
        ]);
    }

    /**
     * Stores a schedule entry with conflict detection and reminder queueing.
     */
    public function storeAction(): void
    {
        ->requireRole(['admin', 'staff']);
        if (['REQUEST_METHOD'] !== 'POST') {
            ->redirect(route('schedules', 'index'));
        }
        if (!Csrf::verify('schedule_create', post('csrf_token'))) {
            ->flash('error', 'Security token mismatch.');
            ->redirect(route('schedules', 'create'));
        }
         = Validation::make(, [
            'enrollment_id' => ['required'],
            'instructor_id' => ['required'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'time'],
            'end_time' => ['required', 'time'],
        ]);
        if (['errors']) {
            ->flash('error', implode(' ', ['errors']));
            ->redirect(route('schedules', 'create'));
        }
         = ['data'];
        try {
             = ->schedules->create([
                'enrollment_id' => (int) ['enrollment_id'],
                'instructor_id' => (int) ['instructor_id'],
                'vehicle_id' => post('vehicle_id') ? (int) post('vehicle_id') : null,
                'branch_id' => post('branch_id') ? (int) post('branch_id') : null,
                'event_type' => post('event_type', 'lesson'),
                'scheduled_date' => ['scheduled_date'],
                'start_time' => ['start_time'],
                'end_time' => ['end_time'],
                'status' => post('status', 'scheduled'),
                'lesson_topic' => post('lesson_topic'),
                'notes' => post('notes'),
                'reminder_sent' => 0,
            ]);
        } catch (\RuntimeException ) {
            ->flash('error', ->getMessage());
            ->redirect(route('schedules', 'create'));
        }

         = ->resolveStudentUserId((int) ['enrollment_id']);
        if () {
            ->reminders->queue([
                'related_type' => 'schedule',
                'related_id' => ,
                'recipient_user_id' => ,
                'channel' => 'sms',
                'reminder_type' => 'Upcoming Lesson',
                'message' => 'Lesson booked for ' . date('d M Y', strtotime(['scheduled_date'])) . ' at ' . ['start_time'],
                'send_on' => date('Y-m-d', strtotime(['scheduled_date'] . ' -1 day')),
                'status' => 'pending',
            ]);
        }
        ->audit->log(->auth->user()['id'] ?? null, 'schedule_created', 'schedule', );
        ->flash('success', 'Lesson scheduled successfully.');
        ->redirect(route('schedules', 'index'));
    }

    /**
     * Helper to find the student user id for reminder delivery.
     */
    private function resolveStudentUserId(int ): int
    {
         = ->enrollments->find();
        if (!) {
            return 0;
        }
         = new StudentModel();
         = ->find((int) ['student_id']);
        return (int) (['user_id'] ?? 0);
    }
}