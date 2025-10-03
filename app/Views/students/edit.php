<section class="card">
    <h1>Edit Student</h1>
    <form method="post" action="<?= route('students', 'update', ['id' => ['id']]); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(); ?>">
        <label>
            <span>Branch</span>
            <select name="branch_id" required>
                <?php foreach ( as ): ?>
                    <option value="<?= e(['id']); ?>" <?= ['id'] == ['branch_id'] ? 'selected' : ''; ?>><?= e(['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>License number</span>
            <input type="text" name="license_number" value="<?= e(['license_number']); ?>">
        </label>
        <label>
            <span>License status</span>
            <select name="license_status">
                <?php foreach (['Learner','P1','P2','Full'] as ): ?>
                    <option value="<?= e(); ?>" <?=  === ['license_status'] ? 'selected' : ''; ?>><?= e(); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>License expiry</span>
            <input type="date" name="license_expiry" value="<?= e(['license_expiry']); ?>">
        </label>
        <label>
            <span>Emergency contact name</span>
            <input type="text" name="emergency_contact_name" value="<?= e(['emergency_contact_name']); ?>">
        </label>
        <label>
            <span>Emergency contact phone</span>
            <input type="tel" name="emergency_contact_phone" value="<?= e(['emergency_contact_phone']); ?>">
        </label>
        <label>
            <span>Address</span>
            <input type="text" name="address_line" value="<?= e(['address_line']); ?>">
        </label>
        <label>
            <span>City</span>
            <input type="text" name="city" value="<?= e(['city']); ?>">
        </label>
        <label>
            <span>Postcode</span>
            <input type="text" name="postcode" value="<?= e(['postcode']); ?>">
        </label>
        <label class="full-width">
            <span>Progress summary</span>
            <textarea name="progress_summary" rows="4"><?= e(['progress_summary']); ?></textarea>
        </label>
        <button class="button" type="submit">Save Changes</button>
    </form>
</section>