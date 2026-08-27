<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-logo-icon">DOT</div>
                <h1 class="auth-title"><?= APP_NAME ?></h1>
                <p class="auth-subtitle"><?= APP_FULL_NAME ?></p>
            </div>

            <?php require VIEWS_PATH . '/partials/flash.php'; ?>

            <?= $content ?>
        </div>
    </div>

    <?php require VIEWS_PATH . '/partials/footer.php'; ?>
</body>
</html>
