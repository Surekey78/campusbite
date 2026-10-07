<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Services/OrderService.php';
require_once APP_ROOT . '/app/Services/MediaService.php';

final class AdminController extends Controller
{
    private function admin(string $permission): array
    {
        $user = require_permission($permission);
        if ($user['role'] !== 'ADMIN') throw new HttpException(403, 'This page is for administrators.');
        return $user;
    }

    public function dashboard(): void
    {
        $this->admin('dashboard.view');
        $summary = db_one("SELECT
            (SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='STUDENT' AND u.status='ACTIVE') AS students,
            (SELECT COUNT(*) FROM vendors) AS vendors,
            (SELECT COUNT(*) FROM vendors WHERE status='ACTIVE') AS active_vendors,
            (SELECT COUNT(*) FROM foods) AS foods,
            (SELECT COALESCE(SUM(total),0) FROM orders WHERE status='COMPLETED' AND DATE(completed_at)=CURDATE()) AS sales_today,
            (SELECT COUNT(*) FROM vendors WHERE status='PENDING') AS pending_vendors,
            (SELECT COUNT(*) FROM review_reports WHERE status='OPEN') AS reported_reviews,
            (SELECT COUNT(*) FROM inventory i WHERE i.quantity<=i.low_stock_threshold) AS low_stock_items");
        $recentOrders = db_all('SELECT o.*, v.name AS vendor_name, u.full_name AS student_name FROM orders o JOIN vendors v ON v.id=o.vendor_id LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8');
        $recentAudit = db_all('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 8');
        $this->view('admin/dashboard', compact('summary', 'recentOrders', 'recentAudit') + ['title' => 'Admin overview']);
    }

    public function users(): void
    {
        $this->admin('users.manage');
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80, 'UTF-8');
        $role = strtoupper((string) ($_GET['role'] ?? ''));
        if (!in_array($role, ['STUDENT', 'VENDOR', 'ADMIN'], true)) $role = '';
        $status = strtoupper((string) ($_GET['status'] ?? ''));
        if (!in_array($status, ['ACTIVE', 'SUSPENDED'], true)) $status = '';
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 25; $offset = ($page - 1) * $limit;
        $where = ' WHERE 1=1'; $types = ''; $params = [];
        if ($q !== '') { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)'; $types .= 'ss'; $term = '%' . $q . '%'; array_push($params, $term, $term); }
        if ($role !== '') { $where .= ' AND r.name=?'; $types .= 's'; $params[] = $role; }
        if ($status !== '') { $where .= ' AND u.status=?'; $types .= 's'; $params[] = $status; }
        $users = db_all('SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at, r.name AS role, upp.media_id AS profile_media_id FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN user_profile_photos upp ON upp.user_id=u.id' . $where . ' ORDER BY u.created_at DESC LIMIT ? OFFSET ?', $types . 'ii', array_merge($params, [$limit, $offset]));
        $count = db_one('SELECT COUNT(*) AS n FROM users u JOIN roles r ON r.id=u.role_id' . $where, $types, $params);
        $total = (int) ($count['n'] ?? 0);
        $this->view('admin/users', compact('users', 'q', 'role', 'status', 'page', 'limit', 'total') + ['title' => 'Users']);
    }

    public function userStatus(): void
    {
        $this->postOnly();
        $actor = $this->admin('users.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'User');
        if ($id === (int) $actor['id']) throw new InvalidArgumentException('You cannot suspend your own administrator account.');
        $user = db_one('SELECT u.id, u.full_name, u.status, r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?', 'i', [$id]);
        if (!$user) $this->pageNotFound();
        $next = $user['status'] === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
        $vendorImpact = null;
        $db = db(); $db->begin_transaction();
        try {
            db_exec('UPDATE users SET status=?, updated_at=NOW() WHERE id=?', 'si', [$next, $id]);
            if ($user['role'] === 'VENDOR' && $next === 'SUSPENDED') {
                $vendorImpact = db_one('SELECT id, status FROM vendors WHERE user_id=? FOR UPDATE', 'i', [$id]);
                if ($vendorImpact && $vendorImpact['status'] === 'ACTIVE') {
                    db_exec("UPDATE vendors SET status='SUSPENDED', updated_at=NOW() WHERE id=?", 'i', [(int) $vendorImpact['id']]);
                    db_exec('UPDATE vendor_settings SET accepting_orders=0, updated_at=NOW() WHERE vendor_id=?', 'i', [(int) $vendorImpact['id']]);
                }
            }
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        audit_log('Changed user status', 'user', $id, ['from' => $user['status'], 'to' => $next, 'vendor_suspended' => !empty($vendorImpact) && $vendorImpact['status'] === 'ACTIVE']);
        flash_set('success', $user['full_name'] . ' is now ' . strtolower($next) . '.');
        redirect('admin.users');
    }

    public function vendors(): void
    {
        $this->admin('vendors.manage');
        $status = strtoupper((string) ($_GET['status'] ?? ''));
        if (!in_array($status, ['PENDING', 'ACTIVE', 'REJECTED', 'SUSPENDED'], true)) $status = '';
        $sql = 'SELECT v.*, u.full_name AS owner_name, u.email, u.status AS owner_status, (SELECT COUNT(*) FROM foods f WHERE f.vendor_id=v.id) AS food_count FROM vendors v JOIN users u ON u.id=v.user_id';
        $vendors = $status !== '' ? db_all($sql . ' WHERE v.status=? ORDER BY v.created_at DESC', 's', [$status]) : db_all($sql . ' ORDER BY FIELD(v.status, \'PENDING\',\'ACTIVE\',\'SUSPENDED\',\'REJECTED\'), v.created_at DESC');
        $this->view('admin/vendors', compact('vendors', 'status') + ['title' => 'Vendors']);
    }

    public function vendorCreate(): void
    {
        $actor = $this->admin('vendors.manage');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->view('admin/vendor-create', ['title' => 'Create vendor account']);
            return;
        }

        $this->postOnly();
        $ownerName = InputValidator::text($_POST['owner_name'] ?? '', 120, 'Account holder name');
        $storeName = InputValidator::text($_POST['store_name'] ?? '', 120, 'Store name');
        $email = InputValidator::email($_POST['email'] ?? '');
        $phone = InputValidator::text($_POST['phone'] ?? '', 30, 'Phone number', false);
        $pickupLocation = InputValidator::text($_POST['pickup_location'] ?? '', 180, 'Pickup location');
        $stallLabel = InputValidator::text($_POST['stall_label'] ?? '', 80, 'Stall or counter', false);
        $description = InputValidator::text($_POST['description'] ?? '', 1000, 'Store description', false);
        $prepMinutes = InputValidator::positiveInt($_POST['prep_minutes'] ?? 15, 'Typical preparation time', 5, 180);
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        if (!is_string($password) || !is_string($confirmation)) throw new InvalidArgumentException('Password fields must be plain text.');
        if (strlen($password) < 10 || strlen($password) > 72) throw new InvalidArgumentException('Use an initial password between 10 and 72 bytes.');
        if ($password !== $confirmation) throw new InvalidArgumentException('The password fields do not match.');
        $statusValue = $_POST['storefront_status'] ?? 'PENDING';
        if (!is_string($statusValue)) throw new InvalidArgumentException('Choose a valid storefront status.');
        $storefrontStatus = strtoupper(trim($statusValue));
        if (!in_array($storefrontStatus, ['PENDING', 'ACTIVE'], true)) throw new InvalidArgumentException('Choose Pending or Active for the storefront.');

        $db = db();
        $db->begin_transaction();
        try {
            if (db_one('SELECT id FROM users WHERE email=? LIMIT 1', 's', [$email])) {
                throw new InvalidArgumentException('That email is already registered. Choose another email.');
            }
            $vendorRole = db_one("SELECT id FROM roles WHERE name='VENDOR' LIMIT 1");
            if (!$vendorRole) throw new RuntimeException('The VENDOR role is not configured.');
            $account = db_exec("INSERT INTO users (role_id, full_name, email, password_hash, phone, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'ACTIVE', NOW(), NOW())", 'issss', [
                (int) $vendorRole['id'],
                $ownerName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $phone !== '' ? $phone : null,
            ]);
            $userId = (int) $account['insert_id'];
            $slug = slugify($storeName) . '-' . strtolower(bin2hex(random_bytes(3)));
            $approvedAt = $storefrontStatus === 'ACTIVE' ? date('Y-m-d H:i:s') : null;
            $approvedBy = $storefrontStatus === 'ACTIVE' ? (int) $actor['id'] : null;
            $vendor = db_exec('INSERT INTO vendors (user_id, name, slug, description, pickup_location, stall_label, prep_minutes, status, approved_at, approved_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', 'isssssissi', [
                $userId,
                $storeName,
                $slug,
                $description !== '' ? $description : null,
                $pickupLocation,
                $stallLabel !== '' ? $stallLabel : null,
                $prepMinutes,
                $storefrontStatus,
                $approvedAt,
                $approvedBy,
            ]);
            $vendorId = (int) $vendor['insert_id'];
            $acceptingOrders = $storefrontStatus === 'ACTIVE' ? 1 : 0;
            $notice = $storefrontStatus === 'PENDING' ? 'Storefront pending administrator activation' : '';
            db_exec('INSERT INTO vendor_settings (vendor_id, accepting_orders, store_notice, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())', 'iis', [$vendorId, $acceptingOrders, $notice]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        try {
            notify_user($userId, 'Vendor account created', 'An administrator created your account. Storefront status: ' . strtolower($storefrontStatus) . '.', 'vendor.dashboard');
        } catch (Throwable $notificationError) {
            app_log($notificationError);
        }
        audit_log('Created vendor account', 'vendor', $vendorId, ['store' => $storeName, 'status' => $storefrontStatus]);
        flash_set('success', $storeName . ' vendor account was created for ' . $ownerName . '. Share the initial sign-in details securely and ask the owner to change the password.');
        redirect('admin.vendors', ['status' => $storefrontStatus]);
    }

    public function vendorAction(): void
    {
        $this->postOnly();
        $actor = $this->admin('vendors.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'Vendor');
        $action = strtoupper((string) ($_POST['action'] ?? ''));
        $map = ['APPROVE' => 'ACTIVE', 'ACTIVATE' => 'ACTIVE', 'REJECT' => 'REJECTED', 'SUSPEND' => 'SUSPENDED'];
        if (!isset($map[$action])) throw new InvalidArgumentException('Choose a valid vendor action.');
        $vendor = db_one('SELECT v.id, v.user_id, v.name, v.status, u.status AS owner_status FROM vendors v JOIN users u ON u.id=v.user_id WHERE v.id=?', 'i', [$id]);
        if (!$vendor) $this->pageNotFound();
        $next = $map[$action];
        if ($action === 'REJECT' && $vendor['status'] !== 'PENDING') throw new InvalidArgumentException('Only pending storefronts can be rejected.');
        if ($action === 'SUSPEND' && $vendor['status'] !== 'ACTIVE') throw new InvalidArgumentException('Only active vendors can be suspended.');
        if ($next === 'ACTIVE' && $vendor['owner_status'] !== 'ACTIVE') throw new InvalidArgumentException('Activate the vendor account before activating or reopening its storefront.');
        db()->begin_transaction();
        try {
            db_exec('UPDATE vendors SET status=?, approved_at=IF(?=\'ACTIVE\', NOW(), approved_at), approved_by=IF(?=\'ACTIVE\', ?, approved_by), updated_at=NOW() WHERE id=?', 'sssii', [$next, $next, $next, (int) $actor['id'], $id]);
            db_exec('UPDATE vendor_settings SET accepting_orders=IF(?=\'ACTIVE\', 1, 0), updated_at=NOW() WHERE vendor_id=?', 'si', [$next, $id]);
            db()->commit();
        } catch (Throwable $e) { db()->rollback(); throw $e; }
        try {
            notify_user((int) $vendor['user_id'], 'Vendor storefront update', 'Your storefront status is now ' . strtolower($next) . '.', 'vendor.dashboard');
        } catch (Throwable $notificationError) {
            app_log($notificationError);
        }
        audit_log('Changed vendor status', 'vendor', $id, ['from' => $vendor['status'], 'to' => $next]);
        flash_set('success', $vendor['name'] . ' is now ' . strtolower($next) . '.');
        redirect('admin.vendors');
    }

    public function foods(): void
    {
        $this->admin('food.update');
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80, 'UTF-8');
        $sql = 'SELECT f.*, v.name AS vendor_name, c.name AS category_name, m.id AS media_id FROM foods f JOIN vendors v ON v.id=f.vendor_id LEFT JOIN food_categories c ON c.id=f.category_id LEFT JOIN media m ON m.id=f.media_id';
        if ($q !== '') { $term = '%' . $q . '%'; $foods = db_all($sql . ' WHERE f.name LIKE ? OR v.name LIKE ? ORDER BY f.created_at DESC LIMIT 150', 'ss', [$term, $term]); }
        else $foods = db_all($sql . ' ORDER BY f.created_at DESC LIMIT 150');
        $this->view('admin/foods', compact('foods', 'q') + ['title' => 'All foods']);
    }

    public function foodToggle(): void
    {
        $this->postOnly();
        $this->admin('food.delete');
        $id = $this->positiveId($_POST['id'] ?? null, 'Food');
        $food = db_one('SELECT id, name, is_available FROM foods WHERE id=?', 'i', [$id]);
        if (!$food) $this->pageNotFound();
        $next = (int) !$food['is_available'];
        db_exec('UPDATE foods SET is_available=?, updated_at=NOW() WHERE id=?', 'ii', [$next, $id]);
        audit_log($next ? 'Admin relisted food' : 'Admin unlisted food', 'food', $id);
        flash_set('success', $food['name'] . ($next ? ' is now listed in the foods catalog.' : ' was removed from the foods catalog.'));
        redirect('admin.foods');
    }

    public function foodFeatureToggle(): void
    {
        $this->postOnly();
        $this->admin('food.update');
        $id = $this->positiveId($_POST['id'] ?? null, 'Food');
        $db = db();
        $db->begin_transaction();
        try {
            $food = db_one('SELECT id, name, is_featured FROM foods WHERE id=? FOR UPDATE', 'i', [$id]);
            if (!$food) $this->pageNotFound();
            $next = (int) !$food['is_featured'];
            db_exec('UPDATE foods SET is_featured=?, updated_at=NOW() WHERE id=?', 'ii', [$next, $id]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
        audit_log($next ? 'Featured food' : 'Removed food feature', 'food', $id, ['name' => $food['name']]);
        flash_set('success', $food['name'] . ($next ? ' is marked as a homepage feature.' : ' was removed from homepage features.'));
        redirect('admin.foods');
    }

    public function inventory(): void
    {
        $this->admin('system.manage');
        $query = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80, 'UTF-8');
        $stockFilter = strtoupper((string) ($_GET['stock'] ?? ''));
        if (!in_array($stockFilter, ['', 'LOW'], true)) $stockFilter = '';
        $vendorId = max(0, (int) ($_GET['vendor'] ?? 0));
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 30; $offset = ($page - 1) * $limit;
        $where = ' WHERE 1=1'; $types = ''; $params = [];
        if ($query !== '') {
            $where .= ' AND (f.name LIKE ? OR v.name LIKE ?)';
            $types .= 'ss'; $term = '%' . $query . '%'; array_push($params, $term, $term);
        }
        if ($stockFilter === 'LOW') $where .= ' AND i.quantity<=i.low_stock_threshold';
        if ($vendorId > 0) { $where .= ' AND v.id=?'; $types .= 'i'; $params[] = $vendorId; }
        $sql = ' FROM inventory i JOIN foods f ON f.id=i.food_id JOIN vendors v ON v.id=i.vendor_id';
        $items = db_all('SELECT i.*, f.name AS food_name, f.is_available, v.name AS vendor_name, v.status AS vendor_status' . $sql . $where . ' ORDER BY (i.quantity<=i.low_stock_threshold) DESC, v.name, f.name LIMIT ? OFFSET ?', $types . 'ii', array_merge($params, [$limit, $offset]));
        $count = db_one('SELECT COUNT(*) AS n' . $sql . $where, $types, $params);
        $total = (int) ($count['n'] ?? 0);
        $summary = db_one('SELECT COUNT(*) AS tracked, SUM(quantity<=low_stock_threshold) AS low_stock FROM inventory');
        $vendors = db_all('SELECT id, name FROM vendors ORDER BY name');
        $this->view('admin/inventory', compact('items', 'query', 'stockFilter', 'vendorId', 'page', 'limit', 'total', 'summary', 'vendors') + ['title' => 'Inventory oversight']);
    }

    public function orders(): void
    {
        $this->admin('orders.manage_all');
        $status = strtoupper((string) ($_GET['status'] ?? ''));
        if (!in_array($status, ORDER_STATUSES, true)) $status = '';
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 25; $offset = ($page - 1) * $limit;
        $sql = 'SELECT o.*, v.name AS vendor_name, u.full_name AS student_name, p.status AS payment_status, p.cash_received FROM orders o JOIN vendors v ON v.id=o.vendor_id LEFT JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.order_id=o.id';
        if ($status !== '') {
            $orders = db_all($sql . ' WHERE o.status=? ORDER BY o.created_at DESC LIMIT ? OFFSET ?', 'sii', [$status, $limit, $offset]);
            $total = (int) (db_one('SELECT COUNT(*) AS n FROM orders WHERE status=?', 's', [$status])['n'] ?? 0);
        } else {
            $orders = db_all($sql . ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?', 'ii', [$limit, $offset]);
            $total = (int) (db_one('SELECT COUNT(*) AS n FROM orders')['n'] ?? 0);
        }
        $this->view('admin/orders', compact('orders', 'status', 'page', 'limit', 'total') + ['title' => 'All orders']);
    }

    public function orderStatus(): void
    {
        $this->postOnly();
        $actor = $this->admin('orders.manage_all');
        $id = $this->positiveId($_POST['order_id'] ?? null, 'Order');
        $status = strtoupper((string) ($_POST['status'] ?? ''));
        $cash = 0.0;
        if ($status === 'COMPLETED') {
            $cashInput = $_POST['cash_received'] ?? null;
            if (!is_scalar($cashInput) || !is_numeric($cashInput) || (float) $cashInput < 0 || (float) $cashInput > 1000000) throw new InvalidArgumentException('Enter a valid cash amount when completing the order.');
            $cash = (float) $cashInput;
        }
        OrderService::transition($id, $status, (int) $actor['id'], 'ADMIN', null, $cash, InputValidator::text($_POST['note'] ?? 'Administrator update', 240, 'Note', false));
        $order = db_one('SELECT order_number FROM orders WHERE id=?', 'i', [$id]);
        $orderNumber = $order['order_number'] ?? ('#' . $id);
        flash_set('success', 'Order ' . $orderNumber . ' status updated to ' . status_label($status) . '.');
        redirect('admin.orders');
    }

    public function reviews(): void
    {
        $this->admin('reviews.manage');
        $status = strtoupper((string) ($_GET['status'] ?? ''));
        if (!in_array($status, ['PUBLISHED', 'PENDING', 'HIDDEN'], true)) $status = '';
        $sql = "SELECT r.*, u.full_name AS student_name, f.name AS food_name, v.name AS vendor_name, o.order_number,
                (SELECT COUNT(*) FROM review_reports rr WHERE rr.review_id=r.id AND rr.status='OPEN') AS open_reports
                FROM reviews r JOIN users u ON u.id=r.user_id JOIN foods f ON f.id=r.food_id JOIN vendors v ON v.id=f.vendor_id JOIN orders o ON o.id=r.order_id";
        $reviews = $status !== '' ? db_all($sql . ' WHERE r.status=? ORDER BY open_reports DESC, r.created_at DESC LIMIT 150', 's', [$status]) : db_all($sql . ' ORDER BY open_reports DESC, r.created_at DESC LIMIT 150');
        $reportsByReview = [];
        foreach (db_all("SELECT rr.review_id, rr.reason, rr.created_at, u.full_name AS reporter FROM review_reports rr JOIN users u ON u.id=rr.user_id WHERE rr.status='OPEN' ORDER BY rr.created_at DESC") as $report) {
            $reportsByReview[(int) $report['review_id']][] = $report;
        }
        foreach ($reviews as &$review) $review['reports'] = $reportsByReview[(int) $review['id']] ?? [];
        unset($review);
        $this->view('admin/reviews', compact('reviews', 'status') + ['title' => 'Review moderation']);
    }

    public function reviewStatus(): void
    {
        $this->postOnly();
        $this->admin('reviews.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'Review');
        $status = strtoupper((string) ($_POST['status'] ?? ''));
        if (!in_array($status, ['PUBLISHED', 'PENDING', 'HIDDEN'], true)) throw new InvalidArgumentException('Choose a valid review status.');
        $review = db_one('SELECT r.id, r.status, f.name AS food_name FROM reviews r JOIN foods f ON f.id=r.food_id WHERE r.id=?', 'i', [$id]);
        if (!$review) $this->pageNotFound();
        db_exec('UPDATE reviews SET status=?, updated_at=NOW() WHERE id=?', 'si', [$status, $id]);
        db_exec("UPDATE review_reports SET status='RESOLVED' WHERE review_id=? AND status='OPEN'", 'i', [$id]);
        audit_log('Moderated review', 'review', $id, ['from' => $review['status'], 'to' => $status]);
        flash_set('success', 'Review for ' . $review['food_name'] . ' was set to ' . strtolower(status_label($status)) . '.');
        redirect('admin.reviews');
    }

    public function categories(): void
    {
        $this->admin('system.manage');
        $categories = db_all('SELECT c.*, v.name AS vendor_name, (SELECT COUNT(*) FROM foods f WHERE f.category_id=c.id) AS food_count FROM food_categories c LEFT JOIN vendors v ON v.id=c.vendor_id ORDER BY c.vendor_id IS NOT NULL, c.sort_order, c.name');
        $this->view('admin/categories', compact('categories') + ['title' => 'Food categories']);
    }

    public function categorySave(): void
    {
        $this->postOnly();
        $actor = $this->admin('system.manage');
        $name = InputValidator::text($_POST['name'] ?? '', 80, 'Category name');
        $description = InputValidator::text($_POST['description'] ?? '', 240, 'Description', false);
        if (db_one('SELECT id FROM food_categories WHERE vendor_id IS NULL AND name=?', 's', [$name])) throw new InvalidArgumentException('A campus-wide category with that name already exists.');
        $slug = slugify($name) . '-' . strtolower(bin2hex(random_bytes(2)));
        $result = db_exec('INSERT INTO food_categories (vendor_id, name, slug, description, sort_order, is_active, created_at, updated_at) VALUES (NULL, ?, ?, ?, 50, 1, NOW(), NOW())', 'sss', [$name, $slug, $description ?: null]);
        audit_log('Created global food category', 'category', (int) $result['insert_id'], ['name' => $name]);
        flash_set('success', $name . ' was added as a campus-wide food category.');
        redirect('admin.categories');
    }

    public function categoryToggle(): void
    {
        $this->postOnly();
        $this->admin('system.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'Category');
        $db = db();
        $db->begin_transaction();
        try {
            $category = db_one('SELECT id, name, is_active FROM food_categories WHERE id=? AND vendor_id IS NULL FOR UPDATE', 'i', [$id]);
            if (!$category) $this->pageNotFound();
            $next = (int) !$category['is_active'];
            if (!$next) {
                $usage = db_one('SELECT COUNT(*) AS n FROM foods WHERE category_id=?', 'i', [$id]);
                if ((int) ($usage['n'] ?? 0) > 0) throw new InvalidArgumentException('Reassign foods from this category before deactivating it.');
            }
            db_exec('UPDATE food_categories SET is_active=?, updated_at=NOW() WHERE id=?', 'ii', [$next, $id]);
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        audit_log($next ? 'Activated category' : 'Deactivated category', 'category', $id);
        flash_set('success', $category['name'] . ($next ? ' was activated.' : ' was deactivated.'));
        redirect('admin.categories');
    }

    public function cms(): void
    {
        $this->admin('cms.manage');
        $content = db_all('SELECT c.*, u.full_name AS editor, m.id AS media_id, m.original_name AS media_name FROM cms_content c LEFT JOIN users u ON u.id=c.updated_by LEFT JOIN media m ON m.id=c.image_media_id ORDER BY c.section, c.content_key');
        $mediaChoices = db_all('SELECT id, original_name, mime_type FROM media ORDER BY created_at DESC LIMIT 150');
        $this->view('admin/cms', compact('content', 'mediaChoices') + ['title' => 'Site content']);
    }

    public function cmsSave(): void
    {
        $this->postOnly();
        $actor = $this->admin('cms.manage');
        $id = (int) ($_POST['id'] ?? 0);
        $key = strtolower(trim((string) ($_POST['content_key'] ?? '')));
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,99}$/', $key)) throw new InvalidArgumentException('Content key may contain lowercase letters, numbers, dots, underscores, and hyphens.');
        $title = InputValidator::text($_POST['title'] ?? '', 180, 'Title');
        $body = InputValidator::text($_POST['body'] ?? '', 10000, 'Body', false);
        $section = InputValidator::text($_POST['section'] ?? 'page', 40, 'Section');
        if ($section === 'page' && !preg_match('/^page\.[a-z0-9]+(?:-[a-z0-9]+)*$/', $key)) throw new InvalidArgumentException('Public page keys use page.slug format, such as page.faq.');
        $mediaId = filter_var($_POST['image_media_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($mediaId === false || $mediaId < 0) throw new InvalidArgumentException('Choose a valid image or select no image.');
        if ($mediaId > 0 && !db_one('SELECT id FROM media WHERE id=?', 'i', [$mediaId])) throw new InvalidArgumentException('Choose an existing media item or leave the image field empty.');
        $published = isset($_POST['is_published']) ? 1 : 0;
        if ($id > 0) {
            if (!db_one('SELECT id FROM cms_content WHERE id=?', 'i', [$id])) $this->pageNotFound();
            if (db_one('SELECT id FROM cms_content WHERE content_key=? AND id<>?', 'si', [$key, $id])) throw new InvalidArgumentException('That content key is already in use.');
            db_exec('UPDATE cms_content SET content_key=?, title=?, body=?, section=?, image_media_id=?, is_published=?, updated_by=?, updated_at=NOW() WHERE id=?', 'ssssiiii', [$key, $title, $body, $section, $mediaId ?: null, $published, (int) $actor['id'], $id]);
            audit_log('Updated CMS content', 'cms_content', $id, ['key' => $key]);
        } else {
            $insert = db_exec('INSERT INTO cms_content (content_key, title, body, section, image_media_id, is_published, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', 'ssssiii', [$key, $title, $body, $section, $mediaId ?: null, $published, (int) $actor['id']]);
            $id = (int) $insert['insert_id'];
            audit_log('Created CMS content', 'cms_content', $id, ['key' => $key]);
        }
        flash_set('success', 'Site content saved.');
        redirect('admin.cms');
    }

    public function media(): void
    {
        $this->admin('media.manage');
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80, 'UTF-8');
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 24; $offset = ($page - 1) * $limit;
        $sql = 'SELECT m.id, m.original_name, m.stored_name, m.mime_type, m.extension, m.file_size, m.file_hash, m.uploaded_by, m.created_at, u.full_name AS uploader,
            ((SELECT COUNT(*) FROM foods f WHERE f.media_id=m.id)+(SELECT COUNT(*) FROM vendors v WHERE v.logo_media_id=m.id)+(SELECT COUNT(*) FROM cms_content c WHERE c.image_media_id=m.id)+(SELECT COUNT(*) FROM user_profile_photos p WHERE p.media_id=m.id)) AS usage_count FROM media m LEFT JOIN users u ON u.id=m.uploaded_by';
        if ($q !== '') {
            $term = '%' . $q . '%';
            $media = db_all($sql . ' WHERE m.original_name LIKE ? OR m.mime_type LIKE ? ORDER BY m.created_at DESC LIMIT ? OFFSET ?', 'ssii', [$term, $term, $limit, $offset]);
            $total = (int) (db_one('SELECT COUNT(*) AS n FROM media WHERE original_name LIKE ? OR mime_type LIKE ?', 'ss', [$term, $term])['n'] ?? 0);
        } else {
            $media = db_all($sql . ' ORDER BY m.created_at DESC LIMIT ? OFFSET ?', 'ii', [$limit, $offset]);
            $total = (int) (db_one('SELECT COUNT(*) AS n FROM media')['n'] ?? 0);
        }
        $this->view('admin/media', compact('media', 'q', 'page', 'limit', 'total') + ['title' => 'Media library']);
    }

    public function mediaUpload(): void
    {
        $this->postOnly();
        $actor = $this->admin('media.manage');
        $id = upload_media($_FILES['image'] ?? null, (int) $actor['id']);
        if (!$id) throw new InvalidArgumentException('Choose an image to upload.');
        flash_set('success', 'Image uploaded to the database media library.');
        redirect('admin.media');
    }

    public function mediaDelete(): void
    {
        $this->postOnly();
        $this->admin('media.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'Media item');
        $db = db();
        $db->begin_transaction();
        try {
            $item = db_one('SELECT id, original_name FROM media WHERE id=? FOR UPDATE', 'i', [$id]);
            if (!$item) $this->pageNotFound();
            $usage = db_one('SELECT (SELECT COUNT(*) FROM foods WHERE media_id=?)+(SELECT COUNT(*) FROM vendors WHERE logo_media_id=?)+(SELECT COUNT(*) FROM cms_content WHERE image_media_id=?)+(SELECT COUNT(*) FROM user_profile_photos WHERE media_id=?) AS n', 'iiii', [$id, $id, $id, $id]);
            if ((int) $usage['n'] > 0) throw new InvalidArgumentException('This image is still in use in ' . (int) $usage['n'] . ' place(s). Remove those references before deleting it.');
            db_exec('DELETE FROM media WHERE id=?', 'i', [$id]);
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        audit_log('Deleted media', 'media', $id, ['name' => $item['original_name']]);
        flash_set('success', $item['original_name'] . ' was deleted from the media library.');
        redirect('admin.media');
    }

    public function settings(): void
    {
        $this->admin('system.manage');
        $settings = [];
        foreach (db_all('SELECT setting_key, setting_value FROM system_settings') as $row) $settings[$row['setting_key']] = $row['setting_value'];
        $this->view('admin/settings', compact('settings') + ['title' => 'System settings']);
    }

    public function settingsSave(): void
    {
        $this->postOnly();
        $actor = $this->admin('system.manage');
        $siteName = InputValidator::text($_POST['site_name'] ?? APP_NAME, 80, 'Site name');
        $maintenance = isset($_POST['maintenance_mode']) ? '1' : '0';
        db()->begin_transaction();
        try {
            db_exec("INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) VALUES ('site_name', ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by), updated_at=NOW()", 'si', [$siteName, (int) $actor['id']]);
            db_exec("INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) VALUES ('maintenance_mode', ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by), updated_at=NOW()", 'si', [$maintenance, (int) $actor['id']]);
            db()->commit();
        } catch (Throwable $e) { db()->rollback(); throw $e; }
        audit_log('Changed system settings', 'system_settings', null, ['site_name' => $siteName, 'maintenance_mode' => $maintenance]);
        flash_set('success', 'System settings saved. Cash-only payment is unchanged.');
        redirect('admin.settings');
    }

    public function audit(): void
    {
        $this->admin('audit.view');
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 40; $offset = ($page - 1) * $limit;
        $logs = db_all('SELECT a.*, u.full_name, u.email FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT ? OFFSET ?', 'ii', [$limit, $offset]);
        $total = (int) (db_one('SELECT COUNT(*) AS n FROM audit_logs')['n'] ?? 0);
        $this->view('admin/audit', compact('logs', 'page', 'limit', 'total') + ['title' => 'Audit history']);
    }

    public function reports(): void
    {
        $this->admin('reports.view');
        $start = $this->dateValue($_GET['start'] ?? date('Y-m-d', strtotime('-6 days')));
        $end = $this->dateValue($_GET['end'] ?? date('Y-m-d'));
        if ($start > $end) throw new InvalidArgumentException('Start date must be on or before end date.');
        $summary = db_one("SELECT COUNT(*) AS order_count, SUM(status='CANCELLED') AS cancelled_count, SUM(status='REJECTED') AS rejected_count FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", 'ss', [$start, $end]);
        $salesSummary = db_one("SELECT COUNT(*) AS completed_count, COALESCE(SUM(total),0) AS sales FROM orders WHERE status='COMPLETED' AND DATE(completed_at) BETWEEN ? AND ?", 'ss', [$start, $end]);
        $summary = array_merge($summary ?? [], $salesSummary ?? []);
        $daily = db_all("SELECT DATE(completed_at) AS day, SUM(total) AS sales, COUNT(*) AS transactions FROM orders WHERE status='COMPLETED' AND DATE(completed_at) BETWEEN ? AND ? GROUP BY DATE(completed_at) ORDER BY day", 'ss', [$start, $end]);
        $vendors = db_all("SELECT v.name, COUNT(o.id) AS completed_orders, COALESCE(SUM(o.total),0) AS sales FROM vendors v LEFT JOIN orders o ON o.vendor_id=v.id AND o.status='COMPLETED' AND DATE(o.completed_at) BETWEEN ? AND ? GROUP BY v.id, v.name ORDER BY sales DESC LIMIT 10", 'ss', [$start, $end]);
        $this->view('admin/reports', compact('start', 'end', 'summary', 'daily', 'vendors') + ['title' => 'System reports']);
    }

    private function dateValue($value): string
    {
        $value = (string) $value; $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Choose a valid date.');
        return $value;
    }
}
