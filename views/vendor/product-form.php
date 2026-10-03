<section class="form-card" aria-labelledby="product-title">
  <p class="eyebrow">Storefront · <?= h($vendor['name']) ?></p><h1 id="product-title"><?= $food ? 'Edit food' : 'Add food' ?></h1><p>Clear names and prices help students decide quickly.</p>
  <form method="post" enctype="multipart/form-data" action="<?= h(app_url('vendor.product.save')) ?>" class="form-grid" data-submit-feedback>
    <?= csrf_field() ?><?php if ($food): ?><input type="hidden" name="id" value="<?= (int) $food['id'] ?>"><?php endif; ?>
    <div class="form-field"><label for="name">Food name</label><input id="name" name="name" maxlength="140" value="<?= h($food['name'] ?? '') ?>" required></div>
    <div class="form-field"><label for="price">Price (₱)</label><input id="price" name="price" type="number" min="0.01" max="100000" step="0.01" value="<?= h($food['price'] ?? '') ?>" required></div>
    <div class="form-field"><label for="category">Category</label><select id="category" name="category_id"><option value="0">No category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"<?= (int) ($food['category_id'] ?? 0) === (int) $category['id'] ? ' selected' : '' ?>><?= h($category['name']) ?><?= $category['vendor_id'] ? ' · your category' : '' ?></option><?php endforeach; ?></select><span class="field-hint"><a href="<?= h(app_url('vendor.categories')) ?>">Manage categories</a></span></div>
    <div class="form-field"><label for="prep">Preparation time (minutes)</label><input id="prep" name="prep_minutes" type="number" min="1" max="240" value="<?= h($food['prep_minutes'] ?? 10) ?>" required></div>
    <div class="form-field full"><label for="description">Description</label><textarea id="description" name="description" maxlength="3000" rows="4" placeholder="Ingredients or a useful detail for students"><?= h($food['description'] ?? '') ?></textarea></div>
    <div class="form-field full"><label for="image">Food image <span class="muted">(optional · JPG, PNG, WebP · max 3 MB)</span></label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">Images are stored in the database, not an uploads folder.</span><?php if (!empty($food['media_id'])): ?><img class="form-image-preview" src="<?= h(app_url('media.view', ['id' => (int) $food['media_id']])) ?>" alt="Current food image" width="160" loading="lazy"><?php endif; ?></div>
    <?php if (!$food): ?>
      <div class="form-field"><label for="initial-stock">Opening stock</label><input id="initial-stock" type="number" name="initial_stock" min="0" max="1000000" step="0.01" value="0"><span class="field-hint">An opening stock movement is recorded if above zero.</span></div>
      <div class="form-field"><label for="unit">Stock unit</label><input id="unit" name="unit" maxlength="30" value="servings" required></div>
      <div class="form-field"><label for="threshold">Low-stock threshold</label><input id="threshold" type="number" name="low_stock_threshold" min="0" max="1000000" step="0.01" value="5"></div>
    <?php endif; ?>
    <div class="form-field full"><label class="check-row"><input type="checkbox" name="is_available" value="1"<?= !$food || $food['is_available'] ? ' checked' : '' ?>><span>Available to order</span></label></div>
    <div class="form-field full"><div class="form-actions"><button class="button" type="submit" data-loading-text="Saving…">Save food</button><a class="button button-secondary" href="<?= h(app_url('vendor.products')) ?>">Cancel</a></div></div>
  </form>
</section>
