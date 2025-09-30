<?php
// src/Controllers/Admin/DashboardController.php
namespace App\Controllers\Admin;
use App\Core\Controller;

class DashboardController extends BaseAdminController {
  public function index(): mixed {
    return $this->view('admin/index');
  }
}
