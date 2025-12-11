<?php

namespace App\Models\Customer\Repositories;

use App\Core\DB;
use PDO;

class CartRepository
{
    /**
     * Lấy hoặc tạo cart_id cho user
     */
    public function getOrCreateCartId(int $userId): int
    {
        $pdo = DB::pdo();

        // Tìm cart hiện tại
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();

        if ($cartId) {
            return (int)$cartId;
        }

        // Tạo mới nếu chưa có
        $stmt = $pdo->prepare("INSERT INTO carts (user_id, created_by) VALUES (?, ?)");
        $stmt->execute([$userId, $userId]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Load giỏ hàng từ database cho user
     */
    public function loadCartFromDB(int $userId): array
    {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT ci.product_id, ci.qty, ci.price, p.name
            FROM carts c
            INNER JOIN cart_items ci ON ci.cart_id = c.id
            INNER JOIN products p ON p.id = ci.product_id
            WHERE c.user_id = ? AND p.is_active = 1
        ");

        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cart = [];
        foreach ($items as $item) {
            $cart[$item['product_id']] = [
                'id' => (int)$item['product_id'],
                'name' => $item['name'],
                'price' => (float)$item['price'],
                'qty' => (int)$item['qty']
            ];
        }

        return $cart;
    }

    /**
     * Lưu toàn bộ giỏ hàng vào database
     */
    public function saveCartToDB(int $userId, array $cart): bool
    {
        if (empty($cart)) {
            return $this->clearCartDB($userId);
        }

        $pdo = DB::pdo();

        try {
            $pdo->beginTransaction();

            // Get or create cart
            $cartId = $this->getOrCreateCartId($userId);

            // Clear old items
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cartId]);

            // Insert new items
            $stmt = $pdo->prepare("
                INSERT INTO cart_items (cart_id, product_id, qty, price, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cart as $productId => $item) {
                $qty = is_array($item) ? ($item['qty'] ?? 0) : $item;
                $price = is_array($item) ? ($item['price'] ?? 0) : 0;

                if ($qty <= 0) continue;

                // Get current price if not in session
                if ($price <= 0) {
                    $productInfo = $this->getProductInfo($productId);
                    $price = $productInfo['price'] ?? 0;
                }

                $stmt->execute([$cartId, $productId, $qty, $price, $userId]);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error saving cart to DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Thêm/cập nhật 1 item vào database
     */
    public function addItemToDB(int $userId, int $productId, int $quantity, float $price): bool
    {
        $pdo = DB::pdo();

        try {
            $cartId = $this->getOrCreateCartId($userId);

            // Check if item exists
            $stmt = $pdo->prepare("
                SELECT id, qty FROM cart_items 
                WHERE cart_id = ? AND product_id = ?
            ");
            $stmt->execute([$cartId, $productId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update quantity
                $newQty = $existing['qty'] + $quantity;
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET qty = ?, price = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newQty, $price, $userId, $existing['id']]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("
                    INSERT INTO cart_items (cart_id, product_id, qty, price, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$cartId, $productId, $quantity, $price, $userId]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error adding item to DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật số lượng item trong database
     */
    public function updateItemDB(int $userId, int $productId, int $quantity): bool
    {
        $pdo = DB::pdo();

        try {
            $cartId = $this->getOrCreateCartId($userId);

            if ($quantity <= 0) {
                // Remove item
                $stmt = $pdo->prepare("
                    DELETE FROM cart_items 
                    WHERE cart_id = ? AND product_id = ?
                ");
                $stmt->execute([$cartId, $productId]);
            } else {
                // Update quantity
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET qty = ?, updated_by = ?, updated_at = NOW()
                    WHERE cart_id = ? AND product_id = ?
                ");
                $stmt->execute([$quantity, $userId, $cartId, $productId]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error updating item in DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa 1 item khỏi database
     */
    public function removeItemDB(int $userId, int $productId): bool
    {
        $pdo = DB::pdo();

        try {
            $stmt = $pdo->prepare("
                DELETE ci FROM cart_items ci
                INNER JOIN carts c ON c.id = ci.cart_id
                WHERE c.user_id = ? AND ci.product_id = ?
            ");
            $stmt->execute([$userId, $productId]);

            return true;
        } catch (\Exception $e) {
            error_log("Error removing item from DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa toàn bộ giỏ hàng trong database
     */
    public function clearCartDB(int $userId): bool
    {
        $pdo = DB::pdo();

        try {
            $stmt = $pdo->prepare("
                DELETE ci FROM cart_items ci
                INNER JOIN carts c ON c.id = ci.cart_id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);

            return true;
        } catch (\Exception $e) {
            error_log("Error clearing cart from DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin sản phẩm trong giỏ hàng
     */
    public function getCartItems(array $cart): array
    {
        if (empty($cart)) {
            return [];
        }

        $pdo = DB::pdo();
        $productIds = array_keys($cart);

        // Create named placeholders
        $placeholders = [];
        $params = [];
        foreach ($productIds as $i => $id) {
            $key = ":pid_$i";
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $placeholderStr = implode(',', $placeholders);

        // Get products with stock info
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.slug, p.sale_price, s.qty as stock_qty
            FROM products p
            LEFT JOIN stocks s ON p.id = s.product_id
            WHERE p.id IN ($placeholderStr) AND p.is_active = 1
        ");

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, \PDO::PARAM_INT);
        }

        $stmt->execute();
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $cartItems = [];
        foreach ($products as $product) {
            $productId = $product['id'];
            $item = $cart[$productId];
            $quantity = is_array($item) ? ($item['qty'] ?? 0) : $item;

            if (!is_numeric($quantity) || $quantity <= 0) {
                continue;
            }

            $price = (float)$product['sale_price'];
            $subtotal = $price * $quantity;

            $cartItems[] = [
                'id' => $productId,
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => $price,
                'quantity' => $quantity,
                'stock_qty' => (int)($product['stock_qty'] ?? 0),
                'subtotal' => $subtotal,
                'image_url' => $this->getProductImage($productId)
            ];
        }

        return $cartItems;
    }

    /**
     * Tính tổng tiền giỏ hàng
     */
    public function calculateTotal(array $cartItems): float
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['subtotal'] ?? 0;
        }
        return $total;
    }

    /**
     * Kiểm tra tồn kho sản phẩm
     */
    public function checkStock(int $productId): int|false
    {
        $stmt = DB::pdo()->prepare("
            SELECT qty FROM stocks WHERE product_id = :pid
        ");
        $stmt->execute([':pid' => $productId]);
        $stock = $stmt->fetchColumn();

        return $stock !== false ? (int)$stock : false;
    }

    /**
     * Lấy thông tin sản phẩm cơ bản
     */
    public function getProductInfo(int $productId): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT id, name, sale_price as price 
            FROM products 
            WHERE id = :id AND is_active = 1 
            LIMIT 1
        ");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $product ?: null;
    }

    /**
     * Lấy đường dẫn ảnh sản phẩm từ database
     */
    private function getProductImage(int $productId): string
    {
        // Query main image from product_images table
        $stmt = DB::pdo()->prepare("
            SELECT image_url 
            FROM product_images 
            WHERE product_id = ? AND image_type = 'main' 
            ORDER BY is_primary DESC, sort_order ASC
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $imageUrl = $stmt->fetchColumn();

        if ($imageUrl) {
            return $imageUrl; // Return as-is (could be ImgBB URL or local path)
        }

        return '/assets/images/products/default.png';
    }

    /**
     * Đếm số sản phẩm khác nhau trong giỏ (không phải tổng số lượng)
     */
    public function countItems(array $cart): int
    {
        return count($cart);
    }

    /**
     * Validate số lượng với tồn kho
     */
    public function validateQuantity(int $productId, int $requestedQty): array
    {
        $stock = $this->checkStock($productId);

        if ($stock === false) {
            return [
                'valid' => false,
                'message' => 'Sản phẩm không tồn tại hoặc đã ngừng bán',
                'quantity' => 0
            ];
        }

        if ($stock < $requestedQty) {
            return [
                'valid' => false,
                'message' => "Chỉ còn {$stock} sản phẩm trong kho",
                'quantity' => $stock
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
            'quantity' => $requestedQty
        ];
    }
}
