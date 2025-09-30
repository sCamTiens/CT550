<?php
namespace App\Models\Repositories;
use App\Core\DB;

class ProductRepository
{
    public function latest($limit = 12)
    {
        $stmt = DB::pdo()->prepare("SELECT id,slug,name,price FROM products WHERE is_active=1 ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, (int) $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function findBySlug($slug)
    {
        $st = DB::pdo()->prepare("SELECT * FROM products WHERE slug=?");
        $st->execute([$slug]);
        return $st->fetch();
    }
}
