<section class="form-card" aria-labelledby="vendor-apply-title">
  <p class="eyebrow">Campus vendor</p>
  <h1 id="vendor-apply-title">Apply for a storefront</h1>
  <p>Applications are reviewed by a campus administrator. Your menu tools unlock after approval.</p>
  <form method="post" action="<?= h(app_url('auth.vendor')) ?>" class="form-grid" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="owner-name">Your name</label><input id="owner-name" name="owner_name" autocomplete="name" maxlength="120" required></div>
    <div class="form-field"><label for="store-name">Store name</label><input id="store-name" name="store_name" maxlength="120" required></div>
    <div class="form-field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" maxlength="190" required></div>
    <div class="form-field"><label for="pickup">Pickup location</label><input id="pickup" name="pickup_location" maxlength="180" placeholder="e.g. Main Cafeteria" required></div>
    <div class="form-field"><label for="stall">Stall or counter <span class="muted">(optional)</span></label><input id="stall" name="stall_label" maxlength="80" placeholder="e.g. Stall 4"></div>
    <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" required><span class="field-hint">Use 10 or more characters, up to 72 bytes.</span></div>
    <div class="form-field"><label for="confirm">Confirm password</label><input id="confirm" type="password" name="password_confirmation" minlength="10" maxlength="72" autocomplete="new-password" required></div>
    <div class="form-field full"><label for="description">What do you serve?</label><textarea id="description" name="description" maxlength="1000" rows="3" placeholder="A short description for students"></textarea></div>
    <div class="form-field full"><button class="button" type="submit" data-loading-text="Submitting…">Submit vendor application</button></div>
  </form>
  <p class="small muted" style="margin:18px 0 0">Already have an account? <a href="<?= h(app_url('auth.login')) ?>">Sign in</a>.</p>
</section>
