<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DB;

class ProductController extends Controller
{
    /** GET /products */
    public function index(Request $req): mixed
    {
        $page = max(1, (int) $req->input('page', 1));
        $perPage = 12;
        $offset  = ($page - 1) * $perPage;

        $pdo = DB::pdo();

        $stmt = $pdo->prepare(
            "SELECT SQL_CALC_FOUND_ROWS id, name, slug, price, description
             FROM products
             WHERE is_active = 1
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        $total = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));

        return $this->view('products/index', compact('products', 'page', 'pages', 'total'));
    }

    /** GET /products/{slug} */
    public function show(string $slug): mixed
    {
        $pdo = DB::pdo();

        // cho phép truy theo id nếu {slug} là số
        if (ctype_digit($slug)) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->execute([':id' => (int)$slug]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = :slug AND is_active = 1 LIMIT 1");
            $stmt->execute([':slug' => $slug]);
        }

        $product = $stmt->fetch();
        if (!$product) {
            http_response_code(404);
            return $this->view('errors/404', ['message' => 'Sản phẩm không tồn tại']);
        }

        // lấy vài ảnh (nếu có bảng product_images)
        try {
            $imgs = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC, sort_order ASC");
            $imgs->execute([':pid' => (int)$product['id']]);
            $images = $imgs->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $images = [];
        }

        return $this->view('products/show', compact('product', 'images'));
    }
}
