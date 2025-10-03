<section class="card">
    <h1>Send Communication</h1>
    <form method="post" action="<?= route('communications', 'store'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(); ?>">
        <label>
            <span>Channel</span>
            <select name="channel" required>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="in-app">In-App</option>
            </select>
        </label>
        <label>
            <span>Audience scope</span>
            <select name="audience_scope">
                <option value="selected">Selected Users</option>
                <option value="all_students">All Students</option>
                <option value="all_instructors">All Instructors</option>
            </select>
        </label>
        <label class="full-width">
            <span>Subject</span>
            <input type="text" name="subject" placeholder="Optional">
        </label>
        <label class="full-width">
            <span>Message</span>
            <textarea name="message" rows="5" required></textarea>
        </label>
        <label class="full-width">
            <span>Recipients</span>
            <select name="recipients[]" multiple size="8" required>
                <?php foreach ( as ): ?>
                    <option value="<?= e(['id']); ?>"><?= e(['first_name'] . ' ' . ['last_name'] . ' (' . ['role'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <small>Hold Ctrl/Cmd to select multiple users.</small>
        </label>
        <button class="button" type="submit">Send Message</button>
    </form>
</section>