<section class="card">
    <h1>Register Instructor</h1>
    <form method="post" action="<?= route('instructors', 'store'); ?>" class="form-grid">
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
            <span>Password</span>
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
            <span>Certification number</span>
            <input type="text" name="certification_number">
        </label>
        <label>
            <span>Accreditation expiry</span>
            <input type="date" name="accreditation_expiry">
        </label>
        <label>
            <span>Experience (years)</span>
            <input type="number" name="experience_years" min="0" value="0">
        </label>
        <label class="full-width">
            <span>Availability notes</span>
            <textarea name="availability_notes" rows="3"></textarea>
        </label>
        <label class="full-width">
            <span>Bio</span>
            <textarea name="bio" rows="4"></textarea>
        </label>
        <button class="button" type="submit">Create Instructor</button>
    </form>
</section>