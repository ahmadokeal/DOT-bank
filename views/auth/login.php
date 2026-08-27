<div class="login-form-container">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.25rem; text-align: center;">Sign In</h2>

    <form method="POST" action="<?= url('login.php') ?>">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= e($username ?? '') ?>" placeholder="Enter your username" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
            Sign In
        </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 1.25rem;">
        Are you a medical student without an account?
        <br>
        <a href="<?= url('register.php') ?>" style="font-weight: 600;">Create a Student Account</a>
    </div>
</div>
