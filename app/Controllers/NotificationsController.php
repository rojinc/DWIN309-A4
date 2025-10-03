<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificationModel;

/**
 * Manages in-app notification list and read state.
 */
class NotificationsController extends Controller
{
    private NotificationModel $notifications;

    /**
     * Instantiates the notifications DAO.
     */
    public function __construct()
    {
        parent::__construct();
        $this->notifications = new NotificationModel();
    }

    /**
     * Displays notifications for the logged-in user.
     */
    public function indexAction(): void
    {
        $this->requireAuth();
        $user = $this->auth->user();
        $this->render('notifications/index', [
            'pageTitle' => 'Notifications',
            'notifications' => $this->notifications->forUser((int) $user['id']),
        ]);
    }

    /**
     * Marks a notification as read.
     */
    public function readAction(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $this->notifications->markRead($id);
        $this->redirect(route('notifications', 'index'));
    }
}