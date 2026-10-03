<section class="form-card narrow" aria-labelledby="review-title">
  <p class="eyebrow">Completed pickup · <?= h($item['order_number']) ?></p>
  <h1 id="review-title"><?= !empty($review) ? 'Edit your review' : 'Review ' . h($item['food_name']) ?></h1>
  <p>Your rating helps students choose and gives vendors useful feedback.</p>
  <form method="post" action="<?= h(app_url('reviews.save')) ?>" class="stack" data-submit-feedback>
    <?= csrf_field() ?>
    <?php if (!empty($review)): ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><?php else: ?><input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
    <fieldset class="rating-field"><legend class="field-label">Rating <span class="required">*</span></legend><div class="rating-options">
      <?php for ($star=5; $star>=1; $star--): ?><label><input type="radio" name="rating" value="<?= $star ?>"<?= (int) ($review['rating'] ?? 5) === $star ? ' checked' : '' ?> required><span aria-hidden="true"><?= str_repeat('★', $star) ?></span><span class="sr-only"><?= $star ?> <?= $star === 1 ? 'star' : 'stars' ?></span></label><?php endfor; ?>
    </div></fieldset>
    <div class="form-field"><label for="comment">Comment <span class="muted">(optional)</span></label><textarea id="comment" name="comment" maxlength="1000" rows="5" placeholder="Share a useful detail about the food or pickup."><?= h($review['comment'] ?? '') ?></textarea></div>
    <div class="form-actions"><button class="button" type="submit" data-loading-text="Saving…">Save review</button><a class="button button-secondary" href="<?= h(app_url('my.order', ['id' => (int) ($review['order_id'] ?? $item['order_id'] ?? 0)])) ?>">Cancel</a></div>
  </form>
</section>
