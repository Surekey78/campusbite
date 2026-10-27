<?php
/**
 * Skeleton Loader Wireframe Component
 * Mimics page layout with low-fidelity brand-tinted placeholder shapes while real content downloads.
 */
$type = $type ?? 'card';
$count = (int)($count ?? 3);
?>
<div class="skeleton-screen-wireframe" aria-hidden="true" role="status" aria-label="Loading content...">
  <?php if ($type === 'card'): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
      <?php for ($i = 0; $i < $count; $i++): ?>
        <div class="skeleton-card">
          <div class="skeleton-block" style="height: 140px; width: 100%;"></div>
          <div class="skeleton-title"></div>
          <div class="skeleton-line medium"></div>
          <div class="skeleton-line short"></div>
        </div>
      <?php endfor; ?>
    </div>
  <?php elseif ($type === 'list'): ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
      <?php for ($i = 0; $i < $count; $i++): ?>
        <div style="display: flex; align-items: center; gap: 16px; padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
          <div class="skeleton-avatar"></div>
          <div style="flex: 1;">
            <div class="skeleton-title" style="margin-bottom: 6px; width: 50%;"></div>
            <div class="skeleton-line medium" style="margin-bottom: 0;"></div>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  <?php else: ?>
    <div class="skeleton-card">
      <div class="skeleton-title"></div>
      <div class="skeleton-line medium"></div>
      <div class="skeleton-line short"></div>
      <div class="skeleton-block" style="height: 120px; width: 100%; margin-top: 12px;"></div>
    </div>
  <?php endif; ?>
</div>
