<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Origin Driving School'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>
<body class="public-shell">
<header class="public-header">
    <div class="brand">
        <span class="brand-title">Origin Driving School</span>
        <span class="brand-subtitle">Established 2015</span>
    </div>
    <nav class="public-nav">
        <a class="nav-link" href="#about">About</a>
        <a class="nav-link" href="#features">Features</a>
        <a class="nav-link" href="#team">Our Team</a>
        <a class="nav-link" href="#contact">Contact</a>
        <a class="button button-secondary" href="<?= route('enrollmentrequests', 'apply'); ?>">Enroll Now</a>
        <a class="button" href="<?= route('auth', 'login'); ?>">Launch System</a>
    </nav>
</header>
<main>
    <?= $content; ?>
</main>
<footer class="public-footer" id="contact">
    <p>Origin Driving School � Kent Institute Australia � DWIN309 Final Assessment Submission</p>
    <p>Email: hello@origindrivingschool.com.au � Phone: (03) 8000 0000</p>
</footer>
    <script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>

