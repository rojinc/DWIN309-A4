<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\StudentModel;
use App\Models\EnrollmentModel;

/**
 * Writes a formatted assertion result to stdout.
 */
function assertResult(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$students = new StudentModel();
$enrollments = new EnrollmentModel();

$list = $students->all();
assertResult(is_array($list) && count($list) >= 1, 'Student list returns seeded records.');

$student = $students->find(1);
assertResult($student !== null && $student['first_name'] === 'Jack', 'Fetch specific student by ID.');

$search = $students->search('Jack');
assertResult(!empty($search), 'Student search matches query term.');

$stats = $students->progressStats();
assertResult(isset($stats['total']) && $stats['total'] >= 1, 'Progress stats summary aggregates correctly.');

$enroll = $enrollments->forStudent(1);
assertResult(count($enroll) >= 1, 'Enrollment retrieval for student succeeds.');

echo $GLOBALS['__failures'] ? "StudentModel tests completed with failures.\n" : "StudentModel tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);