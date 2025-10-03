<section class="auth-card">
    <h1>Sign In</h1>
    <p>Access the Origin Driving School management system.</p>
    <form method="post" action="<?= route('auth', 'login'); ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <label>
            <span>Email address</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required minlength="6">
        </label>
        <button type="submit" class="button">Sign in</button>
    </form>
    <p class="auth-help">Need an account? Contact your administrator.</p>
</section>