<?php
namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\PromotionRepository;

class PromotionController extends Controller
{
    private PromotionRepository $promotionRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->promotionRepo = new PromotionRepository();
    }

    /**
     * GET /api/promotions/{id} - API lấy chi tiết khuyến mãi
     */
    public function getDetail(Request $request, $id): mixed
    {
        header('Content-Type: application/json');

        try {
            $promotion = $this->promotionRepo->getPromotionDetail((int)$id);

            if (!$promotion) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy khuyến mãi'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'promotion' => $promotion
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải dữ liệu'
            ]);
        }
        
        exit;
    }
}
