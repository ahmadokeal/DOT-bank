<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css?v=core-identity-5') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/student-match-responsive.css?v=1') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/quiz-match-responsive.css?v=1') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous">
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
