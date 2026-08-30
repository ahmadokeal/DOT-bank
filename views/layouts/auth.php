<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css?v=phase7-surfaces') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous">
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
