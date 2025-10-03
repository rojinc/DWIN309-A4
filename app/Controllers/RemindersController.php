<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReminderModel;
use App\Services\ReminderService;

/**
 * Allows admins to review and process reminder queue entries.
 */
class RemindersController extends Controller
{
    private ReminderModel $reminders;
    private ReminderService $service;

    /**
     * Instantiates reminder data access and service layer.
     */
    public function __construct()
    {
        parent::__construct();
        $this->reminders = new ReminderModel();
        $this->service = new ReminderService();
    }

    /**
     * Displays pending and historical reminders.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('reminders/index', [
            'pageTitle' => 'Reminders',
            'reminders' => $this->reminders->all(),
        ]);
    }

    /**
     * Processes all due reminders on demand.
     */
    public function runAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->service->processDueReminders();
        $this->flash('success', 'Due reminders processed successfully.');
        $this->redirect(route('reminders', 'index'));
    }
}