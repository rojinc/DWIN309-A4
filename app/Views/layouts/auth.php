<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Sign In'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>
<body class="auth-shell">
    <main class="auth-container">
        <?= $content; ?>
    </main>
    <footer class="auth-footer">
        <p>© <?= date('Y'); ?> Origin Driving School • DWIN309 Final Assessment</p>
    </footer>
</body>
</html>