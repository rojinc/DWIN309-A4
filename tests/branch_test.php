<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\BranchModel;

/**
 * Simple assertion helper for branch DAO testing.
 */
function branchAssert(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$branches = new BranchModel();
$all = $branches->all();
branchAssert(count($all) >= 3, 'Branch listing returned seeded locations.');

$branch = $branches->find(1);
branchAssert($branch !== null && $branch['name'] === 'CBD Headquarters', 'Branch lookup returns correct row.');

echo $GLOBALS['__failures'] ? "Branch tests completed with failures.\n" : "Branch tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);