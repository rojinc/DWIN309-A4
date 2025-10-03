<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\InvoiceModel;
use App\Models\PaymentModel;

/**
 * Records assertion result for invoice tests.
 */
function verify(bool $condition, string $message): void
{
    if ($condition) {
        echo "[PASS] {$message}\n";
    } else {
        echo "[FAIL] {$message}\n";
        $GLOBALS['__failures'] = true;
    }
}

$invoices = new InvoiceModel();
$payments = new PaymentModel();

$list = $invoices->all();
verify(count($list) >= 1, 'Invoice listing returns records.');

$invoice = $invoices->find(1);
verify($invoice !== null && $invoice['total'] == 968.00, 'Invoice fetch returns correct total.');
verify(abs($invoice['balance_due'] - 568.00) < 0.01, 'Balance due accounts for payments.');

$paymentList = $payments->all();
verify(count($paymentList) >= 1, 'Payment listing returns seeded payment.');

$invoices->setStatus(1, 'partial');
$invoiceAfter = $invoices->find(1);
verify($invoiceAfter['status'] === 'partial', 'Invoice status update persists.');

echo $GLOBALS['__failures'] ? "InvoiceModel tests completed with failures.\n" : "InvoiceModel tests passed.\n";
exit($GLOBALS['__failures'] ? 1 : 0);