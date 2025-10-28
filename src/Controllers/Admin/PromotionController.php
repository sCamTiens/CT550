<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\PromotionRepository;
use App\Controllers\Admin\AuthController;

class PromotionController extends BaseAdminController
{
    private $promotionRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->promotionRepo = new PromotionRepository();
    }

    /** GET /admin/promotions (trả về view) */
    public function index()
    {
        return $this->view('admin/promotions/promotion');
    }

    /** GET /admin/api/promotions (list) */
    public function apiIndex()
    {
        $items = $this->promotionRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/promotions (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        // Validate user
        if ($currentUser === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Không xác định được người dùng. Vui lòng đăng nhập lại.',
                'debug' => [
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'session_user' => $_SESSION['user'] ?? 'not set',
                    'session_admin_user' => $_SESSION['admin_user'] ?? 'not set'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Kiểm tra user có tồn tại không
        $pdo = \App\Core\DB::pdo();
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $stmt->execute([$currentUser]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'ID người dùng không tồn tại trong hệ thống',
                'debug' => [
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'all_users_ids' => $pdo->query("SELECT id FROM users LIMIT 10")->fetchAll(\PDO::FETCH_COLUMN)
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $id = $this->promotionRepo->create($data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();

            // Debug: Log lỗi chi tiết
            error_log("PDO Error Code: " . $errorCode);
            error_log("PDO Error Message: " . $errorMsg);
            error_log("Current User ID: " . $currentUser);

            // Phân tích lỗi cụ thể
            $responseError = 'Lỗi máy chủ khi tạo chương trình khuyến mãi';
            $httpCode = 500;

            if ($errorCode === '23000') {
                // Kiểm tra chi tiết lỗi constraint
                if (stripos($errorMsg, 'Duplicate entry') !== false && stripos($errorMsg, "'name'") !== false) {
                    $responseError = 'Tên chương trình khuyến mãi đã tồn tại';
                    $httpCode = 409;
                } elseif (stripos($errorMsg, 'foreign key constraint') !== false) {
                    if (stripos($errorMsg, 'created_by') !== false || stripos($errorMsg, 'updated_by') !== false) {
                        $responseError = 'ID người dùng không hợp lệ. Vui lòng đăng nhập lại.';
                        $httpCode = 401;
                    } else {
                        $responseError = 'Sản phẩm hoặc danh mục không tồn tại';
                        $httpCode = 400;
                    }
                } elseif (stripos($errorMsg, 'cannot be null') !== false) {
                    $responseError = 'Thiếu thông tin bắt buộc';
                    $httpCode = 400;
                } else {
                    // Lỗi constraint khác
                    $responseError = 'Dữ liệu không hợp lệ: ' . $errorMsg;
                    $httpCode = 400;
                }
            }

            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $responseError,
                'debug' => [
                    'code' => $errorCode,
                    'message' => $errorMsg,
                    'currentUser' => $currentUser,
                    'session_keys' => array_keys($_SESSION),
                    'session_user' => $_SESSION['user'] ?? 'not set',
                    'session_admin_user' => $_SESSION['admin_user'] ?? 'not set'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi không xác định',
                'debug' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** PUT /admin/promotions/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();

        // Validate user
        if ($currentUser === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Không xác định được người dùng. Vui lòng đăng nhập lại.',
                'debug' => 'currentUser is null'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $this->promotionRepo->update($id, $data, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->promotionRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();

            error_log("PDO Update Error Code: " . $errorCode);
            error_log("PDO Update Error Message: " . $errorMsg);

            $responseError = 'Lỗi máy chủ khi cập nhật chương trình khuyến mãi';
            $httpCode = 500;

            if ($errorCode === '23000') {
                if (stripos($errorMsg, 'Duplicate entry') !== false && stripos($errorMsg, "'name'") !== false) {
                    $responseError = 'Tên chương trình khuyến mãi đã tồn tại';
                    $httpCode = 409;
                } elseif (stripos($errorMsg, 'foreign key constraint') !== false) {
                    $responseError = 'Sản phẩm hoặc danh mục không tồn tại';
                    $httpCode = 400;
                } else {
                    $responseError = 'Dữ liệu không hợp lệ: ' . $errorMsg;
                    $httpCode = 400;
                }
            }

            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $responseError,
                'debug' => [
                    'code' => $errorCode,
                    'message' => $errorMsg
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** DELETE /admin/promotions/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->promotionRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Kiểm tra và áp dụng khuyến mãi cho giỏ hàng
     * POST /admin/api/promotions/check
     * Body: { items: [{product_id, quantity, unit_price}] }
     */
    public function check()
    {
        // Đảm bảo response luôn là JSON
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $items = $data['items'] ?? [];

            if (empty($items)) {
                echo json_encode(['promotions' => [], 'items' => []]);
                exit;
            }

            // Lấy tất cả CTKM đang active
            $activePromotions = $this->getActivePromotions();

            $appliedPromotions = [];
            $updatedItems = $items;
            $giftItems = [];

            foreach ($activePromotions as $promo) {
                $result = $this->applyPromotion($promo, $updatedItems);
                
                if ($result['applied']) {
                    $appliedPromotions[] = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->promo_type,
                        'description' => $result['description'],
                        'discount_amount' => $result['discount_amount'] ?? 0,
                        'items_affected' => $result['items_affected'] ?? [],
                    ];

                    if (!empty($result['updated_items'])) {
                        $updatedItems = $result['updated_items'];
                    }

                    if (!empty($result['gift_items'])) {
                        $giftItems = array_merge($giftItems, $result['gift_items']);
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'promotions' => $appliedPromotions,
                'items' => $updatedItems,
                'gift_items' => $giftItems,
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Error in promotion check: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Lấy danh sách CTKM đang active
     */
    private function getActivePromotions(): array
    {
        $all = $this->promotionRepo->all();
        $now = date('Y-m-d H:i:s');

        return array_filter($all, function($promo) use ($now) {
            return $promo->is_active 
                && $promo->starts_at <= $now 
                && $promo->ends_at >= $now;
        });
    }

    /**
     * Áp dụng một CTKM cho giỏ hàng
     */
    private function applyPromotion($promo, $items): array
    {
        switch ($promo->promo_type) {
            case 'discount':
                return $this->applyDiscount($promo, $items);
            
            case 'bundle':
                return $this->applyBundle($promo, $items);
            
            case 'gift':
                return $this->applyGift($promo, $items);
            
            case 'combo':
                return $this->applyCombo($promo, $items);
            
            default:
                return ['applied' => false];
        }
    }

    /**
     * Áp dụng CTKM Giảm giá thường
     */
    private function applyDiscount($promo, $items): array
    {
        $productIds = $promo->product_ids ?? [];
        $discountAmount = 0;
        $itemsAffected = [];

        foreach ($items as &$item) {
            // Kiểm tra áp dụng cho sản phẩm
            $applicable = ($promo->apply_to === 'all') 
                || ($promo->apply_to === 'product' && in_array($item['product_id'], $productIds));

            if (!$applicable) continue;

            $itemTotal = $item['quantity'] * $item['unit_price'];
            
            if ($promo->discount_type === 'percentage') {
                $discount = $itemTotal * ($promo->discount_value / 100);
            } else {
                $discount = min($promo->discount_value, $itemTotal);
            }

            $discountAmount += $discount;
            $itemsAffected[] = $item['product_id'];
        }

        if ($discountAmount > 0) {
            return [
                'applied' => true,
                'description' => $promo->discount_type === 'percentage' 
                    ? "Giảm {$promo->discount_value}%" 
                    : "Giảm " . number_format($promo->discount_value) . "đ",
                'discount_amount' => $discountAmount,
                'items_affected' => $itemsAffected,
            ];
        }

        return ['applied' => false];
    }

    /**
     * Áp dụng CTKM Mua kèm (Bundle)
     */
    private function applyBundle($promo, $items): array
    {
        $rules = $promo->bundle_rules ?? [];
        $updatedItems = $items;
        $totalDiscount = 0;
        $itemsAffected = [];

        foreach ($rules as $rule) {
            foreach ($updatedItems as &$item) {
                if ($item['product_id'] == $rule['product_id'] && $item['quantity'] >= $rule['qty']) {
                    // Tính số bộ bundle
                    $bundles = floor($item['quantity'] / $rule['qty']);
                    $remainingQty = $item['quantity'] % $rule['qty'];

                    // Giá bundle cho các bộ đủ điều kiện
                    $bundleTotal = $bundles * $rule['price'];
                    // Giá gốc cho số lượng lẻ
                    $remainingTotal = $remainingQty * $item['unit_price'];
                    
                    $oldTotal = $item['quantity'] * $item['unit_price'];
                    $newTotal = $bundleTotal + $remainingTotal;
                    
                    $discount = $oldTotal - $newTotal;
                    
                    if ($discount > 0) {
                        // Cập nhật đơn giá trung bình
                        $item['unit_price'] = round($newTotal / $item['quantity'], 0);
                        $item['bundle_applied'] = true;
                        $totalDiscount += $discount;
                        $itemsAffected[] = $item['product_id'];
                    }
                }
            }
        }

        if ($totalDiscount > 0) {
            return [
                'applied' => true,
                'description' => "Mua kèm: " . $this->describeBundleRules($rules),
                'discount_amount' => $totalDiscount,
                'updated_items' => $updatedItems,
                'items_affected' => $itemsAffected,
            ];
        }

        return ['applied' => false];
    }

    /**
     * Áp dụng CTKM Tặng quà (Gift)
     */
    private function applyGift($promo, $items): array
    {
        $rules = $promo->gift_rules ?? [];
        $giftItems = [];
        $itemsAffected = [];

        foreach ($rules as $rule) {
            foreach ($items as $item) {
                if ($item['product_id'] == $rule['trigger_product_id'] 
                    && $item['quantity'] >= $rule['trigger_qty']) {
                    
                    // Tính số lượng quà được tặng
                    $sets = floor($item['quantity'] / $rule['trigger_qty']);
                    $giftQty = $sets * $rule['gift_qty'];

                    $giftItems[] = [
                        'product_id' => $rule['gift_product_id'],
                        'quantity' => $giftQty,
                        'unit_price' => 0, // Quà tặng = 0đ
                        'is_gift' => true,
                    ];
                    
                    $itemsAffected[] = $item['product_id'];
                }
            }
        }

        if (!empty($giftItems)) {
            return [
                'applied' => true,
                'description' => "Tặng quà: " . $this->describeGiftRules($rules),
                'gift_items' => $giftItems,
                'items_affected' => $itemsAffected,
            ];
        }

        return ['applied' => false];
    }

    /**
     * Áp dụng CTKM Combo
     */
    private function applyCombo($promo, $items): array
    {
        $comboItems = $promo->combo_items ?? [];
        $comboPrice = $promo->combo_price ?? 0;

        // Kiểm tra xem giỏ hàng có đủ sản phẩm combo không
        $hasAllItems = true;
        $minSets = PHP_INT_MAX;

        foreach ($comboItems as $comboItem) {
            $found = false;
            foreach ($items as $item) {
                if ($item['product_id'] == $comboItem['product_id']) {
                    $sets = floor($item['quantity'] / $comboItem['qty']);
                    $minSets = min($minSets, $sets);
                    $found = true;
                    break;
                }
            }
            if (!$found || $minSets == 0) {
                $hasAllItems = false;
                break;
            }
        }

        if ($hasAllItems && $minSets > 0) {
            // Tính tổng giá gốc của combo
            $originalPrice = 0;
            foreach ($comboItems as $comboItem) {
                foreach ($items as $item) {
                    if ($item['product_id'] == $comboItem['product_id']) {
                        $originalPrice += $comboItem['qty'] * $item['unit_price'];
                        break;
                    }
                }
            }

            $discount = ($originalPrice - $comboPrice) * $minSets;

            if ($discount > 0) {
                return [
                    'applied' => true,
                    'description' => "Combo: " . number_format($comboPrice) . "đ (Tiết kiệm " . number_format($discount) . "đ)",
                    'discount_amount' => $discount,
                    'items_affected' => array_column($comboItems, 'product_id'),
                ];
            }
        }

        return ['applied' => false];
    }

    /**
     * Mô tả quy tắc bundle
     */
    private function describeBundleRules($rules): string
    {
        $descriptions = [];
        foreach ($rules as $rule) {
            $descriptions[] = "Mua {$rule['qty']} = " . number_format($rule['price']) . "đ";
        }
        return implode(', ', $descriptions);
    }

    /**
     * Mô tả quy tắc gift
     */
    private function describeGiftRules($rules): string
    {
        $descriptions = [];
        foreach ($rules as $rule) {
            $descriptions[] = "Mua {$rule['trigger_qty']} tặng {$rule['gift_qty']}";
        }
        return implode(', ', $descriptions);
    }

    private function currentUserId(): ?int
    {
        // Debug session
        error_log("Session data: " . json_encode($_SESSION));
        
        $userId = $_SESSION['user']['id'] ?? null;
        
        if ($userId === null) {
            error_log("User ID is null - Session user: " . json_encode($_SESSION['user'] ?? 'not set'));
            // Nếu không có user trong session, thử lấy từ admin_user
            $userId = $_SESSION['admin_user']['id'] ?? null;
            if ($userId === null) {
                error_log("Admin user ID also null - Session admin_user: " . json_encode($_SESSION['admin_user'] ?? 'not set'));
            }
        }
        
        return $userId;
    }
}