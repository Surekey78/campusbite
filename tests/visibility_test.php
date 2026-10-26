<?php
define('APP_ROOT', dirname(__DIR__));
assert(file_exists(APP_ROOT . '/views/components/order-timeline.php'), "Order timeline component missing");
assert(file_exists(APP_ROOT . '/views/admin/audit.php'), "Audit log view missing");
echo "System process visibility components verified successfully.\n";
