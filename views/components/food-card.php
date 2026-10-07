<?php $foodUser = current_user(); ?>
<article class="food-card">
  <a class="food-image" href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>" aria-label="View <?= h($food['name']) ?>">
    <?php if (!empty($food['media_id'])): ?><img src="<?= h(app_url('media.view', ['id' => (int) $food['media_id']])) ?>" alt="<?= h($food['name']) ?>" loading="lazy">
    <?php else: ?><span class="food-placeholder" aria-hidden="true">🍽</span><?php endif; ?>
  </a>
  <div class="food-card-body">
    <h3><a href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>"><?= h($food['name']) ?></a></h3>
    <div class="food-meta">
      <a href="<?= h(app_url('vendor', ['slug' => $food['vendor_slug']])) ?>"><?= h($food['vendor_name']) ?></a>
      <span><?= (int) ($food['prep_minutes'] ?? 0) ?> min</span>
    </div>
    <div class="food-price-line">
      <span class="food-price"><?= h(money($food['price'])) ?></span>
      <span class="rating" aria-label="<?= !empty($food['rating_count']) ? h(number_format((float) $food['average_rating'], 1) . ' out of 5 stars') : 'Not yet rated' ?>">
        <?= !empty($food['rating_count']) ? '★ ' . h(number_format((float) $food['average_rating'], 1)) . ' <span class="rating-count">(' . (int) $food['rating_count'] . ')</span>' : '<span class="rating-count">New</span>' ?>
      </span>
    </div>
    <div class="food-meta food-meta-availability"><span class="availability<?= empty($food['accepting_orders']) ? ' is-unavailable' : '' ?>"><?= empty($food['accepting_orders']) ? 'Orders paused' : 'Available to order' ?></span><span><?= h($food['category_name'] ?? 'Campus food') ?></span></div>
    <div class="food-actions">
      <a class="button button-secondary button-small" href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>">View</a>
      <?php if (!empty($food['accepting_orders']) && $foodUser && $foodUser['role'] === 'STUDENT'): ?>
        <form method="post" action="<?= h(app_url('cart.add')) ?>">
          <?= csrf_field() ?><input type="hidden" name="food_id" value="<?= (int) $food['id'] ?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="return_to" value="<?= isset($currentRoute) && $currentRoute === 'home' ? 'home' : 'foods' ?>">
          <button class="button button-small" type="submit" aria-label="Add <?= h($food['name']) ?> to cart">Add to cart</button>
        </form>
      <?php elseif (!empty($food['accepting_orders']) && !$foodUser): ?>
        <a class="button button-small" href="<?= h(app_url('auth.login')) ?>">Sign in to order</a>
      <?php elseif (empty($food['accepting_orders'])): ?>
        <span class="muted small">Store closed</span>
      <?php endif; ?>
    </div>
  </div>
</article>
