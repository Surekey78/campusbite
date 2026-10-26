<section class="form-card" aria-labelledby="vendor-apply-title">
  <p class="eyebrow">Campus vendor</p>
  <h1 id="vendor-apply-title">Apply for a storefront</h1>
  <p>Applications are reviewed by a campus administrator. Your menu tools unlock after approval.</p>
  <form method="post" action="<?= h(app_url('auth.vendor')) ?>" class="form-grid" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="owner-name">Your name</label><input id="owner-name" name="owner_name" autocomplete="name" maxlength="120" value="<?= h(old('owner_name')) ?>"<?= form_field_invalid_attr('owner_name') ?> required><?= field_error_html('owner_name') ?></div>
    <div class="form-field"><label for="store-name">Store name</label><input id="store-name" name="store_name" maxlength="120" value="<?= h(old('store_name')) ?>"<?= form_field_invalid_attr('store_name') ?> required><?= field_error_html('store_name') ?></div>
    <div class="form-field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" maxlength="190" value="<?= h(old('email')) ?>"<?= form_field_invalid_attr('email') ?> required><?= field_error_html('email') ?></div>
    <div class="form-field"><label for="pickup">Pickup location</label><input id="pickup" name="pickup_location" maxlength="180" placeholder="e.g. Main Cafeteria" value="<?= h(old('pickup_location')) ?>"<?= form_field_invalid_attr('pickup_location') ?> required><?= field_error_html('pickup_location') ?></div>
    <div class="form-field"><label for="stall">Stall or counter <span class="muted">(optional)</span></label><input id="stall" name="stall_label" maxlength="80" placeholder="e.g. Stall 4" value="<?= h(old('stall_label')) ?>"<?= form_field_invalid_attr('stall_label') ?>><?= field_error_html('stall_label') ?></div>
    <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password"<?= form_field_invalid_attr('password') ?> required><span class="field-hint">Use 10 or more characters, up to 72 bytes.</span><?= field_error_html('password') ?></div>
    <div class="form-field"><label for="confirm">Confirm password</label><input id="confirm" type="password" name="password_confirmation" minlength="10" maxlength="72" autocomplete="new-password"<?= form_field_invalid_attr('password_confirmation') ?> required><?= field_error_html('password_confirmation') ?></div>
    <div class="form-field full"><label for="description">What do you serve?</label><textarea id="description" name="description" maxlength="1000" rows="3" placeholder="A short description for students"<?= form_field_invalid_attr('description') ?>><?= h(old('description')) ?></textarea><?= field_error_html('description') ?></div>
    <div class="form-field full"><button class="button" type="submit" data-loading-text="Submitting…">Submit vendor application</button></div>
  </form>
  <p class="small muted" style="margin:18px 0 0">Already have an account? <a href="<?= h(app_url('auth.login')) ?>">Sign in</a>.</p>
</section>
