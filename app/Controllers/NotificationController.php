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
