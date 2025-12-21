<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DB;
use App\Models\Customer\Repositories\CartRepository;
use App\Models\Customer\Repositories\AddressRepository;
use App\Models\Customer\Repositories\PromotionRepository;
use App\Models\Customer\Repositories\ProductRepository;

class CheckoutController extends Controller
{
    private CartRepository $cartRepo;
    private AddressRepository $addressRepo;
    private PromotionRepository $promotionRepo;
    private ProductRepository $productRepo;
    private \App\Services\DeliveryDistanceService $deliveryService;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
        $this->addressRepo = new AddressRepository();
        $this->promotionRepo = new PromotionRepository();
        $this->productRepo = new ProductRepository();
        $this->deliveryService = new \App\Services\DeliveryDistanceService();
    }

    /**
     * Display checkout page
     */
    public function index(Request $request): mixed
    {
        $customerId = null;

        // Try JWT first (if middleware was applied)
        if (isset($request->user) && is_array($request->user) && isset($request->user['id'])) {
            $customerId = $request->user['id'];
        }

        // Fallback to session for initial page load
        if (!$customerId && !empty($_SESSION['customer']['id'])) {
            $customerId = $_SESSION['customer']['id'];
        }

        if (!$customerId) {
            header('Location: /login');
            exit;
        }

        $pdo = DB::pdo();

        // Kiểm tra nếu có ?product_id=X (mua ngay 1 sản phẩm)
        $productId = $_GET['product_id'] ?? null;
        $selectedIds = []; // Initialize để tránh undefined variable warning

        if ($productId) {
            error_log("=== CHECKOUT BUY NOW === Product ID: $productId");

            // Mua ngay: Lấy thông tin sản phẩm từ DB
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.slug, p.sale_price AS price, 
                       COALESCE(s.qty, 0) AS stock_qty
                FROM products p
                LEFT JOIN stocks s ON p.id = s.product_id
                WHERE p.id = ? AND p.is_active = 1
            ");
            $stmt->execute([(int)$productId]);
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$product) {
                error_log("=== PRODUCT NOT FOUND === ID: $productId");
                $_SESSION['error'] = 'Sản phẩm không tồn tại';
                header('Location: /');
                exit;
            }

            error_log("=== PRODUCT FOUND === " . json_encode($product));

            // Tạo cart item với số lượng 1
            $product['image_url'] = $this->productRepo->getProductImage($product['id']);
            $product['quantity'] = 1;
            $product['subtotal'] = $product['price'];

            error_log("=== PRODUCT IMAGE === " . ($product['image_url'] ?? 'NO IMAGE'));

            $cartItems = [$product];
            $subtotal = $product['price'];

            error_log("=== BUY NOW CART ITEMS === Count: " . count($cartItems));
            error_log("=== BUY NOW SUBTOTAL === " . $subtotal);
            error_log("=== BUY NOW PRODUCT ===" . json_encode([
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['quantity'],
                'image_url' => $product['image_url']
            ]));
        } else {
            // Checkout từ giỏ hàng - Load từ database thay vì session
            // 1) Nếu có ?items=1,2,3 thì dùng nó (ưu tiên)
            $itemsParam = $_GET['items'] ?? null;

            if (!empty($itemsParam)) {
                $selectedIds = array_filter(array_map('intval', explode(',', $itemsParam)));
            }

            // Load cart từ database
            $cart = $this->cartRepo->loadCartFromDB($customerId);
            $allCartItems = $this->cartRepo->getCartItems($cart);

            // Nếu không có selection cụ thể -> mặc định checkout toàn bộ giỏ hàng
            if (empty($selectedIds)) {
                $cartItems = $allCartItems;
            } else {
                // Lọc các item trong giỏ dựa trên selectedIds
                $selectedStrIds = array_map('strval', array_map('intval', $selectedIds));
                $cartItems = array_values(array_filter($allCartItems, function ($item) use ($selectedStrIds) {
                    return in_array((string) $item['id'], $selectedStrIds, true);
                }));
            }

            // Nếu vẫn không có items để checkout -> redirect về cart với thông báo
            if (empty($cartItems)) {
                $_SESSION['error'] = 'Vui lòng chọn sản phẩm để thanh toán';
                header('Location: /cart');
                exit;
            }

            // Tính subtotal
            $subtotal = array_sum(array_map(fn($it) => $it['subtotal'] ?? 0, $cartItems));

            // DEBUG: Log checkout page load
            error_log("[CheckoutController->index] Selected IDs from URL: " . ($itemsParam ?? 'ALL'));
            error_log("[CheckoutController->index] Parsed selectedIds: " . json_encode($selectedIds));
            error_log("[CheckoutController->index] All cart items count: " . count($allCartItems));
            error_log("[CheckoutController->index] Filtered cart items count: " . count($cartItems));
            error_log("[CheckoutController->index] Cart items IDs: " . json_encode(array_column($cartItems, 'id')));
            error_log("[CheckoutController->index] Subtotal for display: " . $subtotal);
        }

        // Lưu selectedIds vào session để dùng trong process() (tránh mất data khi refresh)
        $_SESSION['checkout_selected_items'] = $selectedIds;

        // Lấy khuyến mãi áp dụng cho từng sản phẩm
        $promotions = $this->getApplicablePromotions($cartItems, $pdo);

        // LƯU GIÁ GỐC TRƯỚC KHI ÁP DỤNG KHUYẾN MÃI
        // Tính giống CartController: Tổng giá × số lượng của TẤT CẢ sản phẩm được chọn
        $originalSubtotalBeforePromo = 0;
        foreach ($cartItems as $item) {
            $itemOriginal = $item['price'] * $item['quantity'];
            $originalSubtotalBeforePromo += $itemOriginal;
            error_log("[CheckoutController] Product ID {$item['id']} (name: {$item['name']}): price={$item['price']} × qty={$item['quantity']} = " . number_format($itemOriginal, 0, ',', '.'));
        }
        error_log("[CheckoutController] Total originalSubtotalBeforePromo: " . number_format($originalSubtotalBeforePromo, 0, ',', '.') . " from " . count($cartItems) . " items");

        // Tính lại subtotal sau khi áp dụng khuyến mãi
        $subtotalAfterPromo = 0;
        $processedCombos = []; // Lưu các combo đã xử lý

        foreach ($cartItems as $index => $item) {
            $productId = $item['id'];
            $itemPromotions = $promotions[$productId] ?? [];
            $itemTotal = $item['price'] * $item['quantity']; // Mặc định = giá gốc

            // Kiểm tra xem có khuyến mãi không
            $hasPromo = false;
            foreach ($itemPromotions as $promo) {
                // Giảm giá
                if ($promo['promo_type'] === 'discount') {
                    $itemPrice = $item['price'];
                    if ($promo['discount_type'] === 'percentage') {
                        $itemPrice = $item['price'] * (1 - $promo['discount_value'] / 100);
                    } else {
                        $itemPrice = $item['price'] - $promo['discount_value'];
                    }
                    $itemTotal = $itemPrice * $item['quantity'];
                    $hasPromo = true;
                    break;
                }

                // Bundle (Mua kèm)
                if ($promo['promo_type'] === 'bundle' && $item['quantity'] >= ($promo['required_qty'] ?? 1)) {
                    $requiredQty = $promo['required_qty'] ?? 1;
                    $bundlePrice = $promo['bundle_price'] ?? $item['price'];

                    // Tính số lần mua bundle
                    $bundleSets = floor($item['quantity'] / $requiredQty);
                    $itemTotal = $bundlePrice * $bundleSets;

                    // Thêm giá của số lượng lẻ (nếu có)
                    $remainingQty = $item['quantity'] % $requiredQty;
                    if ($remainingQty > 0) {
                        $itemTotal += $item['price'] * $remainingQty;
                    }
                    $hasPromo = true;
                    break;
                }

                // Combo - XỬ LÝ ĐẶC BIỆT
                if ($promo['promo_type'] === 'combo' && !empty($promo['combo_price'])) {
                    $comboId = $promo['id'];

                    // Nếu combo này chưa được xử lý
                    if (!isset($processedCombos[$comboId])) {
                        // Lấy tất cả sản phẩm trong combo
                        $comboItems = $promo['combo_items'] ?? [];
                        $comboTotal = 0;
                        $comboOriginalTotal = 0;
                        $canApplyCombo = true;

                        // Kiểm tra xem tất cả sản phẩm trong combo có trong giỏ không
                        foreach ($comboItems as $comboItem) {
                            $found = false;
                            foreach ($cartItems as $cartItem) {
                                if (
                                    $cartItem['id'] == $comboItem['product_id'] &&
                                    $cartItem['quantity'] >= $comboItem['required_qty']
                                ) {
                                    $found = true;
                                    $comboOriginalTotal += $cartItem['price'] * $comboItem['required_qty'];
                                    break;
                                }
                            }
                            if (!$found) {
                                $canApplyCombo = false;
                                break;
                            }
                        }

                        if ($canApplyCombo) {
                            // Áp dụng giá combo
                            $processedCombos[$comboId] = [
                                'applied' => true,
                                'combo_price' => $promo['combo_price'],
                                'original_total' => $comboOriginalTotal
                            ];
                            // Chỉ tính giá combo 1 LẦN cho cả nhóm
                            $itemTotal = 0; // Sản phẩm này sẽ được tính trong combo, không tính riêng
                            $hasPromo = true;
                        }
                    } else {
                        // Combo đã được xử lý rồi, sản phẩm này không tính riêng
                        $itemTotal = 0;
                        $hasPromo = true;
                    }
                    break;
                }
            }

            // CẬP NHẬT SUBTOTAL SAU KHI ÁP DỤNG KHUYẾN MÃI
            $cartItems[$index]['subtotal'] = $itemTotal;
            $subtotalAfterPromo += $itemTotal;
        }

        // Thêm giá combo vào tổng
        foreach ($processedCombos as $comboData) {
            if ($comboData['applied']) {
                $subtotalAfterPromo += $comboData['combo_price'];
            }
        }

        // Tính tổng giảm giá
        $totalDiscount = $originalSubtotalBeforePromo - $subtotalAfterPromo;
        if ($totalDiscount < 0) $totalDiscount = 0; // Đảm bảo không âm

        // Lấy địa chỉ khách hàng
        $addresses = $this->addressRepo->getCustomerAddresses($customerId);
        $defaultAddress = $this->addressRepo->getDefaultAddress($customerId);

        return $this->view('customer.checkout.checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotalAfterPromo,  // Tổng sau khuyến mãi
            'originalSubtotal' => $originalSubtotalBeforePromo,    // Tổng giá gốc (chưa giảm)
            'totalDiscount' => $totalDiscount,  // Tổng giảm giá
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'promotions' => $promotions
        ]);
    }

    /**
     * Lấy các khuyến mãi áp dụng cho sản phẩm trong giỏ
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
                $gift['gift_image_url'] = $this->productRepo->getProductImage($gift['gift_product_id']);
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
        $promos = array_merge($promos, $comboPromos);

        // DEBUG: Log query results
        if (isset($_GET['debug'])) {
            error_log("=== DEBUG getApplicablePromotions ===");
            error_log("Product IDs: " . implode(', ', $productIds));
            error_log("Now: " . $now);
            error_log("Found " . count($promos) . " promotions");
            error_log("Promotions: " . print_r($promos, true));
        }

        // Nhóm khuyến mãi theo product_id
        $result = [];
        foreach ($promos as $promo) {
            $productId = $promo['product_id'];
            if (!isset($result[$productId])) {
                $result[$productId] = [];
            }

            // Lấy thêm thông tin chi tiết tùy theo loại khuyến mãi
            switch ($promo['promo_type']) {
                case 'discount':
                    // Giảm giá: đã có discount_type và discount_value
                    break;

                case 'bundle':
                    // Mua kèm: thông tin đã được lấy trong query ban đầu (required_qty, bundle_price)
                    break;

                case 'gift':
                    // Quà tặng: thông tin đã được lấy trong query ban đầu
                    // Lấy ảnh chính xác từ ProductRepository
                    if (!empty($promo['gift_product_id'])) {
                        $promo['gift_image_url'] = $this->productRepo->getProductImage($promo['gift_product_id']);
                    }
                    break;

                case 'combo':
                    // Combo: lấy các sản phẩm trong combo
                    $comboStmt = $pdo->prepare("
                        SELECT pci.product_id, pci.required_qty, p.name, p.sale_price
                        FROM promotion_combo_items pci
                        INNER JOIN products p ON pci.product_id = p.id
                        WHERE pci.promotion_id = ?
                    ");
                    $comboStmt->execute([$promo['id']]);
                    $items = $comboStmt->fetchAll(\PDO::FETCH_ASSOC);

                    // Add image URL for combo items
                    foreach ($items as &$item) {
                        $item['image_url'] = $this->productRepo->getProductImage($item['product_id']);
                    }
                    $promo['combo_items'] = $items;
                    break;
            }

            $result[$productId][] = $promo;
        }

        return $result;
    }

    /**
     * Process checkout (create order)
     */
    public function process(Request $request): mixed
    {
        header('Content-Type: application/json');

        try {
            // Get customer ID from JWT token
            $customerId = $request->user['id'] ?? null;

            if (!$customerId) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
                exit;
            }

            $body = file_get_contents('php://input');
            $data = json_decode($body, true);

            // Validation
            if (empty($data['address_id'])) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Vui lòng chọn địa chỉ giao hàng'
                ]);
                exit;
            }

            if (empty($data['payment_method'])) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Vui lòng chọn phương thức thanh toán'
                ]);
                exit;
            }

            $pdo = \App\Core\DB::pdo();

            // Lấy thông tin từ SESSION (ưu tiên) hoặc từ checkout page (?items=1,2,3)
            $selectedItemIds = [];
            if (!empty($_SESSION['checkout_selected_items'])) {
                $selectedItemIds = $_SESSION['checkout_selected_items'];
            } elseif (!empty($_GET['items'])) {
                $selectedItemIds = array_filter(array_map('intval', explode(',', $_GET['items'])));
            }

            // Load cart từ database
            $cart = $this->cartRepo->loadCartFromDB($customerId);
            $allCartItems = $this->cartRepo->getCartItems($cart);

            // Lọc items được chọn để checkout
            if (!empty($selectedItemIds)) {
                $selectedStrIds = array_map('strval', $selectedItemIds);
                $cartItems = array_values(array_filter($allCartItems, function ($item) use ($selectedStrIds) {
                    return in_array((string) $item['id'], $selectedStrIds, true);
                }));
            } else {
                // Nếu không có selection, checkout tất cả
                $cartItems = $allCartItems;
            }

            if (empty($cartItems)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không có sản phẩm để thanh toán'
                ]);
                exit;
            }

            // Tính tổng tiền (chỉ cho items được chọn)
            $subtotal = array_sum(array_column($cartItems, 'subtotal'));

            // DEBUG: Log để kiểm tra
            error_log("[CheckoutController->process] Payment method: " . $data['payment_method']);
            error_log("[CheckoutController->process] Selected item IDs from URL: " . json_encode($selectedItemIds));
            error_log("[CheckoutController->process] Cart items count: " . count($cartItems));
            error_log("[CheckoutController->process] All cart items count: " . count($allCartItems));
            error_log("[CheckoutController->process] Subtotal calculated: " . $subtotal);
            error_log("[CheckoutController->process] Cart items: " . json_encode(array_map(function ($item) {
                return ['id' => $item['id'], 'name' => $item['name'], 'subtotal' => $item['subtotal']];
            }, $cartItems)));

            // Map payment method to DB enum values
            $paymentMethodMap = [
                'cod' => 'Thanh toán khi nhận hàng (COD)',
                'vnpay' => 'VNPay',
                'zalopay' => 'ZaloPay'
            ];
            $dbPaymentMethod = $paymentMethodMap[$data['payment_method']] ?? 'Thanh toán khi nhận hàng (COD)';

            // === VNPay: Lưu pending data vào DATABASE ===
            if ($data['payment_method'] === 'vnpay') {
                $pdo = \App\Core\DB::pdo();

                // Lưu pending order vào bảng tạm
                $shippingFee = $data['shipping_fee'] ?? 0;
                $grandTotal = $data['amount'] ?? ($subtotal + $shippingFee);

                $pendingData = json_encode([
                    'customer_id' => $customerId,
                    'address_id' => $data['address_id'],
                    'cart_items' => $cartItems,
                    'subtotal' => $data['subtotal'] ?? $subtotal,
                    'shipping_fee' => $shippingFee,
                    'grand_total' => $grandTotal,
                    'selected_item_ids' => $selectedItemIds
                ]);

                $vnp_TxnRef = $customerId . '_' . time();

                // Insert vào pending_orders table
                $stmt = $pdo->prepare("
                    INSERT INTO pending_orders (txn_ref, customer_id, order_data, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE order_data = ?, created_at = NOW()
                ");
                $stmt->execute([$vnp_TxnRef, $customerId, $pendingData, $pendingData]);

                // VNPay config
                $vnp_TmnCode = getenv('VNPAY_TMN_CODE');
                $vnp_HashSecret = getenv('VNPAY_HASH_SECRET');
                $vnp_Url = getenv('VNPAY_URL');
                $vnp_ReturnUrl = getenv('VNPAY_RETURN_URL') ?: 'http://localhost/payment/vnpay/callback';

                if (!$vnp_TmnCode || !$vnp_HashSecret || !$vnp_Url) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cấu hình VNPay chưa đầy đủ'
                    ]);
                    exit;
                }

                // Build payment data
                $vnp_TxnRef = $customerId . '_' . time(); // Không dùng orderId vì chưa tạo
                $vnp_OrderInfo = 'Thanh toán đơn hàng MiniGo - ' . $customerId;
                $vnp_OrderType = 'billpayment';
                $vnp_Amount = (int)($grandTotal * 100); // VNPay requires amount in VND * 100 (includes shipping)
                $vnp_Locale = 'vn';
                $vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

                $inputData = [
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => $vnp_TmnCode,
                    "vnp_Amount" => $vnp_Amount,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $vnp_IpAddr,
                    "vnp_Locale" => $vnp_Locale,
                    "vnp_OrderInfo" => $vnp_OrderInfo,
                    "vnp_OrderType" => $vnp_OrderType,
                    "vnp_ReturnUrl" => $vnp_ReturnUrl,
                    "vnp_TxnRef" => $vnp_TxnRef,
                ];

                ksort($inputData);
                $query = "";
                $i = 0;
                $hashdata = "";

                foreach ($inputData as $key => $value) {
                    if ($i == 1) {
                        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                    } else {
                        $hashdata .= urlencode($key) . "=" . urlencode($value);
                        $i = 1;
                    }
                    $query .= urlencode($key) . "=" . urlencode($value) . '&';
                }

                $vnp_Url = $vnp_Url . "?" . $query;
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

                echo json_encode([
                    'success' => true,
                    'message' => 'Đang chuyển đến trang thanh toán VNPay',
                    'payment_method' => 'vnpay',
                    'payment_url' => $vnp_Url
                ]);
                exit;
            }

            // === ZaloPay: Lưu pending data vào DATABASE ===
            if ($data['payment_method'] === 'zalopay') {
                $pdo = \App\Core\DB::pdo();

                // Lưu pending order vào bảng tạm
                $shippingFee = $data['shipping_fee'] ?? 0;
                $grandTotal = $data['amount'] ?? ($subtotal + $shippingFee);

                $pendingData = json_encode([
                    'customer_id' => $customerId,
                    'address_id' => $data['address_id'],
                    'cart_items' => $cartItems,
                    'subtotal' => $data['subtotal'] ?? $subtotal,
                    'shipping_fee' => $shippingFee,
                    'grand_total' => $grandTotal,
                    'selected_item_ids' => $selectedItemIds
                ]);

                $transID = time();
                $appTransId = date('ymd') . '_' . $transID;

                // Insert vào pending_orders table
                $stmt = $pdo->prepare("
                    INSERT INTO pending_orders (txn_ref, customer_id, order_data, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE order_data = ?, created_at = NOW()
                ");
                $stmt->execute([$appTransId, $customerId, $pendingData, $pendingData]);

                // ZaloPay config
                $appId = getenv('ZALOPAY_APP_ID');
                $key1 = getenv('ZALOPAY_KEY1');
                $endpoint = getenv('ZALOPAY_ENDPOINT');
                $callbackUrl = getenv('ZALOPAY_CALLBACK_URL') ?: 'http://localhost/payment/zalopay/callback';

                if (!$appId || !$key1 || !$endpoint) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cấu hình ZaloPay chưa đầy đủ'
                    ]);
                    exit;
                }

                // Build payment data
                $embedData = json_encode([
                    'redirecturl' => getenv('APP_URL') . '/payment/zalopay/return'
                ]);

                $items = json_encode([]);

                $order = [
                    'app_id' => (int)$appId,
                    'app_trans_id' => $appTransId,
                    'app_user' => 'user_' . $customerId,
                    'app_time' => round(microtime(true) * 1000),
                    'amount' => (int)$subtotal,
                    'item' => $items,
                    'embed_data' => $embedData,
                    'description' => 'Thanh toán đơn hàng MiniGo - ' . $customerId,
                    'bank_code' => '',
                    'callback_url' => $callbackUrl
                ];

                // Create MAC
                $macData = $order['app_id'] . '|' . $order['app_trans_id'] . '|' . $order['app_user'] . '|' .
                    $order['amount'] . '|' . $order['app_time'] . '|' . $order['embed_data'] . '|' .
                    $order['item'];
                $order['mac'] = hash_hmac('sha256', $macData, $key1);

                // Call ZaloPay API
                $context = stream_context_create([
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($order)
                    ]
                ]);

                $response = file_get_contents($endpoint, false, $context);
                $result = json_decode($response, true);

                if ($result['return_code'] == 1) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đang chuyển đến trang thanh toán ZaloPay',
                        'payment_method' => 'zalopay',
                        'payment_url' => $result['order_url']
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không thể tạo thanh toán ZaloPay: ' . ($result['return_message'] ?? 'Unknown error')
                    ]);
                }
                exit;
            }

            // === COD và các phương thức khác: Tạo đơn ngay ===
            $pdo->beginTransaction();
            try {
                // Generate order code
                $orderCode = 'ORD' . date('YmdHis') . rand(100, 999);

                // Fetch shipping address for GHN
                $address = $this->addressRepo->getAddressById($data['address_id'], $customerId);

                if (!$address) {
                    throw new \Exception('Địa chỉ giao hàng không hợp lệ');
                }

                // === KIỂM TRA KHU VỰC GIAO HÀNG: TÍNH KHOẢNG CÁCH ===
                $deliveryCheck = $this->deliveryService->checkDeliveryArea($address);

                if (!$deliveryCheck['success']) {
                    throw new \Exception($deliveryCheck['message']);
                }

                // Log distance for debugging
                if (isset($deliveryCheck['distance'])) {
                    error_log(sprintf(
                        '[Checkout] Delivery distance check passed: %.2f km (max: %.0f km)',
                        $deliveryCheck['distance'],
                        $this->deliveryService->getMaxDeliveryRadius()
                    ));
                }


                // Get district ID from address (saved when user selected district)
                $districtId = $address['district_id'] ?? null;

                // TODO: Uncomment this when GHN API integration is fully tested
                /*
                if (!empty($address['commune_code'])) {
                    try {
                        $districtInfo = \App\Support\GHNWardCache::getDistrictByWardCode(
                            $address['commune_code'],
                            $address['province_code']
                        );

                        if ($districtInfo) {
                            $districtId = $districtInfo['district_id'];
                        }
                    } catch (\Exception $e) {
                        error_log("Failed to get district from GHN: " . $e->getMessage());
                    }
                }
                */

                // Insert order with shipping address data for GHN
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        code, user_id, order_type, status, subtotal, grand_total,
                        payment_method, payment_status, shipping_address_id,
                        delivery_name, delivery_phone, delivery_address,
                        shipping_province, shipping_ward,
                        shipping_province_id, shipping_district_id, shipping_ward_code,
                        created_at, updated_at
                    )
                    VALUES (?, ?, 'Online', 'Chờ xử lý', ?, ?, ?, 'Chưa thanh toán', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $orderCode,
                    $customerId,
                    $subtotal,
                    $subtotal, // grand_total (total_amount)
                    $dbPaymentMethod,
                    $data['address_id'],
                    $address['recipient_name'] ?? $address['receiver_name'] ?? '',
                    $address['phone_number'] ?? $address['receiver_phone'] ?? '',
                    $address['address_line'] ?? $address['line1'] ?? '',
                    $address['province'] ?? $address['province_name'] ?? '',
                    $address['ward'] ?? $address['ward_name'] ?? '',
                    $address['province_code'] ?? null,
                    $districtId, // Only save district_id (needed by GHN)
                    $address['commune_code'] ?? ''
                ]);

                $orderId = $pdo->lastInsertId();

                // Insert order items
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, qty, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($cartItems as $item) {
                    $stmtItem->execute([
                        $orderId,
                        $item['id'],
                        $item['quantity'],
                        $item['price'],
                        $item['subtotal']
                    ]);

                    // Trừ số lượng trong kho
                    $stmtStock = $pdo->prepare("
                        UPDATE stocks 
                        SET qty = qty - ?, 
                            updated_at = NOW() 
                        WHERE product_id = ?
                    ");
                    $stmtStock->execute([$item['quantity'], $item['id']]);
                }

                // Xóa CHÍNH XÁC các items đã checkout khỏi giỏ
                if (!empty($selectedItemIds)) {
                    // Xóa từng item cụ thể
                    foreach ($selectedItemIds as $productId) {
                        $this->cartRepo->removeItemDB($customerId, $productId);
                    }
                } else {
                    // Xóa toàn bộ giỏ hàng
                    $this->cartRepo->clearCartDB($customerId);
                }

                $pdo->commit();

                // Lấy thông tin khách hàng để gửi email
                $customerStmt = $pdo->prepare("
                    SELECT id, username, email, full_name, phone 
                    FROM users 
                    WHERE id = ?
                ");
                $customerStmt->execute([$customerId]);
                $customerInfo = $customerStmt->fetch(\PDO::FETCH_ASSOC);

                // Gửi thông báo cho khách hàng
                try {
                    \App\Support\NotificationHelper::send(
                        (int)$customerId,
                        'Đặt hàng thành công',
                        'Đơn hàng ' . $orderCode . ' của bạn đã được khởi tạo thành công.',
                        '/profile?tab=orders&open=' . $orderId,
                        'success'
                    );
                } catch (\Throwable $ex) { /* Ignore notification error */
                }

                // Gửi email xác nhận đơn hàng
                if ($customerInfo && !empty($customerInfo['email'])) {
                    try {
                        // Lấy thông tin đơn hàng vừa tạo kèm thông tin địa chỉ đầy đủ
                        $orderStmt = $pdo->prepare("
                            SELECT o.*, ua.district_name as shipping_district
                            FROM orders o
                            LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
                            WHERE o.id = ?
                        ");
                        $orderStmt->execute([$orderId]);
                        $orderData = $orderStmt->fetch(\PDO::FETCH_ASSOC);

                        // Lấy chi tiết sản phẩm trong đơn hàng
                        $itemsStmt = $pdo->prepare("
                            SELECT oi.*, p.name as product_name
                            FROM order_items oi
                            INNER JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id = ?
                        ");
                        $itemsStmt->execute([$orderId]);
                        $orderItems = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

                        // Gửi email
                        $emailService = new \App\Services\EmailService();
                        $emailResult = $emailService->sendOrderConfirmation($orderData, $customerInfo, $orderItems);

                        if ($emailResult['success']) {
                            error_log("Order confirmation email sent successfully for order: " . $orderCode);
                        } else {
                            error_log("Failed to send order confirmation email: " . $emailResult['message']);
                        }
                    } catch (\Throwable $emailEx) {
                        // Log lỗi nhưng không làm fail transaction
                        error_log("Error sending order confirmation email: " . $emailEx->getMessage());
                    }
                } else {
                    error_log("Cannot send order confirmation email: Customer email is missing for user ID: " . $customerId);
                }

                // COD - Redirect to success page (like VNPay)
                $_SESSION['order_success'] = [
                    'order_id' => $orderId,
                    'order_code' => $orderCode,
                    'amount' => $subtotal,
                    'payment_method' => $dbPaymentMethod
                ];

                // Return redirect URL instead of JSON
                echo json_encode([
                    'success' => true,
                    'message' => 'Đặt hàng thành công',
                    'redirect_url' => '/payment/cod/success'
                ]);
                exit;
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(Request $request): mixed
    {
        header('Content-Type: application/json');

        try {
            // Get customer ID from JWT
            $customerId = $request->user['id'] ?? null;
            if (!$customerId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                exit;
            }

            $body = file_get_contents('php://input');
            $data = json_decode($body, true);

            $code = $data['code'] ?? '';
            $orderAmount = (float)($data['order_amount'] ?? 0);

            if (empty($code)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vui lòng nhập mã giảm giá'
                ]);
                exit;
            }

            $couponRepo = new \App\Models\Repositories\CouponRepository();
            $result = $couponRepo->validateCoupon($code, $orderAmount, $customerId);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
