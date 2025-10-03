<section class="card">
    <h1>Add Staff Member</h1>
    <form method="post" action="<?= route('staff', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <label>
            <span>First name</span>
            <input type="text" name="first_name" required>
        </label>
        <label>
            <span>Last name</span>
            <input type="text" name="last_name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Phone</span>
            <input type="tel" name="phone" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required minlength="6">
        </label>
        <label>
            <span>Branch</span>
            <select name="branch_id" required>
                <option value="">Select branch</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= e($branch['id']); ?>"><?= e($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Position title</span>
            <input type="text" name="position_title">
        </label>
        <label>
            <span>Employment type</span>
            <select name="employment_type">
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Casual">Casual</option>
            </select>
        </label>
        <label>
            <span>Start date</span>
            <input type="date" name="start_date" value="<?= e(date('Y-m-d')); ?>">
        </label>
        <label class="full-width">
            <span>Notes</span>
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button class="button" type="submit">Create Staff Account</button>
    </form>
</section>
