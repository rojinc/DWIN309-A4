<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\InstructorModel;
use App\Models\BranchModel;

/**
 * Writes a formatted assertion and tracks failures.
 */
function check(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$instructors = new InstructorModel();
$branches = new BranchModel();

$list = $instructors->all();
check(!empty($list), 'Instructor list retrieves records.');
$record = $instructors->find(1);
check($record !== null && $record['branch_name'] === 'Bayside Training Hub', 'Instructor fetch resolves branch name.');

$branchList = $instructors->forBranch(2);
check(count($branchList) >= 1, 'Instructor branch filter works.');

check($branches->find(1)['name'] === 'CBD Headquarters', 'Branch lookup for reference data.');

echo $GLOBALS['__failures'] ? "InstructorModel tests completed with failures.\n" : "InstructorModel tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);