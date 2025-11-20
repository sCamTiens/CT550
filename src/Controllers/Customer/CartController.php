<?php
namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer\Repositories\CartRepository;

class CartController extends Controller
{
    private CartRepository $cartRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $this->cartRepo = new CartRepository();
        
        // Nếu user đã đăng nhập, load giỏ hàng từ database
        if (!empty($_SESSION['customer']['id'])) {
            $userId = $_SESSION['customer']['id'];
            
            // Nếu session cart rỗng, load từ DB
            if (empty($_SESSION['cart'])) {
                $_SESSION['cart'] = $this->cartRepo->loadCartFromDB($userId);
            } else {
                // Nếu có cart trong session, sync vào DB (trường hợp vừa đăng nhập)
                $this->cartRepo->saveCartToDB($userId, $_SESSION['cart']);
            }
        } else {
            // Guest user, chỉ dùng session
            $_SESSION['cart'] ??= [];
        }
    }

    /** GET /cart - Hiển thị giỏ hàng */
    public function index(): mixed
    {
        $cartItems = $this->cartRepo->getCartItems($_SESSION['cart']);
        $total = $this->cartRepo->calculateTotal($cartItems);
        
        return $this->view('customer/cart/cart', compact('cartItems', 'total'));
    }

    /** POST /cart/add (JSON) - Thêm sản phẩm vào giỏ */
    public function add(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = max(1, (int)($input['quantity'] ?? 1));
        $promotionId = (int)($input['promotion_id'] ?? 0); // Optional promotion ID

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
            exit;
        }

        // Validate stock
        $validation = $this->cartRepo->validateQuantity($productId, $quantity);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        // Add to cart
        if (isset($_SESSION['cart'][$productId])) {
            $currentQty = is_array($_SESSION['cart'][$productId]) 
                ? $_SESSION['cart'][$productId]['qty'] 
                : $_SESSION['cart'][$productId];
            
            $newQty = $currentQty + $quantity;
            
            // Re-validate with new quantity
            $revalidation = $this->cartRepo->validateQuantity($productId, $newQty);
            if (!$revalidation['valid']) {
                $newQty = $revalidation['quantity'];
            }
            
            $_SESSION['cart'][$productId]['qty'] = $newQty;
        } else {
            // Get product info
            $product = $this->cartRepo->getProductInfo($productId);
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
                exit;
            }

            $_SESSION['cart'][$productId] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'qty' => $quantity,
            ];

            // Add promotion_id if provided
            if ($promotionId > 0) {
                $_SESSION['cart'][$productId]['promotion_id'] = $promotionId;
            }
        }

        // Lưu vào database nếu user đã đăng nhập
        if (!empty($_SESSION['customer']['id'])) {
            $userId = $_SESSION['customer']['id'];
            $price = $_SESSION['cart'][$productId]['price'];
            $qty = $_SESSION['cart'][$productId]['qty'];
            $this->cartRepo->addItemToDB($userId, $productId, $quantity, $price);
        }

        $cartCount = $this->cartRepo->countItems($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount
        ]);
        exit;
    }

    /** POST /cart/update (JSON) - Cập nhật số lượng */
    public function update(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Remove if quantity is 0
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            
            // Xóa khỏi database nếu user đã đăng nhập
            if (!empty($_SESSION['customer']['id'])) {
                $this->cartRepo->removeItemDB($_SESSION['customer']['id'], $productId);
            }
        } else {
            // Validate stock
            $validation = $this->cartRepo->validateQuantity($productId, $quantity);
            
            if (!$validation['valid']) {
                // If not enough stock, set to max available
                $quantity = $validation['quantity'];
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$productId]);
                    
                    // Xóa khỏi database
                    if (!empty($_SESSION['customer']['id'])) {
                        $this->cartRepo->removeItemDB($_SESSION['customer']['id'], $productId);
                    }
                    
                    echo json_encode([
                        'success' => false, 
                        'message' => $validation['message']
                    ]);
                    exit;
                }
            }

            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['qty'] = $quantity;
                
                // Cập nhật database nếu user đã đăng nhập
                if (!empty($_SESSION['customer']['id'])) {
                    $this->cartRepo->updateItemDB($_SESSION['customer']['id'], $productId, $quantity);
                }
            }
        }

        // Recalculate totals
        $cartItems = $this->cartRepo->getCartItems($_SESSION['cart']);
        $total = $this->cartRepo->calculateTotal($cartItems);
        $cartCount = $this->cartRepo->countItems($_SESSION['cart']);
        
        // Find updated item
        $updatedItem = null;
        foreach ($cartItems as $item) {
            if ($item['id'] == $productId) {
                $updatedItem = $item;
                break;
            }
        }

        echo json_encode([
            'success' => true,
            'cart_count' => $cartCount,
            'quantity' => $quantity,
            'item' => $updatedItem,
            'total' => $total
        ]);
        exit;
    }

    /** POST /cart/remove (JSON) - Xóa sản phẩm */
    public function remove(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            
            // Xóa khỏi database nếu user đã đăng nhập
            if (!empty($_SESSION['customer']['id'])) {
                $this->cartRepo->removeItemDB($_SESSION['customer']['id'], $productId);
            }
        }

        $cartCount = $this->cartRepo->countItems($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'cart_count' => $cartCount
        ]);
        exit;
    }

    /** POST /cart/clear (JSON) - Xóa toàn bộ giỏ */
    public function clear(Request $req): mixed
    {
        header('Content-Type: application/json');
        $_SESSION['cart'] = [];
        
        // Xóa khỏi database nếu user đã đăng nhập
        if (!empty($_SESSION['customer']['id'])) {
            $this->cartRepo->clearCartDB($_SESSION['customer']['id']);
        }
        
        echo json_encode(['success' => true, 'cart_count' => 0]);
        exit;
    }

    /** POST /api/cart/add-combo - Thêm combo vào giỏ */
    public function addCombo(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $promotionId = (int)($input['promotion_id'] ?? 0);
        $items = $input['items'] ?? [];

        if ($promotionId <= 0 || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Validate và thêm từng sản phẩm trong combo
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);

            if ($productId <= 0) continue;

            // Validate stock
            $validation = $this->cartRepo->validateQuantity($productId, $quantity);
            if (!$validation['valid']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Sản phẩm không đủ hàng: ' . $validation['message']
                ]);
                exit;
            }

            // Add to cart
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['qty'] += $quantity;
            } else {
                $product = $this->cartRepo->getProductInfo($productId);
                if (!$product) continue;

                $_SESSION['cart'][$productId] = [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'qty' => $quantity,
                    'promotion_id' => $promotionId
                ];
            }

            // Lưu vào database
            if (!empty($_SESSION['customer']['id'])) {
                $price = $_SESSION['cart'][$productId]['price'];
                $qty = $_SESSION['cart'][$productId]['qty'];
                $this->cartRepo->addItemToDB($_SESSION['customer']['id'], $productId, $quantity, $price);
            }
        }

        $cartCount = $this->cartRepo->countItems($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm combo vào giỏ hàng',
            'cart_count' => $cartCount
        ]);
        exit;
    }

    /** POST /api/cart/add-bundle - Thêm bundle vào giỏ */
    public function addBundle(Request $req): mixed
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $promotionId = (int)($input['promotion_id'] ?? 0);
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);
        $bundlePrice = (float)($input['bundle_price'] ?? 0);

        if ($productId <= 0 || $bundlePrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Validate stock
        $validation = $this->cartRepo->validateQuantity($productId, $quantity);
        if (!$validation['valid']) {
            echo json_encode([
                'success' => false, 
                'message' => $validation['message']
            ]);
            exit;
        }

        // Add to cart với giá bundle
        $product = $this->cartRepo->getProductInfo($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
            exit;
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['qty'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => $bundlePrice / $quantity, // Giá đã được giảm
                'qty' => $quantity,
                'promotion_id' => $promotionId,
                'bundle_price' => $bundlePrice
            ];
        }

        // Lưu vào database
        if (!empty($_SESSION['customer']['id'])) {
            $price = $_SESSION['cart'][$productId]['price'];
            $qty = $_SESSION['cart'][$productId]['qty'];
            $this->cartRepo->addItemToDB($_SESSION['customer']['id'], $productId, $quantity, $price);
        }

        $cartCount = $this->cartRepo->countItems($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount
        ]);
        exit;
    }
}
