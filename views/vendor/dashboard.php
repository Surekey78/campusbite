<?php if (!empty($pending)): ?>
  <?php $status=$vendor['status']; $badgeLabel=match ($status) { 'PENDING' => 'Pending activation', 'REJECTED' => 'Rejected', 'SUSPENDED' => 'Suspended', default => status_label($status) }; $statusMessage=match ($status) { 'PENDING' => 'Your vendor account is ready. Its storefront is waiting for administrator activation. Foods, orders, and POS unlock after activation.', 'REJECTED' => 'This storefront was not approved. Contact a campus administrator if you need clarification.', 'SUSPENDED' => 'Your storefront is suspended. Contact a campus administrator; reactivation requires an active owner account.', default => 'Your storefront is not available yet. Contact a campus administrator for help.' }; ?>
  <div class="page-heading"><div><p class="eyebrow">Storefront status</p><h1><?= h($vendor['name']) ?></h1><p>Your CampusBite storefront is not public yet.</p></div><?php require APP_ROOT . '/views/components/status-badge.php'; ?></div>
  <div class="panel" style="max-width:720px"><h2><?= $status === 'PENDING' ? 'Storefront status' : 'Store status' ?></h2><p><?= h($statusMessage) ?></p><p class="muted">Pickup location: <?= h($vendor['pickup_location']) ?><?= $vendor['stall_label'] ? ' · ' . h($vendor['stall_label']) : '' ?></p><a class="button button-secondary" href="<?= h(app_url('notifications')) ?>">Check updates</a></div>
<?php else: ?>
<div class="page-heading"><div><p class="eyebrow"><?= h(date('l, F j')) ?></p><h1>Good day, <?= h(explode(' ', $vendor['owner_name'])[0]) ?></h1><p>Here is what is happening at <?= h($vendor['name']) ?> today.</p></div><div class="button-row"><a class="button button-secondary" href="<?= h(app_url('vendor.orders', ['status' => 'PENDING'])) ?>">Pending orders · <?= (int) ($counts['PENDING'] ?? 0) ?></a><a class="button" href="<?= h(app_url('vendor.pos')) ?>">Open POS</a></div></div>
<div class="kpi-grid">
  <div class="kpi"><span class="kpi-label">Sales today</span><strong class="kpi-value"><?= h(money($today['sales_today'] ?? 0)) ?></strong><span class="kpi-note">Completed cash orders</span></div>
  <div class="kpi"><span class="kpi-label">Orders today</span><strong class="kpi-value"><?= (int) ($today['orders_today'] ?? 0) ?></strong><span class="kpi-note">All statuses</span></div>
  <div class="kpi"><span class="kpi-label">Preparing</span><strong class="kpi-value"><?= (int) ($counts['PREPARING'] ?? 0) ?></strong><span class="kpi-note"><?= (int) ($counts['READY'] ?? 0) ?> ready for pickup</span></div>
  <div class="kpi"><span class="kpi-label">Low stock</span><strong class="kpi-value"><?= count($lowStock) ?></strong><span class="kpi-note">At or below your threshold</span></div>
</div>
<div class="dashboard-grid">
  <section class="panel"><div class="spread"><div><h2 class="panel-title" style="margin-bottom:2px">Sales trend</h2><p class="muted small" style="margin:0">Completed orders · last 7 days</p></div><a class="text-link" href="<?= h(app_url('vendor.reports')) ?>">Reports →</a></div>
    <div class="bar-chart" role="img" aria-label="Daily completed sales for the last seven days">
      <?php foreach ($salesTrend as $day): $height = $maxSales > 0 ? max(2, (int) round((float) $day['sales'] / $maxSales * 95)) : 2; ?>
        <div class="bar-col"><span class="bar-value"><?= (float) $day['sales'] ? h(money($day['sales'])) : '—' ?></span><div class="bar-track"><div class="bar-fill" style="height:<?= $height ?>%"></div></div><span class="bar-label"><?= h($day['label']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <p class="muted tiny">A quiet day is shown as zero; only saved completed orders are counted.</p>
  </section>
  <section class="panel"><div class="spread"><h2 class="panel-title">Needs attention</h2><a class="text-link" href="<?= h(app_url('vendor.inventory')) ?>">Inventory →</a></div>
    <?php if ($lowStock): ?><div class="stack"><?php foreach ($lowStock as $item): ?><div class="spread"><span><strong><?= h($item['food_name']) ?></strong><br><span class="muted small"><?= h(number_format((float) $item['quantity'], 2)) ?> <?= h($item['unit']) ?> left</span></span><span class="badge status-pending">Low stock</span></div><?php endforeach; ?></div><?php else: ?><p class="muted">No low-stock items right now.</p><?php endif; ?>
  </section>
</div>
<div class="dashboard-grid">
  <section class="panel"><div class="spread"><h2 class="panel-title">Recent orders</h2><a class="text-link" href="<?= h(app_url('vendor.orders')) ?>">All orders →</a></div>
    <?php if ($recentOrders): ?><div class="table-wrap"><table><thead><tr><th>Order</th><th>Student</th><th>Items</th><th>Status</th><th>Total</th></tr></thead><tbody><?php foreach ($recentOrders as $order): ?><tr><td><strong><?= h($order['order_number']) ?></strong><div class="muted tiny"><?= h(format_datetime($order['created_at'], 'g:i A')) ?></div></td><td><?= h($order['student_name'] ?: 'POS sale') ?></td><td><?= h($order['item_summary'] ?: '—') ?></td><td><?php $status=$order['status']; require APP_ROOT . '/views/components/status-badge.php'; ?></td><td><?= h(money($order['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">New orders will show here.</p><?php endif; ?>
  </section>
  <section class="panel"><div class="spread"><h2 class="panel-title">Best sellers</h2><a class="text-link" href="<?= h(app_url('vendor.reports')) ?>">Full report →</a></div>
    <?php if ($bestSellers): ?><div class="activity-list"><?php foreach ($bestSellers as $food): ?><div class="activity-item"><span class="activity-dot" aria-hidden="true"></span><div class="spread"><strong><?= h($food['food_name']) ?></strong><span class="muted small"><?= (int) $food['units_sold'] ?> sold · <?= h(money($food['sales'])) ?></span></div></div><?php endforeach; ?></div><?php else: ?><p class="muted">Completed order data will build this list.</p><?php endif; ?>
  </section>
</div>
<?php endif; ?>
