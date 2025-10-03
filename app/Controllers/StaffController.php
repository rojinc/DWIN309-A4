<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\StaffModel;
use App\Models\UserModel;
use App\Models\BranchModel;
use App\Services\AuditService;

/**
 * Oversees staff account creation and management.
 */
class StaffController extends Controller
{
    private StaffModel $staff;
    private UserModel $users;
    private BranchModel $branches;
    private AuditService $audit;

    /**
     * Prepares staff controller dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        $this->staff = new StaffModel();
        $this->users = new UserModel();
        $this->branches = new BranchModel();
        $this->audit = new AuditService();
    }

    /**
     * Lists staff members.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin']);
        $this->render('staff/index', [
            'pageTitle' => 'Staff',
            'staff' => $this->staff->all(),
        ]);
    }

    /**
     * Displays staff creation form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin']);
        $token = Csrf::token('staff_create');
        $this->render('staff/create', [
            'pageTitle' => 'New Staff Member',
            'branches' => $this->branches->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Stores a new staff record with login credentials.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('staff', 'index'));
        }
        if (!Csrf::verify('staff_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('staff', 'create'));
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
            $this->redirect(route('staff', 'create'));
        }
        $data = $validation['data'];
        $userId = $this->users->create([
            'role' => 'staff',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'branch_id' => (int) $data['branch_id'],
        ]);
        $staffId = $this->staff->create([
            'user_id' => $userId,
            'branch_id' => (int) $data['branch_id'],
            'position_title' => post('position_title'),
            'employment_type' => post('employment_type', 'Full-time'),
            'start_date' => post('start_date') ?: date('Y-m-d'),
            'notes' => post('notes'),
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'staff_created', 'staff', $staffId);
        $this->flash('success', 'Staff account created successfully.');
        $this->redirect(route('staff', 'index'));
    }
}