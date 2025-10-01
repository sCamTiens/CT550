<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class BrandController extends Controller
{
    /** GET /admin/brands (view) */
    public function index()
    {
        return $this->view('admin/brands/brand');
    }

    /** GET /admin/api/brands (list JSON) */
    public function apiIndex()
    {
        $pdo = \App\Core\DB::pdo();
        $rows = $pdo->query("SELECT b.id, b.name, b.slug, b.created_at, b.updated_at,
                            b.created_by, b.updated_by,
                            u1.full_name AS created_by_name,
                            u2.full_name AS updated_by_name
                     FROM brands b
                     LEFT JOIN users u1 ON u1.id = b.created_by
                     LEFT JOIN users u2 ON u2.id = b.updated_by
                     ORDER BY b.id DESC")
            ->fetchAll(\PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/brands (create) */
    public function store()
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        if ($slug !== null && mb_strlen($slug) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 190 ký tự']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO brands(name,slug,created_by,updated_by)
                                   VALUES(:name,:slug,:created_by,:updated_by)");
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug ?: null,
                ':created_by' => $currentUser,
                ':updated_by' => $currentUser
            ]);
            $id = $pdo->lastInsertId();
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo thương hiệu']);
            exit;
        }
    }

    /** POST /admin/brands/{id} (update) */
    public function update($id)
    {
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $stmt = $pdo->prepare("UPDATE brands 
                                   SET name=:name, slug=:slug, updated_by=:updated_by
                                   WHERE id=:id");
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':slug' => $slug ?: null,
                ':updated_by' => $currentUser
            ]);
            echo json_encode($this->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật thương hiệu']);
            exit;
        }
    }

    /** POST /admin/brands/{id}/delete (delete) */
    /** DELETE /admin/brands/{id} */
    public function destroy($id)
    {
        $pdo = \App\Core\DB::pdo();
        $pdo->prepare("DELETE FROM brands WHERE id=?")->execute([$id]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT b.id, b.name, b.slug, b.created_at, b.updated_at,
                            b.created_by, b.updated_by,
                            u1.full_name AS created_by_name,
                            u2.full_name AS updated_by_name
                     FROM brands b
                     LEFT JOIN users u1 ON u1.id = b.created_by
                     LEFT JOIN users u2 ON u2.id = b.updated_by
                     WHERE b.id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 190) ?: null;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}

