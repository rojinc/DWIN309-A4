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
            <?php if (empty()): ?>
                <tr><td colspan="5">No payments recorded.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['invoice_number']); ?></td>
                        <td><?= e(date('d M Y', strtotime(['payment_date']))); ?></td>
                        <td>$<?= e(number_format(['amount'], 2)); ?></td>
                        <td><?= e(['method']); ?></td>
                        <td><?= e(['reference']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>