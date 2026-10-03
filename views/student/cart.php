<div class="page-heading"><div><p class="eyebrow">Student</p><h1>Your cart</h1><p>One vendor per checkout keeps pickup clear and simple.</p></div><a class="button button-secondary" href="<?= h(app_url('foods')) ?>">Continue browsing</a></div>
<?php if ($items): ?>
  <?php $vendorIds = array_unique(array_column($items, 'vendor_id')); if (count($vendorIds) > 1): ?><div class="alert alert-error" role="alert"><span aria-hidden="true">!</span><div>Your cart has items from more than one vendor. Remove items until only one vendor remains before checkout.</div></div><?php endif; ?>
  <div class="cart-layout">
    <section class="panel" aria-label="Cart items">
      <div class="spread"><h2 class="panel-title" style="margin-bottom:0"><?= h($vendorName) ?></h2><span class="muted small"><?= count($items) ?> line<?= count($items) === 1 ? '' : 's' ?></span></div>
      <?php foreach ($items as $item): ?>
        <article class="cart-item">
          <?php if ($item['media_id']): ?><img class="cart-thumb" src="<?= h(app_url('media.view', ['id' => (int) $item['media_id']])) ?>" alt="" loading="lazy"><?php else: ?><span class="cart-thumb food-placeholder" aria-hidden="true">🍽</span><?php endif; ?>
          <div><h3><?= h($item['name']) ?></h3><p><?= h($item['vendor_name']) ?> · <?= h(money($item['price'])) ?> each</p><p><?= empty($item['accepting_orders']) ? 'Vendor not accepting orders' : ($item['is_available'] && $item['vendor_status'] === 'ACTIVE' ? 'Available' : 'No longer available') ?> · <?= (int) $item['stock'] ?> in stock</p>
            <form method="post" action="<?= h(app_url('cart.update')) ?>" class="quantity-form" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="food_id" value="<?= (int) $item['food_id'] ?>"><label class="sr-only" for="qty-<?= (int) $item['food_id'] ?>">Quantity of <?= h($item['name']) ?></label><input id="qty-<?= (int) $item['food_id'] ?>" type="number" name="quantity" min="1" max="99" value="<?= (int) $item['quantity'] ?>"><button class="button button-secondary button-small" type="submit">Update</button></form>
          </div>
          <div class="cart-side" style="text-align:right"><strong><?= h(money($item['price'] * $item['quantity'])) ?></strong><form method="post" action="<?= h(app_url('cart.remove')) ?>" data-confirm="Remove <?= h($item['name']) ?> from your cart?" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="food_id" value="<?= (int) $item['food_id'] ?>"><button class="link-button small" type="submit">Remove</button></form></div>
        </article>
      <?php endforeach; ?>
    </section>
    <aside class="panel" aria-label="Order summary">
      <h2 class="panel-title">Order summary</h2>
      <div class="summary-line"><span>Items</span><span><?= count($items) ?></span></div>
      <div class="summary-line summary-total"><span>Total</span><span><?= h(money($total)) ?></span></div>
      <p class="cash-note"><strong>Cash only.</strong> Pay the vendor when you pick up the order.</p>
      <?php $ready = count($vendorIds) === 1; foreach ($items as $cartItem) { if (!(int) $cartItem['is_available'] || $cartItem['vendor_status'] !== 'ACTIVE' || !(int) $cartItem['accepting_orders'] || (int) $cartItem['quantity'] > (float) $cartItem['stock']) $ready = false; } ?>
      <?php if ($ready): ?><a class="button" style="width:100%" href="<?= h(app_url('checkout')) ?>">Review order</a><?php else: ?><button class="button button-disabled" style="width:100%" type="button" disabled>Review order</button><?php endif; ?>
      <?php if (!$ready): ?><p class="small muted">Update the cart, check stock, or wait until this vendor is accepting orders.</p><?php endif; ?>
    </aside>
  </div>
<?php else: ?>
  <div class="empty-state"><div class="empty-icon" aria-hidden="true">＋</div><h2>Your cart is empty</h2><p>Browse available campus food and add something from one vendor.</p><a class="button" href="<?= h(app_url('foods')) ?>">Browse foods</a></div>
<?php endif; ?>
