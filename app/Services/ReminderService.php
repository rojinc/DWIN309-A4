<?php
namespace App\Services;

use App\Models\ReminderModel;
use App\Models\UserModel;

/**
 * Coordinates reminder scheduling and delivery to in-app notifications and outbound channels.
 */
class ReminderService
{
    private ReminderModel $reminders;
    private NotificationService $notifications;
    private OutboundMessageService $outbound;
    private UserModel $users;

    public function __construct()
    {
        $this->reminders = new ReminderModel();
        $this->notifications = new NotificationService();
        $this->outbound = new OutboundMessageService();
        $this->users = new UserModel();
    }

    /**
     * Enqueues a reminder and optionally triggers immediate delivery.
     */
    public function queue(array $data): void
    {
        $reminderId = $this->reminders->create($data);
        if (($data['send_on'] ?? '') <= date('Y-m-d')) {
            $reminder = $this->reminders->find($reminderId);
            if ($reminder) {
                $this->dispatchReminder($reminder);
            }
        }
    }

    /**
     * Processes pending reminders and converts them to outbound messages.
     */
    public function processDueReminders(): void
    {
        $due = $this->reminders->due();
        foreach ($due as $item) {
            $this->dispatchReminder($item);
        }
    }

    /**
     * Sends the reminder through the configured channel with fallbacks.
     */
    private function dispatchReminder(array $reminder): void
    {
        if (!isset($reminder['id'])) {
            return;
        }

        $user = $this->users->find((int) ($reminder['recipient_user_id'] ?? 0));
        $channel = $reminder['channel'] ?? 'in-app';
        $title = 'Reminder: ' . ($reminder['reminder_type'] ?? 'Schedule');
        $message = $reminder['message'] ?? '';
        $delivered = false;

        if ($user) {
            if ($channel === 'email' && !empty($user['email'])) {
                $delivered = $this->outbound->sendEmail($user['email'], $title, $message);
            } elseif ($channel === 'sms' && !empty($user['phone'])) {
                $delivered = $this->outbound->sendSms($user['phone'], $message);
            } elseif ($channel === 'in-app') {
                $this->notifications->send((int) $user['id'], $title, $message);
                $delivered = true;
            }
        }

        if (!$delivered) {
            $this->notifications->send((int) ($reminder['recipient_user_id'] ?? 0), $title, $message, 'warning');
        }

        $this->reminders->setStatus((int) $reminder['id'], 'sent');
    }
}