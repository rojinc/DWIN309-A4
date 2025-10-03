<section class="card">
    <div class="card-header">
        <h1>Fleet Vehicles</h1>
        <a class="button" href="<?= route('fleet', 'create'); ?>">Add Vehicle</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Plate</th>
                <th>Branch</th>
                <th>Status</th>
                <th>Upcoming Assignments</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vehicles)): ?>
                <tr><td colspan="6">No vehicles recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?= e($vehicle['name']); ?></td>
                        <td><?= e($vehicle['type'] ?? ''); ?></td>
                        <td><?= e($vehicle['plate_number'] ?? ''); ?></td>
                        <td><?= e($vehicle['branch_name'] ?? ''); ?></td>
                        <td><?= e(ucfirst($vehicle['status'] ?? 'unknown')); ?></td>
                        <td><?= e($vehicle['upcoming_assignments'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
