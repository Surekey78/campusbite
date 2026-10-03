<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Services/OrderService.php';

final class VendorController extends Controller
{
    private function vendor(string $permission, bool $mustBeActive = true): array
    {
        $user = require_permission($permission);
        if ($user['role'] !== 'VENDOR') throw new HttpException(403, 'This page is for vendor accounts.');
        $vendor = current_vendor();
        if (!$vendor) throw new HttpException(403, 'Vendor profile not found.');
        if ($mustBeActive && $vendor['status'] !== 'ACTIVE') throw new HttpException(403, 'Your vendor application is ' . strtolower($vendor['status']) . '. An administrator must approve it before this feature is available.');
        return [$user, $vendor];
    }

    public function dashboard(): void
    {
        [$user, $vendor] = $this->vendor('dashboard.view', false);
        if ($vendor['status'] !== 'ACTIVE') {
            $this->view('vendor/dashboard', ['title' => 'Vendor dashboard', 'vendor' => $vendor, 'pending' => true]);
            return;
        }
        $id = (int) $vendor['id'];
        $today = db_one("SELECT (SELECT COUNT(*) FROM orders WHERE vendor_id=? AND DATE(created_at)=CURDATE()) AS orders_today, (SELECT COALESCE(SUM(total),0) FROM orders WHERE vendor_id=? AND status='COMPLETED' AND DATE(completed_at)=CURDATE()) AS sales_today", 'ii', [$id, $id]);
        $statusRows = db_all('SELECT status, COUNT(*) AS total FROM orders WHERE vendor_id=? AND DATE(created_at)=CURDATE() GROUP BY status', 'i', [$id]);
        $counts = []; foreach ($statusRows as $row) $counts[$row['status']] = (int) $row['total'];
        $lowStock = db_all('SELECT i.*, f.name AS food_name FROM inventory i JOIN foods f ON f.id=i.food_id WHERE i.vendor_id=? AND i.quantity<=i.low_stock_threshold ORDER BY i.quantity ASC LIMIT 6', 'i', [$id]);
        $bestSellers = db_all("SELECT oi.food_id, oi.food_name, SUM(oi.quantity) AS units_sold, SUM(oi.line_total) AS sales FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.vendor_id=? AND o.status='COMPLETED' AND o.completed_at>=CURDATE()-INTERVAL 6 DAY AND o.completed_at<CURDATE()+INTERVAL 1 DAY GROUP BY oi.food_id, oi.food_name ORDER BY units_sold DESC LIMIT 5", 'i', [$id]);
        $recentOrders = db_all("SELECT o.*, u.full_name AS student_name, p.status AS payment_status, (SELECT GROUP_CONCAT(CONCAT(oi.quantity, ' × ', oi.food_name) SEPARATOR ', ') FROM order_items oi WHERE oi.order_id=o.id) AS item_summary FROM orders o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.order_id=o.id WHERE o.vendor_id=? ORDER BY o.created_at DESC LIMIT 7", 'i', [$id]);
        $salesRaw = db_all("SELECT DATE(completed_at) AS day, SUM(total) AS sales FROM orders WHERE vendor_id=? AND status='COMPLETED' AND completed_at>=CURDATE()-INTERVAL 6 DAY AND completed_at<CURDATE()+INTERVAL 1 DAY GROUP BY DATE(completed_at) ORDER BY day", 'i', [$id]);
        $salesMap = []; foreach ($salesRaw as $row) $salesMap[$row['day']] = (float) $row['sales'];
        $salesTrend = [];
        for ($i = 6; $i >= 0; $i--) { $day = date('Y-m-d', strtotime('-' . $i . ' days')); $salesTrend[] = ['day' => $day, 'label' => date('D', strtotime($day)), 'sales' => $salesMap[$day] ?? 0]; }
        $maxSales = max(1, ...array_column($salesTrend, 'sales'));
        $this->view('vendor/dashboard', compact('vendor', 'today', 'counts', 'lowStock', 'bestSellers', 'recentOrders', 'salesTrend', 'maxSales') + ['title' => 'Vendor dashboard', 'pending' => false]);
    }

    public function orders(): void
    {
        [, $vendor] = $this->vendor('order.manage');
        $status = strtoupper(trim((string) ($_GET['status'] ?? '')));
        if ($status !== '' && !in_array($status, ORDER_STATUSES, true)) $status = '';
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 20; $offset = ($page - 1) * $limit;
        $sql = "SELECT o.*, u.full_name AS student_name, p.status AS payment_status, p.cash_received, (SELECT GROUP_CONCAT(CONCAT(oi.quantity, ' × ', oi.food_name) SEPARATOR ', ') FROM order_items oi WHERE oi.order_id=o.id) AS item_summary FROM orders o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.order_id=o.id WHERE o.vendor_id=?";
        $types = 'i'; $params = [(int) $vendor['id']];
        if ($status !== '') { $sql .= ' AND o.status=?'; $types .= 's'; $params[] = $status; }
        $sql .= ' ORDER BY FIELD(o.status, \'PENDING\',\'CONFIRMED\',\'PREPARING\',\'READY\',\'COMPLETED\',\'CANCELLED\',\'REJECTED\'), o.created_at DESC LIMIT ? OFFSET ?';
        $types .= 'ii'; $params[] = $limit; $params[] = $offset;
        $orders = db_all($sql, $types, $params);
        $countSql = 'SELECT COUNT(*) AS n FROM orders WHERE vendor_id=?' . ($status !== '' ? ' AND status=?' : '');
        $countRow = $status !== '' ? db_one($countSql, 'is', [(int) $vendor['id'], $status]) : db_one($countSql, 'i', [(int) $vendor['id']]);
        $total = (int) ($countRow['n'] ?? 0);
        $this->view('vendor/orders', compact('orders', 'vendor', 'status', 'page', 'limit', 'total') + ['title' => 'Orders']);
    }

    public function orderStatus(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('order.manage');
        $id = $this->positiveId($_POST['order_id'] ?? null, 'Order');
        $newStatus = strtoupper((string) ($_POST['status'] ?? ''));
        $cash = $_POST['cash_received'] ?? 0;
        if (!is_numeric($cash) || (float) $cash < 0 || (float) $cash > 1000000) throw new InvalidArgumentException('Enter a valid cash amount.');
        $note = InputValidator::text($_POST['note'] ?? '', 240, 'Note', false);
        OrderService::transition($id, $newStatus, (int) $user['id'], 'VENDOR', (int) $vendor['id'], (float) $cash, $note);
        flash_set('success', 'Order status updated to ' . status_label($newStatus) . '.');
        redirect('vendor.orders');
    }

    public function products(): void
    {
        [, $vendor] = $this->vendor('food.update');
        $foods = db_all('SELECT f.*, c.name AS category_name, m.id AS media_id, i.quantity AS stock, i.unit, i.low_stock_threshold FROM foods f LEFT JOIN food_categories c ON c.id=f.category_id LEFT JOIN media m ON m.id=f.media_id LEFT JOIN inventory i ON i.food_id=f.id WHERE f.vendor_id=? ORDER BY f.is_available DESC, f.name', 'i', [(int) $vendor['id']]);
        $this->view('vendor/products', compact('foods', 'vendor') + ['title' => 'Foods']);
    }

    public function productNew(): void
    {
        [, $vendor] = $this->vendor('food.create');
        $food = null;
        $categories = $this->categoriesFor((int) $vendor['id']);
        $this->view('vendor/product-form', compact('vendor', 'food', 'categories') + ['title' => 'Add food']);
    }

    public function productEdit(): void
    {
        [, $vendor] = $this->vendor('food.update');
        $id = $this->positiveId($_GET['id'] ?? null, 'Food');
        $food = db_one('SELECT f.*, m.id AS media_id, i.quantity AS stock, i.unit, i.low_stock_threshold FROM foods f LEFT JOIN media m ON m.id=f.media_id LEFT JOIN inventory i ON i.food_id=f.id WHERE f.id=? AND f.vendor_id=?', 'ii', [$id, (int) $vendor['id']]);
        if (!$food) $this->pageNotFound();
        $categories = $this->categoriesFor((int) $vendor['id']);
        $this->view('vendor/product-form', compact('vendor', 'food', 'categories') + ['title' => 'Edit ' . $food['name']]);
    }

    public function productSave(): void
    {
        $this->postOnly();
        $id = (int) ($_POST['id'] ?? 0);
        [$user, $vendor] = $this->vendor($id > 0 ? 'food.update' : 'food.create');
        $name = InputValidator::text($_POST['name'] ?? '', 140, 'Food name');
        $description = InputValidator::text($_POST['description'] ?? '', 3000, 'Description', false);
        $price = InputValidator::money($_POST['price'] ?? '', 'Price');
        $prep = InputValidator::positiveInt($_POST['prep_minutes'] ?? 10, 'Preparation time', 1, 240);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $available = isset($_POST['is_available']) ? 1 : 0;
        $db = db(); $db->begin_transaction();
        try {
            if ($categoryId > 0 && !db_one('SELECT id FROM food_categories WHERE id=? AND is_active=1 AND (vendor_id IS NULL OR vendor_id=?) FOR UPDATE', 'ii', [$categoryId, (int) $vendor['id']])) throw new InvalidArgumentException('Choose a valid food category.');
            $imageId = upload_media($_FILES['image'] ?? null, (int) $user['id']);
            if ($id > 0) {
                $existing = db_one('SELECT id, slug, media_id FROM foods WHERE id=? AND vendor_id=? FOR UPDATE', 'ii', [$id, (int) $vendor['id']]);
                if (!$existing) throw new HttpException(404, 'Food not found.');
                if ($imageId) {
                    db_exec('UPDATE foods SET category_id=?, media_id=?, name=?, description=?, price=?, prep_minutes=?, is_available=?, updated_at=NOW() WHERE id=? AND vendor_id=?', 'iissdiiii', [$categoryId ?: null, $imageId, $name, $description ?: null, $price, $prep, $available, $id, (int) $vendor['id']]);
                } else {
                    db_exec('UPDATE foods SET category_id=?, name=?, description=?, price=?, prep_minutes=?, is_available=?, updated_at=NOW() WHERE id=? AND vendor_id=?', 'issdiiii', [$categoryId ?: null, $name, $description ?: null, $price, $prep, $available, $id, (int) $vendor['id']]);
                }
                audit_log('Updated food', 'food', $id, ['name' => $name]);
            } else {
                $slug = slugify($name) . '-' . strtolower(bin2hex(random_bytes(3)));
                $result = db_exec('INSERT INTO foods (vendor_id, category_id, media_id, name, slug, description, price, prep_minutes, is_available, is_featured, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())', 'iiisssdii', [(int) $vendor['id'], $categoryId ?: null, $imageId, $name, $slug, $description ?: null, $price, $prep, $available]);
                $foodId = (int) $result['insert_id'];
                $initial = $this->decimal($_POST['initial_stock'] ?? '0', 'Opening stock', 0, 1000000);
                $threshold = $this->decimal($_POST['low_stock_threshold'] ?? '5', 'Low-stock threshold', 0, 1000000);
                $unit = InputValidator::text($_POST['unit'] ?? 'servings', 30, 'Stock unit');
                $inventory = db_exec('INSERT INTO inventory (vendor_id, food_id, quantity, unit, low_stock_threshold, updated_at) VALUES (?, ?, ?, ?, ?, NOW())', 'iidsd', [(int) $vendor['id'], $foodId, $initial, $unit, $threshold]);
                if ($initial > 0) $this->movement((int) $inventory['insert_id'], 'PURCHASE', $initial, 0, $initial, 'Opening stock', $foodId, (int) $user['id']);
                audit_log('Created food', 'food', $foodId, ['name' => $name]);
                $id = $foodId;
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
        flash_set('success', 'Food saved.');
        redirect('vendor.products');
    }

    public function productToggle(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('food.delete');
        $id = $this->positiveId($_POST['id'] ?? null, 'Food');
        $food = db_one('SELECT id, name, is_available FROM foods WHERE id=? AND vendor_id=?', 'ii', [$id, (int) $vendor['id']]);
        if (!$food) $this->pageNotFound();
        $next = (int) !$food['is_available'];
        db_exec('UPDATE foods SET is_available=?, updated_at=NOW() WHERE id=? AND vendor_id=?', 'iii', [$next, $id, (int) $vendor['id']]);
        audit_log($next ? 'Relisted food' : 'Unlisted food', 'food', $id, ['name' => $food['name']]);
        flash_set('success', $food['name'] . ($next ? ' is available again.' : ' is no longer listed.'));
        redirect('vendor.products');
    }

    public function categories(): void
    {
        [, $vendor] = $this->vendor('category.manage');
        $categories = db_all('SELECT c.*, (SELECT COUNT(*) FROM foods f WHERE f.category_id=c.id AND f.vendor_id=?) AS food_count FROM food_categories c WHERE c.vendor_id IS NULL OR c.vendor_id=? ORDER BY c.vendor_id IS NOT NULL, c.sort_order, c.name', 'ii', [(int) $vendor['id'], (int) $vendor['id']]);
        $this->view('vendor/categories', compact('categories', 'vendor') + ['title' => 'Categories']);
    }

    public function categorySave(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('category.manage');
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $name = InputValidator::text($_POST['name'] ?? '', 80, 'Category name');
        $description = InputValidator::text($_POST['description'] ?? '', 240, 'Description', false) ?: null;
        if ($id > 0) {
            $category = db_one('SELECT id FROM food_categories WHERE id=? AND vendor_id=?', 'ii', [$id, (int) $vendor['id']]);
            if (!$category) $this->pageNotFound();
            if (db_one('SELECT id FROM food_categories WHERE vendor_id=? AND name=? AND id<>?', 'isi', [(int) $vendor['id'], $name, $id])) throw new InvalidArgumentException('You already have a category with that name.');
            db_exec('UPDATE food_categories SET name=?, description=?, updated_at=NOW() WHERE id=? AND vendor_id=?', 'ssii', [$name, $description, $id, (int) $vendor['id']]);
            audit_log('Updated food category', 'category', $id, ['name' => $name]);
            flash_set('success', 'Category updated.');
        } else {
            if (db_one('SELECT id FROM food_categories WHERE vendor_id=? AND name=?', 'is', [(int) $vendor['id'], $name])) throw new InvalidArgumentException('You already have a category with that name.');
            $slug = slugify($name) . '-' . strtolower(bin2hex(random_bytes(2)));
            $result = db_exec('INSERT INTO food_categories (vendor_id, name, slug, description, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 50, 1, NOW(), NOW())', 'isss', [(int) $vendor['id'], $name, $slug, $description]);
            audit_log('Created food category', 'category', (int) $result['insert_id'], ['name' => $name]);
            flash_set('success', 'Category added.');
        }
        redirect('vendor.categories');
    }

    public function categoryToggle(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('category.manage');
        $id = $this->positiveId($_POST['id'] ?? null, 'Category');
        $db = db();
        $db->begin_transaction();
        try {
            $category = db_one('SELECT id, name, is_active FROM food_categories WHERE id=? AND vendor_id=? FOR UPDATE', 'ii', [$id, (int) $vendor['id']]);
            if (!$category) $this->pageNotFound();
            $next = (int) !$category['is_active'];
            if (!$next) {
                $usage = db_one('SELECT COUNT(*) AS n FROM foods WHERE category_id=?', 'i', [$id]);
                if ((int) ($usage['n'] ?? 0) > 0) throw new InvalidArgumentException('Reassign this category’s foods before deactivating it.');
            }
            db_exec('UPDATE food_categories SET is_active=?, updated_at=NOW() WHERE id=? AND vendor_id=?', 'iii', [$next, $id, (int) $vendor['id']]);
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        audit_log($next ? 'Activated vendor category' : 'Deactivated vendor category', 'category', $id);
        flash_set('success', 'Category updated.');
        redirect('vendor.categories');
    }

    public function inventory(): void
    {
        [, $vendor] = $this->vendor('inventory.view');
        $items = db_all('SELECT i.*, f.name AS food_name, f.is_available FROM inventory i JOIN foods f ON f.id=i.food_id WHERE i.vendor_id=? ORDER BY (i.quantity<=i.low_stock_threshold) DESC, f.name', 'i', [(int) $vendor['id']]);
        $movements = db_all('SELECT m.*, f.name AS food_name, u.full_name AS changed_by_name FROM inventory_movements m JOIN inventory i ON i.id=m.inventory_id JOIN foods f ON f.id=i.food_id LEFT JOIN users u ON u.id=m.changed_by WHERE i.vendor_id=? ORDER BY m.created_at DESC, m.id DESC LIMIT 25', 'i', [(int) $vendor['id']]);
        $this->view('vendor/inventory', compact('items', 'movements', 'vendor') + ['title' => 'Inventory']);
    }

    public function inventoryMove(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('inventory.manage');
        $foodId = $this->positiveId($_POST['food_id'] ?? null, 'Food');
        $type = strtoupper((string) ($_POST['movement_type'] ?? ''));
        if (!in_array($type, ['RESTOCK', 'PURCHASE', 'WASTE', 'ADJUSTMENT'], true)) throw new InvalidArgumentException('Choose a valid stock movement.');
        $note = InputValidator::text($_POST['note'] ?? '', 240, 'Note', false);
        $db = db(); $db->begin_transaction();
        try {
            $inventory = db_one('SELECT i.* FROM inventory i JOIN foods f ON f.id=i.food_id WHERE i.food_id=? AND i.vendor_id=? FOR UPDATE', 'ii', [$foodId, (int) $vendor['id']]);
            if (!$inventory) throw new HttpException(404, 'Inventory item not found.');
            $before = (float) $inventory['quantity'];
            if ($type === 'ADJUSTMENT') {
                $target = $this->decimal($_POST['quantity'] ?? '', 'New stock count', 0, 1000000);
                $after = $target; $change = $after - $before;
            } else {
                $amount = $this->decimal($_POST['quantity'] ?? '', 'Quantity', 0.01, 1000000);
                $change = in_array($type, ['WASTE'], true) ? -$amount : $amount;
                $after = round($before + $change, 2);
                if ($after < 0) throw new InvalidArgumentException('Stock cannot go below zero.');
                if ($after > 1000000) throw new InvalidArgumentException('Stock cannot exceed 1,000,000 units.');
            }
            db_exec('UPDATE inventory SET quantity=?, updated_at=NOW() WHERE id=?', 'di', [$after, (int) $inventory['id']]);
            $this->movement((int) $inventory['id'], $type, $change, $before, $after, $note ?: null, $foodId, (int) $user['id']);
            $db->commit();
            audit_log('Inventory movement', 'inventory', (int) $inventory['id'], ['type' => $type, 'change' => $change, 'after' => $after]);
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
        flash_set('success', 'Inventory updated and movement recorded.');
        redirect('vendor.inventory');
    }

    public function pos(): void
    {
        [, $vendor] = $this->vendor('pos.use');
        $foods = db_all('SELECT f.id, f.name, f.price, f.media_id, f.prep_minutes, f.category_id, i.quantity AS stock, i.unit FROM foods f JOIN inventory i ON i.food_id=f.id WHERE f.vendor_id=? AND f.is_available=1 AND i.quantity>=1 ORDER BY f.name', 'i', [(int) $vendor['id']]);
        $categories = $this->categoriesFor((int) $vendor['id']);
        $receipt = null;
        $lastId = (int) ($_GET['last'] ?? 0);
        if ($lastId > 0) $receipt = db_one("SELECT o.*, p.cash_received, p.change_due, p.method FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.vendor_id=? AND o.source='POS'", 'ii', [$lastId, (int) $vendor['id']]);
        $receiptItems = $receipt ? db_all('SELECT * FROM order_items WHERE order_id=?', 'i', [$lastId]) : [];
        $this->view('vendor/pos', compact('foods', 'categories', 'vendor', 'receipt', 'receiptItems') + ['title' => 'Point of sale']);
    }

    public function posComplete(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('pos.use');
        $items = $_POST['items'] ?? [];
        if (!is_array($items)) throw new InvalidArgumentException('Select food items for this sale.');
        $cash = $_POST['cash_received'] ?? null;
        if (!is_numeric($cash) || (float) $cash < 0 || (float) $cash > 1000000) throw new InvalidArgumentException('Enter a valid cash amount.');
        $orderId = OrderService::createPos((int) $vendor['id'], (int) $user['id'], $items, (float) $cash);
        $order = db_one('SELECT order_number FROM orders WHERE id=?', 'i', [$orderId]);
        flash_set('success', 'Sale ' . $order['order_number'] . ' recorded. Inventory and cash payment were saved.');
        redirect('vendor.pos', ['last' => $orderId]);
    }

    public function reports(): void
    {
        [, $vendor] = $this->vendor('reports.view');
        $start = $this->dateValue($_GET['start'] ?? date('Y-m-d', strtotime('-6 days')), 'start');
        $end = $this->dateValue($_GET['end'] ?? date('Y-m-d'), 'end');
        if ($start > $end) throw new InvalidArgumentException('Start date must be on or before end date.');
        $range = db_one("SELECT COUNT(*) AS order_count, SUM(status='CANCELLED') AS cancelled_count, SUM(status='REJECTED') AS rejected_count FROM orders WHERE vendor_id=? AND DATE(created_at) BETWEEN ? AND ?", 'iss', [(int) $vendor['id'], $start, $end]);
        $salesInfo = db_one("SELECT COUNT(*) AS completed_count, COALESCE(SUM(total),0) AS sales, AVG(CASE WHEN accepted_at IS NOT NULL AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, accepted_at, completed_at) END) AS avg_fulfillment FROM orders WHERE vendor_id=? AND status='COMPLETED' AND DATE(completed_at) BETWEEN ? AND ?", 'iss', [(int) $vendor['id'], $start, $end]);
        $range = array_merge($range ?? [], $salesInfo ?? []);
        $daily = db_all("SELECT DATE(completed_at) AS day, COUNT(*) AS transactions, SUM(total) AS sales FROM orders WHERE vendor_id=? AND status='COMPLETED' AND DATE(completed_at) BETWEEN ? AND ? GROUP BY DATE(completed_at) ORDER BY day", 'iss', [(int) $vendor['id'], $start, $end]);
        $best = db_all("SELECT oi.food_id, oi.food_name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS sales FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.vendor_id=? AND o.status='COMPLETED' AND DATE(o.completed_at) BETWEEN ? AND ? GROUP BY oi.food_id, oi.food_name ORDER BY units DESC LIMIT 10", 'iss', [(int) $vendor['id'], $start, $end]);
        $lowStock = db_all('SELECT f.name, i.quantity, i.unit, i.low_stock_threshold FROM inventory i JOIN foods f ON f.id=i.food_id WHERE i.vendor_id=? AND i.quantity<=i.low_stock_threshold ORDER BY i.quantity LIMIT 20', 'i', [(int) $vendor['id']]);
        $this->view('vendor/reports', compact('vendor', 'start', 'end', 'range', 'daily', 'best', 'lowStock') + ['title' => 'Reports']);
    }

    public function reviews(): void
    {
        [, $vendor] = $this->vendor('review.respond');
        $reviews = db_all("SELECT r.*, u.full_name, f.name AS food_name, o.order_number FROM reviews r JOIN users u ON u.id=r.user_id JOIN foods f ON f.id=r.food_id JOIN orders o ON o.id=r.order_id WHERE f.vendor_id=? AND r.status='PUBLISHED' ORDER BY r.created_at DESC LIMIT 100", 'i', [(int) $vendor['id']]);
        $this->view('vendor/reviews', compact('reviews', 'vendor') + ['title' => 'Reviews']);
    }

    public function reviewReply(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('review.respond');
        $id = $this->positiveId($_POST['review_id'] ?? null, 'Review');
        $reply = InputValidator::text($_POST['vendor_response'] ?? '', 1000, 'Response');
        $review = db_one('SELECT r.id FROM reviews r JOIN foods f ON f.id=r.food_id WHERE r.id=? AND f.vendor_id=? AND r.status=\'PUBLISHED\'', 'ii', [$id, (int) $vendor['id']]);
        if (!$review) $this->pageNotFound();
        db_exec('UPDATE reviews SET vendor_response=?, vendor_responded_at=NOW(), updated_at=NOW() WHERE id=?', 'si', [$reply, $id]);
        audit_log('Responded to review', 'review', $id);
        flash_set('success', 'Response saved.');
        redirect('vendor.reviews');
    }

    public function settings(): void
    {
        [, $vendor] = $this->vendor('vendor.settings');
        $settings = db_one('SELECT * FROM vendor_settings WHERE vendor_id=?', 'i', [(int) $vendor['id']]);
        $this->view('vendor/settings', compact('vendor', 'settings') + ['title' => 'Store settings']);
    }

    public function settingsSave(): void
    {
        $this->postOnly();
        [$user, $vendor] = $this->vendor('vendor.settings');
        $name = InputValidator::text($_POST['name'] ?? '', 120, 'Store name');
        $description = InputValidator::text($_POST['description'] ?? '', 2000, 'Description', false);
        $pickup = InputValidator::text($_POST['pickup_location'] ?? '', 180, 'Pickup location');
        $stall = InputValidator::text($_POST['stall_label'] ?? '', 80, 'Stall or counter', false);
        $prep = InputValidator::positiveInt($_POST['prep_minutes'] ?? 15, 'Preparation estimate', 1, 240);
        $notice = InputValidator::text($_POST['store_notice'] ?? '', 240, 'Store notice', false);
        $accepting = isset($_POST['accepting_orders']) ? 1 : 0;
        $db = db();
        $db->begin_transaction();
        try {
            db_exec('UPDATE vendors SET name=?, description=?, pickup_location=?, stall_label=?, prep_minutes=?, updated_at=NOW() WHERE id=? AND user_id=?', 'ssssiii', [$name, $description ?: null, $pickup, $stall ?: null, $prep, (int) $vendor['id'], (int) $user['id']]);
            db_exec('UPDATE vendor_settings SET accepting_orders=?, store_notice=?, updated_at=NOW() WHERE vendor_id=?', 'isi', [$accepting, $notice ?: null, (int) $vendor['id']]);
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        audit_log('Updated vendor settings', 'vendor', (int) $vendor['id']);
        flash_set('success', 'Store settings saved.');
        redirect('vendor.settings');
    }

    private function categoriesFor(int $vendorId): array
    {
        return db_all('SELECT id, name, vendor_id FROM food_categories WHERE is_active=1 AND (vendor_id IS NULL OR vendor_id=?) ORDER BY vendor_id IS NOT NULL, sort_order, name', 'i', [$vendorId]);
    }

    private function movement(int $inventoryId, string $type, float $change, float $before, float $after, ?string $note, int $foodId, int $userId): void
    {
        db_exec('INSERT INTO inventory_movements (inventory_id, movement_type, quantity_change, quantity_before, quantity_after, note, reference_type, reference_id, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, \'FOOD\', ?, ?, NOW())', 'isdddsii', [$inventoryId, $type, $change, $before, $after, $note, $foodId, $userId]);
    }

    private function decimal($value, string $label, float $min, float $max): float
    {
        if (!is_numeric($value)) throw new InvalidArgumentException($label . ' must be a number.');
        $number = round((float) $value, 2);
        if ($number < $min || $number > $max) throw new InvalidArgumentException($label . ' must be between ' . $min . ' and ' . $max . '.');
        return $number;
    }

    private function dateValue($value, string $label): string
    {
        $value = (string) $value;
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Choose a valid ' . $label . ' date.');
        return $value;
    }
}
