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
            <?php if (empty()): ?>
                <tr><td colspan="6">No vehicles recorded.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['name']); ?></td>
                        <td><?= e(['type']); ?></td>
                        <td><?= e(['plate_number']); ?></td>
                        <td><?= e(['branch_name']); ?></td>
                        <td><?= e(ucfirst(['status'])); ?></td>
                        <td><?= e(['upcoming_assignments']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>