<div class="page-heading"><div><p class="eyebrow">Step 2 of 3 · Review</p><h1>Confirm your order</h1><p>Check the pickup details before sending it to the vendor.</p></div><a class="button button-secondary" href="<?= h(app_url('cart')) ?>">Back to cart</a></div>
<div class="cart-layout">
  <section class="panel">
    <h2 class="panel-title">Items from <?= h($vendor['name']) ?></h2>
    <?php foreach ($items as $item): ?><div class="summary-line"><span><?= (int) $item['quantity'] ?> × <?= h($item['name']) ?> <span class="muted">· <?= h(money($item['price'])) ?> each</span></span><strong><?= h(money($item['quantity'] * $item['price'])) ?></strong></div><?php endforeach; ?>
    <div class="summary-line summary-total"><span>Total</span><strong><?= h(money($total)) ?></strong></div>
    <div class="section-heading" style="margin:22px 0 12px"><div><h2>Pickup information</h2></div></div>
    <div class="summary-line"><span>Vendor</span><strong><?= h($vendor['name']) ?></strong></div>
    <div class="summary-line"><span>Pickup location</span><strong><?= h($vendor['pickup_location']) ?><?= $vendor['stall_label'] ? ' · ' . h($vendor['stall_label']) : '' ?></strong></div>
    <?php $estimate = max((int) $vendor['prep_minutes'], max(array_map(static fn($item) => (int) ($item['prep_minutes'] ?? 0), $items))); ?>
    <p class="muted small">Estimated preparation: about <?= $estimate ?> minutes. The vendor will update your order status.</p>
  </section>
  <aside class="panel">
    <h2 class="panel-title">Payment</h2>
    <div class="payment-choice"><span aria-hidden="true">●</span><div><strong>Cash</strong><p>Pay <?= h(money($total)) ?> to the vendor when you pick up.</p></div></div>
    <p class="cash-note"><strong>CASH ONLY.</strong> No online payment is collected through CampusBite.</p>
    <form method="post" action="<?= h(app_url('order.place')) ?>" class="stack" data-submit-feedback>
      <?= csrf_field() ?>
      <div class="form-field"><label for="customer-note">Pickup note <span class="muted">(optional)</span></label><textarea id="customer-note" name="customer_note" maxlength="300" rows="3" placeholder="Anything the vendor should know?"<?= form_field_invalid_attr('customer_note') ?>><?= h(old('customer_note')) ?></textarea><?= field_error_html('customer_note') ?></div>
      <button class="button" type="submit" data-loading-text="Sending order…">Place order · <?= h(money($total)) ?></button>
      <p class="small muted" style="margin:0">The vendor confirms your order before preparing it.</p>
    </form>
  </aside>
</div>
