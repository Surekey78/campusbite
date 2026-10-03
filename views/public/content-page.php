<article class="content-page panel">
  <p class="eyebrow">CampusBite information</p>
  <h1 class="page-title"><?= h($content['title']) ?></h1>
  <?php if (!empty($content['image_media_id'])): ?><img class="content-page-image" src="<?= h(app_url('media.view', ['id' => (int) $content['image_media_id']])) ?>" alt="" loading="lazy"><?php endif; ?>
  <div class="content-copy" style="margin-top:18px"><?= nl2br(h($content['body'] ?? '')) ?></div>
</article>
