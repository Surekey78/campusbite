<aside class="workspace-sidebar" id="workspace-sidebar" aria-label="CampusBite workspace">
  <div class="workspace-sidebar-brand">
    <a class="workspace-brand" href="<?= h(app_url($user['role'] === 'ADMIN' ? 'admin.dashboard' : ($user['role'] === 'VENDOR' ? 'vendor.dashboard' : 'home'))) ?>" aria-label="<?= h(site_name()) ?> workspace home">
      <img src="<?= h(APP_BASE) ?>/assets/icons/campusbite.svg" width="38" height="38" alt="">
      <span class="workspace-brand-copy"><strong><?= h(site_name()) ?></strong><small><?= h($workspaceRoleLabel) ?> workspace</small></span>
    </a>
  </div>

  <nav class="workspace-navigation" aria-label="Workspace navigation">
    <?php foreach ($workspaceNavGroups as $group): ?>
      <div class="workspace-nav-group">
        <p class="workspace-nav-label"><?= h($group['label']) ?></p>
        <ul>
          <?php foreach ($group['items'] as $item):
            $activeRoutes = $item['active'] ?? [$item['route']];
            $isCurrent = in_array($currentRoute, $activeRoutes, true);
            $badgeCount = (int) ($item['badge'] ?? 0);
          ?>
            <li>
              <a class="workspace-nav-link<?= $isCurrent ? ' is-current' : '' ?>" href="<?= h(app_url($item['route'])) ?>"<?php if ($item['route'] === 'notifications'): ?> data-live-unread-link data-live-label="Notifications" data-live-badge-class="workspace-nav-badge" aria-label="Notifications<?= $badgeCount ? ', ' . $badgeCount . ' unread' : '' ?>"<?php endif; ?><?= $isCurrent ? ' aria-current="page"' : '' ?>>
                <svg class="workspace-nav-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $workspaceIcons[$item['icon']] ?? $workspaceIcons['grid'] ?></g></svg>
                <span class="workspace-nav-text"><?= h($item['label']) ?></span>
                <?php if ($badgeCount > 0): ?><span class="workspace-nav-badge"<?php if ($item['route'] === 'notifications'): ?> data-live-unread-badge aria-hidden="true"<?php else: ?> aria-label="<?= $badgeCount ?> <?= h($item['badge_label'] ?? 'updates') ?>"<?php endif; ?>><?= $badgeCount > 99 ? '99+' : $badgeCount ?></span><?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="workspace-sidebar-footer">
    <div class="workspace-sidebar-user">
      <span class="workspace-avatar" aria-hidden="true"><?php if ($profileImageUrl): ?><img src="<?= h($profileImageUrl) ?>" alt=""><?php else: ?><?= h($userInitial) ?><?php endif; ?></span>
      <span class="workspace-sidebar-user-copy"><strong><?= h($userName ?: 'CampusBite user') ?></strong><small><?= h($workspaceRoleLabel) ?></small></span>
    </div>
    <a class="workspace-sidebar-security" href="<?= h(app_url('account.password')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 19 6v5c0 4.6-2.9 8-7 10-4.1-2-7-5.4-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
      <span>Password &amp; security</span>
    </a>
    <form class="workspace-signout-form" method="post" action="<?= h(app_url('auth.logout')) ?>">
      <?= csrf_field() ?>
      <button class="workspace-signout" type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 16l4-4-4-4M18 12H9"/></svg>
        <span>Sign out</span>
      </button>
    </form>
    <p class="workspace-sidebar-footnote">Cash at pickup · CampusBite</p>
  </div>
</aside>
