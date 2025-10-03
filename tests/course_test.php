<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\CourseModel;

/**
 * Assertion helper for course DAO tests.
 */
function courseAssert(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$courses = new CourseModel();
$list = $courses->all();
courseAssert(count($list) >= 3, 'Course list contains seed entries.');

$course = $courses->find(1);
courseAssert($course !== null && isset($course['instructors']), 'Course retrieval includes instructor assignments.');

echo $GLOBALS['__failures'] ? "Course tests completed with failures.\n" : "Course tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);