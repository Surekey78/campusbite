<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, '.skeleton-avatar') !== false, "Skeleton avatar CSS missing");
assert(strpos($css, '.skeleton-card') !== false, "Skeleton card CSS missing");
assert(strpos($css, '.skeleton-title') !== false, "Skeleton title CSS missing");
echo "Refined structural skeleton components verified successfully.\n";
