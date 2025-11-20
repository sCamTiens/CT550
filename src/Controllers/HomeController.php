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
        
        // Lấy hình ảnh cho từng khuyến mãi
        foreach ($promotions as &$promo) {
            $promo['images'] = $promotionRepo->getPromotionImages($promo['id'], $promo['promo_type']);
        }
        
        // Lấy filter từ query string
        $categorySlug = $req->input('category');
        $page = max(1, (int) $req->input('page', 1));
        $perPage = 20;
        
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
        
        // Lấy sản phẩm
        if (!empty($categoryIds)) {
            // Lấy sản phẩm theo danh mục
            $products = $productRepo->getByCategories($categoryIds, $page, $perPage);
        } else {
            // Lấy tất cả sản phẩm (hiển thị ngẫu nhiên)
            $products = $productRepo->getRandomProducts($page, $perPage);
        }
        
        // Sử dụng view mới cho customer
        require_once __DIR__ . '/../views/customer/home/index.php';
    }
}
