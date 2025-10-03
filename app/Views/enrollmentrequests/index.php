<?php
$statusFilter = $statusFilter ?? null;
?>
<section class="card">
    <div class="card-header">
        <h1>Enrollment Requests</h1>
        <form method="get" action="" class="inline-form">
            <input type="hidden" name="page" value="enrollmentrequests">
            <input type="hidden" name="action" value="index">
            <label>
                <span class="sr-only">Status</span>
                <select name="status" onchange="this.form.submit()">
                    <option value="" <?= $statusFilter === null ? 'selected' : ''; ?>>All statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="declined" <?= $statusFilter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                </select>
            </label>
        </form>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Preferred</th>
                <th>Status</th>
                <th>Instructor</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="7">No enrollment requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <?= e(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? '')); ?><br>
                            <small><?= e($request['student_email'] ?? ''); ?></small>
                        </td>
                        <td><?= e($request['course_title'] ?? ''); ?></td>
                        <td>
                            <?php if (!empty($request['preferred_date'])): ?>
                                <?= e(date('d M Y', strtotime($request['preferred_date']))); ?>
                            <?php endif; ?>
                            <?php if (!empty($request['preferred_time'])): ?>
                                <div><?= e(substr($request['preferred_time'], 0, 5)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-pill status-<?= e($request['status']); ?>"><?= e(ucfirst($request['status'])); ?></span></td>
                        <td>
                            <?php if (!empty($request['instructor_first_name'])): ?>
                                <?= e($request['instructor_first_name'] . ' ' . $request['instructor_last_name']); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= e(date('d M Y', strtotime($request['created_at'] ?? 'now'))); ?></td>
                        <td><a class="button button-secondary" href="<?= route('enrollmentrequests', 'view', ['id' => $request['id']]); ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

