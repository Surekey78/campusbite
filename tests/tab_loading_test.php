<?php
define('APP_ROOT', dirname(__DIR__));
$css = file_get_contents(APP_ROOT . '/assets/css/app.css');
assert(strpos($css, 'top-loading-bar') !== false, "Top loading progress bar CSS missing");
echo "Top loading progress bar verified successfully.\n";
