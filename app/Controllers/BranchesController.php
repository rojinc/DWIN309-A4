<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\BranchModel;
use App\Services\AuditService;

/**
 * Handles creation and maintenance of branch locations.
 */
class BranchesController extends Controller
{
    private BranchModel $branches;
    private AuditService $audit;

    /**
     * Sets up branch controller dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        $this->branches = new BranchModel();
        $this->audit = new AuditService();
    }

    /**
     * Lists all branches available to the organisation.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('branches/index', [
            'pageTitle' => 'Branches',
            'branches' => $this->branches->all(),
        ]);
    }

    /**
     * Displays branch creation form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin']);
        $token = Csrf::token('branch_create');
        $this->render('branches/create', [
            'pageTitle' => 'New Branch',
            'csrfToken' => $token,
        ]);
    }

    /**
     * Stores a new branch record.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('branches', 'index'));
        }
        if (!Csrf::verify('branch_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('branches', 'create'));
        }
        $validation = Validation::make($_POST, [
            'name' => ['required'],
            'email' => ['required', 'email'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('branches', 'create'));
        }
        $data = $validation['data'];
        $branchId = $this->branches->create([
            'name' => $data['name'],
            'address' => post('address'),
            'city' => post('city'),
            'state' => post('state'),
            'postcode' => post('postcode'),
            'phone' => post('phone'),
            'email' => $data['email'],
            'manager_name' => post('manager_name'),
            'opening_hours' => post('opening_hours'),
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'branch_created', 'branch', $branchId);
        $this->flash('success', 'Branch created successfully.');
        $this->redirect(route('branches', 'index'));
    }
}