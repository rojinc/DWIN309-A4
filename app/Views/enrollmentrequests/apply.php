<?php
$coursePayload = [];
foreach ($courses as $course) {
    $coursePayload[] = [
        'id' => (int) ($course['id'] ?? 0),
        'title' => $course['title'] ?? '',
        'description' => $course['description'] ?? '',
        'price' => (float) ($course['price'] ?? 0),
        'lesson_count' => (int) ($course['lesson_count'] ?? 0),
    ];
}
$courseDataJson = json_encode($coursePayload) ?: '[]';
$minDate = date('Y-m-d', strtotime('+1 day'));
?>
<section class="card enrollment-request">
    <div class="enrollment-intro">
        <h1>Secure Your Place</h1>
        <p class="lead-text">Follow the three simple steps below to request your driving course enrolment.</p>
        <ol class="enrollment-steps">
            <li><strong>Course &amp; calendar:</strong> Pick your course, branch, and ideal start time.</li>
            <li><strong>Your details:</strong> Tell us how to contact you and where you are based.</li>
            <li><strong>Emergency &amp; login:</strong> Add a safety contact and choose your account password.</li>
        </ol>
    </div>
    <form id="enrollment-wizard" class="enrollment-wizard" method="post" action="<?= route('enrollmentrequests', 'apply'); ?>" data-courses='<?= e($courseDataJson); ?>' data-min-date="<?= e($minDate); ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? ''); ?>">
        <noscript>
            <p class="noscript-warning">Please enable JavaScript to complete the step-by-step enrolment form.</p>
        </noscript>
        <div class="wizard-progress" role="tablist" aria-label="Enrolment steps">
            <button type="button" class="wizard-progress-step is-active" data-step="1">1. Course</button>
            <button type="button" class="wizard-progress-step" data-step="2">2. Your details</button>
            <button type="button" class="wizard-progress-step" data-step="3">3. Emergency &amp; login</button>
        </div>
        <section class="wizard-panel is-active" data-step="1" role="tabpanel" aria-labelledby="step-1">
            <h2>Select your course and preferred calendar</h2>
            <p class="muted">Choose the course you want to join, then nominate a branch and time that works for you.</p>
            <div class="form-grid">
                <label class="full-width">
                    <span>Course</span>
                    <select name="course_id" id="enrollment-course" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= e($course['id']); ?>">
                                <?= e($course['title']); ?> (<?= e('$' . number_format((float) ($course['price'] ?? 0), 2)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="course-summary full-width" id="course-summary" aria-live="polite">
                    <h3>Course snapshot</h3>
                    <p class="muted">Pick a course to see the lesson count and price breakdown.</p>
                </div>
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
                    <select name="instructor_id" id="preferred-instructor">
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
                    <input type="date" name="preferred_date" id="preferred-date" min="<?= e($minDate); ?>">
                </label>
                <label>
                    <span>Preferred start time</span>
                    <input type="time" name="preferred_time" id="preferred-time">
                </label>
                <div class="calendar-picker full-width" id="enrollment-calendar" aria-label="Select a preferred start date">
                    <div class="calendar-picker-head">
                        <h3>Quick pick calendar</h3>
                        <p class="muted">Tap a suggested date to populate the calendar field or choose your own.</p>
                    </div>
                    <div class="calendar-grid" role="list"></div>
                </div>
            </div>
        </section>
        <section class="wizard-panel" data-step="2" role="tabpanel" aria-labelledby="step-2" hidden>
            <h2>Your contact details</h2>
            <p class="muted">Make sure these are accurate so your instructor can reach you.</p>
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
        </section>
        <section class="wizard-panel" data-step="3" role="tabpanel" aria-labelledby="step-3" hidden>
            <h2>Emergency contact &amp; login</h2>
            <p class="muted">Provide a safety contact and secure your student account.</p>
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
        </section>
        <div class="wizard-controls">
            <button type="button" class="button button-secondary wizard-prev" data-direction="-1" disabled>Back</button>
            <button type="button" class="button wizard-next" data-direction="1">Continue</button>
            <button type="submit" class="button wizard-submit" hidden>Submit enrolment request</button>
        </div>
    </form>
</section>
