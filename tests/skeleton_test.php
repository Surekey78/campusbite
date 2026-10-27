<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, 'rgba(254, 243, 199') !== false, "Brand-tinted warm yellow gradient missing");
assert(file_exists(APP_ROOT . '/views/components/skeleton.php'), "Skeleton wireframe component missing");
echo "Brand-tinted skeleton shimmer and wireframe component verified successfully.\n";
