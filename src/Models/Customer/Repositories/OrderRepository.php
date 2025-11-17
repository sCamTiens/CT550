<?php
namespace App\Models\Customer\Repositories;

use App\Core\DB;
use PDO;

class OrderRepository
{
    /**
     * Lấy danh sách đơn hàng của khách hàng
     */
    public function getOrders(int $customerId): array
    {
        $sql = "SELECT 
                o.id,
                o.code,
                o.status,
                o.grand_total,
                o.loyalty_points_earned,
                o.created_at,
                o.note,
                o.payment_method,
                o.payment_status,
                o.order_type,
                ua.receiver_name,
                ua.receiver_phone,
                ua.line1 AS delivery_address,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
            FROM orders o
            LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC, o.id DESC";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$customerId]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data
        foreach ($orders as &$order) {
            $order['status_label'] = $this->getStatusLabel($order['status']);
            $order['payment_status_label'] = $this->getPaymentStatusLabel($order['payment_status']);
            $order['payment_method_label'] = $this->getPaymentMethodLabel($order['payment_method']);
        }

        return $orders;
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderById(int $orderId, int $customerId): ?array
    {
        $sql = "SELECT 
                o.id,
                o.code,
                o.status,
                o.grand_total,
                o.subtotal,
                o.discount_amount,
                o.tax_amount,
                o.shipping_fee,
                o.loyalty_points_earned,
                o.loyalty_points_used,
                o.created_at,
                o.note,
                o.payment_method,
                o.payment_status,
                o.order_type,
                ua.receiver_name,
                ua.receiver_phone,
                ua.line1 AS delivery_address,
                ua.line2,
                ua.city,
                ua.district,
                ua.ward
            FROM orders o
            LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
            WHERE o.id = ? AND o.user_id = ?";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$orderId, $customerId]);

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Format labels
        $order['status_label'] = $this->getStatusLabel($order['status']);
        $order['payment_status_label'] = $this->getPaymentStatusLabel($order['payment_status']);
        $order['payment_method_label'] = $this->getPaymentMethodLabel($order['payment_method']);

        // Lấy items
        $order['items'] = $this->getOrderItems($orderId);

        return $order;
    }

    /**
     * Lấy danh sách items của đơn hàng
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT 
                oi.id,
                oi.product_id,
                oi.qty AS quantity,
                oi.unit_price,
                oi.discount,
                oi.tax,
                oi.line_total AS total,
                oi.is_gift,
                p.name AS product_name,
                p.sku,
                p.image_url AS product_image
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC";

        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số đơn hàng của khách hàng
     */
    public function countOrders(int $customerId): int
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$customerId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Lấy nhãn trạng thái đơn hàng
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];

        return $labels[$status] ?? 'Không xác định';
    }

    /**
     * Lấy nhãn trạng thái thanh toán
     */
    private function getPaymentStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại',
            'refunded' => 'Đã hoàn tiền'
        ];

        return $labels[$status] ?? 'Không xác định';
    }

    /**
     * Lấy nhãn phương thức thanh toán
     */
    private function getPaymentMethodLabel(string $method): string
    {
        $labels = [
            'cod' => 'Thanh toán khi nhận hàng',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'vnpay' => 'VNPay'
        ];

        return $labels[$method] ?? 'Không xác định';
    }
}
