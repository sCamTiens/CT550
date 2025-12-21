<?php

namespace App\Services;

/**
 * Service tính khoảng cách giao hàng
 * - Sử dụng Nominatim (OpenStreetMap) để geocoding (địa chỉ → tọa độ)
 * - Tính khoảng cách bằng công thức Haversine
 * - OpenMap.vn API key được giữ lại cho Distance Matrix (tính phí ship) trong tương lai
 */
class DeliveryDistanceService
{
    private string $apiKey;
    private float $storeLat;
    private float $storeLng;
    private float $maxDeliveryRadius; // km

    public function __construct()
    {
        $this->apiKey = getenv('OPENMAP_KEY') ?: '';

        // Tọa độ cửa hàng (Cần Thơ) - Mặc định: Khu II Trường Đại học Cần Thơ
        $this->storeLat = (float)(getenv('STORE_LATITUDE') ?: '10.03289');
        $this->storeLng = (float)(getenv('STORE_LONGITUDE') ?: '105.769082');

        // Bán kính giao hàng tối đa (km)
        $this->maxDeliveryRadius = (float)(getenv('MAX_DELIVERY_RADIUS_KM') ?: '10');
    }

    /**
     * Kiểm tra địa chỉ có nằm trong vùng giao hàng không
     * 
     * @param array $address Thông tin địa chỉ
     * @return array ['success' => bool, 'distance' => float, 'message' => string]
     */
    public function checkDeliveryArea(array $address): array
    {
        try {
            // Tạo địa chỉ đầy đủ để geocoding
            $fullAddress = $this->buildFullAddress($address);

            // Lấy tọa độ từ địa chỉ
            $coordinates = $this->geocodeAddress($fullAddress);

            if (!$coordinates) {
                // Log để debug
                error_log('[DeliveryDistanceService] Cannot geocode address: ' . $fullAddress);

                return [
                    'success' => false,
                    'distance' => null,
                    'message' => 'Địa chỉ giao hàng hiện chỉ hỗ trợ trong khu vực trung tâm TP. Cần Thơ. Vui lòng kiểm tra và nhập lại địa chỉ.'
                ];
            }

            // Tính khoảng cách
            $distance = $this->calculateDistance(
                $this->storeLat,
                $this->storeLng,
                $coordinates['lat'],
                $coordinates['lng']
            );

            // Kiểm tra có nằm trong bán kính giao hàng
            if ($distance > $this->maxDeliveryRadius) {
                return [
                    'success' => false,
                    'distance' => round($distance, 2),
                    'message' => sprintf(
                        '📍 Địa chỉ giao hàng cách cửa hàng %.1f km, vượt quá bán kính giao hàng %.0f km. Chúng tôi chỉ giao hàng trong bán kính %.0f km từ cửa hàng tại Cần Thơ.',
                        $distance,
                        $this->maxDeliveryRadius,
                        $this->maxDeliveryRadius
                    )
                ];
            }

            return [
                'success' => true,
                'distance' => round($distance, 2),
                'message' => sprintf('Địa chỉ giao hàng hợp lệ (cách %.1f km)', $distance)
            ];
        } catch (\Exception $e) {
            error_log('[DeliveryDistanceService] Error: ' . $e->getMessage());

            // Fallback: Nếu có lỗi API, chỉ check province
            return $this->fallbackProvinceCheck($address);
        }
    }

    /**
     * Tạo địa chỉ đầy đủ từ các thành phần
     */
    private function buildFullAddress(array $address): string
    {
        $parts = array_filter([
            $address['address_line'] ?? $address['line1'] ?? '',
            $address['ward'] ?? $address['ward_name'] ?? '',
            $address['district'] ?? $address['district_name'] ?? '',
            $address['province'] ?? $address['province_name'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    /**
     * Lấy tọa độ GPS từ địa chỉ sử dụng Nominatim (OpenStreetMap)
     * OpenMap.vn được giữ lại cho Distance Matrix API (tính phí ship)
     * 
     * @param string $address Địa chỉ đầy đủ
     * @return array|null ['lat' => float, 'lng' => float]
     */
    private function geocodeAddress(string $address): ?array
    {
        // Nominatim (OpenStreetMap) - Free geocoding API
        $url = 'https://nominatim.openstreetmap.org/search';

        $params = [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'vn', // Chỉ tìm ở Việt Nam
            'addressdetails' => 1
        ];

        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        // Log for debugging
        error_log('[DeliveryDistanceService] Geocoding with Nominatim: ' . $address);

        // Call API with proper User-Agent (required by Nominatim)
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: MiniGo-DeliveryService/1.0\r\nAccept: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($fullUrl, false, $context);

        if ($response === false) {
            error_log('[DeliveryDistanceService] Failed to call Nominatim API');
            return null;
        }

        $data = json_decode($response, true);

        // Nominatim returns array of results
        if (!empty($data) && is_array($data) && isset($data[0])) {
            $firstResult = $data[0];

            // Nominatim format: {lat: string, lon: string}
            if (isset($firstResult['lat']) && isset($firstResult['lon'])) {
                $lat = (float)$firstResult['lat'];
                $lng = (float)$firstResult['lon'];

                error_log(sprintf(
                    '[DeliveryDistanceService] Geocoding SUCCESS: %s → lat=%f, lng=%f',
                    $address,
                    $lat,
                    $lng
                ));

                return [
                    'lat' => $lat,
                    'lng' => $lng
                ];
            }
        }

        error_log('[DeliveryDistanceService] Geocoding FAILED: No results for "' . $address . '"');
        return null;
    }

    /**
     * Tính khoảng cách giữa 2 điểm GPS theo công thức Haversine
     * 
     * @param float $lat1 Vĩ độ điểm 1
     * @param float $lng1 Kinh độ điểm 1
     * @param float $lat2 Vĩ độ điểm 2
     * @param float $lng2 Kinh độ điểm 2
     * @return float Khoảng cách (km)
     */
    private function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371; // Bán kính Trái Đất (km)

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Fallback: Chỉ kiểm tra tỉnh Cần Thơ nếu API lỗi
     */
    private function fallbackProvinceCheck(array $address): array
    {
        $provinceName = $address['province'] ?? '';
        $provinceCode = $address['province_code'] ?? '';

        $isCanTho = false;

        if ($provinceCode == '292' || $provinceCode == '48') {
            $isCanTho = true;
        }

        if (
            stripos($provinceName, 'Cần Thơ') !== false ||
            stripos($provinceName, 'Can Tho') !== false ||
            stripos($provinceName, 'Cantho') !== false
        ) {
            $isCanTho = true;
        }

        if (!$isCanTho) {
            return [
                'success' => false,
                'distance' => null,
                'message' => '📦 Xin lỗi! Hiện tại chúng tôi chỉ giao hàng trong khu vực thành phố Cần Thơ.'
            ];
        }

        // Nếu là Cần Thơ nhưng không biết khoảng cách chính xác
        return [
            'success' => true,
            'distance' => null,
            'message' => 'Địa chỉ giao hàng hợp lệ (trong khu vực Cần Thơ)'
        ];
    }

    /**
     * Get store coordinates
     */
    public function getStoreCoordinates(): array
    {
        return [
            'lat' => $this->storeLat,
            'lng' => $this->storeLng
        ];
    }

    /**
     * Get max delivery radius
     */
    public function getMaxDeliveryRadius(): float
    {
        return $this->maxDeliveryRadius;
    }
}
