<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = require_login();
        $notifications = db_all('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 80', 'i', [(int) $user['id']]);
        $this->view('student/notifications', compact('notifications') + ['title' => 'Notifications']);
    }

    public function poll(): void
    {
        $user = current_user();
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Sign in required.']);
            return;
        }

        $after = filter_var($_GET['after'] ?? 0, FILTER_VALIDATE_INT);
        if ($after === false || $after < 0) $after = 0;
        $rows = db_all('SELECT id, title, body, created_at FROM notifications WHERE user_id=? AND id>? ORDER BY id ASC LIMIT 20', 'ii', [(int) $user['id'], (int) $after]);
        $notifications = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $notifications[] = [
                'id' => $id,
                'title' => (string) $row['title'],
                'body' => (string) $row['body'],
                'created_at' => format_datetime($row['created_at']),
                'open_url' => app_url('notifications.open', ['id' => $id]),
            ];
        }
        $unreadCount = (int) (db_one('SELECT COUNT(*) AS n FROM notifications WHERE user_id=? AND read_at IS NULL', 'i', [(int) $user['id']])['n'] ?? 0);
        echo json_encode(['ok' => true, 'unread_count' => $unreadCount, 'notifications' => $notifications], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function read(): void
    {
        $this->postOnly();
        $user = require_login();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_exec('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?', 'ii', [$id, (int) $user['id']]);
        } else {
            db_exec('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL', 'i', [(int) $user['id']]);
        }
        flash_set('success', 'Notifications marked as read.');
        redirect('notifications');
    }

    public function open(): void
    {
        $user = require_login();
        $id = $this->positiveId($_GET['id'] ?? null, 'Notification');
        $notification = db_one('SELECT target_url FROM notifications WHERE id=? AND user_id=?', 'ii', [$id, (int) $user['id']]);
        if (!$notification) $this->pageNotFound();
        db_exec('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?', 'ii', [$id, (int) $user['id']]);
        $target = (string) $notification['target_url'];
        if ($target === 'vendor.dashboard' && $user['role'] === 'VENDOR') redirect('vendor.dashboard');
        if ($target === 'vendor.orders' && $user['role'] === 'VENDOR') redirect('vendor.orders');
        if ($target === 'my.orders' && $user['role'] === 'STUDENT') redirect('my.orders');
        if (preg_match('/^my\.order&id=([0-9]+)$/', $target, $match) && $user['role'] === 'STUDENT') redirect('my.order', ['id' => (int) $match[1]]);
        redirect('notifications');
    }
}
