<?php

namespace App\Support;

use App\Core\DB;

class PromotionHelper
{
    /**
     * Tính tổng giảm giá cho toàn bộ đơn hàng
     * VERSION 3: Support DISCOUNT + GIFT + BUNDLE + COMBO
     */
    public static function calculateOrderTotal(array $cartItems): array
    {
        $pdo = DB::pdo();
        $productIds = array_column($cartItems, 'id');

        if (empty($productIds)) {
            return [
                'subtotal' => 0,
                'total_discount' => 0,
                'grand_total' => 0,
                'item_details' => [],
                'gift_items' => []
            ];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        // 1. DISCOUNT PROMOTIONS
        $discountSql = "
            SELECT p.id, p.name, p.promo_type, p.discount_type, p.discount_value, p.priority, p.description,
                   pp.product_id
            FROM promotions p
            INNER JOIN promotion_products pp ON p.id = pp.promotion_id
            WHERE pp.product_id IN ($placeholders)
            AND p.is_active = 1
            AND p.promo_type = 'discount'
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
            ORDER BY p.priority DESC
        ";

        $stmt = $pdo->prepare($discountSql);
        $stmt->execute($productIds);
        $discountPromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. GIFT PROMOTIONS
        $giftSql = "
            SELECT p.id, p.name,
                   pgr.trigger_product_id, pgr.required_qty,
                   pgr.gift_product_id, pgr.gift_qty
            FROM promotions p
            INNER JOIN promotion_gift_rules pgr ON p.id = pgr.promotion_id
            WHERE pgr.trigger_product_id IN ($placeholders)
            AND p.is_active = 1
            AND p.promo_type = 'gift'
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
        ";

        $stmt = $pdo->prepare($giftSql);
        $stmt->execute($productIds);
        $giftPromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 3. BUNDLE PROMOTIONS
        $bundleSql = "
            SELECT p.id, p.name, p.priority, p.description,
                   pbr.product_id, pbr.required_qty, pbr.bundle_price
            FROM promotions p
            INNER JOIN promotion_bundle_rules pbr ON p.id = pbr.promotion_id
            WHERE pbr.product_id IN ($placeholders)
            AND p.is_active = 1
            AND p.promo_type = 'bundle'
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
            ORDER BY p.priority DESC
        ";

        $stmt = $pdo->prepare($bundleSql);
        $stmt->execute($productIds);
        $bundlePromos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 4. COMBO PROMOTIONS
        $comboSql = "
            SELECT p.id, p.name, p.combo_price, p.priority, p.description,
                   GROUP_CONCAT(pci.product_id) as combo_products,
                   GROUP_CONCAT(pci.required_qty) as required_qtys
            FROM promotions p
            INNER JOIN promotion_combo_items pci ON p.id = pci.promotion_id
            WHERE p.is_active = 1
            AND p.promo_type = 'combo'
            AND p.starts_at <= NOW()
            AND p.ends_at >= NOW()
            GROUP BY p.id
            HAVING FIND_IN_SET(?, combo_products) > 0
            ORDER BY p.priority DESC
        ";

        // Check combo cho từng sản phẩm trong cart
        $comboPromos = [];
        foreach ($productIds as $pid) {
            $stmt = $pdo->prepare($comboSql);
            $stmt->execute([$pid]);
            $combos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($combos as $combo) {
                $comboPromos[$combo['id']] = $combo;
            }
        }

        // Group discounts by product
        $discountsByProduct = [];
        foreach ($discountPromos as $promo) {
            $pid = $promo['product_id'];
            if (!isset($discountsByProduct[$pid])) {
                $discountsByProduct[$pid] = [];
            }
            $discountsByProduct[$pid][] = $promo;
        }

        // Group bundles by product
        $bundlesByProduct = [];
        foreach ($bundlePromos as $promo) {
            $pid = $promo['product_id'];
            if (!isset($bundlesByProduct[$pid])) {
                $bundlesByProduct[$pid] = [];
            }
            $bundlesByProduct[$pid][] = $promo;
        }

        // CALCULATE
        $subtotal = 0;
        $totalDiscount = 0;
        $itemDetails = [];
        $giftItems = [];
        $appliedCombos = [];

        // Check COMBO trước (ưu tiên cao)
        foreach ($comboPromos as $combo) {
            $comboProducts = explode(',', $combo['combo_products']);
            $requiredQtys = explode(',', $combo['required_qtys']);

            // Kiểm tra có đủ sản phẩm cho combo không
            $canApplyCombo = true;
            foreach ($comboProducts as $idx => $cpid) {
                $requiredQty = $requiredQtys[$idx] ?? 1;
                $cartItem = array_values(array_filter($cartItems, fn($i) => $i['id'] == $cpid))[0] ?? null;

                if (!$cartItem || $cartItem['quantity'] < $requiredQty) {
                    $canApplyCombo = false;
                    break;
                }
            }

            if ($canApplyCombo) {
                $appliedCombos[] = [
                    'id' => $combo['id'],
                    'name' => $combo['name'],
                    'products' => $comboProducts,
                    'combo_price' => $combo['combo_price']
                ];
            }
        }

        foreach ($cartItems as $item) {
            $productId = $item['id'];
            $price = $item['price'] ?? 0;
            $qty = $item['quantity'] ?? 1;

            $itemSubtotal = $price * $qty;
            $itemDiscount = 0;
            $appliedPromo = null;

            // Kiểm tra trong COMBO
            $inCombo = false;
            foreach ($appliedCombos as $combo) {
                if (in_array($productId, $combo['products'])) {
                    $inCombo = true;
                    // Combo discount sẽ tính riêng
                    break;
                }
            }

            // Nếu không trong combo, áp dụng BUNDLE hoặc DISCOUNT
            if (!$inCombo) {
                // Thu thập TẤT CẢ promotions có thể áp dụng
                $candidatePromos = [];

                // Check BUNDLE
                if (isset($bundlesByProduct[$productId])) {
                    foreach ($bundlesByProduct[$productId] as $bundle) {
                        if ($qty >= $bundle['required_qty']) {
                            $normalPrice = $price * $qty;
                            $bundlePrice = $bundle['bundle_price'];
                            $discount = $normalPrice - $bundlePrice;

                            $candidatePromos[] = [
                                'priority' => $bundle['priority'] ?? 0,
                                'discount' => $discount,
                                'data' => [
                                    'promo_name' => $bundle['name'],
                                    'promo_type' => 'bundle',
                                    'bundle_price' => $bundle['bundle_price'],
                                    'description' => $bundle['description'] ?? ''
                                ]
                            ];
                        }
                    }
                }

                // Check DISCOUNT
                if (isset($discountsByProduct[$productId])) {
                    foreach ($discountsByProduct[$productId] as $promo) {
                        $discount = 0;

                        if ($promo['discount_type'] === 'percentage') {
                            $discount = $itemSubtotal * ($promo['discount_value'] / 100);
                        } elseif ($promo['discount_type'] === 'fixed') {
                            $discount = min($promo['discount_value'] * $qty, $itemSubtotal);
                        }

                        $candidatePromos[] = [
                            'priority' => $promo['priority'] ?? 0,
                            'discount' => $discount,
                            'data' => [
                                'promo_name' => $promo['name'],
                                'promo_type' => 'discount',
                                'discount_type' => $promo['discount_type'],
                                'discount_value' => $promo['discount_value'],
                                'description' => $promo['description'] ?? ''
                            ]
                        ];
                    }
                }

                // Chọn promotion TỐT NHẤT theo: priority DESC, discount DESC
                if (!empty($candidatePromos)) {
                    usort($candidatePromos, function ($a, $b) {
                        // So sánh priority trước
                        if ($a['priority'] != $b['priority']) {
                            return $b['priority'] - $a['priority']; // DESC
                        }
                        // Nếu priority bằng nhau, so sánh discount
                        return $b['discount'] - $a['discount']; // DESC
                    });

                    $best = $candidatePromos[0];
                    $itemDiscount = $best['discount'];
                    $appliedPromo = $best['data'];
                }
            }

            $subtotal += $itemSubtotal;
            $totalDiscount += $itemDiscount;

            $itemDetails[] = [
                'product_id' => $productId,
                'original_price' => $price,
                'quantity' => $qty,
                'subtotal_before_discount' => $itemSubtotal,
                'discount_amount' => $itemDiscount,
                'final_price' => $itemSubtotal - $itemDiscount,
                'applied_promotion' => $appliedPromo
            ];

            // Check GIFT
            foreach ($giftPromos as $giftPromo) {
                if ($giftPromo['trigger_product_id'] == $productId && $qty >= $giftPromo['required_qty']) {
                    $giftItems[] = [
                        'promo_id' => $giftPromo['id'],
                        'promo_name' => $giftPromo['name'],
                        'product_id' => $giftPromo['gift_product_id'],
                        'quantity' => $giftPromo['gift_qty']
                    ];
                }
            }
        }

        // Tính giảm giá COMBO
        foreach ($appliedCombos as $combo) {
            $comboOriginalPrice = 0;
            foreach ($combo['products'] as $cpid) {
                $cartItem = array_values(array_filter($cartItems, fn($i) => $i['id'] == $cpid))[0] ?? null;
                if ($cartItem) {
                    $comboOriginalPrice += $cartItem['price'] * $cartItem['quantity'];
                }
            }

            $comboDiscount = $comboOriginalPrice - $combo['combo_price'];
            $totalDiscount += $comboDiscount;

            // Thêm vào item_details (dạng tổng hợp)
            $itemDetails[] = [
                'product_id' => 0,
                'original_price' => 0,
                'quantity' => 0,
                'subtotal_before_discount' => $comboOriginalPrice,
                'discount_amount' => $comboDiscount,
                'final_price' => $combo['combo_price'],
                'applied_promotion' => [
                    'promo_name' => $combo['name'],
                    'promo_type' => 'combo',
                    'combo_price' => $combo['combo_price']
                ]
            ];
        }

        return [
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
            'grand_total' => max(0, $subtotal - $totalDiscount),
            'item_details' => $itemDetails,
            'gift_items' => $giftItems
        ];
    }
}
