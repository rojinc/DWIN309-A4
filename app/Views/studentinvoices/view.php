<?php
$invoice = $invoice ?? [];
$items = $invoice['items'] ?? [];
$payments = $invoice['payments'] ?? [];
$balance = (float) ($invoice['balance_due'] ?? 0);
?>
<section class="card invoice-detail">
    <div class="invoice-actions">
        <div>
            <h1>Invoice <?= e($invoice['invoice_number'] ?? ''); ?></h1>
            <p class="invoice-meta-line">Issued <?= e(date('d M Y', strtotime($invoice['issue_date'] ?? 'now'))); ?> · Due <?= e(date('d M Y', strtotime($invoice['due_date'] ?? 'now'))); ?></p>
        </div>
        <div class="invoice-actions-buttons">
            <a class="button button-secondary" href="<?= route('studentinvoices', 'index'); ?>">Back to list</a>
        </div>
    </div>
    <div class="invoice-meta">
        <div>
            <p><strong>Course:</strong> <?= e($invoice['course_title'] ?? ''); ?></p>
            <p><strong>Status:</strong> <span class="status-pill status-<?= e($invoice['status'] ?? 'sent'); ?>"><?= e(ucfirst($invoice['status'] ?? 'sent')); ?></span></p>
        </div>
        <div>
            <p><strong>Total:</strong> $<?= e(number_format((float) ($invoice['total'] ?? 0), 2)); ?></p>
            <p><strong>Balance Due:</strong> $<?= e(number_format($balance, 2)); ?></p>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['description']); ?></td>
                    <td><?= e($item['quantity']); ?></td>
                    <td>$<?= e(number_format((float) $item['unit_price'], 2)); ?></td>
                    <td>$<?= e(number_format((float) $item['total'], 2)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Subtotal</th>
                <td>$<?= e(number_format((float) ($invoice['subtotal'] ?? 0), 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Tax</th>
                <td>$<?= e(number_format((float) ($invoice['tax_amount'] ?? 0), 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Total</th>
                <td>$<?= e(number_format((float) ($invoice['total'] ?? 0), 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Balance Due</th>
                <td>$<?= e(number_format($balance, 2)); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php if (!empty($invoice['notes'])): ?>
        <p class="invoice-notes"><strong>Notes:</strong> <?= e($invoice['notes']); ?></p>
    <?php endif; ?>
</section>
<section class="grid two-column">
    <div class="card">
        <h2>Payments</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="4">No payments recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($payment['payment_date']))); ?></td>
                            <td>$<?= e(number_format((float) $payment['amount'], 2)); ?></td>
                            <td><?= e($payment['method'] ?? ''); ?></td>
                            <td><?= e($payment['reference'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($balance > 0): ?>
    <div class="card">
        <h2>Pay Outstanding Balance</h2>
        <p class="muted">This is a mock payment form for testing purposes. No real charge will be made.</p>
        <form method="post" action="<?= route('studentinvoices', 'pay', ['id' => $invoice['id']]); ?>" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e($paymentToken ?? ''); ?>">
            <label class="full-width">
                <span>Name on card</span>
                <input type="text" name="name_on_card" required>
            </label>
            <label>
                <span>Card number</span>
                <input type="text" name="card_number" inputmode="numeric" maxlength="19" placeholder="4111111111111111" required>
            </label>
            <label>
                <span>Expiry (MM/YY)</span>
                <input type="text" name="card_expiry" placeholder="08/27" required>
            </label>
            <label>
                <span>CVV</span>
                <input type="text" name="card_cvv" inputmode="numeric" maxlength="4" required>
            </label>
            <button class="button" type="submit">Pay $<?= e(number_format($balance, 2)); ?></button>
        </form>
    </div>
    <?php else: ?>
    <div class="card">
        <h2>Payment Complete</h2>
        <p class="muted">Thank you! This invoice has been fully paid. You can download or print this page for your records.</p>
    </div>
    <?php endif; ?>
</section>
