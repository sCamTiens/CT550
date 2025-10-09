<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Stocktake;

class StocktakeRepository
{
    public static function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT st.id, st.created_by, u.full_name AS created_by_name, st.created_at, st.note
                FROM stocktakes st
                LEFT JOIN users u ON st.created_by = u.id
                ORDER BY st.created_at DESC, st.id DESC LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Stocktake($row), $rows);
    }
}
