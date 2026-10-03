<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/app/Validators/InputValidator.php';
require_once __DIR__ . '/app/Middleware/Auth.php';

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

$route = trim((string) ($_GET['r'] ?? 'home'));
if (!preg_match('/^[a-z0-9.]+$/i', $route)) $route = 'not-found';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    $database = db();
    $routes = require __DIR__ . '/routes/web.php';

    if (maintenance_enabled() && !in_array($route, ['auth.login', 'auth.logout'], true)) {
        $maintenanceUser = current_user();
        if (!$maintenanceUser || $maintenanceUser['role'] !== 'ADMIN' || !can('system.manage')) {
            throw new HttpException(503, 'CampusBite is temporarily unavailable while campus maintenance is in progress. Please try again later.');
        }
    }

    if ($method === 'GET' && $route !== 'media.view' && $route !== 'notifications.open') {
        $allowedParams = ['id', 'slug', 'item_id', 'page', 'q', 'category', 'vendor', 'sort', 'status', 'start', 'end', 'role'];
        $backParams = [];
        foreach ($allowedParams as $key) {
            if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
                $value = (string) $_GET[$key];
                if (strlen($value) <= 180) $backParams[$key] = $value;
            }
        }
        $_SESSION['_back_to'] = ['route' => $route, 'params' => $backParams];
    }

    if ($method === 'POST') csrf_verify();
    if (!isset($routes[$route])) throw new HttpException(404, 'We could not find that page.');
    [$class, $action] = $routes[$route];
    $controller = new $class($database);
    $controller->{$action}();
} catch (InvalidArgumentException $error) {
    flash_set('error', $error->getMessage());
    if ($method === 'POST') {
        $back = $_SESSION['_back_to'] ?? ['route' => 'home', 'params' => []];
        $backRoute = preg_match('/^[a-z0-9.]+$/i', (string) ($back['route'] ?? '')) ? $back['route'] : 'home';
        redirect($backRoute, is_array($back['params'] ?? null) ? $back['params'] : []);
    }
    http_response_code(422);
    $status = 422; $errorMessage = $error->getMessage(); $title = 'Please check your request';
    require __DIR__ . '/views/errors/http.php';
} catch (HttpException $error) {
    http_response_code($error->statusCode());
    $status = $error->statusCode(); $errorMessage = $error->getMessage(); $title = $status === 404 ? 'Page not found' : ($status === 403 ? 'Access unavailable' : 'Request could not be completed');
    require __DIR__ . '/views/errors/http.php';
} catch (Throwable $error) {
    app_log($error);
    http_response_code(500);
    if (!isset($database) || !($database instanceof mysqli)) {
        $title = 'Database unavailable';
        $errorMessage = 'CampusBite could not connect to MySQL. Start MySQL in XAMPP, import schema.sql, and check config/database.php.';
    } else {
        $title = 'Something went wrong';
        $errorMessage = 'Something went wrong while loading this page. Please try again. If it continues, contact the campus administrator.';
    }
    $status = 500;
    require __DIR__ . '/views/errors/http.php';
}
