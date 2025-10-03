<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\VehicleModel;
use App\Models\BranchModel;
use App\Services\AuditService;

/**
 * Maintains the driving school fleet inventory and availability.
 */
class FleetController extends Controller
{
    private VehicleModel $vehicles;
    private BranchModel $branches;
    private AuditService $audit;

    /**
     * Initialises fleet dependencies.
     */
    public function __construct()
    {
        parent::__construct();
        $this->vehicles = new VehicleModel();
        $this->branches = new BranchModel();
        $this->audit = new AuditService();
    }

    /**
     * Lists all fleet vehicles.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('fleet/index', [
            'pageTitle' => 'Fleet',
            'vehicles' => $this->vehicles->all(),
        ]);
    }

    /**
     * Shows the add vehicle form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('vehicle_create');
        $this->render('fleet/create', [
            'pageTitle' => 'Add Vehicle',
            'branches' => $this->branches->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Persists a new fleet vehicle record.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('fleet', 'index'));
        }
        if (!Csrf::verify('vehicle_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('fleet', 'create'));
        }
        $validation = Validation::make($_POST, [
            'name' => ['required'],
            'plate_number' => ['required'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('fleet', 'create'));
        }
        $data = $validation['data'];
        $vehicleId = $this->vehicles->create([
            'name' => $data['name'],
            'type' => post('type'),
            'transmission' => post('transmission'),
            'plate_number' => $data['plate_number'],
            'vin' => post('vin'),
            'branch_id' => post('branch_id') ? (int) post('branch_id') : null,
            'status' => post('status', 'available'),
            'last_service_date' => post('last_service_date'),
            'next_service_due' => post('next_service_due'),
            'notes' => post('notes'),
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'vehicle_created', 'vehicle', $vehicleId);
        $this->flash('success', 'Vehicle added to fleet.');
        $this->redirect(route('fleet', 'index'));
    }
}