<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\ReportModel;

/**
 * Writes assertion results for report metrics.
 */
function checkReport(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$reports = new ReportModel();
$summary = $reports->dashboardSummary();
checkReport(isset($summary['students']) && $summary['students'] >= 1, 'Dashboard summary counts students.');

$revenue = $reports->monthlyRevenue(3);
checkReport(is_array($revenue), 'Monthly revenue query executes.');

if (!empty($revenue)) {
    checkReport(isset($revenue[0]['period']) && isset($revenue[0]['total']), 'Revenue row structure valid.');
}

echo $GLOBALS['__failures'] ? "Report tests completed with failures.\n" : "Report tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);