<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer\Repositories\CartRepository;
use App\Models\Customer\Repositories\PromotionRepository;
use App\Core\DB;

class CartController extends Controller
{
    private CartRepository $cartRepo;
    private PromotionRepository $promotionRepo;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
        $this->promotionRepo = new PromotionRepository();
    }

    /** GET /cart - Hiển thị giỏ hàng */
    public function index(Request $req): mixed
    {
        $customerId = null;

        // Try to get customer_id from JWT middleware first (if middleware was applied)
        if (isset($req->user) && is_array($req->user) && isset($req->user['id'])) {
            $customerId = $req->user['id'];
        }

        // Fallback to PHP session if JWT not available
        if (!$customerId && !empty($_SESSION['customer']['id'])) {
            $customerId = $_SESSION['customer']['id'];
        }

        if (!$customerId) {
            // Redirect to login if not authenticated
            header('Location: /login');
            exit;
        }

        // Load cart từ database
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartItems = $this->cartRepo->getCartItems($cart);
        $pdo = DB::pdo();

        // Lấy khuyến mãi áp dụng cho từng sản phẩm
        $promotions = $this->getApplicablePromotions($cartItems, $pdo);

        // Tính tổng giá gốc
        $originalTotal = $this->cartRepo->calculateTotal($cartItems);

        // Tính tổng sau khuyến mãi
        $totalAfterPromo = $this->calculateTotalAfterPromo($cartItems, $promotions);

        // Tổng giảm giá
        $totalDiscount = $originalTotal - $totalAfterPromo;
        if ($totalDiscount < 0) $totalDiscount = 0;

        return $this->view('customer/cart/cart', [
            'cartItems' => $cartItems,
            'total' => $totalAfterPromo,
            'originalTotal' => $originalTotal,
            'totalDiscount' => $totalDiscount,
            'promotions' => $promotions
        ]);
    }

    /**
     * Lấy các khuyến mãi áp dụng cho sản phẩm trong giỏ
     * (Copy từ CheckoutController)
     */
    private function getApplicablePromotions(array $cartItems, \PDO $pdo): array
    {
        $productIds = array_column($cartItems, 'id');
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $now = date('Y-m-d H:i:s');

        // Lấy tất cả khuyến mãi từ các bảng khác nhau
        // 1. Discount từ promotion_products
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, pp.product_id
            FROM promotions p
            INNER JOIN promotion_products pp ON p.id = pp.promotion_id
            WHERE pp.product_id IN ($placeholders)
                AND p.is_active = 1
                AND p.starts_at <= ?
                AND p.ends_at >= ?
                AND p.promo_type = 'discount'
            ORDER BY p.priority DESC, p.id DESC
        ");
        $params = array_merge($productIds, [$now, $now]);
        $stmt->execute($params);
        $promos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Bundle từ promotion_bundle_rules
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, pbr.product_id, pbr.required_qty, pbr.bundle_price
            FROM promotions p
            INNER JOIN promotion_bundle_rules pbr ON p.id = pbr.promotion_id
            WHERE pbr.product_id IN ($placeholders)
                AND p.is_active = 1
                AND p.starts_at <= ?
                AND p.ends_at >= ?
                AND p.promo_type = 'bundle'
            ORDER BY p.priority DESC, p.id DESC
        ");
        $stmt->execute($params);
        $bundlePromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $promos = array_merge($promos, $bundlePromos);

        // 3. Gift từ promotion_gift_rules
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, pgr.trigger_product_id as product_id,
                   pgr.required_qty, pgr.gift_qty, pgr.gift_product_id,
                   gp.name as gift_name
            FROM promotions p
            INNER JOIN promotion_gift_rules pgr ON p.id = pgr.promotion_id
            LEFT JOIN products gp ON pgr.gift_product_id = gp.id
            WHERE pgr.trigger_product_id IN ($placeholders)
                AND p.is_active = 1
                AND p.starts_at <= ?
                AND p.ends_at >= ?
                AND p.promo_type = 'gift'
            ORDER BY p.priority DESC, p.id DESC
        ");
        $stmt->execute($params);
        $giftPromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Thêm gift_image_url cho mỗi gift
        foreach ($giftPromos as &$gift) {
            if (!empty($gift['gift_product_id'])) {
                // Query from product_images table
                $stmtImg = $pdo->prepare("
                    SELECT image_url 
                    FROM product_images 
                    WHERE product_id = ? AND image_type = 'main' 
                    ORDER BY is_primary DESC, sort_order ASC
                    LIMIT 1
                ");
                $stmtImg->execute([$gift['gift_product_id']]);
                $imageUrl = $stmtImg->fetchColumn();

                $gift['gift_image_url'] = $imageUrl ?: '/assets/images/products/default.png';
            }
        }

        $promos = array_merge($promos, $giftPromos);

        // 4. Combo từ promotion_combo_items
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, pci.product_id
            FROM promotions p
            INNER JOIN promotion_combo_items pci ON p.id = pci.promotion_id
            WHERE pci.product_id IN ($placeholders)
                AND p.is_active = 1
                AND p.starts_at <= ?
                AND p.ends_at >= ?
                AND p.promo_type = 'combo'
            ORDER BY p.priority DESC, p.id DESC
        ");
        $stmt->execute($params);
        $comboPromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Lấy thêm combo_items cho mỗi combo
        foreach ($comboPromos as &$combo) {
            $stmt = $pdo->prepare("
                SELECT pci.product_id, pci.required_qty, p.name, p.sale_price
                FROM promotion_combo_items pci
                INNER JOIN products p ON pci.product_id = p.id
                WHERE pci.promotion_id = ?
            ");
            $stmt->execute([$combo['id']]);
            $combo['combo_items'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $promos = array_merge($promos, $comboPromos);

        // Nhóm khuyến mãi theo product_id
        $result = [];
        foreach ($promos as $promo) {
            $productId = $promo['product_id'];
            if (!isset($result[$productId])) {
                $result[$productId] = [];
            }
            $result[$productId][] = $promo;
        }

        return $result;
    }

    /**
     * Tính tổng tiền sau khi áp dụng khuyến mãi
     * (Copy từ CheckoutController)
     */
    private function calculateTotalAfterPromo(array $cartItems, array $promotions): float
    {
        $subtotalAfterPromo = 0;
        $processedCombos = [];

        foreach ($cartItems as $item) {
            $productId = $item['id'];
            $itemPromotions = $promotions[$productId] ?? [];
            $itemTotal = $item['price'] * $item['quantity'];

            foreach ($itemPromotions as $promo) {
                if ($promo['promo_type'] === 'discount') {
                    $itemPrice = $item['price'];
                    if ($promo['discount_type'] === 'percentage') {
                        $itemPrice = $item['price'] * (1 - $promo['discount_value'] / 100);
                    } else {
                        $itemPrice = $item['price'] - $promo['discount_value'];
                    }
                    $itemTotal = $itemPrice * $item['quantity'];
                    break;
                }

                if ($promo['promo_type'] === 'bundle' && $item['quantity'] >= ($promo['required_qty'] ?? 1)) {
                    $requiredQty = $promo['required_qty'] ?? 1;
                    $bundlePrice = $promo['bundle_price'] ?? $item['price'];
                    $bundleSets = floor($item['quantity'] / $requiredQty);
                    $itemTotal = $bundlePrice * $bundleSets;
                    $remainingQty = $item['quantity'] % $requiredQty;
                    if ($remainingQty > 0) {
                        $itemTotal += $item['price'] * $remainingQty;
                    }
                    break;
                }

                if ($promo['promo_type'] === 'combo' && !empty($promo['combo_price'])) {
                    $comboId = $promo['id'];
                    if (!isset($processedCombos[$comboId])) {
                        $comboItems = $promo['combo_items'] ?? [];
                        $canApplyCombo = true;

                        foreach ($comboItems as $comboItem) {
                            $found = false;
                            foreach ($cartItems as $cartItem) {
                                if (
                                    $cartItem['id'] == $comboItem['product_id'] &&
                                    $cartItem['quantity'] >= $comboItem['required_qty']
                                ) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $canApplyCombo = false;
                                break;
                            }
                        }

                        if ($canApplyCombo) {
                            $processedCombos[$comboId] = [
                                'applied' => true,
                                'combo_price' => $promo['combo_price']
                            ];
                            $itemTotal = 0;
                        }
                    } else {
                        $itemTotal = 0;
                    }
                    break;
                }
            }

            $subtotalAfterPromo += $itemTotal;
        }

        foreach ($processedCombos as $comboData) {
            if ($comboData['applied']) {
                $subtotalAfterPromo += $comboData['combo_price'];
            }
        }

        return $subtotalAfterPromo;
    }

    /** POST /cart/add (JSON) - Thêm sản phẩm vào giỏ */
    public function add(Request $req): mixed
    {
        header('Content-Type: application/json');

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

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

        // Get product info
        $product = $this->cartRepo->getProductInfo($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
            exit;
        }

        // Add to cart database (JWT authenticated)
        $price = (float)$product['price'];
        $this->cartRepo->addItemToDB($customerId, $productId, $quantity, $price);

        // Reload cart to get updated count
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartCount = $this->cartRepo->countItems($cart);

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

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Remove if quantity is 0
        if ($quantity <= 0) {
            $this->cartRepo->removeItemDB($customerId, $productId);
        } else {
            // Validate stock
            $validation = $this->cartRepo->validateQuantity($productId, $quantity);

            if (!$validation['valid']) {
                // If not enough stock, set to max available
                $quantity = $validation['quantity'];
                if ($quantity <= 0) {
                    $this->cartRepo->removeItemDB($customerId, $productId);

                    echo json_encode([
                        'success' => false,
                        'message' => $validation['message']
                    ]);
                    exit;
                }
            }

            // Update database
            $this->cartRepo->updateItemDB($customerId, $productId, $quantity);
        }

        // Recalculate totals
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartItems = $this->cartRepo->getCartItems($cart);
        $total = $this->cartRepo->calculateTotal($cartItems);
        $cartCount = $this->cartRepo->countItems($cart);

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

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Remove from database
        $this->cartRepo->removeItemDB($customerId, $productId);

        // Reload cart to get updated count
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartCount = $this->cartRepo->countItems($cart);

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

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        // Clear cart in database
        $this->cartRepo->clearCartDB($customerId);

        echo json_encode(['success' => true, 'cart_count' => 0]);
        exit;
    }

    /** POST /api/cart/store-selected - Store selected items for checkout */
    public function storeSelected(Request $request): mixed
    {
        header('Content-Type: application/json');

        // Lấy customer_id từ JWT middleware
        $customerId = $request->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        try {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $selectedIds = $data['selected_ids'] ?? [];

            // Store in database or temporary table (can be implemented later)
            // For now, return success
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /** POST /api/cart/add-combo - Thêm combo vào giỏ */
    public function addCombo(Request $req): mixed
    {
        header('Content-Type: application/json');

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

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

            // Add to database
            $product = $this->cartRepo->getProductInfo($productId);
            if (!$product) continue;

            $price = (float)$product['price'];
            $this->cartRepo->addItemToDB($customerId, $productId, $quantity, $price);
        }

        // Reload cart to get updated count
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartCount = $this->cartRepo->countItems($cart);

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

        // Lấy customer_id từ JWT middleware
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

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

        // Add to cart database with bundle price
        $product = $this->cartRepo->getProductInfo($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
            exit;
        }

        $pricePerUnit = $bundlePrice / $quantity;
        $this->cartRepo->addItemToDB($customerId, $productId, $quantity, $pricePerUnit);

        // Reload cart to get updated count
        $cart = $this->cartRepo->loadCartFromDB($customerId);
        $cartCount = $this->cartRepo->countItems($cart);

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount
        ]);
        exit;
    }
}
