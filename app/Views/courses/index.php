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
            <?php if (empty($courses)): ?>
                <tr><td colspan="6">No courses available.</td></tr>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?= e($course['title']); ?></td>
                        <td><?= e($course['category'] ?? ''); ?></td>
                        <td>$<?= e(number_format((float) ($course['price'] ?? 0), 2)); ?></td>
                        <td><?= e($course['lesson_count'] ?? 0); ?></td>
                        <td><?= e($course['active_students'] ?? 0); ?></td>
                        <td><?= e(ucfirst($course['status'] ?? 'inactive')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
