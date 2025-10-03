<?php
namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\BranchModel;
use App\Models\CourseModel;
use App\Models\DocumentModel;
use App\Models\EnrollmentModel;
use App\Models\InvoiceModel;
use App\Models\NoteModel;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OutboundMessageService;
use App\Services\ReminderService;

/**
 * Manages student lifecycle operations including enrolments and documents.
 */
class StudentsController extends Controller
{
    private StudentModel $students;
    private UserModel $users;
    private BranchModel $branches;
    private CourseModel $courses;
    private EnrollmentModel $enrollments;
    private InvoiceModel $invoices;
    private DocumentModel $documents;
    private NoteModel $notes;
    private ScheduleModel $schedules;
    private InstructorModel $instructors;
    private AuditService $audit;
    private NotificationService $notifications;
    private ReminderService $reminders;
    private OutboundMessageService $outbound;

    public function __construct()
    {
        parent::__construct();
        $this->students = new StudentModel();
        $this->users = new UserModel();
        $this->branches = new BranchModel();
        $this->courses = new CourseModel();
        $this->enrollments = new EnrollmentModel();
        $this->invoices = new InvoiceModel();
        $this->documents = new DocumentModel();
        $this->notes = new NoteModel();
        $this->schedules = new ScheduleModel();
        ->instructors = new InstructorModel();
        $this->audit = new AuditService();
        $this->notifications = new NotificationService();
        $this->reminders = new ReminderService();
        $this->outbound = new OutboundMessageService();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $user = $this->auth->user();
        $role = $user['role'] ?? null;
        $term = trim((string) ($_GET['q'] ?? ''));
        if ($role === 'instructor') {
            $instructor = $this->instructors->findByUserId((int) ($user['id'] ?? 0));
            $instructorId = (int) ($instructor['id'] ?? 0);
            $students = $instructorId > 0
                ? $this->students->forInstructor($instructorId, $term === '' ? null : $term)
                : [];
        } else {
            $students = $term === '' ? $this->students->all() : $this->students->search($term);
        }
        $this->render('students/index', [
            'pageTitle' => 'Students',
            'students' => $students,
            'search' => $term,
        ]);
    }
        \->render('students/index', [
            'pageTitle' => 'Students',
            'students' => \,
            'search' => \,
        ]);
    }
        \->render('students/index', [
            'pageTitle' => 'Students',
            'students' => \,
            'search' => \,
        ]);
    }

    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('student_create');
        $this->render('students/create', [
            'pageTitle' => 'New Student',
            'branches' => $this->branches->all(),
            'courses' => $this->courses->all(),
            'csrfToken' => $token,
        ]);
    }

    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('students', 'index'));
        }
        if (!Csrf::verify('student_create', post('csrf_token'))) {
            $this->flash('error', 'Security check failed.');
            $this->redirect(route('students', 'create'));
        }

        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'phone' => ['required'],
            'password' => ['required', 'min:6'],
            'branch_id' => ['required'],
            'course_id' => ['required'],
            'start_date' => ['required', 'date'],
        ];
        $validation = Validation::make($_POST, $rules);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('students', 'create'));
        }
        $data = $validation['data'];
        $course = $this->courses->find((int) $data['course_id']);
        if (!$course) {
            $this->flash('error', 'Selected course was not found.');
            $this->redirect(route('students', 'create'));
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $userId = $this->users->create([
                'role' => 'student',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
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
                'progress_summary' => 'Enrolled and awaiting first lesson.'
            ]);

            $enrollmentId = $this->enrollments->create([
                'student_id' => $studentId,
                'course_id' => (int) $data['course_id'],
                'start_date' => $data['start_date'],
                'status' => 'active',
                'progress_percentage' => 0,
                'notes' => post('notes'),
            ]);

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string) $studentId, 4, '0', STR_PAD_LEFT);
            $subtotal = (float) $course['price'];
            $tax = round($subtotal * 0.1, 2);
            $total = $subtotal + $tax;
            $invoiceId = $this->invoices->create([
                'enrollment_id' => $enrollmentId,
                'invoice_number' => $invoiceNumber,
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $total,
                'status' => 'sent',
                'notes' => 'Auto-generated upon enrolment.'
            ], [
                [
                    'description' => $course['title'] . ' Course Fee',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'total' => $subtotal,
                ],
            ]);

            $this->audit->log($this->auth->user()['id'] ?? null, 'student_created', 'student', $studentId, 'Enrollment #' . $enrollmentId . ', Invoice #' . $invoiceNumber);
            $this->notifications->send($userId, 'Welcome to Origin Driving School', 'Your enrolment has been created and your first lesson will be scheduled shortly.');
            $this->reminders->queue([
                'related_type' => 'invoice',
                'related_id' => $invoiceId,
                'recipient_user_id' => $userId,
                'channel' => 'email',
                'reminder_type' => 'Invoice Due',
                'message' => 'Your invoice ' . $invoiceNumber . ' is due on ' . date('d M Y', strtotime('+7 days')) . '.',
                'send_on' => date('Y-m-d', strtotime('+5 days')),
                'status' => 'pending',
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            $this->flash('error', 'Failed to create student: ' . $exception->getMessage());
            $this->redirect(route('students', 'create'));
        }

        $invoice = $this->invoices->find($invoiceId ?? 0);
        if ($invoice) {
            $this->sendInvoiceIssuedEmail($invoice);
        }

        $this->flash('success', 'Student registered successfully.');
        $this->redirect(route('students', 'view', ['id' => $studentId]));
    }

    public function viewAction(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $student = $this->students->find($id);
        if (!$student) {
            $this->flash('error', 'Student not found.');
            $this->redirect(route('students', 'index'));
        }
        $user = $this->auth->user();
        if ($user['role'] === 'student' && (int) $student['user_id'] !== (int) $user['id']) {
            $this->redirect(route('dashboard', 'forbidden'));
        }

        $enrollments = $this->enrollments->forStudent($id);
        $invoices = [];
        foreach ($enrollments as $enrollment) {
            $invoice = $this->invoices->findByEnrollment((int) $enrollment['id']);
            if ($invoice) {
                $invoices[] = $invoice;
            }
        }
        $schedules = $this->schedules->forStudent($id);
        $documents = $this->documents->forUser((int) $student['user_id']);
        $notes = $this->notes->for('student', $id);

        $canUpload = in_array($user['role'], ['admin', 'staff'], true);
        $canManageNotes = in_array($user['role'], ['admin', 'staff', 'instructor'], true);

        $this->render('students/view', [
            'pageTitle' => 'Student Profile',
            'student' => $student,
            'enrollments' => $enrollments,
            'invoices' => $invoices,
            'schedules' => $schedules,
            'documents' => $documents,
            'notes' => $notes,
            'csrfToken' => Csrf::token('student_note_' . $id),
            'uploadToken' => Csrf::token('student_upload_' . $id),
            'canUploadDocuments' => $canUpload,
            'canManageNotes' => $canManageNotes,
        ]);
    }

    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $student = $this->students->find($id);
        if (!$student) {
            $this->flash('error', 'Student not found.');
            $this->redirect(route('students', 'index'));
        }
        $this->render('students/edit', [
            'pageTitle' => 'Edit Student',
            'student' => $student,
            'branches' => $this->branches->all(),
            'csrfToken' => Csrf::token('student_edit_' . $id),
        ]);
    }

    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('students', 'index'));
        }
        $id = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('student_edit_' . $id, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('students', 'edit', ['id' => $id]));
        }
        $validation = Validation::make($_POST, [
            'branch_id' => ['required'],
            'license_status' => ['required'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('students', 'edit', ['id' => $id]));
        }
        $data = $validation['data'];
        $updated = $this->students->update($id, [
            'branch_id' => (int) $data['branch_id'],
            'license_number' => post('license_number'),
            'license_status' => $data['license_status'],
            'license_expiry' => post('license_expiry'),
            'emergency_contact_name' => post('emergency_contact_name'),
            'emergency_contact_phone' => post('emergency_contact_phone'),
            'address_line' => post('address_line'),
            'city' => post('city'),
            'postcode' => post('postcode'),
            'progress_summary' => post('progress_summary'),
        ]);
        if ($updated) {
            $this->audit->log($this->auth->user()['id'] ?? null, 'student_updated', 'student', $id, 'Profile updated.');
            $this->flash('success', 'Student details updated.');
        }
        $this->redirect(route('students', 'view', ['id' => $id]));
    }

    public function noteAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('students', 'index'));
        }
        $studentId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('student_note_' . $studentId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        $content = trim((string) post('content'));
        if ($content === '') {
            $this->flash('error', 'Note content cannot be empty.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        $this->notes->create([
            'related_type' => 'student',
            'related_id' => $studentId,
            'author_user_id' => $this->auth->user()['id'] ?? null,
            'content' => $content,
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'student_note_added', 'student', $studentId, 'Note added to profile.');
        $this->flash('success', 'Note added successfully.');
        $this->redirect(route('students', 'view', ['id' => $studentId]));
    }

    public function uploadAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('students', 'index'));
        }
        $studentId = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('student_upload_' . $studentId, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please choose a valid file to upload.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        $file = $_FILES['document'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = Config::get('app.allowed_upload_types', []);
        if (!in_array($extension, $allowed, true)) {
            $this->flash('error', 'Unsupported file type. Allowed: ' . implode(', ', $allowed));
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        if ($file['size'] > Config::get('app.max_upload_size')) {
            $this->flash('error', 'File exceeds the maximum allowed size.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        $targetDir = Config::get('app.upload_dir');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $uniqueName = uniqid('doc_', true) . '.' . $extension;
        $targetPath = $targetDir . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->flash('error', 'Failed to store the uploaded file.');
            $this->redirect(route('students', 'view', ['id' => $studentId]));
        }
        $student = $this->students->find($studentId);
        if ($student) {
            $this->documents->create([
                'user_id' => $student['user_id'],
                'file_name' => basename($file['name']),
                'file_path' => $uniqueName,
                'mime_type' => $file['type'] ?? 'application/octet-stream',
                'file_size' => (int) $file['size'],
                'category' => post('category'),
                'notes' => post('description'),
            ]);
            $this->audit->log($this->auth->user()['id'] ?? null, 'student_document_uploaded', 'student', $studentId, basename($file['name']));
            $this->flash('success', 'Document uploaded successfully.');
        }
        $this->redirect(route('students', 'view', ['id' => $studentId]));
    }

    private function sendInvoiceIssuedEmail(array $invoice): void
    {
        $subject = 'Invoice ' . ($invoice['invoice_number'] ?? '') . ' issued';
        $body = "Dear " . ($invoice['student_name'] ?? 'student') . ",\n\nA new invoice has been issued.\n" . $this->buildInvoiceSummary($invoice) . "\n\nPlease contact us if you have any questions.";
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
        return implode("\n", [
            'Invoice number: ' . ($invoice['invoice_number'] ?? ''),
            'Total: $' . number_format($invoice['total'] ?? 0, 2),
            'Balance due: $' . number_format($invoice['balance_due'] ?? 0, 2),
            'Due date: ' . date('d M Y', strtotime($invoice['due_date'] ?? date('Y-m-d'))),
        ]);
    }
}





