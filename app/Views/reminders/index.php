<section class="card">
    <div class="card-header">
        <h1>Reminders Queue</h1>
        <a class="button" href="<?= route('reminders', 'run'); ?>">Process Due Reminders</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Recipient</th>
                <th>Channel</th>
                <th>Send On</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reminders)): ?>
                <tr><td colspan="6">No reminders queued.</td></tr>
            <?php else: ?>
                <?php foreach ($reminders as $reminder): ?>
                    <?php $sendDate = !empty($reminder['send_on']) ? date('d M Y', strtotime($reminder['send_on'])) : ''; ?>
                    <tr>
                        <td><?= e($reminder['reminder_type'] ?? ''); ?></td>
                        <td><?= e($reminder['recipient_user_id'] ?? ''); ?></td>
                        <td><?= e(strtoupper($reminder['channel'] ?? '')); ?></td>
                        <td><?= e($sendDate); ?></td>
                        <td><?= e(ucfirst($reminder['status'] ?? 'pending')); ?></td>
                        <td><?= e($reminder['message'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
