<?php
define('APP_ROOT', dirname(__DIR__));
$js = file_get_contents(APP_ROOT . '/assets/js/app.js');
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($js, 'is-loading') !== false, "Navigation loading JS missing");
assert(strpos($css, 'is-loading::after') !== false, "10x Skeleton shimmer overlay missing");
echo "10x Tab transition skeleton loading overlay verified successfully.\n";
