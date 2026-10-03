<nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= h(app_url('foods')) ?>">Foods</a><span aria-hidden="true">/</span><span><?= h($food['name']) ?></span></nav>
<section class="detail-layout" aria-labelledby="food-title">
  <div>
    <?php if (!empty($food['media_id'])): ?><img class="detail-image" src="<?= h(app_url('media.view', ['id' => (int) $food['media_id']])) ?>" alt="<?= h($food['name']) ?>" fetchpriority="high">
    <?php else: ?><div class="detail-image food-placeholder" role="img" aria-label="Food image unavailable">🍽</div><?php endif; ?>
    <div class="detail-facts">
      <div class="fact"><span>Availability</span><strong class="availability<?= empty($food['accepting_orders']) ? ' is-unavailable' : '' ?>"><?= empty($food['accepting_orders']) ? 'Orders paused' : 'Available to order' ?></strong></div>
      <div class="fact"><span>Preparation</span><strong>About <?= (int) $food['prep_minutes'] ?> minutes</strong></div>
      <div class="fact"><span>Pickup</span><strong><?= h($food['pickup_location']) ?></strong></div>
      <div class="fact"><span>Vendor</span><strong><a href="<?= h(app_url('vendor', ['slug' => $food['vendor_slug']])) ?>"><?= h($food['vendor_name']) ?></a><?= $food['stall_label'] ? ' · ' . h($food['stall_label']) : '' ?></strong></div>
    </div>
  </div>
  <div class="detail-info">
    <p class="eyebrow"><?= h($food['category_name'] ?? 'Campus food') ?></p>
    <h1 id="food-title"><?= h($food['name']) ?></h1>
    <div class="food-meta" style="margin-top:10px"><span class="rating"><?= (int) $food['rating_count'] ? '★ ' . h(number_format((float) $food['average_rating'], 1)) . ' (' . (int) $food['rating_count'] . ' ratings)' : 'Not yet rated' ?></span><span>from <a href="<?= h(app_url('vendor', ['slug' => $food['vendor_slug']])) ?>"><?= h($food['vendor_name']) ?></a></span></div>
    <p class="detail-price"><?= h(money($food['price'])) ?></p>
    <p class="detail-copy"><?= nl2br(h($food['description'] ?: 'Freshly prepared by a campus vendor.')) ?></p>
    <div class="surface" style="margin-top:18px">
      <h2 class="panel-title">Order this food</h2>
      <?php $user = current_user(); if (empty($food['accepting_orders'])): ?>
        <p class="muted small">This vendor is not accepting new orders right now. You can still browse the menu.</p>
      <?php elseif ($user && $user['role'] === 'STUDENT'): ?>
        <form method="post" action="<?= h(app_url('cart.add')) ?>" class="row-wrap" data-submit-feedback>
          <?= csrf_field() ?><input type="hidden" name="food_id" value="<?= (int) $food['id'] ?>"><input type="hidden" name="return_to" value="food">
          <label class="form-field" for="quantity" style="max-width:120px"><span class="field-label">Quantity</span><input id="quantity" type="number" name="quantity" min="1" max="99" value="1" required></label>
          <button class="button" type="submit" style="align-self:end" data-loading-text="Adding…">Add to cart</button>
        </form>
      <?php elseif (!$user): ?>
        <p class="muted small">Browse freely. Sign in when you are ready to place an order.</p><a class="button" href="<?= h(app_url('auth.login')) ?>">Sign in to order</a>
      <?php else: ?>
        <p class="muted small">Ordering is available from a student account.</p>
      <?php endif; ?>
      <p class="cash-note" style="margin-bottom:0"><strong>Cash only.</strong> Pay the vendor when you pick up your order.</p>
    </div>
  </div>
</section>
<section aria-labelledby="reviews-title">
  <div class="section-heading"><div><h2 id="reviews-title">Student reviews</h2><p><?= (int) $food['rating_count'] ?> verified rating<?= (int) $food['rating_count'] === 1 ? '' : 's' ?> from completed purchases.</p></div></div>
  <?php if ($reviews): ?><div class="review-list">
    <?php foreach ($reviews as $review): ?>
      <article class="review-card">
        <div class="spread"><div><strong><?= h($review['full_name']) ?></strong><span class="muted small"> · <?= h(format_datetime($review['created_at'], 'M j, Y')) ?></span></div><span class="stars" aria-label="<?= (int) $review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5-(int) $review['rating']) ?></span></div>
        <?php if ($review['comment']): ?><p><?= nl2br(h($review['comment'])) ?></p><?php endif; ?>
        <?php if ($review['vendor_response']): ?><div class="vendor-reply"><strong>Vendor response</strong><p><?= nl2br(h($review['vendor_response'])) ?></p></div><?php endif; ?>
        <?php if (current_user() && current_user()['role'] === 'STUDENT' && (int) current_user()['id'] !== (int) $review['user_id']): ?>
          <details><summary class="small">Report this review</summary><form method="post" action="<?= h(app_url('reviews.report')) ?>" class="row-wrap" style="margin-top:9px"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><label class="sr-only" for="report-<?= (int) $review['id'] ?>">Why are you reporting this review?</label><input id="report-<?= (int) $review['id'] ?>" class="control" name="reason" maxlength="500" placeholder="Reason for report" required style="max-width:380px"><button class="button button-secondary button-small" type="submit">Send report</button></form></details>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div><?php else: ?><div class="empty-state"><h2>No reviews yet</h2><p>Students can leave a rating after a completed pickup.</p></div><?php endif; ?>
</section>
