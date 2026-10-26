<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, '.skeleton') !== false, "Skeleton CSS class missing");
assert(strpos($css, 'skeleton-shimmer') !== false, "Skeleton shimmer animation missing");
echo "Skeleton loading screen CSS verified successfully.\n";
