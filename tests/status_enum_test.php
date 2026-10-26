<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_BASE', '');
require_once APP_ROOT . '/app/Helpers/functions.php';
$db = db();
$db->query("UPDATE orders SET status = 'NO_SHOW' WHERE id = 2");
$order = db_one("SELECT status FROM orders WHERE id = 2");
assert($order['status'] === 'NO_SHOW', "Failed to update order status to NO_SHOW");
echo "Status enum test passed.\n";
