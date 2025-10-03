<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Csrf;
use App\Helpers\Validation;
use App\Models\CourseModel;
use App\Models\InstructorModel;
use App\Services\AuditService;

/**
 * Maintains the course catalogue and instructor assignments.
 */
class CoursesController extends Controller
{
    private CourseModel $courses;
    private InstructorModel $instructors;
    private AuditService $audit;

    /**
     * Wires required data access layers.
     */
    public function __construct()
    {
        parent::__construct();
        $this->courses = new CourseModel();
        $this->instructors = new InstructorModel();
        $this->audit = new AuditService();
    }

    /**
     * Lists all courses with active student counts.
     */
    public function indexAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $this->render('courses/index', [
            'pageTitle' => 'Courses',
            'courses' => $this->courses->all(),
        ]);
    }

    /**
     * Shows course creation form.
     */
    public function createAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        $token = Csrf::token('course_create');
        $this->render('courses/create', [
            'pageTitle' => 'New Course',
            'instructors' => $this->instructors->all(),
            'csrfToken' => $token,
        ]);
    }

    /**
     * Persists a new course entry.
     */
    public function storeAction(): void
    {
        $this->requireRole(['admin', 'staff']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(route('courses', 'index'));
        }
        if (!Csrf::verify('course_create', post('csrf_token'))) {
            $this->flash('error', 'Security token mismatch.');
            $this->redirect(route('courses', 'create'));
        }
        $validation = Validation::make($_POST, [
            'title' => ['required'],
            'price' => ['required', 'numeric'],
            'lesson_count' => ['required', 'numeric'],
        ]);
        if ($validation['errors']) {
            $this->flash('error', implode(' ', $validation['errors']));
            $this->redirect(route('courses', 'create'));
        }
        $data = $validation['data'];
        $courseId = $this->courses->create([
            'title' => $data['title'],
            'description' => post('description'),
            'price' => (float) $data['price'],
            'lesson_count' => (int) $data['lesson_count'],
            'category' => post('category'),
            'status' => post('status', 'active'),
            'instructor_ids' => $_POST['instructor_ids'] ?? [],
        ]);
        $this->audit->log($this->auth->user()['id'] ?? null, 'course_created', 'course', $courseId);
        $this->flash('success', 'Course created successfully.');
        $this->redirect(route('courses', 'index'));
    }
}