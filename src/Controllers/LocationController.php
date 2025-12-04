<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DB;
use PDO;

class LocationController extends Controller
{
    /**
     * Get all provinces
     */
    public function getProvinces(Request $request): void
    {
        header('Content-Type: application/json');
        
        try {
            $stmt = DB::pdo()->prepare("
                SELECT id, province_code, name, short_name, code
                FROM provinces
                ORDER BY name ASC
            ");
            $stmt->execute();
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $provinces
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải danh sách tỉnh/thành phố'
            ]);
        }
        exit;
    }

    /**
     * Get wards by province code
     */
    public function getWards(Request $request): void
    {
        header('Content-Type: application/json');
        
        try {
            $provinceCode = $_GET['province_code'] ?? null;
            
            if (empty($provinceCode)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Thiếu mã tỉnh/thành phố'
                ]);
                exit;
            }
            
            $stmt = DB::pdo()->prepare("
                SELECT id, ward_code, name, province_code
                FROM wards
                WHERE province_code = :province_code
                ORDER BY name ASC
            ");
            $stmt->execute(['province_code' => $provinceCode]);
            $wards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $wards
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải danh sách phường/xã'
            ]);
        }
        exit;
    }
}
