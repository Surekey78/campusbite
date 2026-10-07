<div class="page-heading">
  <div><p class="eyebrow">Administrator</p><h1>Create a vendor account</h1><p>Only an administrator can create vendor logins. The owner can update their profile and change this initial password after signing in.</p></div>
</div>
<section class="form-card" aria-labelledby="vendor-create-title">
  <h2 id="vendor-create-title">Vendor and storefront details</h2>
  <p>Choose Pending if the storefront should remain closed until you activate it from the Vendors page.</p>
  <form method="post" action="<?= h(app_url('admin.vendor.create')) ?>" class="form-grid" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="owner-name">Account holder's full name</label><input id="owner-name" name="owner_name" maxlength="120" autocomplete="name" required></div>
    <div class="form-field"><label for="store-name">Storefront name</label><input id="store-name" name="store_name" maxlength="120" required></div>
    <div class="form-field"><label for="email">Sign-in email</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required></div>
    <div class="form-field"><label for="phone">Phone number <span class="muted">(optional)</span></label><input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel"></div>
    <div class="form-field"><label for="password">Initial password</label><input id="password" name="password" type="password" minlength="10" maxlength="72" autocomplete="new-password" required><span class="field-hint">10–72 bytes. Share it securely and ask the owner to change it after signing in.</span></div>
    <div class="form-field"><label for="password-confirmation">Confirm initial password</label><input id="password-confirmation" name="password_confirmation" type="password" minlength="10" maxlength="72" autocomplete="new-password" required></div>
    <div class="form-field"><label for="pickup-location">Pickup location</label><input id="pickup-location" name="pickup_location" maxlength="180" required></div>
    <div class="form-field"><label for="stall-label">Stall or counter <span class="muted">(optional)</span></label><input id="stall-label" name="stall_label" maxlength="80"></div>
    <div class="form-field"><label for="prep-minutes">Typical preparation time (minutes)</label><input id="prep-minutes" name="prep_minutes" type="number" min="5" max="180" step="1" value="15" required></div>
    <div class="form-field"><label for="storefront-status">Initial storefront status</label><select id="storefront-status" name="storefront_status"><option value="PENDING" selected>Pending — closed until activated</option><option value="ACTIVE">Active — eligible to accept orders</option></select></div>
    <div class="form-field full"><label for="description">Store description <span class="muted">(optional)</span></label><textarea id="description" name="description" maxlength="1000" rows="4"></textarea></div>
    <div class="form-field full"><div class="form-actions"><button class="button" type="submit" data-loading-text="Creating account…">Create vendor account</button><a class="button button-secondary" href="<?= h(app_url('admin.vendors')) ?>">Cancel</a></div></div>
  </form>
</section>
