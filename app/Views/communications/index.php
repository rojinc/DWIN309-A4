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
            <?php if (empty($communications)): ?>
                <tr><td colspan="5">No communications recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($communications as $communication): ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime($communication['created_at']))); ?></td>
                        <td><?= e(strtoupper($communication['channel'])); ?></td>
                        <td><?= e($communication['subject'] ?? '(No subject)'); ?></td>
                        <td><?= e($communication['sender_name'] ?? 'System'); ?></td>
                        <td><?= e(ucfirst($communication['audience_scope'] ?? 'selected')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
