<section class="card">
    <h1>Your Notifications</h1>
    <ul class="notification-list">
        <?php if (empty()): ?>
            <li>No notifications yet.</li>
        <?php else: ?>
            <?php foreach ( as ): ?>
                <li class="notification-item">
                    <span class="badge badge-<?= e(['level']); ?>"><?= e(strtoupper(['level'])); ?></span>
                    <div>
                        <strong><?= e(['title']); ?></strong>
                        <p><?= e(['message']); ?></p>
                        <small><?= e(date('d M Y H:i', strtotime(['created_at']))); ?></small>
                    </div>
                    <?php if (!['is_read']): ?>
                        <a class="button button-small" href="<?= route('notifications', 'read', ['id' => ['id']]); ?>">Mark as read</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>