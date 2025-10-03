<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\VehicleModel;

/**
 * Assertion helper for vehicle DAO tests.
 */
function vehicleAssert(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$vehicles = new VehicleModel();
$list = $vehicles->all();
vehicleAssert(count($list) >= 2, 'Vehicle listing returns fleet.');

$vehicle = $vehicles->find(1);
vehicleAssert($vehicle !== null && $vehicle['status'] === 'available', 'Vehicle fetch returns correct status.');

echo $GLOBALS['__failures'] ? "Vehicle tests completed with failures.\n" : "Vehicle tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);