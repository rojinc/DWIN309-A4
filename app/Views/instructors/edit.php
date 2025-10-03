<section class="card">
    <h1>Edit Instructor</h1>
    <form method="post" action="<?= route('instructors', 'update', ['id' => ['id']]); ?>" class="form-grid">
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
            <span>Certification number</span>
            <input type="text" name="certification_number" value="<?= e(['certification_number']); ?>">
        </label>
        <label>
            <span>Accreditation expiry</span>
            <input type="date" name="accreditation_expiry" value="<?= e(['accreditation_expiry']); ?>">
        </label>
        <label>
            <span>Experience (years)</span>
            <input type="number" name="experience_years" min="0" value="<?= e(['experience_years']); ?>">
        </label>
        <label class="full-width">
            <span>Availability notes</span>
            <textarea name="availability_notes" rows="3"><?= e(['availability_notes']); ?></textarea>
        </label>
        <label class="full-width">
            <span>Bio</span>
            <textarea name="bio" rows="4"><?= e(['bio']); ?></textarea>
        </label>
        <button class="button" type="submit">Save Changes</button>
    </form>
</section>