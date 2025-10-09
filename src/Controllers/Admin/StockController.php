<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\StockRepository;

use App\Controllers\Admin\AuthController;
class StockController extends Controller
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }
    /** GET /admin/stock (view) */
    public function index()
    {
        return $this->view('admin/stock/stock');
    }

    /** GET /admin/api/stocks (list JSON) */
    public function apiIndex()
    {
        $rows = StockRepository::all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
