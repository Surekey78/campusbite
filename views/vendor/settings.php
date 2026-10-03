<section class="form-card" aria-labelledby="vendor-settings-title">
  <p class="eyebrow">Storefront</p><h1 id="vendor-settings-title">Store settings</h1><p>These details appear on your public vendor page and order pickup instructions.</p>
  <form method="post" action="<?= h(app_url('vendor.settings.save')) ?>" class="form-grid" data-submit-feedback><?= csrf_field() ?>
    <div class="form-field"><label for="name">Store name</label><input id="name" name="name" maxlength="120" value="<?= h($vendor['name']) ?>" required></div>
    <div class="form-field"><label for="prep">Typical preparation estimate (minutes)</label><input id="prep" type="number" name="prep_minutes" min="1" max="240" value="<?= (int) $vendor['prep_minutes'] ?>" required><span class="field-hint">Used as the minimum estimate at checkout; food-specific times can raise it.</span></div>
    <div class="form-field"><label for="pickup">Pickup location</label><input id="pickup" name="pickup_location" maxlength="180" value="<?= h($vendor['pickup_location']) ?>" required></div>
    <div class="form-field"><label for="stall">Stall or counter</label><input id="stall" name="stall_label" maxlength="80" value="<?= h($vendor['stall_label'] ?? '') ?>"></div>
    <div class="form-field full"><label for="description">Store description</label><textarea id="description" name="description" maxlength="2000" rows="4"><?= h($vendor['description'] ?? '') ?></textarea></div>
    <div class="form-field full"><label for="notice">Store notice <span class="muted">(optional)</span></label><input id="notice" name="store_notice" maxlength="240" value="<?= h($settings['store_notice'] ?? '') ?>" placeholder="A short update for students"></div>
    <div class="form-field full"><label class="check-row"><input type="checkbox" name="accepting_orders" value="1"<?= !empty($settings['accepting_orders']) ? ' checked' : '' ?>><span>Accept new student orders</span></label><span class="field-hint">Turning this off keeps the menu visible but prevents checkout.</span></div>
    <div class="form-field full"><p class="cash-note"><strong>Payment setting: CASH ONLY.</strong> Students pay when they pick up; this cannot be changed.</p></div>
    <div class="form-field full"><button class="button" type="submit" data-loading-text="Saving…">Save settings</button></div>
  </form>
</section>
