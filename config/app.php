<?php
require_once __DIR__ . '/constants.php';

define('APP_ROOT', dirname(__DIR__));
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
define('APP_BASE', rtrim($scriptDirectory, '/') === '/' ? '' : rtrim($scriptDirectory, '/'));

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$isSecure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('campusbite_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => APP_BASE !== '' ? APP_BASE . '/' : '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
