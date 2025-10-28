<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\RoleRepository;

class RoleController extends BaseAdminController
{
    protected $repo;
    public function __construct()
    {
        parent::__construct();
        $this->repo = new RoleRepository();
    }

    public function index()
    {
        $items = $this->repo->all();
        return $this->view('admin/roles/role', compact('items'));
    }
}
