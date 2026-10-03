<?php
final class OrderService
{
    public static function placeStudentOrder(int $userId, string $customerNote = ''): int
    {
        $db = db();
        $db->begin_transaction();
        try {
            $items = db_all("SELECT ci.food_id, ci.quantity, f.name, f.price, f.prep_minutes, f.is_available, f.vendor_id, f.media_id, i.id AS inventory_id, i.quantity AS stock, v.name AS vendor_name, v.status AS vendor_status, v.user_id AS vendor_user_id, v.pickup_location, v.stall_label, v.prep_minutes AS vendor_prep_minutes, vs.accepting_orders
                FROM cart_items ci
                JOIN foods f ON f.id=ci.food_id
                JOIN vendors v ON v.id=f.vendor_id
                LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id
                JOIN inventory i ON i.food_id=f.id
                WHERE ci.user_id=? ORDER BY f.id FOR UPDATE", 'i', [$userId]);
            if (!$items) throw new InvalidArgumentException('Your cart is empty. Add a food before checkout.');

            $vendorId = (int) $items[0]['vendor_id'];
            $vendorName = $items[0]['vendor_name'];
            $pickup = trim((string) $items[0]['pickup_location']);
            $stall = trim((string) $items[0]['stall_label']);
            $prepMinutes = (int) $items[0]['vendor_prep_minutes'];
            $subtotal = 0.0;
            foreach ($items as $item) {
                if ((int) $item['vendor_id'] !== $vendorId) throw new InvalidArgumentException('A cart can contain food from one vendor only. Remove items from another vendor and try again.');
                if ($item['vendor_status'] !== 'ACTIVE' || !(int) $item['accepting_orders']) throw new InvalidArgumentException($vendorName . ' is not accepting orders right now.');
                if (!(int) $item['is_available']) throw new InvalidArgumentException($item['name'] . ' is no longer available. Remove it from your cart to continue.');
                if ((float) $item['stock'] < (int) $item['quantity']) throw new InvalidArgumentException('There is not enough stock for ' . $item['name'] . '. Update your cart and try again.');
                $subtotal += (float) $item['price'] * (int) $item['quantity'];
                if ($subtotal > 1000000) throw new InvalidArgumentException('The order total cannot exceed ₱1,000,000.');
                $prepMinutes = max($prepMinutes, (int) $item['prep_minutes']);
            }

            $number = Order::nextNumber();
            $location = $pickup . ($stall !== '' ? ' · ' . $stall : '');
            $insert = db_exec("INSERT INTO orders (order_number, user_id, vendor_id, source, status, subtotal, total, pickup_location, prep_estimate_minutes, customer_note, created_at, updated_at)
                VALUES (?, ?, ?, 'STUDENT', 'PENDING', ?, ?, ?, ?, ?, NOW(), NOW())", 'siiddsis', [$number, $userId, $vendorId, $subtotal, $subtotal, $location, $prepMinutes, $customerNote]);
            $orderId = (int) $insert['insert_id'];

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                $lineTotal = $price * $quantity;
                db_exec('INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total, inventory_id) VALUES (?, ?, ?, ?, ?, ?, ?)', 'iisdidi', [$orderId, (int) $item['food_id'], $item['name'], $price, $quantity, $lineTotal, (int) $item['inventory_id']]);
                $before = (float) $item['stock'];
                $after = $before - $quantity;
                $changed = db_exec('UPDATE inventory SET quantity=?, updated_at=NOW() WHERE id=? AND quantity>=?', 'did', [$after, (int) $item['inventory_id'], $quantity]);
                if ($changed['affected'] !== 1) throw new RuntimeException('Stock changed while checkout was in progress.');
                self::movement((int) $item['inventory_id'], 'SALE', -$quantity, $before, $after, 'Reserved for ' . $number, 'ORDER', $orderId, $userId);
            }

            db_exec("INSERT INTO payments (order_id, method, status, amount, cash_received, change_due, created_at) VALUES (?, 'CASH', 'UNPAID', ?, 0, 0, NOW())", 'id', [$orderId, $subtotal]);
            db_exec("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note, created_at) VALUES (?, NULL, 'PENDING', ?, 'Order placed by student', NOW())", 'ii', [$orderId, $userId]);
            db_exec('DELETE FROM cart_items WHERE user_id=?', 'i', [$userId]);
            $db->commit();
            try {
                notify_user((int) $items[0]['vendor_user_id'], 'New order received', 'Order ' . $number . ' is waiting for confirmation.', 'vendor.orders');
            } catch (Throwable $notificationError) {
                app_log($notificationError);
            }
            audit_log('Placed student order', 'order', $orderId, ['order_number' => $number, 'total' => $subtotal]);
            return $orderId;
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function transition(int $orderId, string $newStatus, int $actorId, string $actorRole, ?int $vendorId = null, float $cashReceived = 0, string $note = ''): void
    {
        if (!in_array($newStatus, ORDER_STATUSES, true)) throw new InvalidArgumentException('Choose a valid order status.');
        $db = db();
        $db->begin_transaction();
        try {
            $order = db_one('SELECT * FROM orders WHERE id=? FOR UPDATE', 'i', [$orderId]);
            if (!$order) throw new HttpException(404, 'Order not found.');
            if ($actorRole === 'VENDOR' && (int) $order['vendor_id'] !== (int) $vendorId) throw new HttpException(403, 'This order belongs to another vendor.');
            if (!in_array($newStatus, Order::allowedNext($order['status']), true)) {
                throw new InvalidArgumentException('That order cannot move from ' . status_label($order['status']) . ' to ' . status_label($newStatus) . '.');
            }

            if ($newStatus === 'COMPLETED') {
                $payment = db_one('SELECT * FROM payments WHERE order_id=? FOR UPDATE', 'i', [$orderId]);
                if (!$payment || $payment['method'] !== 'CASH') throw new RuntimeException('The cash payment record is missing. Ask an administrator to check this order.');
                $cashReceived = round($cashReceived, 2);
                if (!is_finite($cashReceived) || $cashReceived < 0 || $cashReceived > 1000000) throw new InvalidArgumentException('Enter cash received between ₱0 and ₱1,000,000.');
                if ($cashReceived < (float) $order['total']) throw new InvalidArgumentException('Cash received must be at least ' . money($order['total']) . '.');
                db_exec("UPDATE payments SET status='PAID', cash_received=?, change_due=?, confirmed_by=?, paid_at=NOW() WHERE order_id=?", 'ddii', [$cashReceived, $cashReceived - (float) $order['total'], $actorId, $orderId]);
            }

            if (in_array($newStatus, ['CANCELLED', 'REJECTED'], true)) {
                $reserved = db_all('SELECT oi.inventory_id, oi.quantity, f.name, i.quantity AS stock FROM order_items oi JOIN foods f ON f.id=oi.food_id JOIN inventory i ON i.id=oi.inventory_id WHERE oi.order_id=? FOR UPDATE', 'i', [$orderId]);
                foreach ($reserved as $item) {
                    $before = (float) $item['stock'];
                    $after = $before + (int) $item['quantity'];
                    db_exec('UPDATE inventory SET quantity=?, updated_at=NOW() WHERE id=?', 'di', [$after, (int) $item['inventory_id']]);
                    self::movement((int) $item['inventory_id'], 'RETURN', (int) $item['quantity'], $before, $after, 'Released after order ' . strtolower($newStatus), 'ORDER', $orderId, $actorId);
                }
            }

            $timestampColumn = match ($newStatus) {
                'CONFIRMED' => ', accepted_at=NOW()',
                'READY' => ', ready_at=NOW()',
                'COMPLETED' => ', completed_at=NOW()',
                default => '',
            };
            db_exec('UPDATE orders SET status=?, updated_at=NOW()' . $timestampColumn . ' WHERE id=?', 'si', [$newStatus, $orderId]);
            db_exec('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note, created_at) VALUES (?, ?, ?, ?, ?, NOW())', 'issis', [$orderId, $order['status'], $newStatus, $actorId, $note]);
            $db->commit();

            $studentId = $order['user_id'] ? (int) $order['user_id'] : null;
            if ($studentId) {
                try {
                    notify_user($studentId, 'Order update: ' . status_label($newStatus), 'Order ' . $order['order_number'] . ' is now ' . strtolower(status_label($newStatus)) . '.', 'my.order&id=' . $orderId);
                } catch (Throwable $notificationError) {
                    app_log($notificationError);
                }
            }
            audit_log('Changed order status', 'order', $orderId, ['from' => $order['status'], 'to' => $newStatus, 'note' => $note]);
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function createPos(int $vendorId, int $actorId, array $requestedItems, float $cashReceived): int
    {
        $quantities = [];
        foreach ($requestedItems as $foodId => $quantity) {
            $foodId = filter_var($foodId, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($foodId === false || $foodId < 1 || $quantity === false || $quantity < 1 || $quantity > 99) continue;
            $quantities[(int) $foodId] = min(99, ($quantities[(int) $foodId] ?? 0) + (int) $quantity);
        }
        if (!$quantities) throw new InvalidArgumentException('Add at least one food to the sale.');
        if (!is_finite($cashReceived) || $cashReceived < 0 || $cashReceived > 1000000) throw new InvalidArgumentException('Enter cash received between ₱0 and ₱1,000,000.');

        $db = db();
        $db->begin_transaction();
        try {
            $foodIds = array_keys($quantities);
            sort($foodIds);
            $items = [];
            $subtotal = 0.0;
            $prep = 0;
            foreach ($foodIds as $foodId) {
                $item = db_one("SELECT f.id, f.name, f.price, f.prep_minutes, f.is_available, i.id AS inventory_id, i.quantity AS stock
                    FROM foods f JOIN inventory i ON i.food_id=f.id WHERE f.id=? AND f.vendor_id=? FOR UPDATE", 'ii', [$foodId, $vendorId]);
                if (!$item || !(int) $item['is_available']) throw new InvalidArgumentException('A selected food is unavailable or belongs to another vendor.');
                $quantity = $quantities[$foodId];
                if ((float) $item['stock'] < $quantity) throw new InvalidArgumentException('Not enough stock for ' . $item['name'] . '.');
                $item['quantity'] = $quantity;
                $items[] = $item;
                $subtotal += (float) $item['price'] * $quantity;
                if ($subtotal > 1000000) throw new InvalidArgumentException('The sale total cannot exceed ₱1,000,000.');
                $prep = max($prep, (int) $item['prep_minutes']);
            }
            $cashReceived = round($cashReceived, 2);
            if ($cashReceived < $subtotal) throw new InvalidArgumentException('Cash received must be at least ' . money($subtotal) . '.');
            $vendor = db_one('SELECT name, pickup_location, stall_label, prep_minutes FROM vendors WHERE id=? AND status=\'ACTIVE\' FOR UPDATE', 'i', [$vendorId]);
            if (!$vendor) throw new HttpException(403, 'This vendor account is not active.');
            $prep = max($prep, (int) $vendor['prep_minutes']);

            $number = Order::nextNumber();
            $location = trim($vendor['pickup_location'] . ($vendor['stall_label'] ? ' · ' . $vendor['stall_label'] : ''));
            $insert = db_exec("INSERT INTO orders (order_number, user_id, vendor_id, source, status, subtotal, total, pickup_location, prep_estimate_minutes, customer_note, created_at, updated_at, completed_at)
                VALUES (?, NULL, ?, 'POS', 'COMPLETED', ?, ?, ?, ?, 'In-person POS sale', NOW(), NOW(), NOW())", 'siddsi', [$number, $vendorId, $subtotal, $subtotal, $location, $prep]);
            $orderId = (int) $insert['insert_id'];
            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                db_exec('INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total, inventory_id) VALUES (?, ?, ?, ?, ?, ?, ?)', 'iisdidi', [$orderId, (int) $item['id'], $item['name'], $price, $quantity, $price * $quantity, (int) $item['inventory_id']]);
                $before = (float) $item['stock']; $after = $before - $quantity;
                $changed = db_exec('UPDATE inventory SET quantity=?, updated_at=NOW() WHERE id=? AND quantity>=?', 'did', [$after, (int) $item['inventory_id'], $quantity]);
                if ($changed['affected'] !== 1) throw new RuntimeException('Stock changed while the sale was in progress.');
                self::movement((int) $item['inventory_id'], 'SALE', -$quantity, $before, $after, 'POS sale ' . $number, 'ORDER', $orderId, $actorId);
            }
            db_exec("INSERT INTO payments (order_id, method, status, amount, cash_received, change_due, confirmed_by, paid_at, created_at) VALUES (?, 'CASH', 'PAID', ?, ?, ?, ?, NOW(), NOW())", 'idddi', [$orderId, $subtotal, $cashReceived, $cashReceived - $subtotal, $actorId]);
            db_exec('INSERT INTO pos_transactions (order_id, vendor_id, total, cash_received, change_due, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', 'iidddi', [$orderId, $vendorId, $subtotal, $cashReceived, $cashReceived - $subtotal, $actorId]);
            db_exec("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note, created_at) VALUES (?, NULL, 'COMPLETED', ?, 'Completed through vendor POS', NOW())", 'ii', [$orderId, $actorId]);
            $db->commit();
            audit_log('Completed POS sale', 'pos_transaction', $orderId, ['order_number' => $number, 'total' => $subtotal, 'cash_received' => $cashReceived]);
            return $orderId;
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    private static function movement(int $inventoryId, string $type, float $change, float $before, float $after, string $note, string $referenceType, int $referenceId, int $userId): void
    {
        db_exec('INSERT INTO inventory_movements (inventory_id, movement_type, quantity_change, quantity_before, quantity_after, note, reference_type, reference_id, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())', 'isdddssii', [$inventoryId, $type, $change, $before, $after, $note, $referenceType, $referenceId, $userId]);
    }
}
