<?php
namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\DocumentModel;
use App\Models\InstructorModel;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Services\AuditService;

/**
 * Provides secure streaming access to uploaded documents.
 */
class DocumentsController extends Controller
{
    private DocumentModel $documents;
    private StudentModel $students;
    private InstructorModel $instructors;
    private ScheduleModel $schedules;
    private AuditService $audit;

    public function __construct()
    {
        parent::__construct();
        $this->documents = new DocumentModel();
        $this->students = new StudentModel();
        $this->instructors = new InstructorModel();
        $this->schedules = new ScheduleModel();
        $this->audit = new AuditService();
    }

    /**
     * Streams a document to authorised users.
     */
    public function downloadAction(): void
    {
        $this->requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('error', 'Invalid document request.');
            $this->redirect(route('dashboard'));
        }

        $document = $this->documents->find($id);
        if (!$document) {
            $this->flash('error', 'Document not found.');
            $this->redirect(route('dashboard'));
        }

        $user = $this->auth->user();
        if (!$this->canAccessDocument($user, $document)) {
            $this->flash('error', 'You are not authorised to access this document.');
            $this->redirect(route('dashboard', 'forbidden'));
        }

        $configuredDir = (string) Config::get('app.upload_dir');
        $baseDir = rtrim($configuredDir, '/\\') . DIRECTORY_SEPARATOR;
        $baseRealPath = realpath($baseDir) ?: $baseDir;
        $fileRealPath = realpath($baseDir . $document['file_path']);

        if ($fileRealPath === false || strncmp($fileRealPath, $baseRealPath, strlen($baseRealPath)) !== 0 || !is_file($fileRealPath)) {
            $this->flash('error', 'File is no longer available.');
            $this->redirect(route('dashboard'));
        }

        $this->audit->log($user['id'] ?? null, 'document_downloaded', 'document', (int) $document['id'], $document['file_name'] ?? null);

        header('Content-Type: ' . ($document['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($document['file_name'] ?? $document['file_path']) . '"');
        header('Content-Length: ' . (int) filesize($fileRealPath));
        header('X-Content-Type-Options: nosniff');
        readfile($fileRealPath);
        exit;
    }

    /**
     * Determines whether the current user can view a document.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $document
     */
    private function canAccessDocument(array $user, array $document): bool
    {
        $role = $user['role'] ?? null;
        $currentUserId = (int) ($user['id'] ?? 0);
        $ownerUserId = (int) ($document['user_id'] ?? 0);

        if (in_array($role, ['admin', 'staff'], true)) {
            return true;
        }

        if ($currentUserId !== 0 && $currentUserId === $ownerUserId) {
            return true;
        }

        if ($role === 'student') {
            return $currentUserId !== 0 && $currentUserId === $ownerUserId;
        }

        if ($role === 'instructor') {
            $student = $this->students->findByUserId($ownerUserId);
            if (!$student) {
                return false;
            }

            $instructor = $this->instructors->findByUserId($currentUserId);
            if (!$instructor) {
                return false;
            }

            return $this->schedules->instructorHasStudent((int) $instructor['id'], (int) $student['id']);
        }

        return false;
    }
}
