<section class="card">
    <h1><?= e(['first_name'] . ' ' . ['last_name']); ?></h1>
    <p><strong>Email:</strong> <?= e(['email']); ?></p>
    <p><strong>Phone:</strong> <?= e(['phone']); ?></p>
    <p><strong>Branch:</strong> <?= e(['branch_name']); ?></p>
    <p><strong>Certification:</strong> <?= e(['certification_number']); ?> (expires <?= e(['accreditation_expiry']); ?>)</p>
    <p><strong>Experience:</strong> <?= e(['experience_years']); ?> years</p>
    <p><strong>Availability:</strong> <?= e(['availability_notes']); ?></p>
    <div class="actions">
        <a class="button button-secondary" href="<?= route('instructors', 'edit', ['id' => ['id']]); ?>">Edit</a>
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
            <?php if (empty()): ?>
                <tr><td colspan="4">No scheduled lessons.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(date('d M Y', strtotime(['scheduled_date']))); ?></td>
                        <td><?= e(substr(['start_time'], 0, 5)); ?> - <?= e(substr(['end_time'], 0, 5)); ?></td>
                        <td><?= e(['student_name']); ?></td>
                        <td><?= e(ucfirst(['status'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>