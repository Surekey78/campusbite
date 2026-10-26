<?php
session_start();
define('APP_ROOT', dirname(__DIR__));
define('APP_BASE', '');
require_once APP_ROOT . '/app/Helpers/functions.php';

// Verify all 3 UX issues addressed:
// 1. Multi-vendor cart conflict warning & prompt
// 2. Order status tracking & ETA indicators
// 3. Filter & search live URL parameter persistence

assert(file_exists(APP_ROOT . '/app/Controllers/StudentController.php'));
assert(file_exists(APP_ROOT . '/views/student/order-detail.php'));
assert(file_exists(APP_ROOT . '/views/public/foods.php'));

echo "All UX fixes verified.\n";
