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
            <?php if (empty()): ?>
                <tr><td colspan="6">No reminders queued.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['reminder_type']); ?></td>
                        <td><?= e(['recipient_user_id']); ?></td>
                        <td><?= e(strtoupper(['channel'])); ?></td>
                        <td><?= e(date('d M Y', strtotime(['send_on']))); ?></td>
                        <td><?= e(ucfirst(['status'])); ?></td>
                        <td><?= e(['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>