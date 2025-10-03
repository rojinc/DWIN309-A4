<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\CommunicationModel;
use App\Models\UserModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;

/**
 * Facilitates outbound communications to users with audit history.
 */
class CommunicationsController extends Controller
{
    private CommunicationModel $communications;
    private UserModel $users;
    private AuditService $audit;
    private OutboundMessageService $outbound;
    private NotificationService $notifications;

    /**
     * Sets up communication dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        $this->communications = new CommunicationModel();
        $this->users = new UserModel();
        $this->audit = new AuditService();
        $this->outbound = new OutboundMessageService();
        $this->notifications = new NotificationService();
    }

    /**
     * Lists communication history.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('communications/index', [
            'pageTitle' => 'Communications',
            'communications' => $this->communications->all(),
        ]);
    }

    /**
     * Displays message composer.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('communication_create');
        $this->render('communications/create', [
            'pageTitle' => 'Send Message',
            'users' => $this->users->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Sends a message and stores it in communication history.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('communications', 'index'));
        }
        if (!Csrf::verify('communication_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('communications', 'create'));
        }

        $validation = Validation::make($_POST, [
            'channel' => ['required', 'in:email,sms,in-app'],
            'message' => ['required', 'min:5'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('communications', 'create'));
        }

        $recipientIds = isset($_POST['recipients']) ? array_map('intval', (array) $_POST['recipients']) : [];
        $recipientIds = array_values(array_filter($recipientIds));
        if (empty($recipientIds)) {
            $this->flash('error', 'Please choose at least one recipient.');
            $this->redirect(route('communications', 'create'));
        }

        $data = $validation['data'];
        $channel = $data['channel'];
        $subject = trim((string) post('subject')) ?: 'Message from Origin Driving School';
        $message = $data['message'];

        $communicationId = $this->communications->create([
            'sender_user_id' => $this->auth->user()['id'] ?? null,
            'audience_scope' => post('audience_scope', 'selected'),
            'channel' => $channel,
            'subject' => $subject,
            'message' => $message,
        ], $recipientIds);

        $result = $this->dispatchCommunication($channel, $subject, $message, $recipientIds);

        $this->audit->log($this->auth->user()['id'] ?? null, 'communication_sent', 'communication', $communicationId, 'Recipients: ' . implode(',', $recipientIds));
        $this->flash('success', sprintf('Message queued: %d delivered, %d fallback notifications.', $result['delivered'], $result['fallback']));
        $this->redirect(route('communications', 'index'));
    }

    /**
     * Pushes a communication through the selected channel.
     */
    private function dispatchCommunication(string $channel, string $subject, string $message, array $recipientIds): array
    {
        $delivered = 0;
        $fallback = 0;

        foreach ($recipientIds as $recipientId) {
            $recipient = $this->users->find($recipientId);
            if (!$recipient) {
                continue;
            }

            $primaryDelivered = false;

            switch ($channel) {
                case 'email':
                    $email = $recipient['email'] ?? '';
                    if ($email !== '') {
                        $primaryDelivered = $this->outbound->sendEmail($email, $subject, $message);
                    }
                    if (!$primaryDelivered) {
                        $this->notifications->send($recipientId, $subject, $message, 'warning');
                        $fallback++;
                    }
                    break;
                case 'sms':
                    $phone = $recipient['phone'] ?? '';
                    if ($phone !== '') {
                        $primaryDelivered = $this->outbound->sendSms($phone, $message);
                    }
                    if (!$primaryDelivered) {
                        $this->notifications->send($recipientId, $subject, $message, 'warning');
                        $fallback++;
                    }
                    break;
                default: // in-app
                    $this->notifications->send($recipientId, $subject, $message);
                    $primaryDelivered = true;
                    break;
            }

            if ($primaryDelivered) {
                $delivered++;
            }
        }

        return ['delivered' => $delivered, 'fallback' => $fallback];
    }
}
