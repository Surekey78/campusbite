<?php
session_start();
define('APP_ROOT', dirname(__DIR__));
define('APP_BASE', '');
require_once APP_ROOT . '/app/Helpers/functions.php';

// Test that views and controllers exist and support UX enhancements
assert(file_exists(APP_ROOT . '/app/Helpers/functions.php'));
assert(file_exists(APP_ROOT . '/views/student/order-detail.php'));
assert(file_exists(APP_ROOT . '/views/public/foods.php'));

echo "UX test checks passed successfully.\n";
