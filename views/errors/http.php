<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title ?? 'CampusBite') ?> · CampusBite</title>
  <link rel="stylesheet" href="<?= h(APP_BASE) ?>/assets/css/app.css">
</head>
<body class="error-page">
  <main class="error-card">
    <a class="brand brand-compact" href="<?= h(app_url('home')) ?>" aria-label="CampusBite home"><span class="brand-mark" aria-hidden="true">CB</span><span>CampusBite</span></a>
    <p class="eyebrow"><?= isset($status) ? 'Request ' . (int) $status : 'CampusBite' ?></p>
    <h1><?= h($title ?? 'Something went wrong') ?></h1>
    <p><?= h($errorMessage ?? 'This page could not be shown.') ?></p>
    <div class="button-row"><a class="button button-primary" href="<?= h(app_url('home')) ?>">Go to home</a></div>
    <p class="muted small">If you are an administrator and this keeps happening, check <code>storage/logs/app.log</code>.</p>
  </main>
</body>
</html>
