<?php
$user = current_user();
$currentRoute = (string) ($_GET['r'] ?? 'home');
$vendorContext = $user && $user['role'] === 'VENDOR' ? current_vendor() : null;
$unreadCount = 0;
$cartCount = 0;
if ($user) {
    try {
        $unreadCount = (int) (db_one('SELECT COUNT(*) AS n FROM notifications WHERE user_id=? AND read_at IS NULL', 'i', [(int) $user['id']])['n'] ?? 0);
        if ($user['role'] === 'STUDENT') $cartCount = (int) (db_one('SELECT COALESCE(SUM(quantity),0) AS n FROM cart_items WHERE user_id=?', 'i', [(int) $user['id']])['n'] ?? 0);
    } catch (Throwable $e) { $unreadCount = 0; $cartCount = 0; }
}
$workspaceMode = $user && in_array($user['role'], ['STUDENT', 'VENDOR', 'ADMIN'], true);
$workspaceRoleLabel = match ($user['role'] ?? '') {
    'ADMIN' => 'Administrator',
    'VENDOR' => 'Vendor',
    'STUDENT' => 'Student',
    default => 'Account',
};
$userName = trim((string) ($user['full_name'] ?? ''));
$userFirstName = $userName !== '' ? (explode(' ', $userName)[0] ?? $userName) : 'there';
$userInitial = strtoupper(substr($userFirstName, 0, 1));
$nav = [];
$workspaceNavGroups = [];

if (!$workspaceMode) {
    $nav = [['foods','Foods'],['vendors','Vendors'],['content&page=help','How it works']];
} elseif ($user['role'] === 'STUDENT') {
    $workspaceNavGroups = [
        ['label' => 'Discover', 'items' => [
            ['route' => 'foods', 'label' => 'Browse foods', 'icon' => 'search', 'active' => ['foods', 'food']],
            ['route' => 'vendors', 'label' => 'Vendors', 'icon' => 'store', 'active' => ['vendors', 'vendor']],
        ]],
        ['label' => 'Your activity', 'items' => [
            ['route' => 'my.orders', 'label' => 'My orders', 'icon' => 'receipt', 'active' => ['my.orders', 'my.order', 'my.order.cancel']],
            ['route' => 'cart', 'label' => 'Cart', 'icon' => 'cart', 'active' => ['cart', 'cart.add', 'cart.update', 'cart.remove', 'checkout', 'order.place'], 'badge' => $cartCount, 'badge_label' => 'items in cart'],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'badge' => $unreadCount, 'badge_label' => 'unread notifications'],
        ]],
        ['label' => 'Account', 'items' => [
            ['route' => 'profile', 'label' => 'Profile', 'icon' => 'person', 'active' => ['profile', 'profile.save']],
        ]],
    ];
} elseif ($user['role'] === 'VENDOR' && $vendorContext && $vendorContext['status'] === 'ACTIVE') {
    $workspaceNavGroups = [
        ['label' => 'Workspace', 'items' => [
            ['route' => 'vendor.dashboard', 'label' => 'Overview', 'icon' => 'grid'],
            ['route' => 'vendor.orders', 'label' => 'Orders', 'icon' => 'receipt', 'active' => ['vendor.orders', 'vendor.order.status']],
            ['route' => 'vendor.pos', 'label' => 'Point of sale', 'icon' => 'pos', 'active' => ['vendor.pos', 'vendor.pos.complete']],
        ]],
        ['label' => 'Menu & stock', 'items' => [
            ['route' => 'vendor.products', 'label' => 'Foods', 'icon' => 'food', 'active' => ['vendor.products', 'vendor.product.new', 'vendor.product.edit', 'vendor.product.save', 'vendor.product.toggle']],
            ['route' => 'vendor.categories', 'label' => 'Categories', 'icon' => 'layers', 'active' => ['vendor.categories', 'vendor.category.save', 'vendor.category.toggle']],
            ['route' => 'vendor.inventory', 'label' => 'Inventory', 'icon' => 'box', 'active' => ['vendor.inventory', 'vendor.inventory.move']],
        ]],
        ['label' => 'Insights', 'items' => [
            ['route' => 'vendor.reviews', 'label' => 'Reviews', 'icon' => 'star', 'active' => ['vendor.reviews', 'vendor.review.reply']],
            ['route' => 'vendor.reports', 'label' => 'Reports', 'icon' => 'chart'],
            ['route' => 'vendor.settings', 'label' => 'Store settings', 'icon' => 'settings', 'active' => ['vendor.settings', 'vendor.settings.save']],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'badge' => $unreadCount, 'badge_label' => 'unread notifications'],
        ]],
        ['label' => 'Account', 'items' => [
            ['route' => 'profile', 'label' => 'My profile', 'icon' => 'person', 'active' => ['profile', 'profile.save']],
        ]],
    ];
} elseif ($user['role'] === 'VENDOR') {
    $workspaceNavGroups = [
        ['label' => 'Workspace', 'items' => [
            ['route' => 'vendor.dashboard', 'label' => 'Storefront status', 'icon' => 'store'],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'badge' => $unreadCount, 'badge_label' => 'unread notifications'],
        ]],
        ['label' => 'Account', 'items' => [
            ['route' => 'profile', 'label' => 'My profile', 'icon' => 'person', 'active' => ['profile', 'profile.save']],
        ]],
    ];
} else {
    $workspaceNavGroups = [
        ['label' => 'Overview', 'items' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
            ['route' => 'admin.orders', 'label' => 'Orders', 'icon' => 'receipt', 'active' => ['admin.orders', 'admin.order.status']],
            ['route' => 'admin.vendors', 'label' => 'Vendors', 'icon' => 'store', 'active' => ['admin.vendors', 'admin.vendor.action']],
            ['route' => 'admin.vendor.create', 'label' => 'Create vendor', 'icon' => 'person'],
            ['route' => 'admin.users', 'label' => 'Users', 'icon' => 'people', 'active' => ['admin.users', 'admin.user.status']],
        ]],
        ['label' => 'Catalog & community', 'items' => [
            ['route' => 'admin.foods', 'label' => 'Foods', 'icon' => 'food', 'active' => ['admin.foods', 'admin.food.toggle']],
            ['route' => 'admin.categories', 'label' => 'Categories', 'icon' => 'layers', 'active' => ['admin.categories', 'admin.category.save', 'admin.category.toggle']],
            ['route' => 'admin.inventory', 'label' => 'Inventory', 'icon' => 'box'],
            ['route' => 'admin.reviews', 'label' => 'Reviews', 'icon' => 'star', 'active' => ['admin.reviews', 'admin.review.status']],
        ]],
        ['label' => 'Management', 'items' => [
            ['route' => 'admin.reports', 'label' => 'Reports', 'icon' => 'chart'],
            ['route' => 'admin.cms', 'label' => 'Site content', 'icon' => 'file', 'active' => ['admin.cms', 'admin.cms.save']],
            ['route' => 'admin.media', 'label' => 'Media library', 'icon' => 'image', 'active' => ['admin.media', 'admin.media.upload', 'admin.media.delete']],
            ['route' => 'admin.settings', 'label' => 'Settings', 'icon' => 'settings', 'active' => ['admin.settings', 'admin.settings.save']],
            ['route' => 'admin.audit', 'label' => 'Audit history', 'icon' => 'clock'],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'badge' => $unreadCount, 'badge_label' => 'unread notifications'],
        ]],
        ['label' => 'Account', 'items' => [
            ['route' => 'profile', 'label' => 'My profile', 'icon' => 'person', 'active' => ['profile', 'profile.save']],
        ]],
    ];
}

$workspaceIcons = [
    'grid' => '<rect x="3.25" y="3.25" width="7.25" height="7.25" rx="1.5"/><rect x="13.5" y="3.25" width="7.25" height="7.25" rx="1.5"/><rect x="3.25" y="13.5" width="7.25" height="7.25" rx="1.5"/><rect x="13.5" y="13.5" width="7.25" height="7.25" rx="1.5"/>',
    'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>',
    'store' => '<path d="M3 10.5 4.5 4h15l1.5 6.5"/><path d="M4.5 10.5v9h15v-9M9 19.5v-6h6v6"/><path d="M3 10.5c0 1.4 1.1 2.5 2.5 2.5S8 11.9 8 10.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5"/>',
    'receipt' => '<path d="M6 3.5h12a1 1 0 0 1 1 1v16l-3-1.8-3 1.8-3-1.8-3 1.8v-16a1 1 0 0 1 1-1Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
    'cart' => '<path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
    'bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
    'person' => '<circle cx="12" cy="8" r="3.5"/><path d="M4 20a8 8 0 0 1 16 0"/>',
    'people' => '<path d="M16 20v-1.2a4.3 4.3 0 0 0-4.3-4.3H7.3A4.3 4.3 0 0 0 3 18.8V20"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M16 4.3a3.5 3.5 0 0 1 0 6.8M17 14.7a4.2 4.2 0 0 1 4 4.1V20"/>',
    'food' => '<path d="M4 11h16l-1.3 7.3a2 2 0 0 1-2 1.7H7.3a2 2 0 0 1-2-1.7L4 11Z"/><path d="M3 11h18M8 7c0-1 .8-1.7 1.6-2.5M12 7c0-1 .8-1.7 1.6-2.5M16 7c0-1 .8-1.7 1.6-2.5"/>',
    'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>',
    'box' => '<path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9L12 3Z"/><path d="m3.7 7.7 8.3 4.5 8.3-4.5M12 12.2V21M8 5.1l8.5 4.7"/>',
    'pos' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h2M14 11h2M8 15h2M14 15h2M8 18h8"/>',
    'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>',
    'chart' => '<path d="M4 20V11M10 20V5M16 20v-7M22 20H2"/>',
    'file' => '<path d="M6 3.5h8l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5v5h5M8 13h8M8 17h8"/>',
    'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.8 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.8-1l-1.7.7-1.4-2.4 1.4-1.1a8 8 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.8-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.8 1l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1 0 2Z" transform="translate(-1 -1)"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
];
$flashes = flash_take();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#171e19">
  <title><?= h(($title ?? 'Discover campus food') . ' · ' . site_name()) ?></title>
  <link rel="icon" href="<?= h(APP_BASE) ?>/assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://api.fontshare.com">
  <link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
  <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@400,500,700,800&amp;display=swap">
  <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&amp;display=swap">
  <link rel="stylesheet" href="<?= h(APP_BASE) ?>/assets/css/app.css">
  <script src="<?= h(APP_BASE) ?>/assets/js/app.js" defer></script>
</head>
<body<?= $workspaceMode ? ' class="workspace-body"' : '' ?>>
<a class="skip-link" href="#main-content">Skip to content</a>
<?php if ($workspaceMode): ?>
<div class="workspace-shell" data-workspace-layout>
  <?php require APP_ROOT . '/views/layouts/sidebar.php'; ?>
  <button class="workspace-backdrop" type="button" data-sidebar-close aria-label="Close navigation" hidden></button>
  <div class="workspace-main">
    <header class="workspace-topbar">
      <div class="workspace-topbar-leading">
        <button class="workspace-menu-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="workspace-sidebar" aria-label="Open navigation">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="workspace-greeting">
          <span class="workspace-topbar-kicker"><?= h($workspaceRoleLabel) ?> workspace</span>
          <strong>Good day, <?= h($userFirstName) ?></strong>
        </div>
      </div>
      <div class="workspace-topbar-actions">
        <a class="workspace-updates" href="<?= h(app_url('notifications')) ?>" aria-label="Notifications<?= $unreadCount ? ', ' . (int) $unreadCount . ' unread' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
          <span>Updates</span><?php if ($unreadCount): ?><span class="workspace-topbar-badge"><?= (int) $unreadCount ?></span><?php endif; ?>
        </a>
        <a class="workspace-topbar-account" href="<?= h(app_url('profile')) ?>">
          <span class="workspace-avatar workspace-avatar-small" aria-hidden="true"><?= h($userInitial) ?></span>
          <span class="workspace-topbar-account-label">My profile<small>Profile &amp; contact details</small></span>
        </a>
      </div>
    </header>
    <main class="site-main workspace-site-main" id="main-content">
      <div class="workspace-content">
<?php else: ?>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= h(app_url('home')) ?>" aria-label="<?= h(site_name()) ?> home">
      <img src="<?= h(APP_BASE) ?>/assets/icons/campusbite.svg" width="34" height="34" alt="">
      <span><?= h(site_name()) ?></span>
    </a>
    <button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="main-nav">Menu</button>
    <nav class="main-nav" id="main-nav" data-main-nav aria-label="Main navigation">
      <?php foreach ($nav as [$target, $label]):
        $parts = explode('&', $target, 2); $navRoute = $parts[0]; $navParams = [];
        if (isset($parts[1])) parse_str($parts[1], $navParams);
        $active = $currentRoute === $navRoute || ($navRoute === 'foods' && in_array($currentRoute, ['food'], true)) || ($navRoute === 'vendor' && $currentRoute === 'vendors');
      ?>
        <a href="<?= h(app_url($navRoute, $navParams)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <?php if ($user): ?>
        <a class="button button-quiet button-small" href="<?= h(app_url('notifications')) ?>" aria-label="Notifications<?= $unreadCount ? ', ' . (int) $unreadCount . ' unread' : '' ?>">Updates<?= $unreadCount ? ' · ' . (int) $unreadCount : '' ?></a>
        <a class="button button-quiet button-small" href="<?= h(app_url('account.password')) ?>">Password</a>
        <span class="header-user">Hi, <?= h($userFirstName) ?></span>
        <form class="inline-form" method="post" action="<?= h(app_url('auth.logout')) ?>">
          <?= csrf_field() ?>
          <button class="button button-secondary button-small" type="submit">Sign out</button>
        </form>
      <?php else: ?>
        <a class="button button-secondary button-small" href="<?= h(app_url('auth.login')) ?>">Sign in</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="site-main" id="main-content">
  <div class="container">
<?php endif; ?>
    <?php if ($flashes): ?><div class="toast-stack" aria-live="polite">
      <?php foreach ($flashes as $flash):
        $type = in_array($flash['type'], ['success','error','info'], true) ? $flash['type'] : 'info';
        $class = $type === 'error' ? 'alert-error' : ($type === 'success' ? 'alert-success' : 'alert-info');
      ?>
        <div class="alert <?= h($class) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><span aria-hidden="true"><?= $type === 'success' ? '✓' : ($type === 'error' ? '!' : 'i') ?></span><div><?= h($flash['message']) ?></div></div>
      <?php endforeach; ?>
    </div><?php endif; ?>
