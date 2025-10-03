<section class="card">
    <div class="card-header">
        <h1>Instructors</h1>
        <a class="button" href="<?= route('instructors', 'create'); ?>">Add Instructor</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Branch</th>
                <th>Lessons</th>
                <th>Completed</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instructors)): ?>
                <tr><td colspan="6">No instructors recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($instructors as $instructor): ?>
                    <tr>
                        <td><?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?></td>
                        <td><?= e($instructor['email'] ?? ''); ?></td>
                        <td><?= e($instructor['branch_name'] ?? ''); ?></td>
                        <td><?= e($instructor['lesson_count'] ?? 0); ?></td>
                        <td><?= e($instructor['completed_lessons'] ?? 0); ?></td>
                        <td><a class="button button-small" href="<?= route('instructors', 'view', ['id' => $instructor['id']]); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
