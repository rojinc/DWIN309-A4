<?php
use App\Services\AuthService;

$auth = new AuthService();
$user = $auth->user();
$monthDate = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$prev = (clone $monthDate)->modify('-1 month');
$next = (clone $monthDate)->modify('+1 month');
$students = $students ?? [];
$courses = $courses ?? [];
$instructors = $instructors ?? [];
$vehicles = $vehicles ?? [];
$branches = $branches ?? [];
?>
<section class="card schedule-dashboard"
        data-year="<?= e($year); ?>"
        data-month="<?= e($month); ?>"
        data-events-endpoint="<?= e(route('apischedules', 'events')); ?>"
        data-conflict-endpoint="<?= e(route('apischedules', 'checkConflict')); ?>"
        data-create-endpoint="<?= e(route('apischedules', 'create')); ?>"
        data-can-manage="<?= $canManage ? '1' : '0'; ?>"
        data-csrf="<?= e($csrfAjaxToken ?? ''); ?>">
    <div class="card-header schedule-header">
        <div>
            <h1>Scheduling Calendar</h1>
            <p><?= e($monthDate->format('F Y')); ?></p>
        </div>
        <div class="actions">
            <button class="button button-secondary schedule-nav"
                    data-year="<?= e($prev->format('Y')); ?>"
                    data-month="<?= e($prev->format('n')); ?>">Previous</button>
            <button class="button button-secondary schedule-nav"
                    data-year="<?= e((int) date('Y')); ?>"
                    data-month="<?= e((int) date('n')); ?>">Today</button>
            <button class="button button-secondary schedule-nav"
                    data-year="<?= e($next->format('Y')); ?>"
                    data-month="<?= e($next->format('n')); ?>">Next</button>
            <?php if ($canManage): ?>
                <button class="button" id="schedule-open-modal">Quick Book</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="schedule-grid">
        <div class="schedule-calendar" id="schedule-calendar" aria-live="polite"></div>
        <aside class="schedule-sidebar">
            <h2>Upcoming Lessons</h2>
            <ul id="schedule-upcoming" class="schedule-upcoming-list"></ul>
        </aside>
    </div>
</section>
<?php if ($canManage): ?>
<div class="modal" id="schedule-modal" role="dialog" aria-modal="true" aria-labelledby="schedule-modal-title" hidden>
    <div class="modal-content">
        <header class="modal-header">
            <h2 id="schedule-modal-title">Book Lesson</h2>
            <button type="button" class="modal-close" id="schedule-modal-close" aria-label="Close">&times;</button>
        </header>
        <form id="schedule-form" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e($csrfAjaxToken); ?>">
            <label>
                <span>Student</span>
                <select name="student_id" required>
                    <option value="">Select student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= e($student['id']); ?>">
                            <?= e($student['first_name'] . ' ' . $student['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Course</span>
                <select name="course_id" required>
                    <option value="">Select course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= e($course['id']); ?>">
                            <?= e($course['title']); ?> (<?= e($course['lesson_count']); ?> lessons)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Instructor</span>
                <select name="instructor_id" required>
                    <option value="">Select instructor</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= e($instructor['id']); ?>">
                            <?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Vehicle</span>
                <select name="vehicle_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= e($vehicle['id']); ?>">
                            <?= e($vehicle['name']); ?> (<?= e($vehicle['transmission']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Branch</span>
                <select name="branch_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= e($branch['id']); ?>"><?= e($branch['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Date</span>
                <input type="date" name="scheduled_date" required>
            </label>
            <label>
                <span>Start time</span>
                <input type="time" name="start_time" required>
            </label>
            <label>
                <span>End time</span>
                <input type="time" name="end_time" required>
            </label>
            <label>
                <span>Topic</span>
                <input type="text" name="lesson_topic" placeholder="Lesson focus">
            </label>
            <label class="full-width">
                <span>Notes</span>
                <textarea name="notes" rows="3"></textarea>
            </label>
            <div class="form-actions">
                <button type="submit" class="button">Save</button>
                <button type="button" class="button button-secondary" id="schedule-modal-cancel">Cancel</button>
            </div>
            <p class="form-hint" id="schedule-conflict-message" hidden></p>
        </form>
    </div>
</div>
<?php endif; ?>
