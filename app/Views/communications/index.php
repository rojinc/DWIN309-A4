<section class="card">
    <div class="card-header">
        <h1>Communications</h1>
        <a class="button" href="<?= route('communications', 'create'); ?>">Compose Message</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Sent</th>
                <th>Channel</th>
                <th>Subject</th>
                <th>Sender</th>
                <th>Audience</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty()): ?>
                <tr><td colspan="5">No communications recorded.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime(['created_at']))); ?></td>
                        <td><?= e(strtoupper(['channel'])); ?></td>
                        <td><?= e(['subject']); ?></td>
                        <td><?= e(['sender_name'] ?? 'System'); ?></td>
                        <td><?= e(ucfirst(['audience_scope'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>