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
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
