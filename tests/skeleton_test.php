<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, '.skeleton-block') !== false, "Skeleton block CSS missing");
assert(strpos($css, '.skeleton-line') !== false, "Skeleton line CSS missing");
echo "Structural skeleton loader wireframe shapes verified successfully.\n";
