<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Invoice'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>" media="all">
    <style>
        @media print {
            .invoice-print-actions { display: none; }
            body { background: #ffffff; }
        }
        body.print-layout {
            background: #fff;
            padding: 2rem;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
        }
    </style>
</head>
<body class="print-layout">
    <div class="invoice-print-actions">
        <button onclick="window.print();" class="button">Print</button>
    </div>
    <?= $content; ?>
</body>
</html>