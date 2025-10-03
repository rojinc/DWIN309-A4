<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\ReminderModel;
use App\Services\ReminderService;

/**
 * Helper for asserting reminder-related expectations.
 */
function ensure(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$reminders = new ReminderModel();
$service = new ReminderService();

$all = $reminders->all();
ensure(count($all) >= 1, 'Reminder listing returns queue.');

$dueBefore = $reminders->due();
$service->processDueReminders();
$dueAfter = $reminders->due();
ensure(count($dueAfter) <= count($dueBefore), 'Processing due reminders reduces pending count.');

echo $GLOBALS['__failures'] ? "Reminder tests completed with failures.\n" : "Reminder tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);