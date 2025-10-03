<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReportModel;
use App\Models\ScheduleModel;
use App\Models\InvoiceModel;
use App\Models\NotificationModel;
use App\Services\ReminderService;

/**
 * Renders the administration dashboard with KPIs and activity feeds.
 */
class DashboardController extends Controller
{
    private ReportModel $reports;
    private ScheduleModel $schedules;
    private InvoiceModel $invoices;
    private NotificationModel $notifications;
    private ReminderService $reminders;

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
        $this->reminders = new ReminderService();
    }

    /**
     * Displays the dashboard view with aggregated statistics.
     */
    public function indexAction(): void
    {
        $this->requireAuth();
        $this->reminders->processDueReminders();
        $user = $this->auth->user();
        $this->render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'summary' => $this->reports->dashboardSummary(),
            'upcomingSchedules' => $this->schedules->upcoming(),
            'recentInvoices' => array_slice($this->invoices->all(), 0, 5),
            'notifications' => $this->notifications->forUser((int) $user['id']),
            'revenueSeries' => $this->reports->monthlyRevenue(),
        ]);
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
}