<section class="card">
    <div class="card-header">
        <h1>Branches</h1>
        <a class="button" href="<?= route('branches', 'create'); ?>">Add Branch</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Manager</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($branches)): ?>
                <tr><td colspan="5">No branches configured.</td></tr>
            <?php else: ?>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td><?= e($branch['name']); ?></td>
                        <td><?= e($branch['address'] ?? ''); ?><?= $branch['city'] ? ', ' . e($branch['city']) : ''; ?><?= $branch['state'] ? ' ' . e($branch['state']) : ''; ?><?= $branch['postcode'] ? ' ' . e($branch['postcode']) : ''; ?></td>
                        <td><?= e($branch['phone'] ?? ''); ?></td>
                        <td><?= e($branch['email'] ?? ''); ?></td>
                        <td><?= e($branch['manager_name'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
