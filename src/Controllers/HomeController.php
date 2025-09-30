<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Repositories\ProductRepository;

class HomeController extends Controller
{
    public function index()
    {
        $products = (new ProductRepository())->latest(12);
        return $this->view('home/index', compact('products'));
    }
}
