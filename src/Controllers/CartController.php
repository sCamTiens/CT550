<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\DB;

class CartController extends Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['cart'] ??= []; // dạng [product_id => ['id','name','price','qty']]
    }

    /** POST /cart  (product_id, qty) */
    public function add(Request $req): mixed
    {
        $pid = (int) $req->input('product_id');
        $qty = max(1, (int) $req->input('qty', 1));

        if ($pid <= 0) {
            http_response_code(422);
            return $this->view('errors/validation', ['message' => 'Thiếu product_id']);
        }

        // lấy thông tin sản phẩm tối thiểu
        $stmt = DB::pdo()->prepare("SELECT id, name, price FROM products WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute([':id' => $pid]);
        $p = $stmt->fetch();

        if (!$p) {
            http_response_code(404);
            return $this->view('errors/404', ['message' => 'Không tìm thấy sản phẩm']);
        }

        // thêm/increase vào session cart
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$pid] = [
                'id'    => (int)$p['id'],
                'name'  => $p['name'],
                'price' => (float)$p['price'],
                'qty'   => $qty,
            ];
        }

        // nếu có lớp Response redirect thì dùng, không thì header()
        if (class_exists(Response::class) && method_exists(Response::class, 'redirect')) {
            return Response::redirect('/cart'); // nếu bạn có route GET /cart
        }
        header('Location: /cart');
        exit;
    }

    /** (tuỳ chọn) GET /cart để hiển thị giỏ */
    public function index(): mixed
    {
        $items = array_values($_SESSION['cart']);
        $subtotal = 0.0;
        foreach ($items as $it) $subtotal += $it['price'] * $it['qty'];

        return $this->view('cart/index', compact('items', 'subtotal'));
    }
}
