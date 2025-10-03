<section class="card">
    <div class="card-header">
        <h1>Students</h1>
        <div class="actions">
            <form method="get" action="<?= route('students'); ?>" class="inline-form">
                <input type="hidden" name="page" value="students">
                <input type="hidden" name="action" value="index">
                <input type="search" name="q" value="<?= e(); ?>" placeholder="Search students">
                <button class="button button-secondary" type="submit">Search</button>
            </form>
            <a class="button" href="<?= route('students', 'create'); ?>">Add Student</a>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Branch</th>
                <th>License</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty()): ?>
                <tr><td colspan="6">No students found.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['first_name'] . ' ' . ['last_name']); ?></td>
                        <td><?= e(['email']); ?></td>
                        <td><?= e(['phone']); ?></td>
                        <td><?= e(['branch_name']); ?></td>
                        <td><?= e(['license_status']); ?></td>
                        <td><a class="button button-small" href="<?= route('students', 'view', ['id' => ['id']]); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>