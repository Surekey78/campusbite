<div class="page-heading"><div><p class="eyebrow">Campus menus</p><h1><?= $query !== '' ? 'Food search' : 'All foods' ?></h1><p><?= $total ? (int) $total . ' in-stock menu ' . ($total === 1 ? 'item' : 'items') : 'Search in-stock menus from active campus vendors.' ?></p></div></div>
<form class="filter-bar" action="<?= h(app_url('foods')) ?>" method="get" role="search">
  <input type="hidden" name="r" value="foods">
  <div class="form-field search-span"><label for="food-q">Search</label><input id="food-q" type="search" name="q" value="<?= h($query) ?>" placeholder="Food, ingredient, or vendor" maxlength="80"></div>
  <div class="form-field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"<?= $categoryId === (int) $category['id'] ? ' selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div>
  <div class="form-field"><label for="vendor">Vendor</label><select id="vendor" name="vendor"><option value="">All vendors</option><?php foreach ($vendors as $vendor): ?><option value="<?= (int) $vendor['id'] ?>"<?= $vendorId === (int) $vendor['id'] ? ' selected' : '' ?>><?= h($vendor['name']) ?></option><?php endforeach; ?></select></div>
  <div class="form-field"><label for="sort">Sort by</label><select id="sort" name="sort"><option value="featured"<?= $sort === 'featured' ? ' selected' : '' ?>>Featured</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Popular</option><option value="rating"<?= $sort === 'rating' ? ' selected' : '' ?>>Top rated</option><option value="price_asc"<?= $sort === 'price_asc' ? ' selected' : '' ?>>Price: low to high</option><option value="price_desc"<?= $sort === 'price_desc' ? ' selected' : '' ?>>Price: high to low</option></select></div>
  <button class="button" type="submit">Apply</button>
</form>
<?php if ($foods): ?>
  <div class="food-grid"><?php foreach ($foods as $food): require APP_ROOT . '/views/components/food-card.php'; endforeach; ?></div>
  <?php $routeName='foods'; $perPage=12; $queryParams=['q'=>$query,'category'=>$categoryId,'vendor'=>$vendorId,'sort'=>$sort]; require APP_ROOT . '/views/components/pagination.php'; ?>
<?php else: ?>
  <div class="empty-state"><div class="empty-icon" aria-hidden="true">⌕</div><h2>No foods found</h2><p>Try another search or remove a filter. Vendors update availability throughout the day.</p><a class="button button-secondary" href="<?= h(app_url('foods')) ?>">Clear filters</a></div>
<?php endif; ?>
