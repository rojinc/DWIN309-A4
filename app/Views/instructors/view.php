<section class="card">
    <h1><?= e(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? '')); ?></h1>
    <p><strong>Email:</strong> <?= e($instructor['email'] ?? ''); ?></p>
    <p><strong>Phone:</strong> <?= e($instructor['phone'] ?? ''); ?></p>
    <p><strong>Branch:</strong> <?= e($instructor['branch_name'] ?? ''); ?></p>
    <p><strong>Certification:</strong> <?= e($instructor['certification_number'] ?? ''); ?>
        <?php if (!empty($instructor['accreditation_expiry'])): ?>
            (expires <?= e(date('d M Y', strtotime($instructor['accreditation_expiry']))); ?>)
        <?php endif; ?></p>
    <p><strong>Experience:</strong> <?= e($instructor['experience_years'] ?? 0); ?> years</p>
    <p><strong>Availability:</strong> <?= e($instructor['availability_notes'] ?? ''); ?></p>
    <div class="actions">
        <a class="button button-secondary" href="<?= route('instructors', 'edit', ['id' => $instructor['id']]); ?>">Edit</a>
    </div>
</section>
<section class="card">
    <h2>Upcoming Schedule</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Student</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($schedule)): ?>
                <tr><td colspan="4">No scheduled lessons.</td></tr>
            <?php else: ?>
                <?php foreach ($schedule as $booking): ?>
                    <tr>
                        <td><?= e(date('d M Y', strtotime($booking['scheduled_date']))); ?></td>
                        <td><?= e(substr($booking['start_time'], 0, 5)); ?> - <?= e(substr($booking['end_time'], 0, 5)); ?></td>
                        <td><?= e($booking['student_name'] ?? ''); ?></td>
                        <td><?= e(ucfirst($booking['status'] ?? 'scheduled')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
