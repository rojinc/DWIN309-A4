<?php
$summary = $summary ?? [];
$upcomingSchedules = $upcomingSchedules ?? [];
$recentInvoices = $recentInvoices ?? [];
$notifications = $notifications ?? [];
$revenueSeries = $revenueSeries ?? [];
$revenueSeriesJson = json_encode($revenueSeries) ?: '[]';
?>
<section class="grid dashboard-grid">
    <div class="card kpi-card">
        <h2>Active Students</h2>
        <p class="kpi-value"><?= e((string) ($summary['students'] ?? 0)); ?></p>
    </div>
    <div class="card kpi-card">
        <h2>Upcoming Lessons</h2>
        <p class="kpi-value"><?= e((string) ($summary['upcoming_lessons'] ?? 0)); ?></p>
    </div>
    <div class="card kpi-card">
        <h2>Overdue Invoices</h2>
        <p class="kpi-value"><?= e((string) ($summary['overdue_invoices'] ?? 0)); ?></p>
    </div>
    <div class="card kpi-card">
        <h2>Fleet Availability</h2>
        <p class="kpi-value"><?= e((string) ($summary['fleet_available'] ?? 0)); ?> / <?= e((string) ($summary['fleet_total'] ?? 0)); ?></p>
    </div>
</section>
<section class="card chart-card" data-revenue-series='<?= e($revenueSeriesJson); ?>'>
    <h2>Revenue (Last 6 Months)</h2>
    <canvas id="revenueChart" aria-label="Monthly revenue trends"></canvas>
</section>
<section class="grid two-column">
    <div class="card">
        <h2>Upcoming Schedule</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Student</th>
                    <th>Instructor</th>
                    <th>Course</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcomingSchedules)): ?>
                    <tr><td colspan="5">No upcoming lessons for the next period.</td></tr>
                <?php else: ?>
                    <?php foreach ($upcomingSchedules as $schedule): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($schedule['scheduled_date']))); ?></td>
                            <td><?= e(substr($schedule['start_time'], 0, 5)); ?> - <?= e(substr($schedule['end_time'], 0, 5)); ?></td>
                            <td><?= e($schedule['student_name']); ?></td>
                            <td><?= e($schedule['instructor_name']); ?></td>
                            <td><?= e($schedule['course_title']); ?></td>
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
                    <th>Student</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                    <tr><td colspan="4">No invoices issued yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentInvoices as $invoice): ?>
                        <tr>
                            <td><?= e($invoice['invoice_number']); ?></td>
                            <td><?= e($invoice['student_name']); ?></td>
                            <td>$<?= e(number_format((float) $invoice['total'], 2)); ?></td>
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
