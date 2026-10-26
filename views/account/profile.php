<section class="form-card" aria-labelledby="profile-title">
  <p class="eyebrow"><?= h(ucfirst(strtolower((string) $user['role']))) ?> account</p>
  <h1 id="profile-title">My profile</h1>
  <p>Your display name is read from your account record throughout CampusBite, so changes appear in order, review, vendor-owner, and administration views; names on published reviews are public. Your phone number and profile picture are limited to you and campus administrators; email changes are administrator-managed.</p>
  <form method="post" action="<?= h(app_url('profile.save')) ?>" class="form-grid" enctype="multipart/form-data" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field full profile-picture-field">
      <div class="profile-picture-preview" data-profile-picture-preview>
        <?php if (!empty($user['profile_media_id'])): ?>
          <img src="<?= h(app_url('media.view', ['id' => (int) $user['profile_media_id']])) ?>" alt="Current profile picture" width="96" height="96">
        <?php else: ?>
          <span aria-hidden="true"><?= h($userInitial) ?></span>
        <?php endif; ?>
      </div>
      <div class="profile-picture-controls">
        <label for="profile-picture">Profile picture</label>
        <input id="profile-picture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" data-profile-picture-input>
        <span class="field-hint">JPG, PNG, or WebP · Maximum 3 MB and 3,000 × 3,000 pixels.</span>
        <?php if (!empty($user['profile_media_id'])): ?>
          <label class="check-row" for="remove-profile-picture"><input id="remove-profile-picture" type="checkbox" name="remove_profile_picture" value="1" data-remove-profile-picture>Remove current profile picture</label>
        <?php endif; ?>
      </div>
    </div>
    <div class="form-field full"><label for="full-name">Full name</label><input id="full-name" name="full_name" maxlength="120" autocomplete="name" value="<?= h(old('full_name', $user['full_name'])) ?>"<?= form_field_invalid_attr('full_name') ?> required><?= field_error_html('full_name') ?></div>
    <div class="form-field full"><label for="email">Email address</label><input id="email" type="email" value="<?= h($user['email']) ?>" disabled><span class="field-hint">For an email correction, contact your campus administrator.</span></div>
    <div class="form-field full"><label for="phone">Phone <span class="muted">(optional)</span></label><input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= h(old('phone', $user['phone'] ?? '')) ?>"<?= form_field_invalid_attr('phone') ?>><span class="field-hint">Used only as account contact information.</span><?= field_error_html('phone') ?></div>
    <div class="form-field full"><div class="form-actions"><button class="button" type="submit" data-loading-text="Saving…">Save profile</button><a class="button button-secondary" href="<?= h(app_url('account.password')) ?>">Change password</a></div></div>
  </form>
</section>
