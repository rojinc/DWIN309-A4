<section class="card">
    <h1>Create Course</h1>
    <form method="post" action="<?= route('courses', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <label>
            <span>Course title</span>
            <input type="text" name="title" required>
        </label>
        <label>
            <span>Category</span>
            <input type="text" name="category" placeholder="e.g. Learner, Overseas Licence">
        </label>
        <label>
            <span>Price</span>
            <input type="number" name="price" min="0" step="0.01" required>
        </label>
        <label>
            <span>Number of lessons</span>
            <input type="number" name="lesson_count" min="1" required>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label>
        <label class="full-width">
            <span>Description</span>
            <textarea name="description" rows="4"></textarea>
        </label>
        <label class="full-width">
            <span>Assign instructors</span>
            <select name="instructor_ids[]" multiple size="5">
                <?php foreach ($instructors as $instructor): ?>
                    <option value="<?= e($instructor['id']); ?>">
                        <?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Select multiple instructors with Ctrl/Cmd.</small>
        </label>
        <button class="button" type="submit">Create Course</button>
    </form>
</section>
