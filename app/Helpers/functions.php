<?php
require_once APP_ROOT . '/config/database.php';

class HttpException extends RuntimeException
{
    private int $httpStatus;
    public function __construct(int $httpStatus, string $message)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
    }
    public function statusCode(): int { return $this->httpStatus; }
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $route = 'home', array $params = []): string
{
    $query = array_merge(['r' => $route], $params);
    return APP_BASE . '/index.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function redirect(string $route = 'home', array $params = []): void
{
    header('Location: ' . app_url($route, $params), true, 303);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    $known = (string) ($_SESSION['_csrf'] ?? '');
    if ($sent === '' || $known === '' || !hash_equals($known, $sent)) {
        throw new HttpException(419, 'This form expired or could not be verified. Please go back, refresh, and try again.');
    }
}

function bind_values(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }
    $refs = [];
    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db()->prepare($sql);
    bind_values($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_exec(string $sql, string $types = '', array $params = []): array
{
    $stmt = db()->prepare($sql);
    bind_values($stmt, $types, $params);
    $stmt->execute();
    $result = ['affected' => $stmt->affected_rows, 'insert_id' => $stmt->insert_id];
    $stmt->close();
    return $result;
}

function current_user(): ?array
{
    static $resolved = false;
    static $user = null;
    if ($resolved) {
        return $user;
    }
    $resolved = true;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id < 1) {
        return null;
    }
    $user = db_one('SELECT u.id, u.role_id, u.full_name, u.email, u.phone, u.status, r.name AS role, u.created_at FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1', 'i', [$id]);
    if (!$user || $user['status'] !== 'ACTIVE') {
        $user = null;
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function can(string $permission): bool
{
    static $permissions = null;
    if ($permissions === null) {
        $permissions = [];
        $user = current_user();
        if ($user) {
            foreach (db_all('SELECT p.permission_key FROM permissions p JOIN role_permissions rp ON rp.permission_id = p.id WHERE rp.role_id = ?', 'i', [(int) $user['role_id']]) as $row) {
                $permissions[$row['permission_key']] = true;
            }
        }
    }
    return isset($permissions[$permission]);
}

function current_vendor(): ?array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'VENDOR') {
        return null;
    }
    return db_one('SELECT v.*, u.full_name AS owner_name FROM vendors v JOIN users u ON u.id = v.user_id WHERE v.user_id = ? LIMIT 1', 'i', [(int) $user['id']]);
}

function site_name(): string
{
    static $name = null;
    if ($name !== null) return $name;
    $name = APP_NAME;
    try {
        $row = db_one("SELECT setting_value FROM system_settings WHERE setting_key='site_name' LIMIT 1");
        if ($row && trim((string) $row['setting_value']) !== '') $name = trim((string) $row['setting_value']);
    } catch (Throwable $e) {
        // Keep the default brand available if the settings table is unavailable.
    }
    return $name;
}

function maintenance_enabled(): bool
{
    try {
        $row = db_one("SELECT setting_value FROM system_settings WHERE setting_key='maintenance_mode' LIMIT 1");
        return (string) ($row['setting_value'] ?? '0') === '1';
    } catch (Throwable $e) {
        return false;
    }
}

function money($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function format_datetime($value, string $format = 'M j, Y · g:i A'): string
{
    if (!$value) return '—';
    try { return (new DateTime((string) $value))->format($format); }
    catch (Throwable $e) { return '—'; }
}

function status_label(string $status): string
{
    return ORDER_STATUS_LABELS[$status] ?? ucwords(strtolower(str_replace('_', ' ', $status)));
}

function slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-') ?: 'item';
}

function app_log(Throwable $error): void
{
    $message = '[' . date('Y-m-d H:i:s') . '] ' . get_class($error) . ': ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine() . PHP_EOL;
    $logDir = APP_ROOT . '/storage/logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        @file_put_contents($logDir . '/app.log', $message, FILE_APPEND | LOCK_EX);
    }
    error_log(trim($message));
}

function audit_log(string $action, string $entityType = '', ?int $entityId = null, array $details = []): void
{
    $user = current_user();
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    try {
        db_exec('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', 'ississ', [
            $user ? (int) $user['id'] : null,
            substr($action, 0, 100),
            substr($entityType, 0, 60),
            $entityId,
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $ip,
        ]);
    } catch (Throwable $e) {
        app_log($e);
    }
}

function notify_user(?int $userId, string $title, string $body, string $url = ''): void
{
    if (!$userId) return;
    db_exec('INSERT INTO notifications (user_id, title, body, target_url, created_at) VALUES (?, ?, ?, ?, NOW())', 'isss', [$userId, $title, $body, $url]);
}

function upload_media(?array $file, int $userId): ?int
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    require_once APP_ROOT . '/app/Services/MediaService.php';
    return MediaService::store($file, $userId);
}
