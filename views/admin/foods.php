<div class="page-heading"><div><p class="eyebrow">Administration</p><h1>All foods</h1><p>Control food listings and homepage features. Up to three featured foods show on the home page; if more are marked, the app ranks them by completed sales and published ratings. Picks appear only while listed, in stock, and owned by an active vendor.</p></div></div>
<form class="toolbar" method="get" action="<?= h(app_url('admin.foods')) ?>"><input type="hidden" name="r" value="admin.foods"><div class="form-field"><label for="q">Search food or vendor</label><input id="q" name="q" value="<?= h($q) ?>" maxlength="80"></div><button class="button button-secondary" type="submit">Search</button></form>
<?php if ($foods): ?>
  <div class="table-wrap"><table><thead><tr><th>Food</th><th>Vendor</th><th>Category</th><th>Price</th><th>Listing</th><th>Home feature</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($foods as $food): ?>
      <tr>
        <td><div class="row"><div class="mini-thumb"><?php if ($food['media_id']): ?><img src="<?= h(app_url('media.view',['id'=>(int)$food['media_id']])) ?>" alt="" loading="lazy"><?php else: ?>🍽<?php endif; ?></div><strong><?= h($food['name']) ?></strong></div></td>
        <td><?= h($food['vendor_name']) ?></td><td><?= h($food['category_name'] ?? '—') ?></td><td><?= h(money($food['price'])) ?></td>
        <td><?php $status=$food['is_available']?'ACTIVE':'HIDDEN';$badgeLabel=$food['is_available']?'Listed':'Unlisted';require APP_ROOT . '/views/components/status-badge.php'; ?></td>
        <td><?php $status=$food['is_featured']?'ACTIVE':'HIDDEN';$badgeLabel=$food['is_featured']?'Featured':'Not featured';require APP_ROOT . '/views/components/status-badge.php'; ?></td>
        <td><div class="row-wrap">
          <form method="post" action="<?= h(app_url('admin.food.feature')) ?>" data-confirm="<?= !empty($food['is_featured']) ? 'Remove ' : 'Feature ' ?><?= h($food['name']) ?><?= !empty($food['is_featured']) ? ' from' : ' on' ?> the home page?" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$food['id'] ?>"><button class="button button-secondary button-small" type="submit" aria-label="<?= !empty($food['is_featured']) ? 'Remove ' : 'Feature ' ?><?= h($food['name']) ?><?= !empty($food['is_featured']) ? ' from the home page featured list' : ' on the home page' ?>"><?= !empty($food['is_featured']) ? 'Unfeature' : 'Feature' ?></button>
          </form>
          <form method="post" action="<?= h(app_url('admin.food.toggle')) ?>" data-confirm="<?= $food['is_available']?'Unlist':'List' ?> <?= h($food['name']) ?>?" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$food['id'] ?>"><button class="button button-secondary button-small" type="submit"><?= $food['is_available']?'Unlist':'List' ?></button>
          </form>
        </div></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
<?php else: ?><div class="empty-state"><h2>No foods found</h2><p>Try a different search.</p></div><?php endif; ?>
