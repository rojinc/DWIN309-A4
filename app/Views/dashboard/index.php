<?php
$summary = $summary ?? [];
$upcomingSchedules = $upcomingSchedules ?? [];
$recentInvoices = $recentInvoices ?? [];
$notifications = $notifications ?? [];
$revenueSeries = $revenueSeries ?? [];
$role = $role ?? 'guest';
$showRevenueChart = $showRevenueChart ?? true;
$instructorRevenue = $instructorRevenue ?? null;
$assignedInstructor = $assignedInstructor ?? null;

$cards = [];
$cards[] = [
    'title' => $role === 'student' ? 'Enrolments' : 'Active Students',
    'value' => (int) ($summary['students'] ?? 0),
];
$cards[] = [
    'title' => 'Upcoming Lessons',
    'value' => (int) ($summary['upcoming_lessons'] ?? 0),
];
$cards[] = [
    'title' => 'Overdue Invoices',
    'value' => (int) ($summary['overdue_invoices'] ?? 0),
];
if (array_key_exists('fleet_total', $summary) && $summary['fleet_total'] !== null) {
    $cards[] = [
        'title' => 'Fleet Availability',
        'composite' => true,
        'value' => (int) ($summary['fleet_available'] ?? 0),
        'total' => (int) ($summary['fleet_total'] ?? 0),
    ];
}
if ($role === 'instructor' && $instructorRevenue !== null) {
    $cards[] = [
        'title' => 'Revenue To Date',
        'currency' => true,
        'value' => (float) $instructorRevenue,
    ];
}

$showStudentColumn = $role !== 'student';
$showInstructorColumn = $role !== 'instructor';
$showInvoiceStudentColumn = $role !== 'student';
?>
<section class="grid dashboard-grid">
    <?php foreach ($cards as $card): ?>
        <div class="card kpi-card">
            <h2><?= e($card['title']); ?></h2>
            <?php if (!empty($card['currency'])): ?>
                <p class="kpi-value">$<?= e(number_format($card['value'], 2)); ?></p>
            <?php elseif (!empty($card['composite'])): ?>
                <p class="kpi-value"><?= e((string) $card['value']); ?> / <?= e((string) $card['total']); ?></p>
            <?php else: ?>
                <p class="kpi-value"><?= e((string) $card['value']); ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php if ($role === 'student' && $assignedInstructor): ?>
<section class="card">
    <h2>Your Instructor</h2>
    <p class="lead-text"><?= e($assignedInstructor['name'] ?? 'Unassigned'); ?></p>
    <?php if (!empty($assignedInstructor['course'])): ?>
        <p class="muted">Current course: <?= e($assignedInstructor['course']); ?></p>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($showRevenueChart): ?>
<section class="card chart-card" data-revenue-series='<?= e(json_encode($revenueSeries) ?: '[]'); ?>'>
    <h2>Revenue (Last 6 Months)</h2>
    <canvas id="revenueChart" aria-label="Monthly revenue trends"></canvas>
</section>
<?php endif; ?>
<section class="grid two-column">
    <div class="card">
        <h2>Upcoming Schedule</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <?php if ($showStudentColumn): ?><th>Student</th><?php endif; ?>
                    <?php if ($showInstructorColumn): ?><th>Instructor</th><?php endif; ?>
                    <th>Course</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcomingSchedules)): ?>
                    <tr><td colspan="<?= 3 + (int) $showStudentColumn + (int) $showInstructorColumn; ?>">No upcoming lessons for the next period.</td></tr>
                <?php else: ?>
                    <?php foreach ($upcomingSchedules as $schedule): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($schedule['scheduled_date']))); ?></td>
                            <td><?= e(substr($schedule['start_time'], 0, 5)); ?> - <?= e(substr($schedule['end_time'], 0, 5)); ?></td>
                            <?php if ($showStudentColumn): ?>
                                <td><?= e($schedule['student_name'] ?? ''); ?></td>
                            <?php endif; ?>
                            <?php if ($showInstructorColumn): ?>
                                <td><?= e($schedule['instructor_name'] ?? ''); ?></td>
                            <?php endif; ?>
                            <td><?= e($schedule['course_title'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Recent Invoices</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <?php if ($showInvoiceStudentColumn): ?><th>Student</th><?php endif; ?>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                    <tr><td colspan="<?= $showInvoiceStudentColumn ? 4 : 3; ?>">No invoices issued yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentInvoices as $invoice): ?>
                        <tr>
                            <td><?= e($invoice['invoice_number']); ?></td>
                            <?php if ($showInvoiceStudentColumn): ?>
                                <td><?= e($invoice['student_name'] ?? ''); ?></td>
                            <?php endif; ?>
                            <td>$<?= e(number_format((float) ($invoice['total'] ?? 0), 2)); ?></td>
                            <td><span class="status-pill status-<?= e($invoice['status']); ?>"><?= e(ucfirst($invoice['status'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="card">
    <h2>Notifications</h2>
    <ul class="notification-list">
        <?php if (empty($notifications)): ?>
            <li>No notifications yet.</li>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <li class="notification-item">
                    <span class="badge badge-<?= e($notification['level']); ?>"><?= e(strtoupper($notification['level'])); ?></span>
                    <div>
                        <strong><?= e($notification['title']); ?></strong>
                        <p><?= e($notification['message']); ?></p>
                        <small><?= e(date('d M Y H:i', strtotime($notification['created_at']))); ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>
