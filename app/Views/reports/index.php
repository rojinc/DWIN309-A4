<section class="grid three-column">
    <div class="card kpi-card">
        <h2>Total Students</h2>
        <p class="kpi-value"><?= e($summary['students'] ?? 0); ?></p>
    </div>
    <div class="card kpi-card">
        <h2>Upcoming Lessons</h2>
        <p class="kpi-value"><?= e($summary['upcoming_lessons'] ?? 0); ?></p>
    </div>
    <div class="card kpi-card">
        <h2>Overdue Invoices</h2>
        <p class="kpi-value"><?= e($summary['overdue_invoices'] ?? 0); ?></p>
    </div>
</section>

<section class="grid two-column">
    <div class="card">
        <div class="card-header">
            <h2>Monthly Revenue (12 months)</h2>
            <a class="button button-secondary" href="<?= route('reports', 'export', ['type' => 'revenue_monthly']); ?>">Download CSV</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revenueSeries)): ?>
                    <tr><td colspan="2">No payments within the last 12 months.</td></tr>
                <?php else: ?>
                    <?php foreach ($revenueSeries as $row): ?>
                        <tr>
                            <td><?= e(date('F Y', strtotime($row['period'] . '-01'))); ?></td>
                            <td>$<?= e(number_format($row['total'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Retention Overview</h2>
        <ul class="report-list">
            <li><strong>Total enrolments:</strong> <?= e($retention['total_enrollments'] ?? 0); ?></li>
            <li><strong>Active / In progress:</strong> <?= e($retention['active'] ?? 0); ?></li>
            <li><strong>Completed:</strong> <?= e($retention['completed'] ?? 0); ?></li>
        </ul>
        <a class="button button-secondary" href="<?= route('reports', 'export', ['type' => 'retention']); ?>">Download CSV</a>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2>Revenue by Course</h2>
        <a class="button button-secondary" href="<?= route('reports', 'export', ['type' => 'revenue_course']); ?>">Download CSV</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($courseRevenue)): ?>
                <tr><td colspan="2">No revenue recorded for courses.</td></tr>
            <?php else: ?>
                <?php foreach ($courseRevenue as $course): ?>
                    <tr>
                        <td><?= e($course['course_title']); ?></td>
                        <td>$<?= e(number_format($course['total_revenue'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <div class="card-header">
        <h2>Instructor Performance</h2>
        <a class="button button-secondary" href="<?= route('reports', 'export', ['type' => 'instructors']); ?>">Download CSV</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Instructor</th>
                <th>Branch</th>
                <th>Total Lessons</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instructorPerformance)): ?>
                <tr><td colspan="4">No instructors available.</td></tr>
            <?php else: ?>
                <?php foreach ($instructorPerformance as $instructor): ?>
                    <tr>
                        <td><?= e($instructor['instructor_name']); ?></td>
                        <td><?= e($instructor['branch_name']); ?></td>
                        <td><?= e($instructor['total_lessons']); ?></td>
                        <td><?= e($instructor['completed_lessons']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <div class="card-header">
        <h2>Student Progress Breakdown</h2>
        <a class="button button-secondary" href="<?= route('reports', 'export', ['type' => 'progress']); ?>">Download CSV</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Progress Summary</th>
                <th>Students</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($progressBreakdown)): ?>
                <tr><td colspan="2">No progress data available.</td></tr>
            <?php else: ?>
                <?php foreach ($progressBreakdown as $row): ?>
                    <tr>
                        <td><?= e($row['progress_summary'] ?? 'Unspecified'); ?></td>
                        <td><?= e($row['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>