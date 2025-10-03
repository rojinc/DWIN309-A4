<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\InstructorModel;
use App\Models\UserModel;
use App\Models\BranchModel;
use App\Models\ScheduleModel;
use App\Services\AuditService;

/**
 * Oversees instructor registration, profiles, and schedules.
 */
class InstructorsController extends Controller
{
    private InstructorModel $instructors;
    private UserModel $users;
    private BranchModel $branches;
    private ScheduleModel $schedules;
    private AuditService $audit;

    /**
     * Sets up instructor controller dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        $this->instructors = new InstructorModel();
        $this->users = new UserModel();
        $this->branches = new BranchModel();
        $this->schedules = new ScheduleModel();
        $this->audit = new AuditService();
    }

    /**
     * Lists instructors with headline metrics.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('instructors/index', [
            'pageTitle' => 'Instructors',
            'instructors' => $this->instructors->all(),
        ]);
    }

    /**
     * Renders the instructor creation form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('instructor_create');
        $this->render('instructors/create', [
            'pageTitle' => 'New Instructor',
            'branches' => $this->branches->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Persists a new instructor and associated user account.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('instructors', 'index'));
        }
        if (!Csrf::verify('instructor_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('instructors', 'create'));
        }
        $validation = Validation::make($_POST, [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'phone' => ['required'],
            'password' => ['required', 'min:6'],
            'branch_id' => ['required'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('instructors', 'create'));
        }
        $data = $validation['data'];
        $userId = $this->users->create([
            'role' => 'instructor',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'branch_id' => (int) $data['branch_id'],
        ]);
        $instructorId = $this->instructors->create([
            'user_id' => $userId,
            'branch_id' => (int) $data['branch_id'],
            'certification_number' => post('certification_number'),
            'accreditation_expiry' => post('accreditation_expiry'),
            'experience_years' => (int) post('experience_years', 0),
            'availability_notes' => post('availability_notes'),
            'bio' => post('bio'),
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'instructor_created', 'instructor', $instructorId);
        $this->flash('success', 'Instructor profile created.');
        $this->redirect(route('instructors', 'view', ['id' => $instructorId]));
    }

    /**
     * Shows instructor profile including schedule overview.
     */
    public function viewAction(): void
    {
        $this->requireRole(['admin', 'staff', 'instructor']);
        $id = (int) ($_GET['id'] ?? 0);
        $instructor = $this->instructors->find($id);
        if (!$instructor) {
            $this->flash('error', 'Instructor not found.');
            $this->redirect(route('instructors', 'index'));
        }
        $user = $this->auth->user();
        if ($user['role'] === 'instructor' && (int) $instructor['user_id'] !== (int) $user['id']) {
            $this->redirect(route('dashboard', 'forbidden'));
        }
        $this->render('instructors/view', [
            'pageTitle' => 'Instructor Profile',
            'instructor' => $instructor,
            'schedules' => $this->schedules->forInstructor($id),
        ]);
    }

    /**
     * Presents edit form for instructor profile.
     */
    public function editAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $instructor = $this->instructors->find($id);
        if (!$instructor) {
            $this->flash('error', 'Instructor not found.');
            $this->redirect(route('instructors', 'index'));
        }
        $token = Csrf::token('instructor_edit_' . $id);
        $this->render('instructors/edit', [
            'pageTitle' => 'Edit Instructor',
            'instructor' => $instructor,
            'branches' => $this->branches->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Updates instructor metadata.
     */
    public function updateAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('instructors', 'index'));
        }
        $id = (int) ($_GET['id'] ?? 0);
        if (!Csrf::verify('instructor_edit_' . $id, post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('instructors', 'edit', ['id' => $id]));
        }
        $this->instructors->update($id, [
            'branch_id' => (int) post('branch_id'),
            'certification_number' => post('certification_number'),
            'accreditation_expiry' => post('accreditation_expiry'),
            'experience_years' => (int) post('experience_years', 0),
            'availability_notes' => post('availability_notes'),
            'bio' => post('bio'),
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'instructor_updated', 'instructor', $id);
        $this->flash('success', 'Instructor updated successfully.');
        $this->redirect(route('instructors', 'view', ['id' => $id]));
    }
}