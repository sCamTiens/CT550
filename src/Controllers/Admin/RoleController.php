<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\RoleRepository;

use App\Controllers\Admin\AuthController;
class RoleController extends Controller
{
    protected $repo;
    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new RoleRepository();
    }

    public function index()
    {
        $items = $this->repo->all();
        return $this->view('admin/roles/role', compact('items'));
    }
}
