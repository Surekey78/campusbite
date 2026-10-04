<div class="page-heading"><div><p class="eyebrow">Around campus</p><h1>Campus vendors</h1><p>Browse active storefronts and see where to pick up.</p></div></div>
<?php if ($vendors): ?><div class="vendor-grid">
  <?php foreach ($vendors as $vendor): ?>
    <article class="vendor-card">
      <div class="row"><span class="vendor-mark" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($vendor['name'], 0, 2, 'UTF-8'), 'UTF-8')) ?></span><div><h3><a href="<?= h(app_url('vendor', ['slug' => $vendor['slug']])) ?>"><?= h($vendor['name']) ?></a></h3><span class="muted small"><?= h($vendor['pickup_location']) ?><?= $vendor['stall_label'] ? ' · ' . h($vendor['stall_label']) : '' ?></span></div></div>
      <p><?= h($vendor['description'] ?: 'Campus vendor') ?></p>
      <div class="spread"><span class="rating"><?= (int) $vendor['rating_count'] ? '★ ' . h(number_format((float) $vendor['average_rating'], 1)) . ' <span class="rating-count">(' . (int) $vendor['rating_count'] . ')</span>' : '<span class="rating-count">No reviews yet</span>' ?></span><span class="muted small"><?= (int) $vendor['food_count'] ?> foods in stock</span></div>
      <p><span class="availability small<?= empty($vendor['accepting_orders']) ? ' is-unavailable' : '' ?>"><?= empty($vendor['accepting_orders']) ? 'Orders paused' : 'Accepting orders' ?></span></p>
      <p style="margin-bottom:0"><a class="text-link" href="<?= h(app_url('vendor', ['slug' => $vendor['slug']])) ?>">View storefront →</a></p>
    </article>
  <?php endforeach; ?>
</div><?php else: ?><div class="empty-state"><h2>No active vendors</h2><p>Vendor storefronts are created by campus administrators. Check back for newly activated vendors.</p></div><?php endif; ?>
