<?php
$timelineStatuses = ['PENDING' => 'Order placed', 'CONFIRMED' => 'Confirmed', 'PREPARING' => 'Preparing', 'READY' => 'Ready for pickup', 'COMPLETED' => 'Picked up'];
$currentStatus = (string) ($order['status'] ?? '');
$sequence = array_keys($timelineStatuses);
$currentIndex = array_search($currentStatus, $sequence, true);
$terminal = in_array($currentStatus, ['CANCELLED', 'REJECTED', 'NO_SHOW'], true);
?>
<div class="order-track">
  <div class="spread"><strong>Order progress</strong><?php $status = $currentStatus; require APP_ROOT . '/views/components/status-badge.php'; ?></div>
  <?php if ($terminal): ?>
    <div class="alert alert-error" style="margin:14px 0 0"><span aria-hidden="true">!</span><div>This order was <?= h(strtolower(status_label($currentStatus))) ?>. See its activity below.</div></div>
  <?php else: ?>
    <div class="timeline" aria-label="Order progress">
      <?php foreach ($timelineStatuses as $key => $label): $index = array_search($key, $sequence, true); $stepClass = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : ''); ?>
        <div class="timeline-step <?= h($stepClass) ?>"<?= $index === $currentIndex ? ' aria-current="step"' : '' ?>><span><?= h($label) ?></span></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
