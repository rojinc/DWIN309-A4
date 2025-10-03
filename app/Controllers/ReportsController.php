<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReportModel;
use App\Models\StudentModel;
use App\Models\InstructorModel;

/**
 * Provides analytical views and CSV exports.
 */
class ReportsController extends Controller
{
    private ReportModel $reports;
    private StudentModel $students;
    private InstructorModel $instructors;

    public function __construct()
    {
        parent::__construct();
        $this->reports = new ReportModel();
        $this->students = new StudentModel();
        $this->instructors = new InstructorModel();
    }

    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('reports/index', [
            'pageTitle' => 'Reports & Analytics',
            'summary' => $this->reports->dashboardSummary(),
            'revenueSeries' => $this->reports->monthlyRevenue(12),
            'studentStats' => $this->students->progressStats(),
            'retention' => $this->reports->retentionStats(),
            'courseRevenue' => $this->reports->revenueByCourse(),
            'instructorPerformance' => $this->reports->instructorPerformance(),
            'progressBreakdown' => $this->reports->studentProgressBreakdown(),
        ]);
    }

    public function exportAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $type = $_GET['type'] ?? 'revenue_monthly';
        $filename = 'report_' . $type . '_' . date('Ymd') . '.csv';
        $dataset = [];
        switch ($type) {
            case 'revenue_course':
                $dataset = $this->reports->revenueByCourse();
                break;
            case 'instructors':
                $dataset = $this->reports->instructorPerformance();
                break;
            case 'retention':
                $stats = $this->reports->retentionStats();
                $dataset = [
                    [
                        'total_enrollments' => $stats['total_enrollments'],
                        'completed' => $stats['completed'],
                        'active' => $stats['active'],
                    ],
                ];
                break;
            case 'progress':
                $dataset = $this->reports->studentProgressBreakdown();
                break;
            case 'revenue_monthly':
            default:
                $dataset = $this->reports->monthlyRevenue(12);
                break;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        if (!empty($dataset)) {
            fputcsv($out, array_keys($dataset[0]));
            foreach ($dataset as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }
}