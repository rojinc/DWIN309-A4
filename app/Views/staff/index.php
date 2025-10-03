<section class="card">
    <div class="card-header">
        <h1>Staff Members</h1>
        <a class="button" href="<?= route('staff', 'create'); ?>">Add Staff</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Branch</th>
                <th>Position</th>
                <th>Start Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty()): ?>
                <tr><td colspan="5">No staff accounts created.</td></tr>
            <?php else: ?>
                <?php foreach ( as ): ?>
                    <tr>
                        <td><?= e(['first_name'] . ' ' . ['last_name']); ?></td>
                        <td><?= e(['email']); ?></td>
                        <td><?= e(['branch_name']); ?></td>
                        <td><?= e(['position_title']); ?></td>
                        <td><?= e(date('d M Y', strtotime(['start_date']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>