<?php
$canUpdateProgress = $canUpdateProgress ?? false;
$progressTokens = $progressTokens ?? [];
$upcomingSchedules = $upcomingSchedules ?? [];
$completedSchedules = $completedSchedules ?? [];
?>
<section class="grid two-column">
    <div class="card">
        <h1><?= e($student['first_name'] . ' ' . $student['last_name']); ?></h1>
        <p><strong>Email:</strong> <?= e($student['email']); ?></p>
        <p><strong>Phone:</strong> <?= e($student['phone']); ?></p>
        <p><strong>Branch:</strong> <?= e($student['branch_name']); ?></p>
        <p><strong>License:</strong> <?= e($student['license_status']); ?><?= $student['license_number'] ? ' - ' . e($student['license_number']) : ''; ?></p>
        <p><strong>Progress:</strong> <?= e($student['progress_summary']); ?></p>
        <?php if (!empty($canEditStudent)): ?>
        <div class="actions">
            <a class="button button-secondary" href="<?= route('students', 'edit', ['id' => $student['id']]); ?>">Edit</a>
        </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Emergency Contact</h2>
        <p><?= e($student['emergency_contact_name'] ?? 'Not provided'); ?></p>
        <p><?= e($student['emergency_contact_phone'] ?? ''); ?></p>
        <h2>Address</h2>
        <p><?= e($student['address_line'] ?? ''); ?><br><?= e(($student['city'] ?? '') . ' ' . ($student['postcode'] ?? '')); ?></p>
    </div>
</section>
<section class="card">
    <h2>Enrolments</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Start Date</th>
                <th>Status</th>
                <th>Progress</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($enrollments)): ?>
                <tr><td colspan="4">No enrolments yet.</td></tr>
            <?php else: ?>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?= e($enrollment['course_title']); ?></td>
                        <td><?= e(date('d M Y', strtotime($enrollment['start_date']))); ?></td>
                        <td><?= e(ucfirst($enrollment['status'])); ?></td>
                        <td>
                            <?php if (!empty($canUpdateProgress) && !empty($progressTokens[$enrollment['id']] ?? null)): ?>
                                <form method="post" action="<?= route('students', 'progress', ['id' => $student['id'], 'enrollment' => $enrollment['id']]); ?>" class="inline-progress-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($progressTokens[$enrollment['id']]); ?>">
                                    <label class="visually-hidden" for="progress-<?= e($enrollment['id']); ?>">Progress percentage</label>
                                    <input id="progress-<?= e($enrollment['id']); ?>" class="progress-input" type="number" name="progress_percentage" min="0" max="100" value="<?= e((string) $enrollment['progress_percentage']); ?>">
                                    <button class="button button-small" type="submit">Save</button>
                                </form>
                            <?php else: ?>
                                <?= e($enrollment['progress_percentage']); ?>%
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<section class="grid two-column">
    <div class="card">
        <h2>Invoices</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="3">No invoices generated.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= e($invoice['invoice_number']); ?></td>
                            <td>$<?= e(number_format($invoice['total'], 2)); ?></td>
                            <td><?= e(ucfirst($invoice['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Upcoming Lessons</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Instructor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcomingSchedules)): ?>
                    <tr><td colspan="4">No upcoming lessons scheduled.</td></tr>
                <?php else: ?>
                    <?php foreach ($upcomingSchedules as $schedule): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($schedule['scheduled_date']))); ?></td>
                            <td><?= e(substr($schedule['start_time'], 0, 5)); ?> - <?= e(substr($schedule['end_time'], 0, 5)); ?></td>
                            <td><?= e($schedule['instructor_name']); ?></td>
                            <td><?= e(ucwords(str_replace('_', ' ', $schedule['status'] ?? 'scheduled'))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Completed Lessons</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Instructor</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completedSchedules)): ?>
                    <tr><td colspan="4">No lessons completed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($completedSchedules as $schedule): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($schedule['scheduled_date']))); ?></td>
                            <td><?= e(substr($schedule['start_time'], 0, 5)); ?> - <?= e(substr($schedule['end_time'], 0, 5)); ?></td>
                            <td><?= e($schedule['instructor_name']); ?></td>
                            <td>
                                <?php if (!empty($schedule['student_rating'])): ?>
                                    <?= e((string) $schedule['student_rating']); ?>/10
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="grid two-column">
    <div class="card">
        <h2>Notes</h2>
        <?php if (!empty($canManageNotes)): ?>
        <form method="post" action="<?= route('students', 'note', ['id' => $student['id']]); ?>" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <label class="full-width">
                <span>Add a note</span>
                <textarea name="content" rows="3" required></textarea>
            </label>
            <button class="button" type="submit">Add Note</button>
        </form>
        <?php endif; ?>
        <ul class="note-list">
            <?php if (empty($notes)): ?>
                <li>No notes recorded.</li>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <li>
                        <p><?= e($note['content']); ?></p>
                        <small><?= e($note['author_name']); ?> - <?= e(date('d M Y H:i', strtotime($note['created_at']))); ?></small>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Documents</h2>
        <?php if (!empty($canUploadDocuments)): ?>
        <form method="post" action="<?= route('students', 'upload', ['id' => $student['id']]); ?>" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e($uploadToken); ?>">
            <label>
                <span>Category</span>
                <input type="text" name="category" placeholder="e.g. ID, Learner Permit">
            </label>
            <label>
                <span>Description</span>
                <input type="text" name="description" placeholder="Optional short note">
            </label>
            <label class="full-width">
                <span>Upload file</span>
                <input type="file" name="document" required>
            </label>
            <button class="button" type="submit">Upload</button>
        </form>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="4">No documents uploaded.</td></tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= e($doc['file_name']); ?></td>
                            <td><?= e($doc['category']); ?></td>
                            <td><?= e(date('d M Y', strtotime($doc['created_at']))); ?></td>
                            <td>
                                <a class="button button-small" href="<?= route('documents', 'download', ['id' => $doc['id']]); ?>">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>