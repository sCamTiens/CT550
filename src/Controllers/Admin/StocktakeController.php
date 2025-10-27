<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\StocktakeRepository;

use App\Controllers\Admin\AuthController;
class StocktakeController extends Controller
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }
    /** GET /admin/stocktake (view) */
    public function index()
    {
        return $this->view('admin/stock/stocktake');
    }

    /** GET /admin/api/stocktakes (list JSON) */
    public function apiIndex()
    {
        $rows = StocktakeRepository::all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/stocktakes/create */
    public function apiCreate()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Vui lòng nhập ít nhất 1 sản phẩm'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stocktakeId = StocktakeRepository::create(
                $input['note'] ?? '',
                $userId,
                $input['items']
            );
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'stocktake_id' => $stocktakeId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** GET /admin/api/stocktakes/{id} */
    public function apiDetail($id)
    {
        try {
            error_log("Fetching stocktake detail for ID: " . $id);
            $stocktake = StocktakeRepository::findById($id);
            
            error_log("Stocktake result: " . print_r($stocktake, true));
            
            if (!$stocktake) {
                error_log("Stocktake not found for ID: " . $id);
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Không tìm thấy phiếu kiểm kê'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($stocktake, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log("Exception in apiDetail: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
