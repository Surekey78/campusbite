<?php
$user = current_user();
$currentRoute = (string) ($_GET['r'] ?? 'home');
$vendorContext = $user && $user['role'] === 'VENDOR' ? current_vendor() : null;
$unreadCount = 0; $cartCount = 0;
if ($user) {
    try {
        $unreadCount = (int) (db_one('SELECT COUNT(*) AS n FROM notifications WHERE user_id=? AND read_at IS NULL', 'i', [(int) $user['id']])['n'] ?? 0);
        if ($user['role'] === 'STUDENT') $cartCount = (int) (db_one('SELECT COALESCE(SUM(quantity),0) AS n FROM cart_items WHERE user_id=?', 'i', [(int) $user['id']])['n'] ?? 0);
    } catch (Throwable $e) { $unreadCount = 0; $cartCount = 0; }
}
$nav = [];
if (!$user) {
    $nav = [['foods','Foods'],['vendors','Vendors'],['content&page=help','How it works']];
} elseif ($user['role'] === 'STUDENT') {
    $nav = [['foods','Foods'],['vendors','Vendors'],['my.orders','My orders'],['cart','Cart' . ($cartCount ? ' · ' . $cartCount : '')],['profile','Profile']];
} elseif ($user['role'] === 'VENDOR' && $vendorContext && $vendorContext['status'] === 'ACTIVE') {
    $nav = [['vendor.dashboard','Overview'],['vendor.orders','Orders'],['vendor.products','Foods'],['vendor.pos','POS'],['vendor.inventory','Inventory'],['vendor.reports','Reports']];
} elseif ($user['role'] === 'VENDOR') {
    $nav = [['vendor.dashboard','Application status']];
} else {
    $nav = [['admin.dashboard','Overview'],['admin.vendors','Vendors'],['admin.users','Users'],['admin.inventory','Inventory'],['admin.orders','Orders'],['admin.reports','Reports'],['admin.cms','Site content']];
}
$flashes = flash_take();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#185e49">
  <title><?= h(($title ?? 'Discover campus food') . ' · ' . site_name()) ?></title>
  <link rel="icon" href="<?= h(APP_BASE) ?>/assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="<?= h(APP_BASE) ?>/assets/css/app.css">
  <script src="<?= h(APP_BASE) ?>/assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
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
        <a class="button button-quiet button-small" href="<?= h(app_url('notifications')) ?>" aria-label="Notifications<?= $unreadCount ? ', ' . $unreadCount . ' unread' : '' ?>">Updates<?= $unreadCount ? ' · ' . (int) $unreadCount : '' ?></a>
        <a class="button button-quiet button-small" href="<?= h(app_url('account.password')) ?>">Password</a>
        <span class="header-user">Hi, <?= h(explode(' ', $user['full_name'])[0]) ?></span>
        <form class="inline-form" method="post" action="<?= h(app_url('auth.logout')) ?>">
          <?= csrf_field() ?>
          <button class="button button-secondary button-small" type="submit">Sign out</button>
        </form>
      <?php else: ?>
        <a class="button button-quiet button-small" href="<?= h(app_url('auth.vendor')) ?>">Sell on campus</a>
        <a class="button button-secondary button-small" href="<?= h(app_url('auth.login')) ?>">Sign in</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="site-main" id="main-content">
  <div class="container">
    <?php if ($flashes): ?><div class="toast-stack" aria-live="polite">
      <?php foreach ($flashes as $flash):
        $type = in_array($flash['type'], ['success','error','info'], true) ? $flash['type'] : 'info';
        $class = $type === 'error' ? 'alert-error' : ($type === 'success' ? 'alert-success' : 'alert-info');
      ?>
        <div class="alert <?= h($class) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><span aria-hidden="true"><?= $type === 'success' ? '✓' : ($type === 'error' ? '!' : 'i') ?></span><div><?= h($flash['message']) ?></div></div>
      <?php endforeach; ?>
    </div><?php endif; ?>
