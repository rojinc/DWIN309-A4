<section class="card">
    <div class="invoice-actions">
        <div>
            <h1>Invoice <?= e($invoice['invoice_number']); ?></h1>
            <p class="invoice-meta-line">Issued <?= e(date('d M Y', strtotime($invoice['issue_date']))); ?> · Due <?= e(date('d M Y', strtotime($invoice['due_date']))); ?></p>
        </div>
        <div class="invoice-actions-buttons">
            <a class="button button-secondary" href="<?= route('invoices', 'edit', ['id' => $invoice['id']]); ?>">Edit</a>
            <a class="button button-secondary" target="_blank" href="<?= route('invoices', 'print', ['id' => $invoice['id']]); ?>">Print</a>
        </div>
    </div>
    <div class="invoice-meta">
        <div>
            <p><strong>Student:</strong> <?= e($invoice['student_name']); ?> (<?= e($invoice['student_email'] ?? ''); ?>)</p>
            <p><strong>Course:</strong> <?= e($invoice['course_title']); ?></p>
        </div>
        <div>
            <p><strong>Status:</strong> <span class="status-pill status-<?= e($invoice['status']); ?>"><?= e(ucfirst($invoice['status'])); ?></span></p>
            <p><strong>Total:</strong> $<?= e(number_format($invoice['total'], 2)); ?></p>
            <p><strong>Balance Due:</strong> $<?= e(number_format($invoice['balance_due'], 2)); ?></p>
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
            <?php foreach ($invoice['items'] as $item): ?>
                <tr>
                    <td><?= e($item['description']); ?></td>
                    <td><?= e($item['quantity']); ?></td>
                    <td>$<?= e(number_format($item['unit_price'], 2)); ?></td>
                    <td>$<?= e(number_format($item['total'], 2)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Subtotal</th>
                <td>$<?= e(number_format($invoice['subtotal'], 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Tax</th>
                <td>$<?= e(number_format($invoice['tax_amount'], 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Total</th>
                <td>$<?= e(number_format($invoice['total'], 2)); ?></td>
            </tr>
            <tr>
                <th colspan="3">Balance Due</th>
                <td>$<?= e(number_format($invoice['balance_due'], 2)); ?></td>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoice['payments'])): ?>
                    <tr><td colspan="3">No payments on record.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoice['payments'] as $payment): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($payment['payment_date']))); ?></td>
                            <td>$<?= e(number_format($payment['amount'], 2)); ?></td>
                            <td><?= e($payment['method']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Add Payment</h2>
        <form method="post" action="<?= route('invoices', 'payment', ['id' => $invoice['id']]); ?>" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e($paymentToken); ?>">
            <label>
                <span>Amount</span>
                <input type="number" name="amount" min="0" step="0.01" required>
            </label>
            <label>
                <span>Payment date</span>
                <input type="date" name="payment_date" value="<?= e(date('Y-m-d')); ?>" required>
            </label>
            <label>
                <span>Method</span>
                <select name="method">
                    <option value="Card">Card</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </label>
            <label>
                <span>Reference</span>
                <input type="text" name="reference">
            </label>
            <label class="full-width">
                <span>Notes</span>
                <textarea name="notes" rows="3"></textarea>
            </label>
            <button class="button" type="submit">Record Payment</button>
        </form>
    </div>
</section>