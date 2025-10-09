<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\CustomerRepository;

use App\Controllers\Admin\AuthController;
class CustomerController extends Controller
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }
    /** GET /admin/customers (view) */
    public function index()
    {
        return $this->view('admin/customers/customer');
    }

    /** GET /admin/api/customers (list JSON) */
    public function apiIndex()
    {
        $rows = CustomerRepository::all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Thêm các phương thức store, update, destroy nếu cần
}
