<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\EnrollmentRequestModel;
use App\Models\InstructorModel;
use App\Models\StudentModel;

function assertResult(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$requests = new EnrollmentRequestModel();
$students = new StudentModel();
$instructors = new InstructorModel();

$all = $requests->all();
assertResult(is_array($all) && count($all) >= 2, 'Enrollment request listing returns seeded data.');

$instructor = $instructors->find(1);
$forInstructor = $requests->forInstructor((int) ($instructor['id'] ?? 0));
assertResult(is_array($forInstructor) && count($forInstructor) >= 1, 'Instructor request filter scopes correctly.');

$student = $students->find(1);
$forStudent = $requests->forStudent((int) ($student['id'] ?? 0));
assertResult(is_array($forStudent) && count($forStudent) >= 1, 'Student request filter scopes correctly.');

$newId = $requests->create([
    'student_id' => (int) ($student['id'] ?? 0),
    'course_id' => 1,
    'preferred_date' => date('Y-m-d', strtotime('+7 days')),
    'preferred_time' => '09:00:00',
    'status' => 'pending',
    'instructor_id' => null,
    'student_notes' => 'Automated test request.',
]);
assertResult($newId > 0, 'Enrollment request insertion succeeded.');

$update = $requests->updateStatus($newId, [
    'status' => 'declined',
    'instructor_id' => null,
    'admin_notes' => 'Automated test decline.',
    'decision_by' => 1,
    'decision_at' => date('Y-m-d H:i:s'),
]);
assertResult($update === true, 'Enrollment request status update succeeded.');

// Cleanup inserted record to keep fixture state consistent.
$db = App\Core\Database::connection();
$cleanup = $db->prepare('DELETE FROM enrollment_requests WHERE id = :id');
$cleanup->execute(['id' => $newId]);

if (!empty($GLOBALS['__failures'])) {
    exit(1);
}

echo "EnrollmentRequestModel tests passed.\n";
