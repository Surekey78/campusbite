<section class="form-card narrow" aria-labelledby="register-title">
  <p class="eyebrow">Student account</p>
  <h1 id="register-title">Create your account</h1>
  <p>Browse food without signing in. An account lets you save a cart, order, and track pickup.</p>
  <form method="post" action="<?= h(app_url('auth.register')) ?>" class="stack" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="full-name">Full name</label><input id="full-name" name="full_name" autocomplete="name" maxlength="120" required></div>
    <div class="form-field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" maxlength="190" required></div>
    <div class="form-field"><label for="phone">Phone <span class="muted">(optional)</span></label><input id="phone" type="tel" name="phone" autocomplete="tel" maxlength="30"></div>
    <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="10" maxlength="72" required><span class="field-hint">Use 10 or more characters, up to 72 bytes.</span></div>
    <div class="form-field"><label for="password-confirm">Confirm password</label><input id="password-confirm" type="password" name="password_confirmation" autocomplete="new-password" minlength="10" maxlength="72" required></div>
    <button class="button" type="submit" data-loading-text="Creating account…">Create student account</button>
  </form>
  <p class="small muted" style="margin:18px 0 0">Already have an account? <a href="<?= h(app_url('auth.login')) ?>">Sign in</a>.</p>
</section>
