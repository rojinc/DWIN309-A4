<section class="card">
    <h1>Payments</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5">No payments recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <?php $formattedDate = !empty($payment['payment_date']) ? date('d M Y', strtotime($payment['payment_date'])) : ''; ?>
                    <tr>
                        <td><?= e($payment['invoice_number'] ?? ''); ?></td>
                        <td><?= e($formattedDate); ?></td>
                        <td>$<?= e(number_format((float) ($payment['amount'] ?? 0), 2)); ?></td>
                        <td><?= e($payment['method'] ?? ''); ?></td>
                        <td><?= e($payment['reference'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
