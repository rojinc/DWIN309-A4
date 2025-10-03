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
            <?php if (empty()): ?>
                <tr><td colspan="6">No instructors recorded.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['first_name'] . ' ' . ['last_name']); ?></td>
                        <td><?= e(['email']); ?></td>
                        <td><?= e(['branch_name']); ?></td>
                        <td><?= e(['lesson_count']); ?></td>
                        <td><?= e(['completed_lessons']); ?></td>
                        <td><a class="button button-small" href="<?= route('instructors', 'view', ['id' => ['id']]); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>