<section class="card">
    <h1>Add Fleet Vehicle</h1>
    <form method="post" action="<?= route('fleet', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(); ?>">
        <label>
            <span>Vehicle name</span>
            <input type="text" name="name" required>
        </label>
        <label>
            <span>Type</span>
            <input type="text" name="type" placeholder="e.g. Hatchback">
        </label>
        <label>
            <span>Transmission</span>
            <select name="transmission">
                <option value="Automatic">Automatic</option>
                <option value="Manual">Manual</option>
            </select>
        </label>
        <label>
            <span>Number plate</span>
            <input type="text" name="plate_number" required>
        </label>
        <label>
            <span>VIN</span>
            <input type="text" name="vin">
        </label>
        <label>
            <span>Branch</span>
            <select name="branch_id">
                <option value="">Assign branch</option>
                <?php foreach ( as ): ?>
                    <option value="<?= e(['id']); ?>"><?= e(['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="available">Available</option>
                <option value="in_service">In Service</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </label>
        <label>
            <span>Last service date</span>
            <input type="date" name="last_service_date">
        </label>
        <label>
            <span>Next service due</span>
            <input type="date" name="next_service_due">
        </label>
        <label class="full-width">
            <span>Notes</span>
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button class="button" type="submit">Save Vehicle</button>
    </form>
</section>