<?php
namespace App\Controllers\Admin;
use App\Core\Controller;

use App\Controllers\Admin\AuthController;
class DashboardController extends BaseAdminController {
    public function __construct() {
        AuthController::requirePasswordChanged();
    }
    public function index(): mixed {
        return $this->view('admin/index');
    }
}
