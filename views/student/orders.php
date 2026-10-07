<div class="page-heading"><div><p class="eyebrow">Student</p><h1>My orders</h1><p>Follow your order status and pickup details here.</p></div><a class="button" href="<?= h(app_url('foods')) ?>">Browse foods</a></div>
<?php if ($orders): ?>
  <div class="table-wrap student-orders-table"><table><thead><tr><th>Order</th><th>Vendor</th><th>Placed</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead><tbody>
    <?php foreach ($orders as $order): ?><tr><td><strong><?= h($order['order_number']) ?></strong></td><td><?= h($order['vendor_name']) ?></td><td><?= h(format_datetime($order['created_at'])) ?></td><td><?php $status=$order['status']; require APP_ROOT . '/views/components/status-badge.php'; ?></td><td><?php $status=$order['payment_status'] ?? 'UNPAID'; require APP_ROOT . '/views/components/status-badge.php'; ?></td><td><strong><?= h(money($order['total'])) ?></strong></td><td><a class="button button-secondary button-small" href="<?= h(app_url('my.order', ['id' => (int) $order['id']])) ?>">Track order</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <section class="student-order-cards" aria-label="Your orders">
    <?php foreach ($orders as $order): ?>
      <article class="student-order-card">
        <div class="student-order-card-heading">
          <div><p class="eyebrow">Order</p><h2><?= h($order['order_number']) ?></h2></div>
          <div><?php $status=$order['status']; require APP_ROOT . '/views/components/status-badge.php'; ?></div>
        </div>
        <dl class="student-order-facts">
          <div><dt>Vendor</dt><dd><?= h($order['vendor_name']) ?></dd></div>
          <div><dt>Placed</dt><dd><?= h(format_datetime($order['created_at'])) ?></dd></div>
        </dl>
        <div class="student-order-summary">
          <div><span>Payment</span><?php $status=$order['payment_status'] ?? 'UNPAID'; require APP_ROOT . '/views/components/status-badge.php'; ?></div>
          <div><span>Total</span><strong><?= h(money($order['total'])) ?></strong></div>
        </div>
        <a class="button button-secondary student-order-track" href="<?= h(app_url('my.order', ['id' => (int) $order['id']])) ?>">Track this order</a>
      </article>
    <?php endforeach; ?>
  </section>
  <?php $routeName='my.orders'; $perPage=$limit; $queryParams=[]; require APP_ROOT . '/views/components/pagination.php'; ?>
<?php else: ?><div class="empty-state"><div class="empty-icon" aria-hidden="true">↗</div><h2>No orders yet</h2><p>Orders you place will appear here with pickup information and a live status timeline.</p><a class="button" href="<?= h(app_url('foods')) ?>">Browse foods</a></div><?php endif; ?>
