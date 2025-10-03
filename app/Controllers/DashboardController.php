<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReportModel;
use App\Models\ScheduleModel;
use App\Models\InvoiceModel;
use App\Models\NotificationModel;
use App\Models\InstructorModel;
use App\Models\StudentModel;
use App\Services\ReminderService;
use App\Models\EnrollmentRequestModel;

/**
 * Renders the administration dashboard with KPIs and activity feeds.
 */
class DashboardController extends Controller
{
    private ReportModel $reports;
    private ScheduleModel $schedules;
    private InvoiceModel $invoices;
    private NotificationModel $notifications;
    private InstructorModel $instructors;
    private StudentModel $students;
    private ReminderService $reminders;
    private EnrollmentRequestModel $enrollmentRequests;

    /**
     * Wires up DAO and service dependencies for dashboard data.
     */
    public function __construct()
    {
        parent::__construct();
        $this->reports = new ReportModel();
        $this->schedules = new ScheduleModel();
        $this->invoices = new InvoiceModel();
        $this->notifications = new NotificationModel();
        $this->instructors = new InstructorModel();
        $this->students = new StudentModel();
        $this->reminders = new ReminderService();
        $this->enrollmentRequests = new EnrollmentRequestModel();
    }

    /**
     * Displays the dashboard view with aggregated statistics.
     */
    public function indexAction(): void
    {
        $this->requireAuth();
        $this->reminders->processDueReminders();
        $user = $this->auth->user();
        $role = $user['role'] ?? 'guest';

        $viewData = [
            'pageTitle' => 'Dashboard',
            'role' => $role,
            'notifications' => $this->notifications->forUser((int) ($user['id'] ?? 0)),
        ];

        switch ($role) {
            case 'admin':
            case 'staff':
                $viewData['summary'] = $this->reports->dashboardSummary();
                $viewData['upcomingSchedules'] = $this->schedules->upcoming();
                $viewData['recentInvoices'] = array_slice($this->invoices->all(), 0, 5);
                $viewData['revenueSeries'] = $this->reports->monthlyRevenue();
                $viewData['showRevenueChart'] = true;
                break;
            case 'instructor':
                $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
                $instructorId = (int) ($instructor['id'] ?? 0);
                $viewData['summary'] = $this->reports->instructorDashboardSummary($instructorId);
                $viewData['upcomingSchedules'] = $this->schedules->upcoming(20, $instructorId);
                $viewData['recentInvoices'] = $this->invoices->forInstructor($instructorId, 5);
                $viewData['revenueSeries'] = [];
                $viewData['showRevenueChart'] = false;
                $viewData['instructorRevenue'] = $this->reports->instructorRevenue($instructorId);
                $viewData['assignedStudents'] = $instructorId > 0
                    ? $this->students->forInstructor($instructorId)
                    : [];
                $viewData['requestHistory'] = $instructorId > 0
                    ? array_slice($this->enrollmentRequests->forInstructor($instructorId), 0, 5)
                    : [];
                break;
            case 'student':
                $student = $this->students->findByUserId((int) ($user['id'] ?? 0));
                $studentId = (int) ($student['id'] ?? 0);
                $viewData['summary'] = $this->reports->studentDashboardSummary($studentId);
                $viewData['upcomingSchedules'] = $this->schedules->upcoming(20, null, $studentId);
                $viewData['recentInvoices'] = $this->invoices->forStudent($studentId, 5);
                $viewData['revenueSeries'] = [];
                $viewData['showRevenueChart'] = false;
                $viewData['assignedInstructor'] = $this->resolveAssignedInstructor($studentId);
                $viewData['studentProfile'] = $student ?: [];
                $viewData['enrollmentRequests'] = $this->enrollmentRequests->forStudent($studentId);
                break;
            default:
                $viewData['summary'] = $this->reports->dashboardSummary();
                $viewData['upcomingSchedules'] = $this->schedules->upcoming();
                $viewData['recentInvoices'] = array_slice($this->invoices->all(), 0, 5);
                $viewData['revenueSeries'] = $this->reports->monthlyRevenue();
                $viewData['showRevenueChart'] = true;
                break;
        }

        $this->render('dashboard/index', $viewData);
    }

    /**
     * Handles forbidden access attempts gracefully.
     */
    public function forbiddenAction(): void
    {
        $this->render('partials/forbidden', [
            'pageTitle' => 'Access Denied'
        ]);
    }

    /**
     * Determines the primary instructor information for a student dashboard.
     */
    private function resolveAssignedInstructor(int $studentId): ?array
    {
        if ($studentId <= 0) {
            return null;
        }
        $upcoming = $this->schedules->upcoming(1, null, $studentId);
        if (!empty($upcoming)) {
            return [
                'name' => $upcoming[0]['instructor_name'] ?? null,
                'course' => $upcoming[0]['course_title'] ?? null,
            ];
        }
        $history = $this->schedules->forStudent($studentId);
        if (!empty($history)) {
            return [
                'name' => $history[0]['instructor_name'] ?? null,
                'course' => $history[0]['course_title'] ?? null,
            ];
        }
        return null;
    }
}



