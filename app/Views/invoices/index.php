<section class="card">
    <div class="card-header">
        <h1>Invoices</h1>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Student</th>
                <th>Course</th>
                <th>Issue Date</th>
                <th>Total</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="7">No invoices generated.</td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= e($invoice['invoice_number']); ?></td>
                        <td><?= e($invoice['student_name']); ?></td>
                        <td><?= e($invoice['course_title']); ?></td>
                        <td><?= e(date('d M Y', strtotime($invoice['issue_date']))); ?></td>
                        <td>$<?= e(number_format($invoice['total'], 2)); ?></td>
                        <td><span class="status-pill status-<?= e($invoice['status']); ?>"><?= e(ucfirst($invoice['status'])); ?></span></td>
                        <td class="table-actions">
                            <a class="button button-small" href="<?= route('invoices', 'view', ['id' => $invoice['id']]); ?>">View</a>
                            <a class="button button-small button-secondary" href="<?= route('invoices', 'edit', ['id' => $invoice['id']]); ?>">Edit</a>
                            <a class="button button-small button-secondary" target="_blank" href="<?= route('invoices', 'print', ['id' => $invoice['id']]); ?>">Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>