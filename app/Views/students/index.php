<section class="card">
    <div class="card-header">
        <h1>Students</h1>
        <div class="actions">
            <form method="get" action="<?= route('students'); ?>" class="inline-form">
                <input type="hidden" name="page" value="students">
                <input type="hidden" name="action" value="index">
                <input type="search" name="q" value="<?= e($search ?? ''); ?>" placeholder="Search students">
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
            <?php if (empty($students)): ?>
                <tr><td colspan="6">No students found.</td></tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= e(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></td>
                        <td><?= e($student['email'] ?? ''); ?></td>
                        <td><?= e($student['phone'] ?? ''); ?></td>
                        <td><?= e($student['branch_name'] ?? ''); ?></td>
                        <td><?= e($student['license_status'] ?? ''); ?></td>
                        <td><a class="button button-small" href="<?= route('students', 'view', ['id' => $student['id']]); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
