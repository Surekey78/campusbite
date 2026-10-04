<section class="form-card narrow" aria-labelledby="login-title">
  <p class="eyebrow">Welcome back</p>
  <h1 id="login-title">Sign in to CampusBite</h1>
  <p>Use your student, vendor, or administrator account.</p>
  <form method="post" action="<?= h(app_url('auth.login')) ?>" class="stack" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="username" maxlength="190" required autofocus></div>
    <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
    <button class="button" type="submit" data-loading-text="Signing in…">Sign in</button>
  </form>
  <p class="small muted" style="margin:18px 0 0">New to CampusBite? <a href="<?= h(app_url('auth.register')) ?>">Create a student account</a>.</p>
  <p class="small muted" style="margin:7px 0 0">Campus vendor? Contact your campus administrator to request an account.</p>
</section>
