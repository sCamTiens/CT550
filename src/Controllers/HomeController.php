<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\ProductRepository;
use App\Models\Customer\Repositories\CategoryRepository;
use App\Models\Customer\Repositories\PromotionRepository;
use App\Services\RecommendationService;

class HomeController extends Controller
{
    public function index(Request $req)
    {
        // ❌ KHÔNG validate token trong HomeController nữa!
        // Lý do: Cookies có thể chưa kịp đến trong request đầu tiên sau login
        // Middleware sẽ handle validation cho các protected routes

        // Chỉ log để debug
        if (!empty($_SESSION['customer'])) {
            $hasSessionToken = !empty($_SESSION['customer']['access_token']);
            $hasCookieToken = !empty($_COOKIE['jwt_token']);

            error_log('[HomeController] Customer session exists');
            error_log('[HomeController] Has session token: ' . ($hasSessionToken ? 'YES' : 'NO'));
            error_log('[HomeController] Has cookie token: ' . ($hasCookieToken ? 'YES' : 'NO'));
        }


        $categoryRepo = new CategoryRepository();
        $productRepo = new ProductRepository();
        $promotionRepo = new PromotionRepository();

        // Khởi tạo RecommendationService
        $db = \App\Core\DB::getConnection();
        $recommendationService = new RecommendationService($db);

        // Lấy cấu trúc danh mục cha-con
        $categories = $categoryRepo->getCategoriesTree();

        // Lấy khuyến mãi đang active
        $promotions = $promotionRepo->getActivePromotions(6);

        // DEBUG: Trước khi lấy images
        if (isset($_GET['debug'])) {
            echo "<pre style='background: #ffcccc; padding: 20px; margin: 20px;'>";
            echo "=== BEFORE getPromotionImages ===\n";
            echo "Total: " . count($promotions) . "\n";
            foreach ($promotions as $idx => $p) {
                echo "[$idx] ID: {$p['id']}, Type: {$p['promo_type']}, Name: {$p['name']}\n";
            }
            echo "</pre>";
        }

        // Lấy hình ảnh cho từng khuyến mãi
        foreach ($promotions as &$promo) {
            $promo['images'] = $promotionRepo->getPromotionImages($promo['id'], $promo['promo_type']);
        }
        unset($promo); // IMPORTANT: Unset reference để tránh side effect
        // DEBUG: Sau khi lấy images
        if (isset($_GET['debug'])) {
            echo "<pre style='background: #ccffcc; padding: 20px; margin: 20px;'>";
            echo "=== AFTER getPromotionImages ===\n";
            echo "Total: " . count($promotions) . "\n";
            foreach ($promotions as $idx => $p) {
                echo "[$idx] ID: {$p['id']}, Type: {$p['promo_type']}, Name: {$p['name']}, Images: " . count($p['images']) . "\n";
            }
            echo "</pre>";
        }
        // DEBUG: Kiểm tra promotions
        if (isset($_GET['debug'])) {
            echo "<pre style='background: #f0f0f0; padding: 20px; margin: 20px;'>";
            echo "Total promotions: " . count($promotions) . "\n\n";
            foreach ($promotions as $p) {
                echo "ID: {$p['id']}, Name: {$p['name']}, Type: {$p['promo_type']}, Images: " . count($p['images']) . "\n";
                if (!empty($p['images'])) {
                    foreach ($p['images'] as $img) {
                        echo "  - $img\n";
                    }
                } else {
                    echo "  (No images)\n";
                }
                echo "\n";
            }
            echo "</pre>";
        }

        // Lấy filter từ query string
        $categorySlug = $req->input('category');
        $query = $req->input('q'); // Search query
        $page = max(1, (int) $req->input('page', 1));
        $perPage = 20;

        // Initialize hasActiveFilters (will be set properly later)
        $hasActiveFilters = false;

        // Lấy danh sách brands cho filter
        $brandRepo = new \App\Models\Repositories\BrandRepository();
        $allBrands = $brandRepo->all();

        // Nếu có filter theo danh mục
        $selectedCategory = null;
        $categoryIds = [];

        if ($categorySlug) {
            $selectedCategory = $categoryRepo->findBySlug($categorySlug);
            if ($selectedCategory) {
                // Lấy tất cả ID con của danh mục này (để hiển thị cả sản phẩm trong danh mục con)
                $categoryIds = $categoryRepo->getAllChildIds($selectedCategory['id']);
            }
        }

        // Lấy filters từ request
        $filters = [
            'category_ids' => $categoryIds,
            'brand_ids' => $req->input('brands') ? explode(',', $req->input('brands')) : [],
            'min_price' => $req->input('min_price') ? (float)$req->input('min_price') : null,
            'max_price' => $req->input('max_price') ? (float)$req->input('max_price') : null,
            'sort' => $req->input('sort', 'newest'), // newest, price_asc, price_desc, best_selling
            'search' => $query, // Add search query to filters
        ];

        // Kiểm tra xem có filter/search đang được áp dụng không
        $hasActiveFilters = !empty($categorySlug) ||
            !empty($query) ||
            !empty($filters['brand_ids']) ||
            $filters['min_price'] !== null ||
            $filters['max_price'] !== null ||
            ($filters['sort'] !== 'newest');

        // Lấy sản phẩm với filters
        $products = $productRepo->getProductsWithFilters($filters, $page, $perPage);

        // Lấy user ID (dùng cho cả search history và recommendations)
        $userId = $_SESSION['customer']['id'] ?? null;

        // Lưu lịch sử tìm kiếm nếu có search query
        if (!empty($query)) {
            error_log("=== SEARCH HISTORY DEBUG ===");
            error_log("Query: " . $query);
            error_log("User ID: " . ($userId ?? 'NULL'));
            error_log("Results count: " . ($products['total'] ?? 0));

            try {
                $recommendationService->saveSearchHistory(
                    $userId,
                    $query,
                    $products['total'] ?? 0,
                    'product'
                );
                error_log("✓ Search history saved successfully!");
            } catch (\Exception $e) {
                // Log error nhưng không làm crash app
                error_log("Lưu lịch sử tìm kiếm thất bại: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
        }

        // Lấy sản phẩm gợi ý cá nhân hóa (chỉ khi không có filter)
        $recommendedProducts = [];
        if (!$hasActiveFilters) {
            $recommendedProducts = $recommendationService->getPersonalizedRecommendations($userId, 12);
        }

        // Sử dụng view mới cho customer
        require_once __DIR__ . '/../views/customer/home/index.php';
    }
}
