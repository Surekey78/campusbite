<nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= h(app_url('vendors')) ?>">Vendors</a><span aria-hidden="true">/</span><span><?= h($vendor['name']) ?></span></nav>
<section class="vendor-profile surface">
  <div class="row"><span class="vendor-mark" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($vendor['name'], 0, 2, 'UTF-8'), 'UTF-8')) ?></span><div><p class="eyebrow">Campus vendor</p><h1><?= h($vendor['name']) ?></h1><p class="muted"><?= h($vendor['pickup_location']) ?><?= $vendor['stall_label'] ? ' · ' . h($vendor['stall_label']) : '' ?></p></div></div>
  <?php if ($vendor['description']): ?><p><?= nl2br(h($vendor['description'])) ?></p><?php endif; ?>
  <?php if (!empty($setting['store_notice'])): ?><div class="alert alert-info"><span aria-hidden="true">i</span><div><?= h($setting['store_notice']) ?></div></div><?php endif; ?>
  <?php if (empty($setting['accepting_orders'])): ?><div class="alert alert-error"><span aria-hidden="true">!</span><div>This vendor is not accepting new orders at the moment. You can still browse the menu.</div></div><?php endif; ?>
  <p class="cash-note"><strong>Cash only.</strong> Payment is collected when you pick up.</p>
</section>
<div class="section-heading"><div><h2>Menu</h2><p><?= count($foods) ?> in-stock menu item<?= count($foods) === 1 ? '' : 's' ?> from <?= h($vendor['name']) ?>.</p></div></div>
<?php if ($foods): ?><div class="food-grid"><?php foreach ($foods as $food): require APP_ROOT . '/views/components/food-card.php'; endforeach; ?></div><?php else: ?><div class="empty-state"><h2>No foods in stock</h2><p>This vendor has no in-stock foods to show right now.</p></div><?php endif; ?>
