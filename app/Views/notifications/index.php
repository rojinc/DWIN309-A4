<section class="card">
    <h1>Your Notifications</h1>
    <ul class="notification-list">
        <?php if (empty($notifications)): ?>
            <li>No notifications yet.</li>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                    $level = $notification['level'] ?? 'info';
                    $created = !empty($notification['created_at']) ? date('d M Y H:i', strtotime($notification['created_at'])) : '';
                    $isRead = !empty($notification['is_read']);
                ?>
                <li class="notification-item">
                    <span class="badge badge-<?= e($level); ?>"><?= e(strtoupper($level)); ?></span>
                    <div>
                        <strong><?= e($notification['title'] ?? ''); ?></strong>
                        <p><?= e($notification['message'] ?? ''); ?></p>
                        <?php if ($created !== ''): ?>
                            <small><?= e($created); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isRead && !empty($notification['id'])): ?>
                        <a class="button button-small" href="<?= route('notifications', 'read', ['id' => (int) $notification['id']]); ?>">Mark as read</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>
