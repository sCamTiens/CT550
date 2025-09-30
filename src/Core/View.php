<?php
namespace App\Core;
class View {
    public static function render($tpl, $data=[]){
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__,2)."/views/{$tpl}.php";
        include dirname(__DIR__,2)."/views/layouts/main.php"; // layout nhận $viewFile
    }
}
