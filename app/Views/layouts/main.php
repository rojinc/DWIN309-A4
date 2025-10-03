<?php
use App\Core\Session;
use App\Services\AuthService;
$auth = new AuthService();
$user = $auth->user();
$role = $user['role'] ?? null;

$navItems = [
    [
        'label' => 'Dashboard',
        'page' => 'dashboard',
        'action' => 'index',
        'roles' => ['admin', 'staff', 'instructor', 'student']
    ],
    [
        'label' => 'My Invoices',
        'page' => 'studentinvoices',
        'action' => 'index',
        'roles' => ['student']
    ],
    [
        'label' => 'Students',
        'page' => 'students',
        'action' => 'index',
        'roles' => ['admin', 'staff', 'instructor']
    ],
    [
        'label' => 'Enrollment Requests',
        'page' => 'enrollmentrequests',
        'action' => 'index',
        'roles' => ['admin', 'staff', 'instructor']
    ],
    [
        'label' => 'Instructors',
        'page' => 'instructors',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Staff',
        'page' => 'staff',
        'action' => 'index',
        'roles' => ['admin']
    ],
    [
        'label' => 'Branches',
        'page' => 'branches',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Courses',
        'page' => 'courses',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Scheduling',
        'page' => 'schedules',
        'action' => 'index',
        'roles' => ['admin', 'staff', 'instructor']
    ],
    [
        'label' => 'Invoices',
        'page' => 'invoices',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Payments',
        'page' => 'payments',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Reminders',
        'page' => 'reminders',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Fleet',
        'page' => 'fleet',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Communications',
        'page' => 'communications',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
    [
        'label' => 'Notifications',
        'page' => 'notifications',
        'action' => 'index',
        'roles' => ['admin', 'staff', 'instructor', 'student']
    ],
    [
        'label' => 'Reports',
        'page' => 'reports',
        'action' => 'index',
        'roles' => ['admin', 'staff']
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Origin Driving School'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>
<body class="app-shell">
<header class="app-header">
    <div class="brand">
        <span class="brand-title">Origin Driving School</span>
        <span class="brand-subtitle">Management System</span>
    </div>
    <div class="user-panel">
        <?php if ($user): ?>
        <div class="user-meta">
            <span class="user-name"><?= e($user['first_name'] . ' ' . $user['last_name']); ?></span>
            <span class="user-role">Role: <?= e(ucfirst($user['role'])); ?></span>
        </div>
        <a class="button button-secondary" href="<?= route('auth', 'logout'); ?>">Sign out</a>
        <?php else: ?>
        <a class="button" href="<?= route('auth', 'login'); ?>">Sign in</a>
        <?php endif; ?>
    </div>
</header>
<?php if ($user): ?>
<nav class="app-nav">
    <?php foreach ($navItems as $item): ?>
        <?php if (in_array($role, $item['roles'], true)): ?>
            <a href="<?= route($item['page'], $item['action']); ?>" class="nav-link"><?= e($item['label']); ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<main class="app-content">
    <?php if (!empty($flash)): ?>
        <div class="flash-messages">
            <?php foreach ($flash as $type => $message): ?>
                <div class="alert alert-<?= e($type); ?>"><?= e($message); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content; ?>
</main>
<footer class="app-footer">
    <div>
        <strong>Group Members</strong>: Alice Chen (SID: KIA202301) - Backend &amp; Data Layer, David Singh (SID: KIA202302) - Frontend &amp; UX, Priya Patel (SID: KIA202303) - QA &amp; Documentation.
    </div>
    <div>
        � <?= date('Y'); ?> Origin Driving School. Built for the DWIN309 Final Assessment, Kent Institute Australia.
    </div>
</footer>
<script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>
