<section class="card">
    <h1>Register New Student</h1>
    <form method="post" action="<?= route('students', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(); ?>">
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
            <span>Temporary password</span>
            <input type="password" name="password" required minlength="6">
        </label>
        <label>
            <span>Branch</span>
            <select name="branch_id" required>
                <option value="">Select branch</option>
                <?php foreach ( as ): ?>
                    <option value="<?= e(['id']); ?>"><?= e(['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Course</span>
            <select name="course_id" required>
                <option value="">Select course</option>
                <?php foreach ( as ): ?>
                    <option value="<?= e(['id']); ?>"><?= e(['title']); ?> ($<?= e(number_format(['price'], 2)); ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Start date</span>
            <input type="date" name="start_date" value="<?= e(date('Y-m-d')); ?>" required>
        </label>
        <label>
            <span>License number</span>
            <input type="text" name="license_number">
        </label>
        <label>
            <span>License status</span>
            <select name="license_status">
                <option value="Learner">Learner</option>
                <option value="P1">P1</option>
                <option value="P2">P2</option>
                <option value="Full">Full</option>
            </select>
        </label>
        <label>
            <span>License expiry</span>
            <input type="date" name="license_expiry">
        </label>
        <label>
            <span>Emergency contact name</span>
            <input type="text" name="emergency_contact_name">
        </label>
        <label>
            <span>Emergency contact phone</span>
            <input type="tel" name="emergency_contact_phone">
        </label>
        <label>
            <span>Address</span>
            <input type="text" name="address_line">
        </label>
        <label>
            <span>City</span>
            <input type="text" name="city">
        </label>
        <label>
            <span>Postcode</span>
            <input type="text" name="postcode">
        </label>
        <label class="full-width">
            <span>Initial notes</span>
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button class="button" type="submit">Create Student</button>
    </form>
</section>