<?php
$pages = (int) ceil(($total ?? 0) / max(1, $perPage ?? 12));
if ($pages > 1):
  $params = $queryParams ?? [];
?>
<nav class="pagination" aria-label="Pages">
  <?php if ($page > 1): ?><a class="button button-secondary button-small" href="<?= h(app_url($routeName, array_merge($params, ['page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
  <span class="muted small">Page <?= (int) $page ?> of <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="button button-secondary button-small" href="<?= h(app_url($routeName, array_merge($params, ['page' => $page + 1]))) ?>">Next</a><?php endif; ?>
</nav>
<?php endif; ?>
