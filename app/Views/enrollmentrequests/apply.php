<?php
$minDate = date('Y-m-d', strtotime('+1 day'));
?>
<section class="card enrollment-request">
    <div class="enrollment-intro">
        <h1>Secure Your Place</h1>
        <p class="lead-text">Complete the form below to request your driving course enrolment. Our team will confirm the details shortly after submission.</p>
    </div>
    <form id="enrollment-form" class="enrollment-form" method="post" action="<?= route('enrollmentrequests', 'apply'); ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? ''); ?>">

        <fieldset class="form-section">
            <legend>Course Selection</legend>
            <div class="form-grid">
                <label class="full-width">
                    <span>Course</span>
                    <select name="course_id" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= e($course['id']); ?>">
                                <?= e($course['title']); ?> (<?= e('$' . number_format((float) ($course['price'] ?? 0), 2)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Preferred branch</span>
                    <select name="branch_id" required>
                        <option value="">Select branch</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e($branch['id']); ?>"><?= e($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Preferred instructor</span>
                    <select name="instructor_id">
                        <option value="">No preference</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?= e($instructor['id']); ?>">
                                <?= e($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Preferred start date</span>
                    <input type="date" name="preferred_date" min="<?= e($minDate); ?>">
                </label>
                <label>
                    <span>Preferred start time</span>
                    <input type="time" name="preferred_time">
                </label>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Your Details</legend>
            <div class="form-grid">
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
                    <span>Mobile</span>
                    <input type="tel" name="phone" required>
                </label>
                <label>
                    <span>License number (optional)</span>
                    <input type="text" name="license_number" placeholder="Learner permit or full licence">
                </label>
                <label>
                    <span>License expiry (optional)</span>
                    <input type="date" name="license_expiry">
                </label>
                <label class="full-width">
                    <span>Street address</span>
                    <input type="text" name="address_line" placeholder="House number and street">
                </label>
                <label>
                    <span>City / Suburb</span>
                    <input type="text" name="city">
                </label>
                <label>
                    <span>Postcode</span>
                    <input type="text" name="postcode">
                </label>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Emergency &amp; Login Details</legend>
            <div class="form-grid">
                <label>
                    <span>Emergency contact name</span>
                    <input type="text" name="emergency_contact_name" required>
                </label>
                <label>
                    <span>Emergency contact phone</span>
                    <input type="tel" name="emergency_contact_phone" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" minlength="6" required>
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" minlength="6" required>
                </label>
                <label class="full-width">
                    <span>Anything else we should know?</span>
                    <textarea name="student_notes" rows="4" placeholder="Tell us about your driving goals or availability"></textarea>
                </label>
                <label class="consent full-width">
                    <input type="checkbox" name="agree_terms" value="yes" required>
                    <span>I confirm the details above are correct and agree to be contacted about my enrolment.</span>
                </label>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="button">Submit enrolment request</button>
        </div>
    </form>
</section>
