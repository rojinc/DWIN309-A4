<section class="card">
    <div class="card-header">
        <h1>Courses</h1>
        <a class="button" href="<?= route('courses', 'create'); ?>">Add Course</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Price</th>
                <th>Lessons</th>
                <th>Active Students</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty()): ?>
                <tr><td colspan="6">No courses available.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['title']); ?></td>
                        <td><?= e(['category']); ?></td>
                        <td>$<?= e(number_format(['price'], 2)); ?></td>
                        <td><?= e(['lesson_count']); ?></td>
                        <td><?= e(['active_students']); ?></td>
                        <td><?= e(ucfirst(['status'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>