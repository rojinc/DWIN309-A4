<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\NotificationModel;

/**
 * Assertion helper for notification DAO tests.
 */
function notifAssert(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$notifications = new NotificationModel();
$userNotes = $notifications->forUser(4);
notifAssert(is_array($userNotes), 'Notification list retrieved for user.');

if (!empty($userNotes)) {
    $firstId = (int) $userNotes[0]['id'];
    $notifications->markRead($firstId);
    $updated = $notifications->forUser(4);
    $match = array_filter($updated, fn($note) => (int)$note['id'] === $firstId);
    $first = array_shift($match);
    notifAssert(isset($first['is_read']) && (int) $first['is_read'] === 1, 'Notification mark as read persists.');
}

echo $GLOBALS['__failures'] ? "Notification tests completed with failures.\n" : "Notification tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);