<div class="setup-form-container">
    <div style="margin-bottom: 1.5rem; text-align: center;">
        <span class="badge badge-admin" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">Initial Platform Setup</span>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
            Configure your SQLite database and create the primary Administrator account.
        </p>
    </div>

    <form method="POST" action="<?= url('setup.php') ?>">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label class="form-label" for="platform_name">Platform Name</label>
            <input type="text" id="platform_name" name="platform_name" class="form-control" value="<?= e($platformName ?? 'DOT Bank') ?>" required>
            <div class="form-help">Default: DOT Bank (Doctors of Tomorrow)</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="admin_username">Admin Username</label>
            <input type="text" id="admin_username" name="admin_username" class="form-control" value="<?= e($adminUsername ?? '') ?>" placeholder="e.g. admin" required autofocus autocomplete="username">
            <div class="form-help">Must be 3-30 characters (letters, numbers, dots, hyphens, underscores).</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="admin_password">Admin Password</label>
            <input type="password" id="admin_password" name="admin_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
            <div class="form-help">Minimum 6 characters.</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Admin Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
            Complete Setup & Initialize
        </button>
    </form>
</div>
