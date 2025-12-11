<?php

namespace App\Controllers\Api;

use App\Support\GHNService;

class ShippingController
{
    private GHNService $ghn;

    public function __construct()
    {
        try {
            $this->ghn = new GHNService();
        } catch (\Exception $e) {
            $this->jsonError('GHN Service initialization failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/shipping/provinces
     * Get list of all provinces
     */
    public function getProvinces()
    {
        try {
            $provinces = $this->ghn->getProvinces();
            $this->jsonSuccess($provinces);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * GET /api/shipping/districts?province_id={id}
     * Get districts by province
     */
    public function getDistricts()
    {
        $provinceId = (int)($_GET['province_id'] ?? 0);

        if (!$provinceId) {
            $this->jsonError('province_id is required', 400);
        }

        try {
            $districts = $this->ghn->getDistricts($provinceId);
            $this->jsonSuccess($districts);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * GET /api/shipping/wards?district_id={id}
     * Get wards by district
     */
    public function getWards()
    {
        $districtId = (int)($_GET['district_id'] ?? 0);

        if (!$districtId) {
            $this->jsonError('district_id is required', 400);
        }

        try {
            $wards = $this->ghn->getWards($districtId);
            $this->jsonSuccess($wards);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * POST /api/shipping/calculate-fee
     * Calculate shipping fee
     * 
     * Body: {
     *   "to_district_id": 1442,
     *   "to_ward_code": "21211",
     *   "weight": 1000,
     *   "insurance_value": 100000
     * }
     */
    public function calculateFee()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requiredFields = ['to_district_id', 'to_ward_code', 'weight'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $this->jsonError("Field '$field' is required", 400);
            }
        }

        try {
            $fee = $this->ghn->calculateFee([
                'to_district_id' => (int)$data['to_district_id'],
                'to_ward_code' => (string)$data['to_ward_code'],
                'weight' => (int)$data['weight'],
                'insurance_value' => (int)($data['insurance_value'] ?? 0),
                'service_type_id' => (int)($data['service_type_id'] ?? 2), // 2=Standard
            ]);

            $this->jsonSuccess($fee);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * POST /api/shipping/create-order
     * Create GHN shipping order
     * 
     * Body: {
     *   "order_id": 123,
     *   "to_name": "Nguyen Van A",
     *   "to_phone": "0901234567",
     *   "to_address": "123 Le Loi",
     *   "to_ward_code": "21211",
     *   "to_district_id": 1442,
     *   "weight": 1000,
     *   "cod_amount": 500000,
     *   "items": [...]
     * }
     */
    public function createOrder()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $requiredFields = ['order_id', 'to_name', 'to_phone', 'to_address', 'to_ward_code', 'to_district_id', 'weight'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $this->jsonError("Field '$field' is required", 400);
            }
        }

        try {
            $result = $this->ghn->createOrder([
                'to_name' => $data['to_name'],
                'to_phone' => $data['to_phone'],
                'to_address' => $data['to_address'],
                'to_ward_code' => $data['to_ward_code'],
                'to_district_id' => (int)$data['to_district_id'],
                'weight' => (int)$data['weight'],
                'cod_amount' => (int)($data['cod_amount'] ?? 0),
                'content' => 'Đơn hàng #' . $data['order_id'],
                'note' => $data['note'] ?? '',
                'items' => $data['items'] ?? [],
            ]);

            // Update order with GHN order code
            $this->updateOrderWithGHNCode($data['order_id'], $result['order_code'], $result);

            $this->jsonSuccess($result);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * GET /api/shipping/track/{order_code}
     * Track GHN order
     */
    public function trackOrder($orderCode)
    {
        try {
            $tracking = $this->ghn->getOrderDetail($orderCode);
            $this->jsonSuccess($tracking);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * POST /api/shipping/cancel/{order_code}
     * Cancel GHN order
     */
    public function cancelOrder($orderCode)
    {
        try {
            $result = $this->ghn->cancelOrder($orderCode);
            $this->jsonSuccess($result);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update order with GHN tracking info
     */
    private function updateOrderWithGHNCode(int $orderId, string $ghnOrderCode, array $ghnData)
    {
        $pdo = \App\Core\DB::pdo();

        $stmt = $pdo->prepare("
            UPDATE orders 
            SET ghn_order_code = ?,
                expected_delivery_time = ?,
                ghn_tracking_data = ?
            WHERE id = ?
        ");

        $expectedDelivery = $ghnData['expected_delivery_time'] ?? null;
        $trackingJson = json_encode($ghnData);

        $stmt->execute([$ghnOrderCode, $expectedDelivery, $trackingJson, $orderId]);
    }

    private function jsonSuccess($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $code = 500)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
