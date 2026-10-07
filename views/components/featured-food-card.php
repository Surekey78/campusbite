<?php $featuredUser = current_user(); $featuredRating = (float) ($food['average_rating'] ?? 0); $featuredRatingCount = (int) ($food['rating_count'] ?? 0); ?>
<article class="featured-food-card" aria-labelledby="featured-food-title-<?= (int) $food['id'] ?>">
  <a class="featured-food-image" href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>" aria-label="View <?= h($food['name']) ?>">
    <?php if (!empty($food['media_id'])): ?><img src="<?= h(app_url('media.view', ['id' => (int) $food['media_id']])) ?>" alt="<?= h($food['name']) ?>" fetchpriority="high">
    <?php else: ?><span class="featured-food-placeholder" aria-hidden="true">🍽</span><?php endif; ?>
    <span class="featured-food-stamp" aria-hidden="true">Featured pick</span>
  </a>
  <div class="featured-food-content">
    <div class="featured-food-kickers"><span class="featured-food-category"><?= h($food['category_name'] ?? 'Campus food') ?></span><span><?= (int) ($food['prep_minutes'] ?? 0) ?> min</span></div>
    <h3 id="featured-food-title-<?= (int) $food['id'] ?>"><a href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>"><?= h($food['name']) ?></a></h3>
    <p class="featured-food-vendor">From <a href="<?= h(app_url('vendor', ['slug' => $food['vendor_slug']])) ?>"><?= h($food['vendor_name']) ?></a></p>
    <?php if (!empty($food['description'])): ?><p class="featured-food-description"><?= h(mb_strimwidth((string) $food['description'], 0, 155, '…', 'UTF-8')) ?></p><?php endif; ?>
    <div class="featured-food-facts"><span class="availability<?= empty($food['accepting_orders']) ? ' is-unavailable' : '' ?>"><?= empty($food['accepting_orders']) ? 'Orders paused' : 'Available to order' ?></span><span>Cash at pickup</span></div>
    <div class="featured-food-footer">
      <strong class="featured-food-price"><?= h(money($food['price'])) ?></strong>
      <span class="featured-food-rating" aria-label="<?= $featuredRatingCount ? h(number_format($featuredRating, 1) . ' out of 5, ' . $featuredRatingCount . ' reviews') : 'Not yet rated' ?>"><?php if ($featuredRatingCount): ?>★ <?= h(number_format($featuredRating, 1)) ?> <small>(<?= $featuredRatingCount ?>)</small><?php else: ?>New pick<?php endif; ?></span>
    </div>
    <div class="featured-food-actions">
      <a class="button button-secondary" href="<?= h(app_url('food', ['id' => (int) $food['id']])) ?>">View details</a>
      <?php if (!empty($food['accepting_orders']) && $featuredUser && $featuredUser['role'] === 'STUDENT'): ?>
        <form method="post" action="<?= h(app_url('cart.add')) ?>" data-submit-feedback>
          <?= csrf_field() ?><input type="hidden" name="food_id" value="<?= (int) $food['id'] ?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="return_to" value="home">
          <button class="button" type="submit" aria-label="Add <?= h($food['name']) ?> to cart" data-loading-text="Adding…">Add to cart</button>
        </form>
      <?php elseif (!empty($food['accepting_orders']) && !$featuredUser): ?>
        <a class="button" href="<?= h(app_url('auth.login')) ?>">Sign in to order</a>
      <?php endif; ?>
    </div>
  </div>
</article>
