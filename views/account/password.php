<section class="form-card narrow" aria-labelledby="password-title">
  <p class="eyebrow">Account security</p><h1 id="password-title">Change your password</h1><p>Choose a unique password with at least 10 characters (up to 72 bytes).</p>
  <form method="post" action="<?= h(app_url('account.password')) ?>" class="stack" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required></div>
    <div class="form-field"><label for="new-password">New password</label><input id="new-password" type="password" name="new_password" autocomplete="new-password" minlength="10" maxlength="72" required></div>
    <div class="form-field"><label for="password-confirmation">Confirm new password</label><input id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="10" maxlength="72" required></div>
    <button class="button" type="submit" data-loading-text="Updating password…">Change password</button>
  </form>
</section>
