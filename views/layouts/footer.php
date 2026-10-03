<?php
$footerPages = [];
try {
    $footerPages = db_all("SELECT content_key, title FROM cms_content WHERE section='page' AND is_published=1 ORDER BY content_key");
} catch (Throwable $e) {
    // Keep the page usable if the CMS table is unavailable.
}
?>
  </div>
</main>
<footer class="site-footer">
  <div class="container footer-inner">
    <div><strong><?= h(site_name()) ?></strong><br><span>Discover. Order. Pick up. · Cash at pickup.</span></div>
    <nav class="footer-links" aria-label="Information">
      <?php foreach ($footerPages as $page): if (preg_match('/^page\.([a-z0-9]+(?:-[a-z0-9]+)*)$/', (string) $page['content_key'], $match)): ?>
        <a href="<?= h(app_url('content', ['page' => $match[1]])) ?>"><?= h($page['title']) ?></a>
      <?php endif; endforeach; ?>
    </nav>
  </div>
</footer>
</body>
</html>
