<?php
namespace App\Services;

use App\Models\NotificationModel;

/**
 * High-level helper for creating user notifications.
 */
class NotificationService
{
    private NotificationModel $notifications;

    /**
     * Instantiates the underlying notification DAO.
     */
    public function __construct()
    {
        $this->notifications = new NotificationModel();
    }

    /**
     * Dispatches a notification to a single user.
     */
    public function send(int $userId, string $title, string $message, string $level = 'info'): void
    {
        $this->notifications->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'level' => $level,
        ]);
    }
}