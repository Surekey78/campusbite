<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';

final class AuthController extends Controller
{
    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $emailValue = $_POST['email'] ?? '';
            $email = is_scalar($emailValue) ? strtolower(trim((string) $emailValue)) : '';
            $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            $attemptKey = hash('sha256', $email . "\0" . $ipAddress);
            $attempts = db_one('SELECT attempts, (window_started_at < NOW()-INTERVAL 15 MINUTE) AS expired FROM login_attempts WHERE attempt_key=?', 's', [$attemptKey]);
            if ($attempts && (int) $attempts['expired']) {
                db_exec('DELETE FROM login_attempts WHERE attempt_key=?', 's', [$attemptKey]);
                $attempts = null;
            }
            if ($attempts && (int) $attempts['attempts'] >= 8) {
                throw new HttpException(429, 'Too many sign-in attempts. Please wait 15 minutes before trying again.');
            }
            $passwordValue = $_POST['password'] ?? '';
            $password = is_string($passwordValue) ? $passwordValue : '';
            $user = db_one('SELECT id, password_hash, status FROM users WHERE email=? LIMIT 1', 's', [$email]);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                db_exec("INSERT INTO login_attempts (attempt_key, attempts, window_started_at, updated_at) VALUES (?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE attempts=IF(window_started_at < NOW()-INTERVAL 15 MINUTE, 1, LEAST(attempts+1,255)), window_started_at=IF(window_started_at < NOW()-INTERVAL 15 MINUTE, NOW(), window_started_at), updated_at=NOW()", 's', [$attemptKey]);
                flash_set('error', 'Email or password is incorrect. Check your details and try again.');
                redirect('auth.login');
            }
            if ($user['status'] === 'PENDING') {
                db_exec('DELETE FROM login_attempts WHERE attempt_key=?', 's', [$attemptKey]);
                flash_set('info', 'Your student account is awaiting administrator approval. You can browse foods while it is reviewed.');
                redirect('auth.login');
            }
            if ($user['status'] !== 'ACTIVE') {
                db_exec("INSERT INTO login_attempts (attempt_key, attempts, window_started_at, updated_at) VALUES (?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE attempts=IF(window_started_at < NOW()-INTERVAL 15 MINUTE, 1, LEAST(attempts+1,255)), window_started_at=IF(window_started_at < NOW()-INTERVAL 15 MINUTE, NOW(), window_started_at), updated_at=NOW()", 's', [$attemptKey]);
                flash_set('error', 'Email or password is incorrect. Check your details and try again.');
                redirect('auth.login');
            }
            session_regenerate_id(true);
            unset($_SESSION['_csrf']);
            $_SESSION['user_id'] = (int) $user['id'];
            db_exec('DELETE FROM login_attempts WHERE attempt_key=?', 's', [$attemptKey]);
            db_exec('UPDATE users SET last_login_at=NOW() WHERE id=?', 'i', [(int) $user['id']]);
            audit_log('Login', 'user', (int) $user['id']);
            flash_set('success', 'Welcome back.');
            $account = current_user();
            if ($account && $account['role'] === 'ADMIN') redirect('admin.dashboard');
            if ($account && $account['role'] === 'VENDOR') redirect('vendor.dashboard');
            redirect('home');
        }
        if (current_user()) redirect('home');
        $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function register(): void
    {
        if (current_user()) redirect('home');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name = InputValidator::text($_POST['full_name'] ?? '', 120, 'Full name');
            $email = InputValidator::email($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirmation'] ?? '';
            if (!is_string($password) || !is_string($confirm)) throw new InvalidArgumentException('Password fields must be plain text.');
            if (strlen($password) < 10 || strlen($password) > 72) throw new InvalidArgumentException('Use a password between 10 and 72 bytes.');
            if ($password !== $confirm) throw new InvalidArgumentException('The passwords do not match.');
            if (db_one('SELECT id FROM users WHERE email=?', 's', [$email])) throw new InvalidArgumentException('That email is already registered. Sign in or use another email.');
            $phone = InputValidator::text($_POST['phone'] ?? '', 30, 'Phone', false);
            $role = db_one("SELECT id FROM roles WHERE name='STUDENT'");
            $insert = db_exec('INSERT INTO users (role_id, full_name, email, password_hash, phone, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, \'PENDING\', NOW(), NOW())', 'issss', [(int) $role['id'], $name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null]);
            session_regenerate_id(true);
            unset($_SESSION['_csrf']);
            audit_log('Requested student account approval', 'user', (int) $insert['insert_id']);
            flash_set('success', 'Your student account request was submitted. An administrator must approve it before you can sign in and place orders. You can browse foods while you wait.');
            redirect('auth.login');
        }
        $this->view('auth/register', ['title' => 'Create a student account']);
    }

    public function password(): void
    {
        $user = require_login();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->postOnly();
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmation = $_POST['password_confirmation'] ?? '';
            if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmation)) throw new InvalidArgumentException('Password fields must be plain text.');
            if (strlen($newPassword) < 10 || strlen($newPassword) > 72) throw new InvalidArgumentException('Use a new password between 10 and 72 bytes.');
            if ($newPassword !== $confirmation) throw new InvalidArgumentException('The new passwords do not match.');
            $credential = db_one('SELECT password_hash FROM users WHERE id=?', 'i', [(int) $user['id']]);
            if (!$credential || !password_verify($currentPassword, $credential['password_hash'])) throw new InvalidArgumentException('Your current password is incorrect.');
            db_exec('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?', 'si', [password_hash($newPassword, PASSWORD_DEFAULT), (int) $user['id']]);
            session_regenerate_id(true);
            unset($_SESSION['_csrf']);
            audit_log('Changed account password', 'user', (int) $user['id']);
            flash_set('success', 'Password changed. You remain signed in on this device.');
            redirect('account.password');
        }
        $this->view('account/password', compact('user') + ['title' => 'Change password']);
    }

    public function logout(): void
    {
        $this->postOnly();
        $user = current_user();
        if ($user) audit_log('Logout', 'user', (int) $user['id']);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('home');
    }
}
