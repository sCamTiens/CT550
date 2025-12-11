<?php

namespace App\Support;

/**
 * GHN (Giao Hàng Nhanh) API Service
 * Documentation: https://api.ghn.vn/home/docs/detail?id=78
 */
class GHNService
{
    private string $token;
    private int $shopId;
    private string $baseUrl;

    public function __construct()
    {
        // Load config if not already loaded
        if (!defined('GHN_TOKEN')) {
            $configFile = __DIR__ . '/../../config/ghn.php';
            if (file_exists($configFile)) {
                require_once $configFile;
            }
        }

        $this->token = defined('GHN_TOKEN') ? GHN_TOKEN : ($_ENV['GHN_TOKEN'] ?? '');
        $this->shopId = defined('GHN_SHOP_ID') ? (int)GHN_SHOP_ID : (int)($_ENV['GHN_SHOP_ID'] ?? 0);
        $this->baseUrl = defined('GHN_API_URL') ? GHN_API_URL : ($_ENV['GHN_API_URL'] ?? 'https://online-gateway.ghn.vn/shiip/public-api/v2');

        if (!$this->token || !$this->shopId) {
            throw new \RuntimeException('GHN_TOKEN and GHN_SHOP_ID must be set in config/ghn.php or .env');
        }
    }

    /**
     * Calculate shipping fee
     * 
     * @param array $params [
     *   'to_district_id' => int,
     *   'to_ward_code' => string,
     *   'weight' => int (grams),
     *   'insurance_value' => int (optional),
     *   'service_type_id' => int (optional, 2=Standard)
     * ]
     * @return array ['total' => int, 'service_fee' => int, ...]
     */
    public function calculateFee(array $params): array
    {
        $url = $this->baseUrl . '/shipping-order/fee';

        $data = array_merge([
            'shop_id' => $this->shopId,
            'service_type_id' => 2, // 2 = Standard, 5 = Express
            'insurance_value' => 0,
            'coupon' => null,
        ], $params);

        return $this->request('POST', $url, $data);
    }

    /**
     * Create shipping order
     * 
     * @param array $orderData
     * @return array ['order_code' => string, 'expected_delivery_time' => string, ...]
     */
    public function createOrder(array $orderData): array
    {
        $url = $this->baseUrl . '/shipping-order/create';

        $data = array_merge([
            'shop_id' => $this->shopId,
            'payment_type_id' => 2, // 1=Shop pays, 2=Customer pays (COD)
            'required_note' => 'KHONGCHOXEMHANG', // Don't allow customer to open package
            'service_type_id' => 2, // Standard delivery
        ], $orderData);

        return $this->request('POST', $url, $data);
    }

    /**
     * Get order details & tracking info
     * 
     * @param string $orderCode GHN order code
     * @return array
     */
    public function getOrderDetail(string $orderCode): array
    {
        $url = $this->baseUrl . '/shipping-order/detail';

        return $this->request('POST', $url, [
            'order_code' => $orderCode
        ]);
    }

    /**
     * Cancel order
     * 
     * @param string $orderCode
     * @return array
     */
    public function cancelOrder(string $orderCode): array
    {
        $url = $this->baseUrl . '/shipping-order/cancel';

        return $this->request('POST', $url, [
            'order_codes' => [$orderCode]
        ]);
    }

    /**
     * Get list of provinces
     * 
     * @return array
     */
    public function getProvinces(): array
    {
        $url = 'https://online-gateway.ghn.vn/shiip/public-api/master-data/province';
        return $this->request('GET', $url);
    }

    /**
     * Get districts by province
     * 
     * @param int $provinceId
     * @return array
     */
    public function getDistricts(int $provinceId): array
    {
        $url = 'https://online-gateway.ghn.vn/shiip/public-api/master-data/district';
        return $this->request('POST', $url, ['province_id' => $provinceId]);
    }

    /**
     * Get wards by district
     * 
     * @param int $districtId
     * @return array
     */
    public function getWards(int $districtId): array
    {
        $url = 'https://online-gateway.ghn.vn/shiip/public-api/master-data/ward';
        return $this->request('POST', $url, ['district_id' => $districtId]);
    }

    /**
     * Get ward details by ward code
     * This will return the ward info including district_id
     * 
     * @param string $wardCode
     * @return array|null
     */
    public function getWardByCode(string $wardCode): ?array
    {
        // GHN API doesn't have direct ward lookup by code
        // We need to get all wards and filter
        // This is not efficient but works as a fallback

        // Alternative: Cache ward->district mapping or use provinces list
        // For now, return null and we'll handle it differently
        return null;
    }

    /**
     * Get available services
     * 
     * @param int $toDistrictId
     * @return array
     */
    public function getAvailableServices(int $toDistrictId): array
    {
        $url = $this->baseUrl . '/shipping-order/available-services';

        return $this->request('POST', $url, [
            'shop_id' => $this->shopId,
            'to_district' => $toDistrictId
        ]);
    }

    /**
     * Make HTTP request to GHN API
     * 
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    private function request(string $method, string $url, array $data = []): array
    {
        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->token,
        ];

        if (isset($data['shop_id'])) {
            $headers[] = 'ShopId: ' . $data['shop_id'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('GHN API Request failed: ' . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !isset($result['code']) || $result['code'] !== 200) {
            $message = $result['message'] ?? 'Unknown error';
            $errorDetail = $result['code_message_value'] ?? '';

            error_log("GHN API ERROR: $message ($errorDetail)");
            error_log("GHN URL: $url");
            error_log("GHN RESPONSE: " . $response);

            // Dịch sơ bộ một số lỗi tiếng Anh phổ biến từ GHN
            if (stripos($message, 'ShopId invalid') !== false) {
                $message = 'ID Shop (Cửa hàng) GHN không hợp lệ (Vui lòng kiểm tra cấu hình .env)';
            } elseif (stripos($message, 'Token invalid') !== false) {
                $message = 'Token GHN không hợp lệ hoặc hết hạn';
            } elseif (stripos($message, 'SortCode') !== false) {
                $message = 'Địa chỉ giao hàng không được hỗ trợ hoặc sai thông tin (Lỗi SortCode)';
            } elseif (stripos($message, 'Weight') !== false && stripos($message, 'limit') !== false) {
                $message = 'Khối lượng gói hàng vượt quá giới hạn cho phép';
            }

            // Chỉ ném về message (thường là tiếng Việt từ GHN)
            throw new \RuntimeException($message);
        }

        return $result['data'] ?? [];
    }

    /**
     * Format address for GHN
     * 
     * @param string $street
     * @param string $ward
     * @param string $district
     * @param string $province
     * @return string
     */
    public static function formatAddress(string $street, string $ward, string $district, string $province): string
    {
        return implode(', ', array_filter([$street, $ward, $district, $province]));
    }
}
