<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\CommunicationModel;

/**
 * Assertion helper for communication DAO tests.
 */
function commAssert(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$communications = new CommunicationModel();
$list = $communications->all();
commAssert(count($list) >= 1, 'Communication history list returns rows.');

if (!empty($list)) {
    $recipients = $communications->recipients((int) $list[0]['id']);
    commAssert(count($recipients) >= 1, 'Communication recipients lookup succeeds.');
}

echo $GLOBALS['__failures'] ? "Communication tests completed with failures.\n" : "Communication tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);