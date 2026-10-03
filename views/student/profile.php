<section class="form-card" aria-labelledby="profile-title">
  <p class="eyebrow">Student account</p><h1 id="profile-title">Profile</h1><p>Update the name and contact details used for your account.</p>
  <form method="post" action="<?= h(app_url('profile.save')) ?>" class="form-grid" data-submit-feedback>
    <?= csrf_field() ?>
    <div class="form-field"><label for="full-name">Full name</label><input id="full-name" name="full_name" maxlength="120" value="<?= h($user['full_name']) ?>" required></div>
    <div class="form-field"><label for="email">Email</label><input id="email" type="email" value="<?= h($user['email']) ?>" disabled><span class="field-hint">For an email correction, contact your campus administrator.</span></div>
    <div class="form-field"><label for="phone">Phone <span class="muted">(optional)</span></label><input id="phone" name="phone" type="tel" maxlength="30" value="<?= h($user['phone'] ?? '') ?>"></div>
    <div class="form-field full"><p class="field-hint">For account security, <a href="<?= h(app_url('account.password')) ?>">change your password</a> on the password page.</p><button class="button" type="submit" data-loading-text="Saving…">Save profile</button></div>
  </form>
</section>
