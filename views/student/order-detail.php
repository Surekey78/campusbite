<div class="page-heading"><div><p class="eyebrow">Order tracking</p><h1><?= h($order['order_number']) ?></h1><p>Placed <?= h(format_datetime($order['created_at'])) ?> · <?= h($order['vendor_name']) ?></p></div><a class="button button-secondary" href="<?= h(app_url('my.orders')) ?>">All orders</a></div>
<?php if ($order['status'] === 'READY'): ?><div class="ready-banner" role="status"><strong>Your order is ready for pickup</strong><p>Show order <?= h($order['order_number']) ?> to <?= h($order['vendor_name']) ?>. Please collect it within <?= (int) PICKUP_GRACE_MINUTES ?> minutes after it is marked ready; the vendor may record it as not picked up after that.</p></div><?php elseif ($order['status'] === 'NO_SHOW'): ?><div class="alert alert-info" role="status"><span aria-hidden="true">i</span><div><strong>Order not picked up</strong><br>The pickup window passed. This cash order remains unpaid; prepared food was not returned to inventory. Contact the vendor if you believe this status is incorrect.</div></div><?php endif; ?>
<?php require APP_ROOT . '/views/components/order-timeline.php'; ?>
<div class="cart-layout">
  <section class="panel">
    <h2 class="panel-title">Items</h2>
    <?php foreach ($items as $item): ?><div class="summary-line"><span><?= (int) $item['quantity'] ?> × <?= h($item['food_name']) ?></span><span><?= h(money($item['line_total'])) ?></span></div>
      <?php if ($order['status'] === 'COMPLETED' && $order['source'] === 'STUDENT'): ?>
        <div style="padding:0 0 11px 12px"><?php if ($item['review_id']): ?><a class="small" href="<?= h(app_url('reviews.edit', ['id' => (int) $item['review_id']])) ?>">Edit your review</a><?php else: ?><a class="small" href="<?= h(app_url('reviews.create', ['item_id' => (int) $item['id']])) ?>">Rate this food</a><?php endif; ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
    <div class="summary-line summary-total"><span>Total</span><strong><?= h(money($order['total'])) ?></strong></div>
    <div class="cash-note" style="margin-top:14px"><strong>Cash · <?= h(strtolower($order['payment_status'] ?? 'unpaid')) ?></strong><br><?php if (($order['payment_status'] ?? '') === 'PAID'): ?>Received <?= h(money($order['cash_received'])) ?><?php elseif ($order['status'] === 'NO_SHOW'): ?>No payment was collected or charged for this uncollected order.<?php else: ?>Pay <?= h(money($order['total'])) ?> at pickup.<?php endif; ?></div>
  </section>
  <aside class="stack">
    <section class="panel"><h2 class="panel-title">Pickup details</h2><p><strong><?= h($order['vendor_name']) ?></strong></p><p><?= h($order['pickup_location']) ?></p><p class="muted small">Estimated preparation: about <?= (int) $order['prep_estimate_minutes'] ?> minutes.</p><?php if ($order['customer_note']): ?><div class="alert alert-info"><span aria-hidden="true">i</span><div><strong>Your note</strong><br><?= nl2br(h($order['customer_note'])) ?></div></div><?php endif; ?></section>
    <section class="panel"><h2 class="panel-title">Order activity</h2><div class="activity-list"><?php foreach ($history as $event): ?><div class="activity-item"><span class="activity-dot" aria-hidden="true"></span><div><strong><?= h(status_label($event['new_status'])) ?></strong><div class="muted small"><?= h(format_datetime($event['created_at'])) ?><?= $event['changed_by_name'] ? ' · ' . h($event['changed_by_name']) : '' ?></div><?php if ($event['note']): ?><div class="small"><?= h($event['note']) ?></div><?php endif; ?></div></div><?php endforeach; ?></div></section>
    <?php if (in_array($order['status'], ['PENDING','CONFIRMED','PREPARING'], true)): ?><form method="post" action="<?= h(app_url('my.order.cancel')) ?>" data-confirm="Cancel order <?= h($order['order_number']) ?>? Reserved stock will be released back to the vendor."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><button class="button button-danger button-small" type="submit">Cancel order</button></form><?php endif; ?>
  </aside>
</div>
