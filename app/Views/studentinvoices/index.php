<?php
$invoices = $invoices ?? [];
?>
<section class="card">
    <h1>My Invoices</h1>
    <p class="muted">Review your course invoices, check balances, and pay securely online.</p>
    <table class="table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Course</th>
                <th>Issued</th>
                <th>Total</th>
                <th>Balance</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="7">No invoices yet. Your invoices will appear here after enrollment approval.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                    <?php $balance = (float) ($invoice['balance_due'] ?? ($invoice['total'] ?? 0)); ?>
                    <tr>
                        <td><?= e($invoice['invoice_number']); ?></td>
                        <td><?= e($invoice['course_title'] ?? ''); ?></td>
                        <td><?= e(date('d M Y', strtotime($invoice['issue_date'] ?? 'now'))); ?></td>
                        <td>$<?= e(number_format((float) ($invoice['total'] ?? 0), 2)); ?></td>
                        <td>$<?= e(number_format($balance, 2)); ?></td>
                        <td><span class="status-pill status-<?= e($invoice['status']); ?>"><?= e(ucfirst($invoice['status'])); ?></span></td>
                        <td><a class="button button-secondary" href="<?= route('studentinvoices', 'view', ['id' => $invoice['id']]); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
