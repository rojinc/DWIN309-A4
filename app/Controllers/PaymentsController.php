<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PaymentModel;

/**
 * Provides a consolidated view of all payments received.
 */
class PaymentsController extends Controller
{
    private PaymentModel $payments;

    /**
     * Prepares the payments model.
     */
    public function __construct()
    {
        parent::__construct();
        $this->payments = new PaymentModel();
    }

    /**
     * Displays the payments list.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('payments/index', [
            'pageTitle' => 'Payments',
            'payments' => $this->payments->all(),
        ]);
    }
}