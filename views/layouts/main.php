<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body>
    <?php require VIEWS_PATH . '/partials/nav.php'; ?>

    <main class="main-wrapper">
        <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        <?= $content ?>
    </main>

    <?php require VIEWS_PATH . '/partials/footer.php'; ?>
</body>
</html>
