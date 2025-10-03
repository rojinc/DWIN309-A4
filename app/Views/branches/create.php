<section class="card">
    <h1>Create Branch</h1>
    <form method="post" action="<?= route('branches', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? ''); ?>">
        <label>
            <span>Branch name</span>
            <input type="text" name="name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Phone</span>
            <input type="tel" name="phone">
        </label>
        <label>
            <span>Manager name</span>
            <input type="text" name="manager_name">
        </label>
        <label class="full-width">
            <span>Address</span>
            <input type="text" name="address">
        </label>
        <label>
            <span>City</span>
            <input type="text" name="city">
        </label>
        <label>
            <span>State</span>
            <input type="text" name="state">
        </label>
        <label>
            <span>Postcode</span>
            <input type="text" name="postcode">
        </label>
        <label class="full-width">
            <span>Opening hours</span>
            <input type="text" name="opening_hours" placeholder="Mon-Fri 8am-6pm">
        </label>
        <button class="button" type="submit">Save Branch</button>
    </form>
</section>
