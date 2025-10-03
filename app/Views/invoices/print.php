<section class="invoice-print">
    <header class="invoice-print-header">
        <h1>Invoice <?= e($invoice['invoice_number']); ?></h1>
        <p>Issued <?= e(date('d M Y', strtotime($invoice['issue_date']))); ?> · Due <?= e(date('d M Y', strtotime($invoice['due_date']))); ?></p>
    </header>
    <div class="invoice-print-meta">
        <div>
            <h2>Billing to</h2>
            <p><?= e($invoice['student_name']); ?><br><?= e($invoice['student_email'] ?? ''); ?></p>
        </div>
        <div>
            <h2>Summary</h2>
            <p><strong>Total:</strong> $<?= e(number_format($invoice['total'], 2)); ?></p>
            <p><strong>Status:</strong> <?= e(ucfirst($invoice['status'])); ?></p>
        </div>
    </div>
    <table class="invoice-print-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit price</th>
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
        </tfoot>
    </table>
    <?php if (!empty($invoice['notes'])): ?>
        <p class="invoice-print-notes"><strong>Notes:</strong> <?= e($invoice['notes']); ?></p>
    <?php endif; ?>
</section>