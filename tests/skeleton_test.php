<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, 'rgba(254, 243, 199') !== false, "Brand-tinted warm yellow gradient missing");
echo "Brand-tinted skeleton shimmer verified successfully.\n";
