<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;

/**
 * Manages invoice lifecycle and associated payments.
 */
class InvoicesController extends Controller
{
    private InvoiceModel $invoices;
    private PaymentModel $payments;
    private AuditService $audit;
    private OutboundMessageService $outbound;
    private NotificationService $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceModel();
        $this->payments = new PaymentModel();
        $this->audit = new AuditService();
        $this->outbound = new OutboundMessageService();
        $this->notifications = new NotificationService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('invoices/index', [
            'pageTitle' => 'Invoices',
            'invoices' => $this->invoices->all(),
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/view', [
            'pageTitle' => 'Invoice #' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'paymentToken' => Csrf::token('payment_add_' . $id),
        ]);
    }

    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/edit', [
            'pageTitle' => 'Edit Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'csrfToken' => Csrf::token('invoice_edit_' . $id),
        ]);
    }

    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('invoice_edit_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $validation = Validation::make($_POST, [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required'],
            'tax_rate' => ['numeric'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $descriptions = post('description', []);
        $quantities = post('quantity', []);
        $prices = post('unit_price', []);
        $items = [];
        $count = max(count((array) $descriptions), count((array) $quantities), count((array) $prices));
        for ($i = 0; $i < $count; $i++) {
            $description = trim((string) ($descriptions[$i] ?? ''));
            $quantity = (float) ($quantities[$i] ?? 0);
            $unitPrice = (float) ($prices[$i] ?? 0);
            if ($description === '') {
                continue;
            }
            $quantity = $quantity > 0 ? $quantity : 1;
            $unitPrice = $unitPrice >= 0 ? $unitPrice : 0;
            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $subtotal = array_sum(array_column($items, 'total'));
        $taxRate = (float) post('tax_rate', 10);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $updateSuccess = $this->invoices->update($invoiceId, [
            'issue_date' => $validation['data']['issue_date'],
            'due_date' => $validation['data']['due_date'],
            'status' => $validation['data']['status'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => post('notes'),
        ], $items);

        if ($updateSuccess) {
            $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_updated', 'invoice', $invoiceId);
            $invoice = $this->invoices->find($invoiceId);
            if ($invoice && post('notify_student') === 'yes') {
                $this->sendInvoiceEmail($invoice, 'Invoice updated', $this->buildInvoiceSummary($invoice));
            }
            $this->flash('success', 'Invoice updated successfully.');
        }

        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    public function printAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/print', [
            'invoice' => $invoice,
            'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
        ], 'print');
    }

    public function paymentAction(): void
{
    $this->requireRole(['admin', 'staff']);
    if (<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;

/**
 * Manages invoice lifecycle and associated payments.
 */
class InvoicesController extends Controller
{
    private InvoiceModel $invoices;
    private PaymentModel $payments;
    private AuditService $audit;
    private OutboundMessageService $outbound;
    private NotificationService $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceModel();
        $this->payments = new PaymentModel();
        $this->audit = new AuditService();
        $this->outbound = new OutboundMessageService();
        $this->notifications = new NotificationService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('invoices/index', [
            'pageTitle' => 'Invoices',
            'invoices' => $this->invoices->all(),
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/view', [
            'pageTitle' => 'Invoice #' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'paymentToken' => Csrf::token('payment_add_' . $id),
        ]);
    }

    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/edit', [
            'pageTitle' => 'Edit Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'csrfToken' => Csrf::token('invoice_edit_' . $id),
        ]);
    }

    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('invoice_edit_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $validation = Validation::make($_POST, [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required'],
            'tax_rate' => ['numeric'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $descriptions = post('description', []);
        $quantities = post('quantity', []);
        $prices = post('unit_price', []);
        $items = [];
        $count = max(count((array) $descriptions), count((array) $quantities), count((array) $prices));
        for ($i = 0; $i < $count; $i++) {
            $description = trim((string) ($descriptions[$i] ?? ''));
            $quantity = (float) ($quantities[$i] ?? 0);
            $unitPrice = (float) ($prices[$i] ?? 0);
            if ($description === '') {
                continue;
            }
            $quantity = $quantity > 0 ? $quantity : 1;
            $unitPrice = $unitPrice >= 0 ? $unitPrice : 0;
            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $subtotal = array_sum(array_column($items, 'total'));
        $taxRate = (float) post('tax_rate', 10);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $updateSuccess = $this->invoices->update($invoiceId, [
            'issue_date' => $validation['data']['issue_date'],
            'due_date' => $validation['data']['due_date'],
            'status' => $validation['data']['status'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => post('notes'),
        ], $items);

        if ($updateSuccess) {
            $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_updated', 'invoice', $invoiceId);
            $invoice = $this->invoices->find($invoiceId);
            if ($invoice && post('notify_student') === 'yes') {
                $this->sendInvoiceEmail($invoice, 'Invoice updated', $this->buildInvoiceSummary($invoice));
            }
            $this->flash('success', 'Invoice updated successfully.');
        }

        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    public function printAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/print', [
            'invoice' => $invoice,
            'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
        ], 'print');
    }

    public function paymentAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $validation = Validation::make($_POST, [
            'amount' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $data = $validation['data'];
        $amount = (float) $data['amount'];
        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'method' => post('method', 'Cash'),
            'reference' => post('reference'),
            'notes' => post('notes'),
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice && $invoice['balance_due'] <= 0) {
            $this->invoices->setStatus($invoiceId, 'paid');
            $invoice['status'] = 'paid';
        } else {
            $this->invoices->setStatus($invoiceId, 'partial');
            if ($invoice) {
                $invoice['status'] = 'partial';
            }
        }
        if ($invoice) {
            $this->sendInvoiceEmail(
                $invoice,
                'Payment received for invoice ' . $invoice['invoice_number'],
                'We have recorded a payment of $' . number_format($amount, 2) . " on " . date('d M Y', strtotime($data['payment_date'])) . ".\n\n" . $this->buildInvoiceSummary($invoice)
            );
        }
        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
        $this->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    private function sendInvoiceEmail(array $invoice, string $subject, string $body): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email && $this->outbound->sendEmail($email, $subject, $body)) {
            return;
        }
        $studentId = $invoice['student_user_id'] ?? null;
        if ($studentId) {
            $this->notifications->send((int) $studentId, $subject, $body);
        }
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        $lines = [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ];
        return implode("\n", $lines);
    }
}SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect(route('invoices', 'index'));
    }
    $invoiceId = (int) (<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;

/**
 * Manages invoice lifecycle and associated payments.
 */
class InvoicesController extends Controller
{
    private InvoiceModel $invoices;
    private PaymentModel $payments;
    private AuditService $audit;
    private OutboundMessageService $outbound;
    private NotificationService $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceModel();
        $this->payments = new PaymentModel();
        $this->audit = new AuditService();
        $this->outbound = new OutboundMessageService();
        $this->notifications = new NotificationService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('invoices/index', [
            'pageTitle' => 'Invoices',
            'invoices' => $this->invoices->all(),
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/view', [
            'pageTitle' => 'Invoice #' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'paymentToken' => Csrf::token('payment_add_' . $id),
        ]);
    }

    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/edit', [
            'pageTitle' => 'Edit Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'csrfToken' => Csrf::token('invoice_edit_' . $id),
        ]);
    }

    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('invoice_edit_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $validation = Validation::make($_POST, [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required'],
            'tax_rate' => ['numeric'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $descriptions = post('description', []);
        $quantities = post('quantity', []);
        $prices = post('unit_price', []);
        $items = [];
        $count = max(count((array) $descriptions), count((array) $quantities), count((array) $prices));
        for ($i = 0; $i < $count; $i++) {
            $description = trim((string) ($descriptions[$i] ?? ''));
            $quantity = (float) ($quantities[$i] ?? 0);
            $unitPrice = (float) ($prices[$i] ?? 0);
            if ($description === '') {
                continue;
            }
            $quantity = $quantity > 0 ? $quantity : 1;
            $unitPrice = $unitPrice >= 0 ? $unitPrice : 0;
            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $subtotal = array_sum(array_column($items, 'total'));
        $taxRate = (float) post('tax_rate', 10);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $updateSuccess = $this->invoices->update($invoiceId, [
            'issue_date' => $validation['data']['issue_date'],
            'due_date' => $validation['data']['due_date'],
            'status' => $validation['data']['status'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => post('notes'),
        ], $items);

        if ($updateSuccess) {
            $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_updated', 'invoice', $invoiceId);
            $invoice = $this->invoices->find($invoiceId);
            if ($invoice && post('notify_student') === 'yes') {
                $this->sendInvoiceEmail($invoice, 'Invoice updated', $this->buildInvoiceSummary($invoice));
            }
            $this->flash('success', 'Invoice updated successfully.');
        }

        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    public function printAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/print', [
            'invoice' => $invoice,
            'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
        ], 'print');
    }

    public function paymentAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $validation = Validation::make($_POST, [
            'amount' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $data = $validation['data'];
        $amount = (float) $data['amount'];
        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'method' => post('method', 'Cash'),
            'reference' => post('reference'),
            'notes' => post('notes'),
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice && $invoice['balance_due'] <= 0) {
            $this->invoices->setStatus($invoiceId, 'paid');
            $invoice['status'] = 'paid';
        } else {
            $this->invoices->setStatus($invoiceId, 'partial');
            if ($invoice) {
                $invoice['status'] = 'partial';
            }
        }
        if ($invoice) {
            $this->sendInvoiceEmail(
                $invoice,
                'Payment received for invoice ' . $invoice['invoice_number'],
                'We have recorded a payment of $' . number_format($amount, 2) . " on " . date('d M Y', strtotime($data['payment_date'])) . ".\n\n" . $this->buildInvoiceSummary($invoice)
            );
        }
        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
        $this->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    private function sendInvoiceEmail(array $invoice, string $subject, string $body): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email && $this->outbound->sendEmail($email, $subject, $body)) {
            return;
        }
        $studentId = $invoice['student_user_id'] ?? null;
        if ($studentId) {
            $this->notifications->send((int) $studentId, $subject, $body);
        }
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        $lines = [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ];
        return implode("\n", $lines);
    }
}GET['id'] ?? 0);
    if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
        $this->flash('error', 'Security token mismatch.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }
    $validation = Validation::make(<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;

/**
 * Manages invoice lifecycle and associated payments.
 */
class InvoicesController extends Controller
{
    private InvoiceModel $invoices;
    private PaymentModel $payments;
    private AuditService $audit;
    private OutboundMessageService $outbound;
    private NotificationService $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceModel();
        $this->payments = new PaymentModel();
        $this->audit = new AuditService();
        $this->outbound = new OutboundMessageService();
        $this->notifications = new NotificationService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('invoices/index', [
            'pageTitle' => 'Invoices',
            'invoices' => $this->invoices->all(),
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/view', [
            'pageTitle' => 'Invoice #' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'paymentToken' => Csrf::token('payment_add_' . $id),
        ]);
    }

    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/edit', [
            'pageTitle' => 'Edit Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'csrfToken' => Csrf::token('invoice_edit_' . $id),
        ]);
    }

    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('invoice_edit_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $validation = Validation::make($_POST, [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required'],
            'tax_rate' => ['numeric'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $descriptions = post('description', []);
        $quantities = post('quantity', []);
        $prices = post('unit_price', []);
        $items = [];
        $count = max(count((array) $descriptions), count((array) $quantities), count((array) $prices));
        for ($i = 0; $i < $count; $i++) {
            $description = trim((string) ($descriptions[$i] ?? ''));
            $quantity = (float) ($quantities[$i] ?? 0);
            $unitPrice = (float) ($prices[$i] ?? 0);
            if ($description === '') {
                continue;
            }
            $quantity = $quantity > 0 ? $quantity : 1;
            $unitPrice = $unitPrice >= 0 ? $unitPrice : 0;
            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item.');
            $this->redirect(route('invoices', 'edit', ['id' => $invoiceId]));
        }

        $subtotal = array_sum(array_column($items, 'total'));
        $taxRate = (float) post('tax_rate', 10);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $updateSuccess = $this->invoices->update($invoiceId, [
            'issue_date' => $validation['data']['issue_date'],
            'due_date' => $validation['data']['due_date'],
            'status' => $validation['data']['status'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => post('notes'),
        ], $items);

        if ($updateSuccess) {
            $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_updated', 'invoice', $invoiceId);
            $invoice = $this->invoices->find($invoiceId);
            if ($invoice && post('notify_student') === 'yes') {
                $this->sendInvoiceEmail($invoice, 'Invoice updated', $this->buildInvoiceSummary($invoice));
            }
            $this->flash('success', 'Invoice updated successfully.');
        }

        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    public function printAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->find($id);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('invoices', 'index'));
        }
        $this->render('invoices/print', [
            'invoice' => $invoice,
            'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
        ], 'print');
    }

    public function paymentAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('invoices', 'index'));
        }
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $validation = Validation::make($_POST, [
            'amount' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $data = $validation['data'];
        $amount = (float) $data['amount'];
        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'method' => post('method', 'Cash'),
            'reference' => post('reference'),
            'notes' => post('notes'),
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice && $invoice['balance_due'] <= 0) {
            $this->invoices->setStatus($invoiceId, 'paid');
            $invoice['status'] = 'paid';
        } else {
            $this->invoices->setStatus($invoiceId, 'partial');
            if ($invoice) {
                $invoice['status'] = 'partial';
            }
        }
        if ($invoice) {
            $this->sendInvoiceEmail(
                $invoice,
                'Payment received for invoice ' . $invoice['invoice_number'],
                'We have recorded a payment of $' . number_format($amount, 2) . " on " . date('d M Y', strtotime($data['payment_date'])) . ".\n\n" . $this->buildInvoiceSummary($invoice)
            );
        }
        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
        $this->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    private function sendInvoiceEmail(array $invoice, string $subject, string $body): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email && $this->outbound->sendEmail($email, $subject, $body)) {
            return;
        }
        $studentId = $invoice['student_user_id'] ?? null;
        if ($studentId) {
            $this->notifications->send((int) $studentId, $subject, $body);
        }
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        $lines = [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ];
        return implode("\n", $lines);
    }
}POST, [
        'amount' => ['required', 'numeric'],
        'payment_date' => ['required', 'date'],
    ]);
    if ($validation['errors']) {
        $this->flash('error', implode(' ', $validation['errors']));
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }
    $data = $validation['data'];
    $amount = (float) $data['amount'];
    $this->payments->create([
        'invoice_id' => $invoiceId,
        'amount' => $amount,
        'payment_date' => $data['payment_date'],
        'method' => post('method', 'Cash'),
        'reference' => post('reference'),
        'notes' => post('notes'),
        'recorded_by' => $this->auth->user()['id'] ?? null,
    ]);

    $invoice = $this->invoices->find($invoiceId);
    if ($invoice && $invoice['balance_due'] <= 0) {
        $this->invoices->setStatus($invoiceId, 'paid');
    } else {
        $this->invoices->setStatus($invoiceId, 'partial');
    }
    $invoice = $this->invoices->find($invoiceId);

    if ($invoice) {
        $this->sendInvoiceEmail(
            $invoice,
            'Payment received for invoice ' . $invoice['invoice_number'],
            'We have recorded a payment of 
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $validation = Validation::make($_POST, [
            'amount' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $data = $validation['data'];
        $amount = (float) $data['amount'];
        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'method' => post('method', 'Cash'),
            'reference' => post('reference'),
            'notes' => post('notes'),
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice && $invoice['balance_due'] <= 0) {
            $this->invoices->setStatus($invoiceId, 'paid');
            $invoice['status'] = 'paid';
        } else {
            $this->invoices->setStatus($invoiceId, 'partial');
            if ($invoice) {
                $invoice['status'] = 'partial';
            }
        }
        if ($invoice) {
            $this->sendInvoiceEmail(
                $invoice,
                'Payment received for invoice ' . $invoice['invoice_number'],
                'We have recorded a payment of $' . number_format($amount, 2) . " on " . date('d M Y', strtotime($data['payment_date'])) . ".\n\n" . $this->buildInvoiceSummary($invoice)
            );
        }
        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
        $this->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    private function sendInvoiceEmail(array $invoice, string $subject, string $body): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email && $this->outbound->sendEmail($email, $subject, $body)) {
            return;
        }
        $studentId = $invoice['student_user_id'] ?? null;
        if ($studentId) {
            $this->notifications->send((int) $studentId, $subject, $body);
        }
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        $lines = [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ];
        return implode("\n", $lines);
    }
} . number_format($amount, 2) . ' on ' . date('d M Y', strtotime($data['payment_date'])) . "\n\n" . $this->buildInvoiceSummary($invoice)
        );
    }

    $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
    $this->flash('success', 'Payment recorded successfully.');
    $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
}
        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('payment_add_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $validation = Validation::make($_POST, [
            'amount' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
        }
        $data = $validation['data'];
        $amount = (float) $data['amount'];
        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'method' => post('method', 'Cash'),
            'reference' => post('reference'),
            'notes' => post('notes'),
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice && $invoice['balance_due'] <= 0) {
            $this->invoices->setStatus($invoiceId, 'paid');
            $invoice['status'] = 'paid';
        } else {
            $this->invoices->setStatus($invoiceId, 'partial');
            if ($invoice) {
                $invoice['status'] = 'partial';
            }
        }
        if ($invoice) {
            $this->sendInvoiceEmail(
                $invoice,
                'Payment received for invoice ' . $invoice['invoice_number'],
                'We have recorded a payment of $' . number_format($amount, 2) . " on " . date('d M Y', strtotime($data['payment_date'])) . ".\n\n" . $this->buildInvoiceSummary($invoice)
            );
        }
        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_added', 'invoice', $invoiceId);
        $this->flash('success', 'Payment recorded successfully.');
        $this->redirect(route('invoices', 'view', ['id' => $invoiceId]));
    }

    private function sendInvoiceEmail(array $invoice, string $subject, string $body): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email && $this->outbound->sendEmail($email, $subject, $body)) {
            return;
        }
        $studentId = $invoice['student_user_id'] ?? null;
        if ($studentId) {
            $this->notifications->send((int) $studentId, $subject, $body);
        }
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        $lines = [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ];
        return implode("\n", $lines);
    }
}