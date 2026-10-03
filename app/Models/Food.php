<?php
final class Food
{
    private static function selectSql(): string
    {
        return "SELECT f.*, v.name AS vendor_name, v.slug AS vendor_slug, v.pickup_location, v.stall_label, c.name AS category_name, m.id AS media_id, i.quantity AS stock, COALESCE(vs.accepting_orders, 0) AS accepting_orders, COALESCE(rt.average_rating, 0) AS average_rating, COALESCE(rt.rating_count, 0) AS rating_count, COALESCE(sold.sold_count, 0) AS sold_count
            FROM foods f
            JOIN vendors v ON v.id = f.vendor_id
            JOIN inventory i ON i.food_id = f.id AND i.quantity >= 1
            LEFT JOIN vendor_settings vs ON vs.vendor_id = v.id
            LEFT JOIN food_categories c ON c.id = f.category_id
            LEFT JOIN media m ON m.id = f.media_id
            LEFT JOIN (SELECT food_id, AVG(rating) AS average_rating, COUNT(*) AS rating_count FROM reviews WHERE status = 'PUBLISHED' GROUP BY food_id) rt ON rt.food_id = f.id
            LEFT JOIN (SELECT oi.food_id, SUM(oi.quantity) AS sold_count FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'COMPLETED' GROUP BY oi.food_id) sold ON sold.food_id = f.id";
    }

    public static function findPublic(int $id): ?array
    {
        return db_one(self::selectSql() . ' WHERE f.id = ? AND v.status = \'ACTIVE\' AND f.is_available = 1 LIMIT 1', 'i', [$id]);
    }

    public static function search(array $filters, int $limit = 12, int $offset = 0): array
    {
        $sql = self::selectSql() . " WHERE v.status = 'ACTIVE' AND f.is_available = 1";
        $types = '';
        $params = [];
        if (!empty($filters['q'])) {
            $sql .= ' AND (f.name LIKE ? OR f.description LIKE ? OR v.name LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            $types .= 'sss';
            array_push($params, $term, $term, $term);
        }
        if (!empty($filters['category'])) {
            $sql .= ' AND f.category_id = ?';
            $types .= 'i'; $params[] = (int) $filters['category'];
        }
        if (!empty($filters['vendor'])) {
            $sql .= ' AND f.vendor_id = ?';
            $types .= 'i'; $params[] = (int) $filters['vendor'];
        }
        $sort = $filters['sort'] ?? 'featured';
        $sql .= match ($sort) {
            'price_asc' => ' ORDER BY f.price ASC, f.name ASC',
            'price_desc' => ' ORDER BY f.price DESC, f.name ASC',
            'rating' => ' ORDER BY average_rating DESC, rating_count DESC, f.name ASC',
            'popular' => ' ORDER BY sold_count DESC, f.name ASC',
            default => ' ORDER BY f.is_featured DESC, f.name ASC',
        };
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = max(1, min(36, $limit));
        $params[] = max(0, $offset);
        return db_all($sql, $types, $params);
    }

    public static function countPublic(array $filters): int
    {
        $sql = "SELECT COUNT(*) AS count FROM foods f JOIN vendors v ON v.id=f.vendor_id JOIN inventory i ON i.food_id=f.id AND i.quantity>=1 WHERE v.status='ACTIVE' AND f.is_available=1";
        $types = ''; $params = [];
        if (!empty($filters['q'])) {
            $sql .= ' AND (f.name LIKE ? OR f.description LIKE ? OR v.name LIKE ?)';
            $term = '%' . $filters['q'] . '%'; $types .= 'sss'; array_push($params, $term, $term, $term);
        }
        if (!empty($filters['category'])) { $sql .= ' AND f.category_id=?'; $types .= 'i'; $params[] = (int) $filters['category']; }
        if (!empty($filters['vendor'])) { $sql .= ' AND f.vendor_id=?'; $types .= 'i'; $params[] = (int) $filters['vendor']; }
        $row = db_one($sql, $types, $params);
        return (int) ($row['count'] ?? 0);
    }
}
