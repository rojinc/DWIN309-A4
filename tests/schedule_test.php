<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\ScheduleModel;

/**
 * Outputs assertion result for schedule tests.
 */
function expect(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$schedules = new ScheduleModel();

$calendar = $schedules->calendar(2025, 3);
expect(isset($calendar['2025-03-05']), 'Calendar aggregation returns March lessons.');

$studentSchedules = $schedules->forStudent(1);
expect(count($studentSchedules) >= 1, 'Student schedule lookup returns entries.');

$conflict = $schedules->hasConflict(1, '2025-03-05', '09:15:00', '10:15:00', 1, null);
expect($conflict === true, 'Conflict detection fires for overlapping booking.');

$upcoming = $schedules->upcoming();
expect(!empty($upcoming), 'Upcoming schedule query returns rows.');

echo $GLOBALS['__failures'] ? "ScheduleModel tests completed with failures.\n" : "ScheduleModel tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);