<?php
$request = $request ?? [];
$courses = $courses ?? [];
$instructors = $instructors ?? [];
$decisionToken = $decisionToken ?? '';
$status = $request['status'] ?? 'pending';
$studentName = trim(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? ''));
?>
<section class="card">
    <h1>Enrollment Request</h1>
    <dl class="definition-list">
        <dt>Student</dt>
        <dd><?= e($studentName); ?> (<?= e($request['student_email'] ?? ''); ?>)</dd>

        <dt>Course</dt>
        <dd><?= e($request['course_title'] ?? ''); ?></dd>

        <dt>Preferred schedule</dt>
        <dd>
            <?= !empty($request['preferred_date']) ? e(date('d M Y', strtotime($request['preferred_date']))) : 'N/A'; ?>
            <?php if (!empty($request['preferred_time'])): ?>
                at <?= e(substr($request['preferred_time'], 0, 5)); ?>
            <?php endif; ?>
        </dd>

        <dt>Notes from student</dt>
        <dd><?= $request['student_notes'] ? nl2br(e($request['student_notes'])) : 'None'; ?></dd>

        <dt>Status</dt>
        <dd><span class="status-pill status-<?= e($status); ?>"><?= e(ucfirst($status)); ?></span></dd>

        <dt>Assigned instructor</dt>
        <dd><?= !empty($request['instructor_first_name']) ? e($request['instructor_first_name'] . ' ' . $request['instructor_last_name']) : 'Unassigned'; ?></dd>

        <dt>Submitted</dt>
        <dd><?= e(date('d M Y H:i', strtotime($request['created_at'] ?? 'now'))); ?></dd>

        <?php if (!empty($request['decision_at'])): ?>
            <dt>Last decision</dt>
            <dd><?= e(date('d M Y H:i', strtotime($request['decision_at']))); ?></dd>
        <?php endif; ?>

        <?php if (!empty($request['admin_notes'])): ?>
            <dt>Admin notes</dt>
            <dd><?= nl2br(e($request['admin_notes'])); ?></dd>
        <?php endif; ?>
    </dl>
</section>

<?php if (in_array($status, ['pending', 'approved', 'declined'], true)): ?>
<section class="card">
    <h2>Decision</h2>
    <form method="post" action="<?= route('enrollmentrequests', 'decide', ['id' => $request['id'] ?? 0]); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($decisionToken); ?>">

        <label>
            <span>Status</span>
            <select name="status" required>
                <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="declined" <?= $status === 'declined' ? 'selected' : ''; ?>>Declined</option>
            </select>
        </label>

        <label>
            <span>Instructor</span>
            <select name="instructor_id">
                <option value="">Select instructor</option>
                <?php foreach ($instructors as $instructor): ?>
                    <?php $selected = ($request['instructor_id'] ?? null) == $instructor['id']; ?>
                    <option value="<?= e($instructor['id']); ?>" <?= $selected ? 'selected' : ''; ?>>
                        <?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="full-width">
            <span>Internal notes</span>
            <textarea name="admin_notes" rows="4" placeholder="Optional notes for the student"><?= e($request['admin_notes'] ?? ''); ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="button">Update Request</button>
        </div>
    </form>
</section>
<?php endif; ?>
