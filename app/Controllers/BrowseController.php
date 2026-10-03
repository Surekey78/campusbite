<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Models/Food.php';

final class BrowseController extends Controller
{
    public function home(): void
    {
        $featured = Food::search(['sort' => 'featured'], 4, 0);
        $popular = Food::search(['sort' => 'popular'], 4, 0);
        $topRated = Food::search(['sort' => 'rating'], 4, 0);
        $categories = db_all("SELECT c.*, (SELECT COUNT(*) FROM foods f JOIN vendors v ON v.id=f.vendor_id JOIN inventory i ON i.food_id=f.id AND i.quantity>=1 WHERE f.category_id=c.id AND f.is_available=1 AND v.status='ACTIVE') AS food_count FROM food_categories c WHERE c.is_active=1 AND c.vendor_id IS NULL ORDER BY c.sort_order, c.name LIMIT 8");
        $vendors = db_all("SELECT v.*, COALESCE(vs.accepting_orders,0) AS accepting_orders, (SELECT COUNT(*) FROM foods f JOIN inventory i ON i.food_id=f.id AND i.quantity>=1 WHERE f.vendor_id=v.id AND f.is_available=1) AS food_count FROM vendors v LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id WHERE v.status='ACTIVE' ORDER BY v.name LIMIT 4");
        $hero = db_one("SELECT * FROM cms_content WHERE content_key='home.hero.title' AND is_published=1");
        $heroNote = db_one("SELECT * FROM cms_content WHERE content_key='home.hero.note' AND is_published=1");
        $announcement = db_one("SELECT * FROM cms_content WHERE content_key='announcement.welcome' AND is_published=1");
        $this->view('public/home', compact('featured', 'popular', 'topRated', 'categories', 'vendors', 'hero', 'heroNote', 'announcement') + ['title' => 'Discover campus food']);
    }

    public function foods(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $query = mb_substr($query, 0, 80, 'UTF-8');
        $categoryId = max(0, (int) ($_GET['category'] ?? 0));
        $vendorId = max(0, (int) ($_GET['vendor'] ?? 0));
        $sort = (string) ($_GET['sort'] ?? 'featured');
        if (!in_array($sort, ['featured', 'popular', 'rating', 'price_asc', 'price_desc'], true)) $sort = 'featured';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = ['q' => $query, 'category' => $categoryId, 'vendor' => $vendorId, 'sort' => $sort];
        $total = Food::countPublic($filters);
        $perPage = 12;
        $foods = Food::search($filters, $perPage, ($page - 1) * $perPage);
        $categories = db_all("SELECT id, name FROM food_categories WHERE is_active=1 AND vendor_id IS NULL ORDER BY sort_order, name");
        $vendors = db_all("SELECT id, name FROM vendors WHERE status='ACTIVE' ORDER BY name");
        $this->view('public/foods', compact('foods', 'categories', 'vendors', 'query', 'categoryId', 'vendorId', 'sort', 'page', 'total', 'perPage') + ['title' => $query !== '' ? 'Search results' : 'All foods']);
    }

    public function food(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null, 'Food');
        $food = Food::findPublic($id);
        if (!$food) $this->pageNotFound();
        $reviews = db_all("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.food_id=? AND r.status='PUBLISHED' ORDER BY r.created_at DESC LIMIT 12", 'i', [$id]);
        $this->view('public/food-detail', compact('food', 'reviews') + ['title' => $food['name']]);
    }

    public function vendors(): void
    {
        $vendors = db_all("SELECT v.*, COALESCE(vs.accepting_orders,0) AS accepting_orders, (SELECT COUNT(*) FROM foods f JOIN inventory i ON i.food_id=f.id AND i.quantity>=1 WHERE f.vendor_id=v.id AND f.is_available=1) AS food_count,
            (SELECT ROUND(AVG(r.rating),1) FROM reviews r JOIN foods f2 ON f2.id=r.food_id WHERE f2.vendor_id=v.id AND r.status='PUBLISHED') AS average_rating,
            (SELECT COUNT(*) FROM reviews r JOIN foods f2 ON f2.id=r.food_id WHERE f2.vendor_id=v.id AND r.status='PUBLISHED') AS rating_count
            FROM vendors v LEFT JOIN vendor_settings vs ON vs.vendor_id=v.id WHERE v.status='ACTIVE' ORDER BY v.name");
        $this->view('public/vendors', compact('vendors') + ['title' => 'Campus vendors']);
    }

    public function vendor(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);
        if ($slug !== '') {
            $vendor = db_one("SELECT * FROM vendors WHERE slug=? AND status='ACTIVE'", 's', [$slug]);
        } elseif ($id > 0) {
            $vendor = db_one("SELECT * FROM vendors WHERE id=? AND status='ACTIVE'", 'i', [$id]);
        } else {
            $vendor = null;
        }
        if (!$vendor) $this->pageNotFound();
        $foods = Food::search(['vendor' => (int) $vendor['id'], 'sort' => 'featured'], 36, 0);
        $setting = db_one('SELECT * FROM vendor_settings WHERE vendor_id=?', 'i', [(int) $vendor['id']]);
        $this->view('public/vendor-detail', compact('vendor', 'foods', 'setting') + ['title' => $vendor['name']]);
    }

    public function content(): void
    {
        $rawSlug = $_GET['page'] ?? 'about';
        if (!is_scalar($rawSlug)) $this->pageNotFound();
        $slug = strtolower(trim((string) $rawSlug));
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) $this->pageNotFound();
        $content = db_one("SELECT * FROM cms_content WHERE content_key=? AND section='page' AND is_published=1", 's', ['page.' . $slug]);
        if (!$content) $this->pageNotFound();
        $this->view('public/content-page', compact('content') + ['title' => $content['title']]);
    }
}
