<?php
$students = $students ?? [];
$courses = $courses ?? [];
$instructors = $instructors ?? [];
$vehicles = $vehicles ?? [];
$branches = $branches ?? [];
?>
<section class="card">
    <h1>Book Lesson or Exam</h1>
    <form method="post" action="<?= route('schedules', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? ''); ?>">
        <label>
            <span>Student</span>
            <select name="student_id" required>
                <option value="">Select student</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= e($student['id']); ?>"><?= e($student['first_name'] . ' ' . $student['last_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Course</span>
            <select name="course_id" required>
                <option value="">Select course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']); ?>"><?= e($course['title']); ?> (<?= e($course['lesson_count']); ?> lessons)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Instructor</span>
            <select name="instructor_id" required>
                <option value="">Select instructor</option>
                <?php foreach ($instructors as $instructor): ?>
                    <option value="<?= e($instructor['id']); ?>"><?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Vehicle</span>
            <select name="vehicle_id">
                <option value="">Assign vehicle (optional)</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= e($vehicle['id']); ?>"><?= e($vehicle['name']); ?> (<?= e($vehicle['transmission']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Branch</span>
            <select name="branch_id">
                <option value="">Select branch</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= e($branch['id']); ?>"><?= e($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Event type</span>
            <select name="event_type">
                <option value="lesson">Lesson</option>
                <option value="exam">VicRoads Exam</option>
                <option value="assessment">Assessment</option>
            </select>
        </label>
        <label>
            <span>Date</span>
            <input type="date" name="scheduled_date" value="<?= e(date('Y-m-d')); ?>" required>
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
            <span>Status</span>
            <select name="status">
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </label>
        <label class="full-width">
            <span>Lesson topic</span>
            <input type="text" name="lesson_topic" placeholder="e.g. Parallel Parking">
        </label>
        <label class="full-width">
            <span>Notes</span>
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button class="button" type="submit">Create Schedule</button>
    </form>
</section>
