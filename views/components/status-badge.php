<?php
$badgeStatus = strtoupper((string) ($status ?? ''));
$badgeClass = match ($badgeStatus) {
    'COMPLETED', 'ACTIVE', 'PAID', 'RESTOCK', 'PURCHASE', 'PUBLISHED' => 'status-completed',
    'PENDING', 'UNPAID', 'PREPARING', 'OPEN', 'WASTE' => 'status-pending',
    'CONFIRMED', 'READY', 'RESOLVED' => 'status-ready',
    'CANCELLED', 'REJECTED', 'NO_SHOW', 'SUSPENDED', 'HIDDEN', 'DISMISSED' => 'status-cancelled',
    default => 'status-neutral',
};
$badgeText = $badgeLabel ?? status_label($badgeStatus);
unset($badgeLabel);
?>
<span class="badge <?= h($badgeClass) ?>"><?= h($badgeText) ?></span>
