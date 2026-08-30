<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
