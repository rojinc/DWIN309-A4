<?php
$student = $student ?? [];
$user = $student;
?>
<section class="card">
    <h1>Update Your Details</h1>
    <form method="post" action="<?= route('students', 'profile'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? ''); ?>">

        <label>
            <span>Email</span>
            <input type="email" value="<?= e($student['email'] ?? ''); ?>" disabled>
        </label>
        <label>
            <span>Phone</span>
            <input type="tel" name="phone" value="<?= e($student['phone'] ?? ''); ?>" required>
        </label>

        <label>
            <span>Emergency contact name</span>
            <input type="text" name="emergency_contact_name" value="<?= e($student['emergency_contact_name'] ?? ''); ?>" required>
        </label>
        <label>
            <span>Emergency contact phone</span>
            <input type="tel" name="emergency_contact_phone" value="<?= e($student['emergency_contact_phone'] ?? ''); ?>" required>
        </label>

        <label class="full-width">
            <span>Address</span>
            <input type="text" name="address_line" value="<?= e($student['address_line'] ?? ''); ?>" required>
        </label>
        <label>
            <span>City</span>
            <input type="text" name="city" value="<?= e($student['city'] ?? ''); ?>" required>
        </label>
        <label>
            <span>Postcode</span>
            <input type="text" name="postcode" value="<?= e($student['postcode'] ?? ''); ?>" required>
        </label>

        <label>
            <span>Licence number</span>
            <input type="text" name="license_number" value="<?= e($student['license_number'] ?? ''); ?>">
        </label>
        <label>
            <span>Licence expiry</span>
            <input type="date" name="license_expiry" value="<?= e($student['license_expiry'] ?? ''); ?>">
        </label>

        <div class="form-actions">
            <button type="submit" class="button">Save Changes</button>
            <a href="<?= route('dashboard', 'index'); ?>" class="button button-secondary">Back to Dashboard</a>
        </div>
    </form>
</section>
