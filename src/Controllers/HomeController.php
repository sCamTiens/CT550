<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\ProductRepository;
use App\Models\Customer\Repositories\CategoryRepository;
use App\Models\Customer\Repositories\PromotionRepository;

class HomeController extends Controller
{
    public function index(Request $req)
    {
        $categoryRepo = new CategoryRepository();
        $productRepo = new ProductRepository();
        $promotionRepo = new PromotionRepository();

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
        $page = max(1, (int) $req->input('page', 1));
        $perPage = 20;

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
        ];

        // Lấy sản phẩm với filters
        $products = $productRepo->getProductsWithFilters($filters, $page, $perPage);

        // Sử dụng view mới cho customer
        require_once __DIR__ . '/../views/customer/home/index.php';
    }
}
