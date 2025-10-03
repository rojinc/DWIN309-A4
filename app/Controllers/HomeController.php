<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Renders the public-facing home page with organisational overview.
 */
class HomeController extends Controller
{
    /**
     * Displays the marketing home page.
     */
    public function indexAction(): void
    {
        $this->render('home/index', [
            'pageTitle' => 'Origin Driving School Management System',
        ], 'public');
    }
}