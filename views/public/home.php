<section class="hero" aria-labelledby="hero-title">
  <div>
    <p class="eyebrow">Campus food, made easy</p>
    <h1 id="hero-title"><?= h($hero['title'] ?? 'Discover campus food. Order ahead. Pick up on campus.') ?></h1>
    <p><?= h($heroNote['body'] ?? 'Browse campus vendors without an account. Sign in when you are ready to order.') ?></p>
    <?php if (!empty($hero['image_media_id'])): ?><img class="hero-image" src="<?= h(app_url('media.view', ['id' => (int) $hero['image_media_id']])) ?>" alt="<?= h($hero['title'] ?? 'Campus food') ?>" loading="lazy"><?php endif; ?>
    <form class="search-box" action="<?= h(app_url('foods')) ?>" method="get" role="search">
      <input type="hidden" name="r" value="foods">
      <label class="sr-only" for="home-search">Search foods or vendors</label>
      <input id="home-search" type="search" name="q" placeholder="Try chicken rice, coffee, or a vendor" maxlength="80">
      <button class="button button-primary" type="submit">Find food</button>
    </form>
  </div>
  <div class="hero-steps" aria-label="How ordering works">
    <h2>Discover. Order. Pick up.</h2>
    <div class="hero-step-row">
      <div class="step-line"><span class="step-number">1</span><span>Choose a campus vendor</span></div>
      <div class="step-line"><span class="step-number">2</span><span>Order ahead in a few taps</span></div>
      <div class="step-line"><span class="step-number">3</span><span>Pay cash at pickup</span></div>
    </div>
  </div>
</section>

<?php if (!empty($announcement)): ?>
  <aside class="announcement" aria-label="Campus announcement"><span aria-hidden="true">●</span><div><strong><?= h($announcement['title']) ?></strong><p><?= h($announcement['body']) ?></p></div></aside>
<?php endif; ?>

<section aria-labelledby="categories-title">
  <div class="section-heading"><div><h2 id="categories-title">Browse by category</h2><p>Start with what sounds good.</p></div><a class="text-link" href="<?= h(app_url('foods')) ?>">All foods →</a></div>
  <div class="category-row">
    <?php foreach ($categories as $category): ?>
      <a class="category-chip" href="<?= h(app_url('foods', ['category' => (int) $category['id']])) ?>"><span><?= h($category['name']) ?></span><span class="category-count"><?= (int) $category['food_count'] ?></span></a>
    <?php endforeach; ?>
    <?php if (!$categories): ?><span class="muted small">Categories will appear here when vendors list food.</span><?php endif; ?>
  </div>
</section>

<?php if ($featured): ?>
<section aria-labelledby="featured-title" class="featured-section">
  <div class="section-heading"><div><h2 id="featured-title">Featured foods</h2><p>A few campus picks worth a closer look.</p></div><a class="text-link" href="<?= h(app_url('foods', ['sort' => 'featured'])) ?>">Browse menu →</a></div>
  <div class="featured-food-layout<?= count($featured) === 1 ? ' featured-food-layout-single' : '' ?>">
    <?php $food = $featured[0]; require APP_ROOT . '/views/components/featured-food-card.php'; ?>
    <?php if (count($featured) > 1): ?><div class="featured-food-support<?= count($featured) === 2 ? ' featured-food-support-single' : '' ?>"><?php foreach (array_slice($featured, 1) as $food): require APP_ROOT . '/views/components/food-card.php'; endforeach; ?></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($popular): ?>
<section aria-labelledby="popular-title">
  <div class="section-heading"><div><h2 id="popular-title">Popular on campus</h2><p>Based on completed CampusBite sales, including student pickups and vendor POS.</p></div><a class="text-link" href="<?= h(app_url('foods', ['sort' => 'popular'])) ?>">See popular →</a></div>
  <div class="food-grid"><?php foreach ($popular as $food): require APP_ROOT . '/views/components/food-card.php'; endforeach; ?></div>
</section>
<?php endif; ?>

<?php if ($topRated): ?>
<section aria-labelledby="rated-title">
  <div class="section-heading"><div><h2 id="rated-title">Reviewed by students</h2><p>Foods with published reviews, ordered by average rating.</p></div><a class="text-link" href="<?= h(app_url('foods', ['sort' => 'rating'])) ?>">Browse ratings →</a></div>
  <div class="food-grid"><?php foreach ($topRated as $food): require APP_ROOT . '/views/components/food-card.php'; endforeach; ?></div>
</section>
<?php endif; ?>

<section aria-labelledby="vendors-title">
  <div class="section-heading"><div><h2 id="vendors-title">Campus vendors</h2><p>Find a pickup spot close to your next class.</p></div><a class="text-link" href="<?= h(app_url('vendors')) ?>">All vendors →</a></div>
  <div class="vendor-grid">
    <?php foreach ($vendors as $vendor): ?>
      <article class="vendor-card">
        <div class="row"><span class="vendor-mark" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($vendor['name'], 0, 2, 'UTF-8'), 'UTF-8')) ?></span><div><h3><a href="<?= h(app_url('vendor', ['slug' => $vendor['slug']])) ?>"><?= h($vendor['name']) ?></a></h3><span class="muted small"><?= h($vendor['pickup_location']) ?><?= $vendor['stall_label'] ? ' · ' . h($vendor['stall_label']) : '' ?></span><div><span class="availability small<?= empty($vendor['accepting_orders']) ? ' is-unavailable' : '' ?>"><?= empty($vendor['accepting_orders']) ? 'Orders paused' : 'Accepting orders' ?></span></div></div></div>
        <p><?= h(mb_strimwidth((string) $vendor['description'], 0, 110, '…', 'UTF-8')) ?></p>
        <a class="text-link" href="<?= h(app_url('vendor', ['slug' => $vendor['slug']])) ?>">View menu · <?= (int) $vendor['food_count'] ?> in stock →</a>
      </article>
    <?php endforeach; ?>
    <?php if (!$vendors): ?><div class="empty-state"><h2>No vendors yet</h2><p>Approved campus vendors will show up here.</p></div><?php endif; ?>
  </div>
</section>

<section class="surface" aria-labelledby="how-title" style="margin-top:28px">
  <div class="section-heading" style="margin-top:0"><div><h2 id="how-title">A simple campus pickup</h2><p>No online payment, no extra steps.</p></div></div>
  <div class="three-steps">
    <div><strong>1. Choose</strong><p>Browse menus, availability, ratings, and pickup locations.</p></div>
    <div><strong>2. Order ahead</strong><p>Keep one vendor in your cart and follow the order status.</p></div>
    <div><strong>3. Pay cash and pick up</strong><p>Pay the vendor when your order is ready.</p></div>
  </div>
</section>
