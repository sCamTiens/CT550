<?php

namespace App\Controllers\Api;

use App\Support\GHNService;

class GHNApiController
{
    private GHNService $ghn;

    public function __construct()
    {
        $this->ghn = new GHNService();
    }

    /**
     * GET /api/ghn/provinces
     */
    public function getProvinces()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $provinces = $this->ghn->getProvinces();
            echo json_encode(['data' => $provinces], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * GET /api/ghn/districts?province_id=xxx
     */
    public function getDistricts()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $provinceId = $_GET['province_id'] ?? 0;
            if (!$provinceId) {
                throw new \Exception('province_id is required');
            }

            $districts = $this->ghn->getDistricts((int)$provinceId);
            echo json_encode(['data' => $districts], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * GET /api/ghn/wards?district_id=xxx
     */
    public function getWards()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $districtId = $_GET['district_id'] ?? 0;
            if (!$districtId) {
                throw new \Exception('district_id is required');
            }

            $wards = $this->ghn->getWards((int)$districtId);
            echo json_encode(['data' => $wards], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
