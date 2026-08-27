<?php
$flashes = View::getFlashes();
if (!empty($flashes)):
?>
<div class="flash-messages">
    <?php foreach ($flashes as $flash): 
        $type = e($flash['type'] ?? 'info');
        $msg = e($flash['message'] ?? '');
    ?>
        <div class="alert alert-<?= $type ?>" role="alert">
            <span><?= $msg ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
