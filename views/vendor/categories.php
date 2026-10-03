<div class="page-heading"><div><p class="eyebrow">Storefront</p><h1>Food categories</h1><p>Campus-wide categories are read-only. Manage private categories for your own menu here.</p></div></div>
<div class="dashboard-grid">
  <section class="panel"><h2 class="panel-title">Available categories</h2>
    <?php if ($categories): ?><div class="table-wrap"><table><thead><tr><th>Category</th><th>Scope</th><th>Foods</th><th>Status</th><th>Manage</th></tr></thead><tbody>
      <?php foreach ($categories as $category): ?><tr>
        <td><strong><?= h($category['name']) ?></strong><?php if ($category['description']): ?><div class="muted small"><?= h($category['description']) ?></div><?php endif; ?></td>
        <td><?= $category['vendor_id'] ? 'Your storefront' : 'Campus-wide' ?></td>
        <td><?= (int) $category['food_count'] ?></td>
        <td><?= $category['is_active'] ? 'Active' : 'Inactive' ?></td>
        <td><?php if ($category['vendor_id']): ?><div class="category-actions">
          <details class="category-edit"><summary class="text-link">Edit</summary><form method="post" action="<?= h(app_url('vendor.category.save')) ?>" class="stack" data-submit-feedback><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><div class="form-field"><label for="category-name-<?= (int) $category['id'] ?>">Category name</label><input id="category-name-<?= (int) $category['id'] ?>" name="name" maxlength="80" value="<?= h($category['name']) ?>" required></div><div class="form-field"><label for="category-description-<?= (int) $category['id'] ?>">Description</label><input id="category-description-<?= (int) $category['id'] ?>" name="description" maxlength="240" value="<?= h($category['description'] ?? '') ?>"></div><button class="button button-small" type="submit" data-loading-text="Saving…">Save category</button></form></details>
          <form method="post" action="<?= h(app_url('vendor.category.toggle')) ?>"<?= $category['is_active'] && (int) $category['food_count'] > 0 ? '' : ' data-confirm="' . h(($category['is_active'] ? 'Deactivate ' : 'Activate ') . $category['name'] . '?') . '"' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="button button-secondary button-small" type="submit"<?= $category['is_active'] && (int) $category['food_count'] > 0 ? ' disabled title="Reassign foods before deactivating this category."' : '' ?>><?= $category['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
          <?php if ($category['is_active'] && (int) $category['food_count'] > 0): ?><span class="field-hint">Reassign foods to deactivate.</span><?php endif; ?>
        </div><?php else: ?><span class="muted small">Managed by campus</span><?php endif; ?></td>
      </tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><p class="muted">No categories are available yet.</p><?php endif; ?>
  </section>
  <section class="panel"><h2 class="panel-title">Add a vendor category</h2><form method="post" action="<?= h(app_url('vendor.category.save')) ?>" class="stack" data-submit-feedback><?= csrf_field() ?><div class="form-field"><label for="name">Category name</label><input id="name" name="name" maxlength="80" required></div><div class="form-field"><label for="description">Description <span class="muted">(optional)</span></label><input id="description" name="description" maxlength="240"></div><button class="button" type="submit" data-loading-text="Adding…">Add category</button></form></section>
</div>
