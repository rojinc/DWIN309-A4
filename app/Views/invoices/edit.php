<section class="card">
    <h1>Edit Invoice <?= e($invoice['invoice_number']); ?></h1>
    <form id="invoice-edit-form" method="post" action="<?= route('invoices', 'update', ['id' => $invoice['id']]); ?>" class="form-grid invoice-edit-form" data-tax-rate="<?= e(number_format(($invoice['tax_amount'] > 0 && $invoice['subtotal'] > 0) ? ($invoice['tax_amount'] / $invoice['subtotal'] * 100) : 10, 2, '.', '')); ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <label>
            <span>Issue date</span>
            <input type="date" name="issue_date" value="<?= e($invoice['issue_date']); ?>" required>
        </label>
        <label>
            <span>Due date</span>
            <input type="date" name="due_date" value="<?= e($invoice['due_date']); ?>" required>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <?php foreach (['sent','partial','paid','overdue'] as $status): ?>
                    <option value="<?= e($status); ?>" <?= $status === $invoice['status'] ? 'selected' : ''; ?>><?= e(ucfirst($status)); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Tax rate (%)</span>
            <input type="number" name="tax_rate" step="0.01" value="<?= e(($invoice['tax_amount'] > 0 && $invoice['subtotal'] > 0) ? round($invoice['tax_amount'] / $invoice['subtotal'] * 100, 2) : 10); ?>">
        </label>
        <label class="full-width">
            <span>Notes</span>
            <textarea name="notes" rows="3"><?= e($invoice['notes'] ?? ''); ?></textarea>
        </label>

        <div class="full-width">
            <h2>Line items</h2>
            <table class="table invoice-lines">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th>Line total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="invoice-lines-body">
                    <?php foreach ($invoice['items'] as $index => $item): ?>
                        <tr class="invoice-line">
                            <td>
                                <input type="text" name="description[]" value="<?= e($item['description']); ?>" required>
                            </td>
                            <td>
                                <input type="number" class="invoice-line-qty" name="quantity[]" min="0" step="0.01" value="<?= e($item['quantity']); ?>" required>
                            </td>
                            <td>
                                <input type="number" class="invoice-line-price" name="unit_price[]" min="0" step="0.01" value="<?= e(number_format($item['unit_price'], 2, '.', '')); ?>" required>
                            </td>
                            <td class="invoice-line-total">
                                $<?= e(number_format($item['total'], 2)); ?>
                            </td>
                            <td>
                                <button type="button" class="button button-secondary button-small invoice-line-remove" aria-label="Remove line item">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="button button-secondary" id="invoice-add-line">Add line</button>
        </div>

        <div class="full-width invoice-summary">
            <div class="invoice-summary-row"><span>Subtotal</span><strong id="invoice-subtotal">$<?= e(number_format($invoice['subtotal'], 2)); ?></strong></div>
            <div class="invoice-summary-row"><span>Tax</span><strong id="invoice-tax">$<?= e(number_format($invoice['tax_amount'], 2)); ?></strong></div>
            <div class="invoice-summary-row"><span>Total</span><strong id="invoice-total">$<?= e(number_format($invoice['total'], 2)); ?></strong></div>
        </div>

        <label class="full-width invoice-notify">
            <input type="checkbox" name="notify_student" value="yes">
            <span>Email the updated invoice to the student</span>
        </label>

        <div class="form-actions full-width">
            <button type="submit" class="button">Save changes</button>
            <a class="button button-secondary" href="<?= route('invoices', 'view', ['id' => $invoice['id']]); ?>">Cancel</a>
        </div>
    </form>
</section>

<template id="invoice-line-template">
    <tr class="invoice-line">
        <td>
            <input type="text" name="description[]" required>
        </td>
        <td>
            <input type="number" class="invoice-line-qty" name="quantity[]" min="0" step="0.01" value="1" required>
        </td>
        <td>
            <input type="number" class="invoice-line-price" name="unit_price[]" min="0" step="0.01" value="0" required>
        </td>
        <td class="invoice-line-total">$0.00</td>
        <td>
            <button type="button" class="button button-secondary button-small invoice-line-remove" aria-label="Remove line item">Remove</button>
        </td>
    </tr>
</template>