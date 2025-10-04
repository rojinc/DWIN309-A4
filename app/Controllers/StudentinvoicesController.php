<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Services\NotificationService;
use App\Services\AuditService;

/**
 * Allows students to review and settle their invoices.
 */
class StudentinvoicesController extends Controller
{
    private InvoiceModel $invoices;
    private PaymentModel $payments;
    private StudentModel $students;
    private NotificationService $notifications;
    private AuditService $audit;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new InvoiceModel();
        $this->payments = new PaymentModel();
        $this->students = new StudentModel();
        $this->notifications = new NotificationService();
        $this->audit = new AuditService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['student']);
        $student = $this->currentStudent();
        if (!$student) {
            $this->flash('error', 'Student profile not found.');
            $this->redirect(route('dashboard', 'index'));
        }

        $invoices = $this->invoices->forStudent((int) $student['id'], null);
        $this->render('studentinvoices/index', [
            'pageTitle' => 'My Invoices',
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['student']);
        $student = $this->currentStudent();
        if (!$student) {
            $this->flash('error', 'Student profile not found.');
            $this->redirect(route('dashboard', 'index'));
        }

        $invoiceId = (int) ($_GET['id'] ?? 0);
        $invoice = $this->invoices->findForStudent($invoiceId, (int) $student['id']);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('studentinvoices', 'index'));
        }

        $this->render('studentinvoices/view', [
            'pageTitle' => 'Invoice ' . ($invoice['invoice_number'] ?? ''),
            'invoice' => $invoice,
            'paymentToken' => Csrf::token('student_invoice_pay_' . $invoiceId),
        ]);
    }

    public function payAction(): void
    {
        $this->requireRole(['student']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('studentinvoices', 'index'));
        }

        $invoiceId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('student_invoice_pay_' . $invoiceId, post('csrf_token'))) {
            $this->flash('error', 'Security check failed. Please try again.');
            $this->redirect(route('studentinvoices', 'view', ['id' => $invoiceId]));
        }

        $student = $this->currentStudent();
        if (!$student) {
            $this->flash('error', 'Student profile not found.');
            $this->redirect(route('dashboard', 'index'));
        }

        $invoice = $this->invoices->findForStudent($invoiceId, (int) $student['id']);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect(route('studentinvoices', 'index'));
        }

        $balanceDue = (float) ($invoice['balance_due'] ?? ($invoice['total'] ?? 0));
        if ($balanceDue <= 0) {
            $this->flash('success', 'This invoice is already settled.');
            $this->redirect(route('studentinvoices', 'view', ['id' => $invoiceId]));
        }

        $validation = Validation::make($_POST, [
            'name_on_card' => ['required'],
            'card_number' => ['required', 'min:12', 'max:19'],
            'card_expiry' => ['required'],
            'card_cvv' => ['required', 'min:3', 'max:4'],
        ]);

        $errors = $validation['errors'];
        $data = $validation['data'];

        if (!empty($data['card_number']) && !ctype_digit((string) $data['card_number'])) {
            $errors['card_number'] = 'Card number should contain digits only.';
        }

        if (!empty($data['card_cvv']) && !ctype_digit((string) $data['card_cvv'])) {
            $errors['card_cvv'] = 'CVV should contain digits only.';
        }

        if (!empty($data['card_expiry'])) {
            $sanitised = str_replace([' ', '-'], '', (string) $data['card_expiry']);
            $parts = explode('/', $sanitised);
            if (count($parts) === 2) {
                $month = (int) $parts[0];
                $year = (int) (strlen($parts[1]) === 2 ? ('20' . $parts[1]) : $parts[1]);
                $validMonth = $month >= 1 && $month <= 12;
                if ($validMonth) {
                    $expiryTime = strtotime(sprintf('%04d-%02d-01', $year, $month) . ' +1 month');
                    if ($expiryTime !== false && $expiryTime < time()) {
                        $errors['card_expiry'] = 'Card expiry must be in the future.';
                    }
                } else {
                    $errors['card_expiry'] = 'Card expiry month is invalid.';
                }
            } else {
                $errors['card_expiry'] = 'Card expiry must be in MM/YY format.';
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect(route('studentinvoices', 'view', ['id' => $invoiceId]));
        }

        $amount = round($balanceDue, 2);
        $reference = 'MOCK-' . strtoupper(bin2hex(random_bytes(3)));

        $this->payments->create([
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => date('Y-m-d'),
            'method' => 'Card',
            'reference' => $reference,
            'notes' => 'Mock card payment recorded via student portal.',
            'recorded_by' => $this->auth->user()['id'] ?? null,
        ]);

        $updatedInvoice = $this->invoices->find($invoiceId);
        $statusLabel = 'paid';
        if ($updatedInvoice) {
            $newBalance = (float) ($updatedInvoice['balance_due'] ?? 0);
            if ($newBalance <= 0) {
                $this->invoices->setStatus($invoiceId, 'paid');
                $statusLabel = 'paid';
            } else {
                $this->invoices->setStatus($invoiceId, 'partial');
                $statusLabel = 'partial';
            }

            $userId = (int) ($this->auth->user()['id'] ?? 0);
            if ($userId) {
                $summary = 'Payment of $' . number_format($amount, 2) . ' received for invoice ' . ($updatedInvoice['invoice_number'] ?? '') . '.';
                $this->notifications->send($userId, 'Payment received', $summary, 'success');
            }

            $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_payment_student', 'invoice', $invoiceId, 'Status updated to ' . $statusLabel . '. Reference ' . $reference);
        }

        $this->flash('success', 'Payment processed successfully. A receipt has been recorded.');
        $this->redirect(route('studentinvoices', 'view', ['id' => $invoiceId]));
    }

    private function currentStudent(): ?array
    {
        $user = $this->auth->user();
        if (!$user) {
            return null;
        }
        return $this->students->findByUserId((int) ($user['id'] ?? 0));
    }
}
