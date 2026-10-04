<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Services/OrderService.php';

final class StudentController extends Controller
{
    private function student(string $permission): array
    {
        $user = require_permission($permission);
        if ($user['role'] !== 'STUDENT') throw new HttpException(403, 'This page is for student accounts.');
        return $user;
    }

    private function cartRows(int $userId): array
    {
        return db_all('SELECT ci.id, ci.food_id, ci.quantity, f.name, f.price, f.prep_minutes, f.media_id, f.is_available, i.quantity AS stock, v.id AS vendor_id, v.name AS vendor_name, v.status AS vendor_status, COALESCE(vs.accepting_orders,0) AS accepting_orders FROM cart_items ci JOIN foods f ON f.id=ci.food_id JOIN vendors v ON v.id=f.vendor_id LEFT JOIN inventory i ON i.food_id=f.id LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id WHERE ci.user_id=? ORDER BY ci.created_at', 'i', [$userId]);
    }

    public function cart(): void
    {
        $user = $this->student('cart.manage');
        $items = $this->cartRows((int) $user['id']);
        $total = 0.0; foreach ($items as $item) $total += (float) $item['price'] * (int) $item['quantity'];
        $vendorName = $items[0]['vendor_name'] ?? '';
        $this->view('student/cart', compact('items', 'total', 'vendorName') + ['title' => 'Your cart']);
    }

    public function cartAdd(): void
    {
        $this->postOnly();
        $user = $this->student('cart.manage');
        $foodId = $this->positiveId($_POST['food_id'] ?? null, 'Food');
        $quantity = InputValidator::positiveInt($_POST['quantity'] ?? 1, 'Quantity', 1, 99);
        $food = db_one("SELECT f.id, f.name, f.vendor_id, f.is_available, i.id AS inventory_id, i.quantity AS stock, v.status AS vendor_status, v.name AS vendor_name, vs.accepting_orders FROM foods f JOIN vendors v ON v.id=f.vendor_id JOIN inventory i ON i.food_id=f.id LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id WHERE f.id=?", 'i', [$foodId]);
        if (!$food || !(int) $food['is_available'] || $food['vendor_status'] !== 'ACTIVE' || !(int) $food['accepting_orders']) throw new InvalidArgumentException('That food is not available to order right now.');
        $otherVendor = db_one('SELECT f.vendor_id FROM cart_items ci JOIN foods f ON f.id=ci.food_id WHERE ci.user_id=? AND f.vendor_id<>? LIMIT 1', 'ii', [(int) $user['id'], (int) $food['vendor_id']]);
        if ($otherVendor) throw new InvalidArgumentException('Your cart is for one vendor at a time. Remove the other vendor’s items before adding this food.');
        $existing = db_one('SELECT quantity FROM cart_items WHERE user_id=? AND food_id=?', 'ii', [(int) $user['id'], $foodId]);
        $newQuantity = (int) ($existing['quantity'] ?? 0) + $quantity;
        if ($newQuantity > 99 || $newQuantity > (float) $food['stock']) throw new InvalidArgumentException('There is not enough stock for that quantity. Available now: ' . (int) $food['stock'] . '.');
        db_exec('INSERT INTO cart_items (user_id, food_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE quantity=?, updated_at=NOW()', 'iiii', [(int) $user['id'], $foodId, $newQuantity, $newQuantity]);
        flash_set('success', $food['name'] . ' added to your cart.');
        $return = (string) ($_POST['return_to'] ?? 'foods');
        if ($return === 'food') redirect('food', ['id' => $foodId]);
        redirect('foods');
    }

    public function cartUpdate(): void
    {
        $this->postOnly();
        $user = $this->student('cart.manage');
        $foodId = $this->positiveId($_POST['food_id'] ?? null, 'Food');
        $quantity = InputValidator::positiveInt($_POST['quantity'] ?? null, 'Quantity', 1, 99);
        $item = db_one('SELECT ci.id, f.name, i.quantity AS stock FROM cart_items ci JOIN foods f ON f.id=ci.food_id LEFT JOIN inventory i ON i.food_id=f.id WHERE ci.user_id=? AND ci.food_id=?', 'ii', [(int) $user['id'], $foodId]);
        if (!$item) throw new HttpException(404, 'That cart item is no longer there.');
        if ($quantity > (float) $item['stock']) throw new InvalidArgumentException('Only ' . (int) $item['stock'] . ' are currently available.');
        db_exec('UPDATE cart_items SET quantity=?, updated_at=NOW() WHERE id=? AND user_id=?', 'iii', [$quantity, (int) $item['id'], (int) $user['id']]);
        flash_set('success', 'Cart quantity updated.');
        redirect('cart');
    }

    public function cartRemove(): void
    {
        $user = $this->student('cart.manage');
        $this->postOnly();
        $foodId = $this->positiveId($_POST['food_id'] ?? null, 'Food');
        db_exec('DELETE FROM cart_items WHERE user_id=? AND food_id=?', 'ii', [(int) $user['id'], $foodId]);
        flash_set('success', 'Food removed from your cart.');
        redirect('cart');
    }

    public function checkout(): void
    {
        $user = $this->student('cart.manage');
        $items = $this->cartRows((int) $user['id']);
        if (!$items) {
            flash_set('info', 'Your cart is empty. Browse foods to get started.');
            redirect('foods');
        }
        $vendorId = (int) $items[0]['vendor_id'];
        foreach ($items as $item) {
            if ((int) $item['vendor_id'] !== $vendorId) throw new HttpException(409, 'Your cart contains more than one vendor. Remove items until only one vendor remains.');
            if (!(int) $item['is_available'] || $item['vendor_status'] !== 'ACTIVE' || (int) $item['quantity'] > (float) $item['stock']) {
                flash_set('error', $item['name'] . ' is no longer available in that quantity. Please update your cart.');
                redirect('cart');
            }
        }
        $total = 0; foreach ($items as $item) $total += (float) $item['price'] * (int) $item['quantity'];
        $vendor = db_one('SELECT v.*, vs.accepting_orders FROM vendors v LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id WHERE v.id=?', 'i', [$vendorId]);
        if (!$vendor || !(int) $vendor['accepting_orders']) {
            flash_set('error', 'This vendor is not accepting orders right now. Your cart is unchanged.');
            redirect('cart');
        }
        $this->view('student/checkout', compact('items', 'total', 'vendor') + ['title' => 'Review and place order']);
    }

    public function placeOrder(): void
    {
        $this->postOnly();
        $user = $this->student('order.create');
        $note = InputValidator::text($_POST['customer_note'] ?? '', 300, 'Pickup note', false);
        $orderId = OrderService::placeStudentOrder((int) $user['id'], $note);
        $order = db_one('SELECT order_number FROM orders WHERE id=?', 'i', [$orderId]);
        flash_set('success', 'Order ' . $order['order_number'] . ' was placed. Pay cash when you pick it up.');
        redirect('my.order', ['id' => $orderId]);
    }

    public function orders(): void
    {
        $user = $this->student('order.view_own');
        $page = max(1, (int) ($_GET['page'] ?? 1)); $limit = 12; $offset = ($page - 1) * $limit;
        $orders = db_all('SELECT o.*, v.name AS vendor_name, p.status AS payment_status FROM orders o JOIN vendors v ON v.id=o.vendor_id LEFT JOIN payments p ON p.order_id=o.id WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT ? OFFSET ?', 'iii', [(int) $user['id'], $limit, $offset]);
        $total = (int) (db_one('SELECT COUNT(*) AS n FROM orders WHERE user_id=?', 'i', [(int) $user['id']])['n'] ?? 0);
        $this->view('student/orders', compact('orders', 'page', 'limit', 'total') + ['title' => 'My orders']);
    }

    public function order(): void
    {
        $user = $this->student('order.view_own');
        $id = $this->positiveId($_GET['id'] ?? null, 'Order');
        $order = db_one('SELECT o.*, v.name AS vendor_name, v.slug AS vendor_slug, p.method, p.status AS payment_status, p.cash_received, p.change_due FROM orders o JOIN vendors v ON v.id=o.vendor_id LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=? LIMIT 1', 'ii', [$id, (int) $user['id']]);
        if (!$order) $this->pageNotFound();
        $items = db_all('SELECT oi.*, r.id AS review_id FROM order_items oi LEFT JOIN reviews r ON r.order_item_id=oi.id WHERE oi.order_id=? ORDER BY oi.id', 'i', [$id]);
        $history = db_all('SELECT h.*, u.full_name AS changed_by_name FROM order_status_history h LEFT JOIN users u ON u.id=h.changed_by WHERE h.order_id=? ORDER BY h.created_at, h.id', 'i', [$id]);
        $this->view('student/order-detail', compact('order', 'items', 'history') + ['title' => 'Order ' . $order['order_number']]);
    }

    public function cancelOrder(): void
    {
        $this->postOnly();
        $user = $this->student('order.view_own');
        $id = $this->positiveId($_POST['id'] ?? null, 'Order');
        $order = db_one('SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1', 'ii', [$id, (int) $user['id']]);
        if (!$order) $this->pageNotFound();
        OrderService::transition($id, 'CANCELLED', (int) $user['id'], 'STUDENT', null, 0, 'Cancelled by student');
        flash_set('success', 'Order cancelled. Reserved stock was returned to the vendor.');
        redirect('my.order', ['id' => $id]);
    }

    public function reviewForm(): void
    {
        $user = $this->student('review.create');
        $itemId = $this->positiveId($_GET['item_id'] ?? null, 'Order item');
        $item = db_one("SELECT oi.*, o.status AS order_status, o.user_id, o.order_number, f.vendor_id FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN foods f ON f.id=oi.food_id WHERE oi.id=? AND o.user_id=? AND o.source='STUDENT'", 'ii', [$itemId, (int) $user['id']]);
        if (!$item || $item['order_status'] !== 'COMPLETED') throw new HttpException(403, 'Reviews are available after a student pickup is completed.');
        $review = db_one('SELECT * FROM reviews WHERE order_item_id=? AND user_id=?', 'ii', [$itemId, (int) $user['id']]);
        if ($review) redirect('reviews.edit', ['id' => (int) $review['id']]);
        $this->view('student/review-form', compact('item') + ['title' => 'Review ' . $item['food_name'], 'review' => null]);
    }

    public function reviewEdit(): void
    {
        $user = $this->student('review.create');
        $id = $this->positiveId($_GET['id'] ?? null, 'Review');
        $review = db_one('SELECT r.*, oi.food_name, o.order_number, o.status AS order_status FROM reviews r JOIN order_items oi ON oi.id=r.order_item_id JOIN orders o ON o.id=r.order_id WHERE r.id=? AND r.user_id=?', 'ii', [$id, (int) $user['id']]);
        if (!$review || $review['order_status'] !== 'COMPLETED') $this->pageNotFound();
        $item = ['id' => $review['order_item_id'], 'food_name' => $review['food_name'], 'order_number' => $review['order_number']];
        $this->view('student/review-form', compact('item', 'review') + ['title' => 'Edit your review']);
    }

    public function reviewSave(): void
    {
        $this->postOnly();
        $user = $this->student('review.create');
        $rating = InputValidator::positiveInt($_POST['rating'] ?? null, 'Rating', 1, 5);
        $comment = InputValidator::text($_POST['comment'] ?? '', 1000, 'Review', false);
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $review = db_one("SELECT r.*, o.status AS order_status FROM reviews r JOIN orders o ON o.id=r.order_id WHERE r.id=? AND r.user_id=?", 'ii', [$reviewId, (int) $user['id']]);
            if (!$review || $review['order_status'] !== 'COMPLETED') throw new HttpException(403, 'That review cannot be edited.');
            db_exec('UPDATE reviews SET rating=?, comment=?, updated_at=NOW() WHERE id=? AND user_id=?', 'isii', [$rating, $comment ?: null, $reviewId, (int) $user['id']]);
            audit_log('Edited review', 'review', $reviewId);
            flash_set('success', 'Your review was updated.');
            redirect('my.order', ['id' => (int) $review['order_id']]);
        }
        $itemId = $this->positiveId($_POST['order_item_id'] ?? null, 'Order item');
        $item = db_one("SELECT oi.id, oi.food_id, oi.order_id, o.order_number FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.id=? AND o.user_id=? AND o.source='STUDENT' AND o.status='COMPLETED'", 'ii', [$itemId, (int) $user['id']]);
        if (!$item) throw new HttpException(403, 'You can review this food after a completed campus pickup.');
        if (db_one('SELECT id FROM reviews WHERE order_item_id=?', 'i', [$itemId])) throw new InvalidArgumentException('This purchase already has a review. You can edit your existing review instead.');
        $insert = db_exec("INSERT INTO reviews (user_id, food_id, order_id, order_item_id, rating, comment, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'PUBLISHED', NOW(), NOW())", 'iiiiis', [(int) $user['id'], (int) $item['food_id'], (int) $item['order_id'], $itemId, $rating, $comment ?: null]);
        audit_log('Created review', 'review', (int) $insert['insert_id'], ['rating' => $rating]);
        flash_set('success', 'Thanks for sharing your feedback.');
        redirect('my.order', ['id' => (int) $item['order_id']]);
    }

    public function reviewReport(): void
    {
        $this->postOnly();
        $user = $this->student('review.report');
        $reviewId = $this->positiveId($_POST['review_id'] ?? null, 'Review');
        $reason = InputValidator::text($_POST['reason'] ?? '', 500, 'Reason');
        $review = db_one('SELECT id, user_id FROM reviews WHERE id=? AND status=\'PUBLISHED\'', 'i', [$reviewId]);
        if (!$review || (int) $review['user_id'] === (int) $user['id']) throw new HttpException(404, 'That review cannot be reported.');
        if (db_one('SELECT id FROM review_reports WHERE review_id=? AND user_id=?', 'ii', [$reviewId, (int) $user['id']])) throw new InvalidArgumentException('You have already reported this review.');
        db_exec("INSERT INTO review_reports (review_id, user_id, reason, status, created_at) VALUES (?, ?, ?, 'OPEN', NOW())", 'iis', [$reviewId, (int) $user['id'], $reason]);
        audit_log('Reported review', 'review', $reviewId);
        flash_set('success', 'Report sent to the campus administrator for review.');
        redirect('food', ['id' => (int) (db_one('SELECT food_id FROM reviews WHERE id=?', 'i', [$reviewId])['food_id'] ?? 0)]);
    }

}
