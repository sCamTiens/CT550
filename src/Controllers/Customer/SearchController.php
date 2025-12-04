<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DB;
use App\Models\Customer\Repositories\ProductRepository;
use App\Models\Customer\Repositories\PromotionRepository;

class SearchController extends Controller
{
    /** GET /search?q=... */
    public function index(Request $req): mixed
    {
        $query = trim($req->input('q', ''));
        $page = max(1, (int) $req->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $pdo = DB::pdo();
        $products = [];
        $total = 0;
        $pages = 1;

        if (!empty($query)) {
            // Tìm kiếm sản phẩm theo tên
            $searchTerm = '%' . $query . '%';

            $stmt = $pdo->prepare(
                "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.sale_price, p.description, p.brand_id, p.category_id,
                        s.qty as stock_qty
                 FROM products p
                 LEFT JOIN stocks s ON p.id = s.product_id
                 WHERE p.is_active = 1 
                 AND (p.name LIKE :search OR p.description LIKE :search)
                 ORDER BY p.name ASC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':search', $searchTerm, \PDO::PARAM_STR);
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();

            // Thêm đường dẫn ảnh cho từng sản phẩm
            $repo = new ProductRepository();
            foreach ($products as &$p) {
                $p['image_url'] = $repo->getProductImage($p['id']);
                $p['price'] = $p['sale_price']; // Map sale_price to price for view compatibility
            }

            $total = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
            $pages = max(1, (int) ceil($total / $perPage));
        } else {
            // Khi không có query, hiển thị tất cả sản phẩm
            $stmt = $pdo->prepare(
                "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.sale_price, p.description, p.brand_id, p.category_id,
                        s.qty as stock_qty
                 FROM products p
                 LEFT JOIN stocks s ON p.id = s.product_id
                 WHERE p.is_active = 1
                 ORDER BY p.created_at DESC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();

            // Thêm đường dẫn ảnh cho từng sản phẩm
            $repo = new ProductRepository();
            foreach ($products as &$p) {
                $p['image_url'] = $repo->getProductImage($p['id']);
                $p['price'] = $p['sale_price']; // Map sale_price to price for view compatibility
            }

            $total = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
            $pages = max(1, (int) ceil($total / $perPage));
        }

        // Load categories cho sidebar
        $categoriesStmt = $pdo->query("
            SELECT id, name, slug, parent_id 
            FROM categories 
            WHERE is_active = 1 AND slug != 'qua-tang'
            ORDER BY parent_id ASC, name ASC
        ");
        $allCategories = $categoriesStmt->fetchAll();

        // Build category tree
        $categories = [];
        $categoryMap = [];

        foreach ($allCategories as $cat) {
            $categoryMap[$cat['id']] = $cat;
            $categoryMap[$cat['id']]['children'] = [];
        }

        foreach ($categoryMap as $id => $cat) {
            if ($cat['parent_id'] === null) {
                $categories[] = &$categoryMap[$id];
            } else if (isset($categoryMap[$cat['parent_id']])) {
                $categoryMap[$cat['parent_id']]['children'][] = &$categoryMap[$id];
            }
        }

        // Load all brands
        $brandRepo = new \App\Models\Repositories\BrandRepository();
        $allBrands = $brandRepo->all();

        $productsData = [
            'data' => $products,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ];

        $selectedCategory = null;
        $categorySlug = null;

        // Load promotions
        $promotionRepo = new PromotionRepository();
        $promotions = $promotionRepo->getActivePromotions(6);

        // Lấy hình ảnh cho từng khuyến mãi
        foreach ($promotions as &$promo) {
            $promo['images'] = $promotionRepo->getPromotionImages($promo['id'], $promo['promo_type']);
        }
        unset($promo); // IMPORTANT: Unset reference để tránh side effect

        $pageTitle = !empty($query)
            ? 'Tìm kiếm: ' . htmlspecialchars($query) . ' - MiniGo'
            : 'Tất cả sản phẩm - MiniGo';

        return $this->view('customer/home/index', [
            'products' => $productsData,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'categorySlug' => $categorySlug,
            'allBrands' => $allBrands,
            'promotions' => $promotions,
            'pageTitle' => $pageTitle,
            'query' => $query
        ]);
    }
}
