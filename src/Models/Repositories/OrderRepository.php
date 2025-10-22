<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Order;
use App\Support\Auditable;

class OrderRepository
{
    use Auditable;

    /**
     * Lấy toàn bộ danh sách đơn hàng
     */
    public function all(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT o.id, o.code, o.user_id AS payer_user_id, o.order_type, o.status,
                o.subtotal, 
                o.discount_total AS discount_amount, 
                o.shipping_fee, 
                o.cod_amount, 
                o.grand_total AS total_amount,
                o.coupon_code,
                o.shipping_address_id,
                o.note,
                o.created_at, o.updated_at,
                o.created_by, cu.full_name AS created_by_name,
                o.updated_by, uu.full_name AS updated_by_name,
                u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                p.id AS payment_id, p.method AS payment_method, 
                o.payment_status
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            ORDER BY o.id DESC
            LIMIT 500
        ";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Order($row), $rows);
    }

    /**
     * Tìm đơn hàng theo ID
     */
    public function findOne(int $id): ?Order
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT o.id, o.code, o.user_id AS payer_user_id, o.order_type, o.status,
                o.subtotal, 
                o.discount_total AS discount_amount, 
                o.shipping_fee, 
                o.cod_amount, 
                o.grand_total AS total_amount,
                o.coupon_code,
                o.shipping_address_id,
                o.note,
                o.created_at, o.updated_at,
                o.created_by, cu.full_name AS created_by_name,
                o.updated_by, uu.full_name AS updated_by_name,
                u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
                p.id AS payment_id, p.method AS payment_method, 
                o.payment_status
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN users cu ON cu.id = o.created_by
            LEFT JOIN users uu ON uu.id = o.updated_by
            LEFT JOIN payments p ON p.id = o.payment_id
            WHERE o.id = ?
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? new Order($row) : null;
    }

    /**
     * Tạo mã đơn hàng tự động
     */
    public function generateCode(): string
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM orders");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'DH' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo đơn hàng mới
     */
    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        try {
            error_log("=== OrderRepository::create START ===");
            error_log("Data received: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            error_log("Current user: " . $currentUser);
            
            // Validate required fields
            if (empty($data['code'])) {
                throw new \Exception('Mã đơn hàng không được để trống');
            }
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new \Exception('Đơn hàng phải có ít nhất 1 sản phẩm');
            }
            
            $pdo->beginTransaction();

            // Map payment method
            $paymentMethodMap = [
                'cash' => 'Tiền mặt',
                'credit_card' => 'Quẹt thẻ',
                'bank_transfer' => 'Chuyển khoản'
            ];
            $paymentMethod = $paymentMethodMap[$data['payment_method'] ?? 'cash'] ?? 'Tiền mặt';
            error_log("Payment method: " . $paymentMethod);

            // Tạo payment nếu cần
            $paymentId = null;
            if (!empty($data['payment_method'])) {
                error_log("Creating payment record...");
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (method, amount, created_at)
                    VALUES (:method, :amount, NOW())
                ");
                $stmtPay->execute([
                    ':method' => $paymentMethod,
                    ':amount' => $data['total_amount'] ?? 0,
                ]);
                $paymentId = (int) $pdo->lastInsertId();
                error_log("Payment ID created: " . $paymentId);
            }

            // Xử lý user_id - cho phép null (khách vãng lai)
            // Frontend có thể gửi customer_id hoặc payer_user_id
            $customerId = $data['customer_id'] ?? $data['payer_user_id'] ?? null;
            error_log("Customer ID: " . ($customerId ?? 'NULL'));

            // Tạo đơn hàng - SỬ DỤNG ĐÚNG TÊN CỘT DATABASE
            $sql = "
                INSERT INTO orders
                (code, user_id, order_type, status, payment_id, payment_method, payment_status,
                subtotal, discount_total, shipping_fee, cod_amount, grand_total,
                coupon_code, shipping_address_id, note,
                created_by, created_at)
                VALUES
                (:code, :user_id, :order_type, :status, :payment_id, :payment_method, :payment_status,
                :subtotal, :discount_total, :shipping_fee, :cod_amount, :grand_total,
                :coupon_code, :shipping_address_id, :note,
                :created_by, NOW())
            ";
            
            error_log("SQL: " . $sql);
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':code' => $data['code'],
                ':user_id' => $customerId,
                ':order_type' => 'Offline',
                ':status' => 'Hoàn tất',
                ':payment_id' => $paymentId,
                ':payment_method' => $paymentMethod,
                ':payment_status' => 'Đã thanh toán',
                ':subtotal' => $data['subtotal'] ?? 0,
                // QUAN TRỌNG: Map discount_amount từ frontend -> discount_total trong DB
                ':discount_total' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => 0,
                ':cod_amount' => 0,
                // QUAN TRỌNG: Map total_amount từ frontend -> grand_total trong DB
                ':grand_total' => $data['total_amount'] ?? 0,
                ':coupon_code' => $data['coupon_code'] ?? null,
                ':shipping_address_id' => null,
                ':note' => $data['note'] ?? null,
                ':created_by' => $currentUser,
            ];
            
            error_log("Executing order INSERT with params: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            
            try {
                $stmt->execute($params);
                $id = (int) $pdo->lastInsertId();
                error_log("Order created with ID: " . $id);
            } catch (\PDOException $e) {
                error_log("PDO ERROR in INSERT: " . $e->getMessage());
                error_log("Error Code: " . $e->getCode());
                error_log("SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
                throw $e;
            }

            // Kiểm tra tồn kho trước khi lưu order items
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                    $qtyNeeded = isset($item['qty']) ? (int) $item['qty'] : 0;

                    if ($productId <= 0 || $qtyNeeded <= 0) {
                        continue;
                    }

                    // Lấy tồn kho hiện tại
                    $stmtCheck = $pdo->prepare("
                        SELECT p.name, COALESCE(s.qty, 0) as current_stock 
                        FROM products p
                        LEFT JOIN stocks s ON s.product_id = p.id
                        WHERE p.id = ?
                    ");
                    $stmtCheck->execute([$productId]);
                    $product = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

                    if (!$product) {
                        throw new \Exception("Không tìm thấy sản phẩm với ID: {$productId}");
                    }

                    $currentStock = (int) $product['current_stock'];

                    if ($currentStock < $qtyNeeded) {
                        throw new \Exception(
                            "Sản phẩm '{$product['name']}' không đủ tồn kho. " .
                            "Tồn kho hiện tại: {$currentStock}, yêu cầu: {$qtyNeeded}"
                        );
                    }
                }
            }

            // Lưu order items và trừ tồn kho
            if (!empty($data['items']) && is_array($data['items'])) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, qty, unit_price, discount, tax, line_total)
                    VALUES (:order_id, :product_id, :qty, :unit_price, :discount, :tax, :line_total)
                ");

                $stmtUpdateStock = $pdo->prepare("
                    UPDATE stocks SET qty = qty - :qty WHERE product_id = :product_id
                ");

                foreach ($data['items'] as $item) {
                    $qty = (int) ($item['qty'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $discount = (float) ($item['discount'] ?? 0);
                    $tax = (float) ($item['tax'] ?? 0);
                    $lineTotal = ($qty * $unitPrice) - $discount + $tax;

                    $stmtItem->execute([
                        ':order_id' => $id,
                        ':product_id' => $item['product_id'],
                        ':qty' => $qty,
                        ':unit_price' => $unitPrice,
                        ':discount' => $discount,
                        ':tax' => $tax,
                        ':line_total' => $lineTotal,
                    ]);

                    // Trừ tồn kho từ bảng stocks
                    $stmtUpdateStock->execute([
                        ':qty' => $qty,
                        ':product_id' => $item['product_id']
                    ]);

                    // Ghi log stock movement
                    $stmtMovement = $pdo->prepare("
                        INSERT INTO stock_movements 
                        (product_id, type, ref_type, ref_id, qty, note, created_at)
                        VALUES (:product_id, 'Xuất kho', 'Đơn hàng', :ref_id, :qty, :note, NOW())
                    ");
                    $stmtMovement->execute([
                        ':product_id' => $item['product_id'],
                        ':ref_id' => $id,
                        ':qty' => -$qty,
                        ':note' => "Xuất kho cho đơn hàng {$data['code']}"
                    ]);
                }
            }

            // Tự động tạo phiếu thu
            error_log("Creating receipt voucher...");
            $receiptCode = $this->generateReceiptCode($pdo);
            error_log("Receipt code: " . $receiptCode);
            
            try {
                $stmtReceipt = $pdo->prepare("
                    INSERT INTO receipt_vouchers
                    (code, payer_user_id, order_id, method, txn_ref, received_at, amount, received_by, note, bank_time, created_by, created_at)
                    VALUES
                    (:code, :payer_user_id, :order_id, :method, :txn_ref, NOW(), :amount, :received_by, :note, NULL, :created_by, NOW())
                ");
                $stmtReceipt->execute([
                    ':code' => $receiptCode,
                    ':payer_user_id' => $customerId,
                    ':order_id' => $id,
                    ':method' => $paymentMethod,
                    ':txn_ref' => $data['code'],
                    ':amount' => $data['total_amount'] ?? 0,
                    ':received_by' => $currentUser,
                    ':note' => 'Phiếu thu tự động từ đơn hàng ' . $data['code'],
                    ':created_by' => $currentUser,
                ]);
                error_log("Receipt voucher created successfully");
            } catch (\PDOException $e) {
                error_log("PDO ERROR creating receipt: " . $e->getMessage());
                throw $e;
            }

            // Tự động tạo phiếu xuất kho
            error_log("Creating stock out...");
            $stockOutCode = $this->generateStockOutCode($pdo);
            error_log("Stock out code: " . $stockOutCode);
            
            try {
                $stmtStockOut = $pdo->prepare("
                    INSERT INTO stock_outs
                    (code, type, order_id, status, out_date, total_amount, note, created_by, created_at)
                    VALUES
                    (:code, 'sale', :order_id, 'completed', NOW(), :total_amount, :note, :created_by, NOW())
                ");
                $stmtStockOut->execute([
                    ':code' => $stockOutCode,
                    ':order_id' => $id,
                    ':total_amount' => $data['total_amount'] ?? 0,
                    ':note' => 'Phiếu xuất kho tự động từ đơn hàng ' . $data['code'],
                    ':created_by' => $currentUser,
                ]);
                $stockOutId = (int) $pdo->lastInsertId();
                error_log("Stock out created with ID: " . $stockOutId);

                // Thêm chi tiết phiếu xuất kho (stock_out_items) với logic FEFO + FIFO
                if (!empty($data['items']) && is_array($data['items'])) {
                    $stmtStockOutItem = $pdo->prepare("
                        INSERT INTO stock_out_items
                        (stock_out_id, product_id, batch_id, qty, unit_price, total_price, note, created_at)
                        VALUES
                        (:stock_out_id, :product_id, :batch_id, :qty, :unit_price, :total_price, :note, NOW())
                    ");

                    foreach ($data['items'] as $item) {
                        $productId = $item['product_id'];
                        $qtyNeeded = $item['qty'];
                        $unitPrice = $item['unit_price'];

                        error_log("Processing stock-out item for product_id=$productId, qty=$qtyNeeded");

                        // Lấy danh sách lô hàng theo thứ tự ưu tiên:
                        // 1. Hàng sắp hết hạn (exp_date gần nhất, còn hạn)
                        // 2. Hàng nhập trước (mfg_date cũ nhất)
                        // 3. Có tồn kho > 0 (current_qty trong product_batches)
                        $stmtBatches = $pdo->prepare("
                            SELECT pb.id, pb.batch_code, pb.mfg_date, pb.exp_date, pb.current_qty
                            FROM product_batches pb
                            WHERE pb.product_id = :product_id 
                            AND pb.current_qty > 0
                            ORDER BY 
                                CASE 
                                    WHEN pb.exp_date IS NOT NULL AND pb.exp_date >= CURDATE() 
                                    THEN pb.exp_date 
                                    ELSE '9999-12-31' 
                                END ASC,
                                pb.mfg_date ASC,
                                pb.id ASC
                        ");
                        $stmtBatches->execute([':product_id' => $productId]);
                        $batches = $stmtBatches->fetchAll(\PDO::FETCH_ASSOC);

                        error_log("Found " . count($batches) . " batches for product_id=$productId");

                        $qtyRemaining = $qtyNeeded;
                        foreach ($batches as $batch) {
                            if ($qtyRemaining <= 0) break;

                            $batchId = $batch['id'];
                            $batchCode = $batch['batch_code'];
                            $currentQty = $batch['current_qty'];

                            // Lấy số lượng từ lô này (không vượt quá tồn kho và số lượng cần)
                            $qtyFromBatch = min($qtyRemaining, $currentQty);
                            $totalPrice = $qtyFromBatch * $unitPrice;

                            error_log("Taking $qtyFromBatch from batch_id=$batchId (batch_code=$batchCode, current_qty=$currentQty)");

                            // Thêm vào stock_out_items
                            $stmtStockOutItem->execute([
                                ':stock_out_id' => $stockOutId,
                                ':product_id' => $productId,
                                ':batch_id' => $batchId,
                                ':qty' => $qtyFromBatch,
                                ':unit_price' => $unitPrice,
                                ':total_price' => $totalPrice,
                                ':note' => "Lô: $batchCode"
                            ]);

                            $qtyRemaining -= $qtyFromBatch;
                        }

                        if ($qtyRemaining > 0) {
                            error_log("WARNING: Not enough stock for product_id=$productId, remaining=$qtyRemaining");
                        }
                    }
                    error_log("Stock out items created successfully");
                }

                error_log("Stock out created successfully");
            } catch (\PDOException $e) {
                error_log("PDO ERROR creating stock out: " . $e->getMessage());
                throw $e;
            }

            $pdo->commit();
            error_log("Transaction committed successfully");

            // Log audit
            $this->logCreate('orders', $id, [
                'code' => $data['code'],
                'payer_user_id' => $customerId,
                'order_type' => 'Offline',
                'status' => 'Hoàn tất',
                'payment_method' => $paymentMethod,
                'payment_status' => 'Đã thanh toán',
                'subtotal' => $data['subtotal'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0
            ]);

            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cập nhật đơn hàng
     */
    public function update(int $id, array $data, int $currentUser): void
    {
        $beforeOrder = $this->findOne($id);
        $beforeArray = null;
        if ($beforeOrder) {
            $beforeArray = [
                'payer_user_id' => $beforeOrder->customer_id,
                'status' => $beforeOrder->status,
                'payment_method' => $beforeOrder->payment_method,
                'payment_status' => $beforeOrder->payment_status,
                'subtotal' => $beforeOrder->subtotal,
                'discount_amount' => $beforeOrder->discount_amount,
                'total_amount' => $beforeOrder->total_amount
            ];
        }

        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            // Map payment method
            $paymentMethodMap = [
                'cash' => 'Tiền mặt',
                'credit_card' => 'Quẹt thẻ',
                'bank_transfer' => 'Chuyển khoản'
            ];
            $paymentMethod = $paymentMethodMap[$data['payment_method'] ?? 'cash'] ?? 'Tiền mặt';

            // Cập nhật payment nếu có
            if (isset($data['payment_id']) && $data['payment_id']) {
                $stmtPay = $pdo->prepare("
                    UPDATE payments SET 
                        method = :method,
                        amount = :amount
                    WHERE id = :id
                ");
                $stmtPay->execute([
                    ':id' => $data['payment_id'],
                    ':method' => $paymentMethod,
                    ':amount' => $data['total_amount'] ?? 0,
                ]);
            }

            // Xử lý user_id - cho phép null (khách vãng lai)
            // Frontend có thể gửi customer_id hoặc payer_user_id
            $customerId = $data['customer_id'] ?? $data['payer_user_id'] ?? null;

            // Cập nhật đơn hàng - SỬ DỤNG ĐÚNG TÊN CỘT DATABASE
            $stmt = $pdo->prepare("
                UPDATE orders SET 
                    user_id = :user_id,
                    order_type = :order_type,
                    status = :status,
                    payment_method = :payment_method,
                    payment_status = :payment_status,
                    subtotal = :subtotal,
                    discount_total = :discount_total,
                    shipping_fee = :shipping_fee,
                    cod_amount = :cod_amount,
                    grand_total = :grand_total,
                    coupon_code = :coupon_code,
                    shipping_address_id = :shipping_address_id,
                    note = :note,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id,
                ':user_id' => $customerId,
                ':order_type' => 'Offline',
                ':status' => 'Hoàn tất',
                ':payment_method' => $paymentMethod,
                ':payment_status' => 'Đã thanh toán',
                ':subtotal' => $data['subtotal'] ?? 0,
                // QUAN TRỌNG: Map discount_amount -> discount_total
                ':discount_total' => $data['discount_amount'] ?? 0,
                ':shipping_fee' => 0,
                ':cod_amount' => 0,
                // QUAN TRỌNG: Map total_amount -> grand_total
                ':grand_total' => $data['total_amount'] ?? 0,
                ':coupon_code' => $data['coupon_code'] ?? null,
                ':shipping_address_id' => null,
                ':note' => $data['note'] ?? null,
                ':updated_by' => $currentUser,
            ]);

            // Cập nhật order items
            if (isset($data['items']) && is_array($data['items'])) {
                // Lấy các items cũ để hoàn lại tồn kho
                $stmtOldItems = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
                $stmtOldItems->execute([$id]);
                $oldItems = $stmtOldItems->fetchAll(\PDO::FETCH_ASSOC);

                // Hoàn lại tồn kho cho các sản phẩm cũ
                $stmtRestoreStock = $pdo->prepare("UPDATE stocks SET qty = qty + :qty WHERE product_id = :product_id");
                foreach ($oldItems as $oldItem) {
                    $stmtRestoreStock->execute([
                        ':qty' => $oldItem['qty'],
                        ':product_id' => $oldItem['product_id']
                    ]);
                }

                // Xóa các items cũ
                $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);

                // Thêm các items mới và trừ tồn kho
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, qty, unit_price, discount, tax, line_total)
                    VALUES (:order_id, :product_id, :qty, :unit_price, :discount, :tax, :line_total)
                ");

                $stmtUpdateStock = $pdo->prepare("UPDATE stocks SET qty = qty - :qty WHERE product_id = :product_id");

                foreach ($data['items'] as $item) {
                    $qty = (int) ($item['qty'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $discount = (float) ($item['discount'] ?? 0);
                    $tax = (float) ($item['tax'] ?? 0);
                    $lineTotal = ($qty * $unitPrice) - $discount + $tax;

                    $stmtItem->execute([
                        ':order_id' => $id,
                        ':product_id' => $item['product_id'],
                        ':qty' => $qty,
                        ':unit_price' => $unitPrice,
                        ':discount' => $discount,
                        ':tax' => $tax,
                        ':line_total' => $lineTotal,
                    ]);

                    $stmtUpdateStock->execute([
                        ':qty' => $qty,
                        ':product_id' => $item['product_id']
                    ]);
                }

                // Cập nhật phiếu thu nếu có
                $stmtUpdateReceipt = $pdo->prepare("
                    UPDATE receipt_vouchers 
                    SET payer_user_id = :payer_user_id, 
                        method = :method, 
                        amount = :amount,
                        note = :note,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE order_id = :order_id
                ");
                $stmtUpdateReceipt->execute([
                    ':payer_user_id' => $customerId,
                    ':method' => $paymentMethod,
                    ':amount' => $data['total_amount'] ?? 0,
                    ':note' => 'Phiếu thu từ đơn hàng ' . ($data['code'] ?? ''),
                    ':updated_by' => $currentUser,
                    ':order_id' => $id
                ]);

                // Cập nhật phiếu xuất kho nếu có
                $stmtUpdateStockOut = $pdo->prepare("
                    UPDATE stock_outs 
                    SET total_amount = :total_amount,
                        note = :note,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE order_id = :order_id
                ");
                $stmtUpdateStockOut->execute([
                    ':total_amount' => $data['total_amount'] ?? 0,
                    ':note' => 'Phiếu xuất kho từ đơn hàng ' . ($data['code'] ?? ''),
                    ':updated_by' => $currentUser,
                    ':order_id' => $id
                ]);
            }

            $pdo->commit();

            // Log audit
            if ($beforeArray) {
                $afterArray = [
                    'payer_user_id' => $customerId,
                    'status' => 'Hoàn tất',
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'Đã thanh toán',
                    'subtotal' => $data['subtotal'] ?? 0,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'total_amount' => $data['total_amount'] ?? 0
                ];
                $this->logUpdate('orders', $id, $beforeArray, $afterArray);
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa đơn hàng
     */
    public function delete(int $id): void
    {
        $beforeOrder = $this->findOne($id);
        $beforeArray = null;
        if ($beforeOrder) {
            $beforeArray = [
                'code' => $beforeOrder->code,
                'payer_user_id' => $beforeOrder->customer_id,
                'status' => $beforeOrder->status,
                'payment_method' => $beforeOrder->payment_method,
                'total_amount' => $beforeOrder->total_amount
            ];
        }

        $pdo = DB::pdo();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT payment_id FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $paymentId = $stmt->fetchColumn();

            $stmtItems = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

            $stmtRestoreStock = $pdo->prepare("UPDATE stocks SET qty = qty + :qty WHERE product_id = :product_id");
            foreach ($items as $item) {
                $stmtRestoreStock->execute([
                    ':qty' => $item['qty'],
                    ':product_id' => $item['product_id']
                ]);
            }

            $pdo->prepare("DELETE FROM receipt_vouchers WHERE order_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM stock_outs WHERE order_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);

            if ($paymentId) {
                $pdo->prepare("DELETE FROM payments WHERE id = ?")->execute([$paymentId]);
            }

            $pdo->commit();

            if ($beforeArray) {
                $this->logDelete('orders', $id, $beforeArray);
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lấy danh sách đơn hàng chưa thanh toán
     */
    public function unpaid(): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT o.id, o.code, o.user_id, u.full_name AS customer_name, o.grand_total
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN payments p ON p.id = o.payment_id
            WHERE (o.payment_status IS NULL) OR (o.payment_status != 'Đã thanh toán')
            ORDER BY o.id DESC
        ";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function getOrderItems(int $orderId): array
    {
        $pdo = DB::pdo();
        $sql = "
            SELECT 
                oi.*,
                p.name as product_name, 
                p.sku as product_sku
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Tạo mã phiếu thu tự động
     */
    private function generateReceiptCode($pdo): string
    {
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM receipt_vouchers");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'PT-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Tạo mã phiếu xuất kho tự động
     */
    private function generateStockOutCode($pdo): string
    {
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM stock_outs");
        $maxId = $stmt->fetchColumn() ?: 0;
        $nextId = $maxId + 1;
        return 'XK' . date('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }
}