<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\EnrollmentRequestModel;
use App\Models\CourseModel;
use App\Models\BranchModel;
use App\Models\InstructorModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\EnrollmentModel;
use App\Models\InvoiceModel;
use App\Services\NotificationService;
use App\Services\AuditService;
use App\Services\ReminderService;
use App\Services\OutboundMessageService;

/**
 * Manages the lifecycle for self-service enrollment requests.
 */
class EnrollmentRequestsController extends Controller
{
    private EnrollmentRequestModel $requests;
    private CourseModel $courses;
    private BranchModel $branches;
    private InstructorModel $instructors;
    private StudentModel $students;
    private UserModel $users;
    private EnrollmentModel $enrollments;
    private InvoiceModel $invoices;
    private NotificationService $notifications;
    private AuditService $audit;
    private ReminderService $reminders;
    private OutboundMessageService $outbound;

    public function __construct()
    {
        parent::__construct();
        $this->requests = new EnrollmentRequestModel();
        $this->courses = new CourseModel();
        $this->branches = new BranchModel();
        $this->instructors = new InstructorModel();
        $this->students = new StudentModel();
        $this->users = new UserModel();
        $this->enrollments = new EnrollmentModel();
        $this->invoices = new InvoiceModel();
        $this->notifications = new NotificationService();
        $this->audit = new AuditService();
        $this->reminders = new ReminderService();
        $this->outbound = new OutboundMessageService();
    }

    /**
     * Public enrollment request form and submission handler.
     */
    public function applyAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify('enrollment_apply', post('csrf_token'))) {
                $this->flash('error', 'Security check failed. Please try again.');
                $this->redirect(route('enrollmentrequests', 'apply'));
            }

            $validation = Validation::make($_POST, [
                'first_name' => ['required'],
                'last_name' => ['required'],
                'email' => ['required', 'email'],
                'phone' => ['required'],
                'password' => ['required', 'min:6'],
                'branch_id' => ['required'],
                'course_id' => ['required'],
                'preferred_date' => ['date'],
                'preferred_time' => ['time'],
                'emergency_contact_name' => ['required'],
                'emergency_contact_phone' => ['required'],
                'agree_terms' => ['required'],
            ]);

            $errors = $validation['errors'];
            $data = $validation['data'];

            if (isset($errors['agree_terms'])) {
                $errors['agree_terms'] = 'Please confirm you agree to be contacted about your enrolment.';
            }
            unset($data['agree_terms']);

            if ((string) ($data['password'] ?? '') !== (string) post('password_confirmation')) {
                $errors['password'] = 'Password confirmation does not match.';
            }

            if (!empty($data['preferred_date']) && strtotime((string) $data['preferred_date']) < strtotime(date('Y-m-d'))) {
                $errors['preferred_date'] = 'Please choose a start date from today onwards.';
            }

            if ($this->users->findByEmail((string) ($data['email'] ?? ''))) {
                $errors['email'] = 'An account with this email already exists.';
            }

            $course = null;
            if (empty($errors)) {
                $course = $this->courses->find((int) $data['course_id']);
                if (!$course) {
                    $errors['course_id'] = 'Selected course was not found.';
                }
            }

            $instructorId = post('instructor_id');
            if (empty($errors) && $instructorId !== null && $instructorId !== '') {
                if (!$this->instructors->find((int) $instructorId)) {
                    $errors['instructor_id'] = 'Selected instructor was not found.';
                }
            }

            if (!empty($errors)) {
                $this->flash('error', implode(' ', $errors));
                $this->redirect(route('enrollmentrequests', 'apply'));
            }

            $db = Database::connection();
            $db->beginTransaction();
            try {
                $userId = $this->users->create([
                    'role' => 'student',
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
                    'status' => 'active',
                    'branch_id' => (int) $data['branch_id'],
                ]);

                $studentId = $this->students->create([
                    'user_id' => $userId,
                    'branch_id' => (int) $data['branch_id'],
                    'license_number' => post('license_number'),
                    'license_status' => post('license_status', 'Learner'),
                    'license_expiry' => post('license_expiry'),
                    'emergency_contact_name' => post('emergency_contact_name'),
                    'emergency_contact_phone' => post('emergency_contact_phone'),
                    'address_line' => post('address_line'),
                    'city' => post('city'),
                    'postcode' => post('postcode'),
                    'progress_summary' => 'Enrollment request pending approval.'
                ]);

                $this->requests->create([
                    'student_id' => $studentId,
                    'course_id' => (int) $data['course_id'],
                    'preferred_date' => $data['preferred_date'] ?? null,
                    'preferred_time' => $data['preferred_time'] ?? null,
                    'status' => 'pending',
                    'instructor_id' => $instructorId !== null && $instructorId !== '' ? (int) $instructorId : null,
                    'student_notes' => post('student_notes'),
                ]);

                $db->commit();
            } catch (\Throwable $exception) {
                $db->rollBack();
                $this->flash('error', 'Unable to submit request: ' . $exception->getMessage());
                $this->redirect(route('enrollmentrequests', 'apply'));
            }

            $this->audit->log(null, 'enrollment_request_created', 'enrollment_request', 0, $data['email'] ?? '');
            $this->auth->attempt((string) $data['email'], (string) $data['password']);
            $this->flash('success', 'Thank you for your request. Our team will review it shortly.');
            $this->redirect(route('dashboard', 'index'));
        }

        $this->render('enrollmentrequests/apply', [
            'pageTitle' => 'Enrollment Request',
            'courses' => $this->courses->all(),
            'branches' => $this->branches->all(),
            'instructors' => $this->instructors->all(),
            'csrfToken' => Csrf::token('enrollment_apply'),
        ], 'public');
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string) $_GET['status'] : null;
        $user = $this->auth->user();
        $role = $user['role'] ?? '';

        if ($role === 'instructor') {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0);
            $requests = $instructorId > 0 ? $this->requests->forInstructor($instructorId, $status) : [];
        } else {
            $requests = $this->requests->all($status);
        }

        $this->render('enrollmentrequests/index', [
            'pageTitle' => 'Enrollment Requests',
            'requests' => $requests,
            'statusFilter' => $status,
        ]);
    }

    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $id = (int) ($_GET['id'] ?? 0);
        $request = $this->requests->find($id);
        if (!$request) {
            $this->flash('error', 'Enrollment request not found.');
            $this->redirect(route('enrollmentrequests', 'index'));
        }

        $user = $this->auth->user();
        if (($user['role'] ?? '') === 'instructor') {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0);
            $assigned = (int) ($request['instructor_id'] ?? 0);
            if ($instructorId <= 0 || ($assigned !== 0 && $assigned !== $instructorId)) {
                $this->redirect(route('dashboard', 'forbidden'));
            }
        }

        $this->render('enrollmentrequests/view', [
            'pageTitle' => 'Review Enrollment Request',
            'request' => $request,
            'courses' => $this->courses->all(),
            'instructors' => $this->instructors->all(),
            'decisionToken' => Csrf::token('enrollment_decide_' . $id),
        ]);
    }

    public function decideAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('enrollmentrequests', 'index'));
        }

        $id = (int) ($_GET['id'] ?? 0);
        $request = $this->requests->find($id);
        if (!$request) {
            $this->flash('error', 'Enrollment request not found.');
            $this->redirect(route('enrollmentrequests', 'index'));
        }

        if (!Csrf::verify('enrollment_decide_' . $id, post('csrf_token'))) {
            $this->flash('error', 'Security check failed.');
            $this->redirect(route('enrollmentrequests', 'view', ['id' => $id]));
        }

        $status = (string) post('status');
        if (!in_array($status, ['approved', 'declined'], true)) {
            $this->flash('error', 'Invalid decision status.');
            $this->redirect(route('enrollmentrequests', 'view', ['id' => $id]));
        }

        $user = $this->auth->user();
        $role = $user['role'] ?? '';
        $decisionUserId = (int) ($user['id'] ?? 0);

        $instructorId = null;
        if ($role === 'instructor') {
            $instructor = $this->instructors->findByUserId($decisionUserId);
            $instructorId = (int) ($instructor['id'] ?? 0);
            if ($instructorId <= 0 || (int) ($request['instructor_id'] ?? 0) !== $instructorId) {
                $this->redirect(route('dashboard', 'forbidden'));
            }
        } else {
            $postedInstructor = post('instructor_id');
            if ($postedInstructor !== null && $postedInstructor !== '') {
                $targetInstructor = $this->instructors->find((int) $postedInstructor);
                if (!$targetInstructor) {
                    $this->flash('error', 'Selected instructor was not found.');
                    $this->redirect(route('enrollmentrequests', 'view', ['id' => $id]));
                }
                $instructorId = (int) $targetInstructor['id'];
            }
        }

        if ($status === 'approved' && !$instructorId) {
            $this->flash('error', 'Please select an instructor before approval.');
            $this->redirect(route('enrollmentrequests', 'view', ['id' => $id]));
        }

        $adminNotes = trim((string) post('admin_notes'));
        $decisionAt = date('Y-m-d H:i:s');

        $this->requests->updateStatus($id, [
            'status' => $status,
            'instructor_id' => $instructorId,
            'admin_notes' => $adminNotes,
            'decision_by' => $decisionUserId,
            'decision_at' => $decisionAt,
        ]);

        $student = $this->students->find((int) $request['student_id']);
        $studentUserId = (int) ($student['user_id'] ?? 0);
        $courseTitle = $request['course_title'] ?? '';

        if ($status === 'approved') {
            $enrollmentId = $this->ensureEnrollment((int) $request['student_id'], (int) $request['course_id'], $request['preferred_date'] ?? null, $id);
            if ($student) {
                $this->students->update((int) $request['student_id'], [
                    'branch_id' => $student['branch_id'] ?? null,
                    'license_number' => $student['license_number'] ?? null,
                    'license_status' => $student['license_status'] ?? 'Learner',
                    'license_expiry' => $student['license_expiry'] ?? null,
                    'emergency_contact_name' => $student['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $student['emergency_contact_phone'] ?? null,
                    'address_line' => $student['address_line'] ?? null,
                    'city' => $student['city'] ?? null,
                    'postcode' => $student['postcode'] ?? null,
                    'progress_summary' => 'Enrollment approved on ' . date('d M Y'),
                ]);
            }
            if ($studentUserId) {
                $message = 'Great news! Your enrollment request for ' . $courseTitle . ' has been approved.';
                $this->notifications->send($studentUserId, 'Enrollment Approved', $message, 'success');
            }
            $this->generateInvoiceForEnrollment($enrollmentId, $request, $studentUserId);
        } else {
            if ($student && $studentUserId) {
                $this->students->update((int) $request['student_id'], [
                    'branch_id' => $student['branch_id'] ?? null,
                    'license_number' => $student['license_number'] ?? null,
                    'license_status' => $student['license_status'] ?? 'Learner',
                    'license_expiry' => $student['license_expiry'] ?? null,
                    'emergency_contact_name' => $student['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $student['emergency_contact_phone'] ?? null,
                    'address_line' => $student['address_line'] ?? null,
                    'city' => $student['city'] ?? null,
                    'postcode' => $student['postcode'] ?? null,
                    'progress_summary' => 'Enrollment request declined on ' . date('d M Y'),
                ]);
                $message = 'Your enrollment request for ' . $courseTitle . ' has been declined.';
                if ($adminNotes !== '') {
                    $message .= ' Notes: ' . $adminNotes;
                }
                $this->notifications->send($studentUserId, 'Enrollment Declined', $message, 'warning');
            }
        }

        $this->audit->log($decisionUserId, 'enrollment_request_' . $status, 'enrollment_request', $id, $adminNotes);
        $this->flash('success', 'Enrollment request updated successfully.');
        $this->redirect(route('enrollmentrequests', 'view', ['id' => $id]));
    }

    private function ensureEnrollment(int $studentId, int $courseId, ?string $preferredDate, int $requestId): int
    {
        $existing = $this->enrollments->findByStudentAndCourse($studentId, $courseId);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->enrollments->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'start_date' => $preferredDate ?: date('Y-m-d'),
            'status' => 'active',
            'progress_percentage' => 0,
            'notes' => 'Approved from enrollment request #' . $requestId,
        ]);
    }

    private function generateInvoiceForEnrollment(int $enrollmentId, array $request, int $studentUserId): void
    {
        if ($enrollmentId <= 0) {
            return;
        }

        $existing = $this->invoices->findByEnrollment($enrollmentId);
        if ($existing) {
            return;
        }

        $course = $this->courses->find((int) ($request['course_id'] ?? 0));
        $price = (float) ($course['price'] ?? 0);
        $subtotal = $price;
        $tax = round($subtotal * 0.1, 2);
        $total = $subtotal + $tax;
        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+7 days'));
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string) $enrollmentId, 4, '0', STR_PAD_LEFT);

        $invoiceId = $this->invoices->create([
            'enrollment_id' => $enrollmentId,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $total,
            'status' => 'sent',
            'notes' => 'Generated from enrollment approval.',
        ], [[
            'description' => ($course['title'] ?? 'Course') . ' Fee',
            'quantity' => 1,
            'unit_price' => $subtotal,
            'total' => $subtotal,
        ]]);

        $this->audit->log($this->auth->user()['id'] ?? null, 'invoice_created', 'invoice', $invoiceId, 'Created from enrollment request approval.');

        if ($studentUserId) {
            $message = implode("\n", [
                'Invoice ' . $invoiceNumber . ' issued for ' . ($course['title'] ?? 'course') . '.',
                'Total: $' . number_format($total, 2),
                'Due: ' . date('d M Y', strtotime($dueDate)),
            ]);
            $this->notifications->send($studentUserId, 'Invoice issued', $message, 'info');

            $this->reminders->queue([
                'related_type' => 'invoice',
                'related_id' => $invoiceId,
                'recipient_user_id' => $studentUserId,
                'channel' => 'email',
                'reminder_type' => 'Invoice Due',
                'message' => 'Your invoice ' . $invoiceNumber . ' is due on ' . date('d M Y', strtotime($dueDate)) . '.',
                'send_on' => date('Y-m-d', strtotime('+5 days')),
                'status' => 'pending',
            ]);
        }

        $invoice = $this->invoices->find($invoiceId);
        if ($invoice) {
            $this->sendInvoiceIssuedEmail($invoice);
        }
    }

    private function sendInvoiceIssuedEmail(array $invoice): void
    {
        $email = $invoice['student_email'] ?? '';
        if ($email === '') {
            return;
        }
        $subject = 'Invoice ' . ($invoice['invoice_number'] ?? '') . ' issued';
        $body = "Dear " . ($invoice['student_name'] ?? 'student') . ",\n\nA new invoice has been issued.\n" . $this->buildInvoiceSummary($invoice) . "\n\nPlease contact us if you have any questions.";
        $this->outbound->sendEmail($email, $subject, $body);
    }

    private function buildInvoiceSummary(array $invoice): string
    {
        return implode("\n", [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ]);
    }
}
