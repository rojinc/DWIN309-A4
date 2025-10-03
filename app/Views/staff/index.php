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
            <?php if (empty($staff)): ?>
                <tr><td colspan="5">No staff accounts created.</td></tr>
            <?php else: ?>
                <?php foreach ($staff as $member): ?>
                    <tr>
                        <td><?= e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')); ?></td>
                        <td><?= e($member['email'] ?? ''); ?></td>
                        <td><?= e($member['branch_name'] ?? ''); ?></td>
                        <td><?= e($member['position_title'] ?? ''); ?></td>
                        <td><?= !empty($member['start_date']) ? e(date('d M Y', strtotime($member['start_date']))) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

