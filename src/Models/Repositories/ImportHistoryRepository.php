<?php
namespace App\Models\Repositories;

use App\Core\DB;

class ImportHistoryRepository
{
    public function all()
    {
        $pdo = DB::pdo();
        $sql = "SELECT ih.*, u.full_name AS imported_by_name
                FROM import_history ih
                LEFT JOIN users u ON u.id = ih.imported_by
                ORDER BY ih.imported_at DESC";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByTableName($tableName)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT ih.*, u.full_name AS imported_by_name
                               FROM import_history ih
                               LEFT JOIN users u ON u.id = ih.imported_by
                               WHERE ih.table_name = ?
                               ORDER BY ih.imported_at DESC");
        $stmt->execute([$tableName]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT ih.*, u.full_name AS imported_by_name
                               FROM import_history ih
                               LEFT JOIN users u ON u.id = ih.imported_by
                               WHERE ih.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO import_history
            (table_name, file_name, total_rows, success_rows, failed_rows, status, 
             error_details, file_content, imported_by, imported_by_name, imported_at)
            VALUES (:table_name, :file_name, :total_rows, :success_rows, :failed_rows, :status,
                    :error_details, :file_content, :imported_by, :imported_by_name, NOW())");
        
        $stmt->execute([
            ':table_name' => $data['table_name'],
            ':file_name' => $data['file_name'],
            ':total_rows' => $data['total_rows'],
            ':success_rows' => $data['success_rows'],
            ':failed_rows' => $data['failed_rows'],
            ':status' => $data['status'],
            ':error_details' => $data['error_details'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':imported_by' => $data['imported_by'],
            ':imported_by_name' => $data['imported_by_name'],
        ]);
        
        return $pdo->lastInsertId();
    }

    public function delete($id)
    {
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM import_history WHERE id=?")->execute([$id]);
    }
}
