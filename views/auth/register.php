<div class="register-form-container">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; text-align: center;">Student Registration</h2>
    <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-bottom: 1.25rem;">
        Join Doctors of Tomorrow Question Bank
    </p>

    <form method="POST" action="<?= url('register.php') ?>">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= e($username ?? '') ?>" placeholder="Choose a username" required autofocus autocomplete="username">
            <div class="form-help">3-30 characters (letters, numbers, dots, hyphens, underscores).</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
            <div class="form-help">Minimum 6 characters.</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
            Create Student Account
        </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 1.25rem;">
        Already have an account?
        <a href="<?= url('login.php') ?>" style="font-weight: 600;">Sign In</a>
    </div>
</div>
