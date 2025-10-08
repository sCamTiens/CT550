<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class UnitController extends Controller
{
    /** GET /admin/units (view) */
    public function index()
    {
        return $this->view('admin/units/unit');
    }

    /** GET /admin/api/units (list JSON) */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = \App\Core\DB::pdo();

        $sql = "SELECT u.id, u.name, u.slug,
                       u.created_at, u.updated_at,
                       u.created_by, cu.full_name AS created_by_name,
                       u.updated_by, uu.full_name AS updated_by_name
                FROM units u
                LEFT JOIN users cu ON cu.id = u.created_by
                LEFT JOIN users uu ON uu.id = u.updated_by
                ORDER BY u.id DESC";
        $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/units (create) */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');

        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        // validate
        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        if ($slug !== null && mb_strlen($slug) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 250 ký tự']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO units
                (name, slug, created_by, updated_by, created_at, updated_at)
                VALUES (:name, :slug, :created_by, :updated_by, NOW(), NOW())");
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug ?: null,
                ':created_by' => $currentUserId,
                ':updated_by' => $currentUserId,
            ]);
            $id = $pdo->lastInsertId();

            $row = $this->findOne($id);
            $row['created_by_name'] = $currentUserName;
            $row['updated_by_name'] = $currentUserName;

            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo đơn vị']);
            exit;
        }
    }

    /** PUT /admin/units/{id} */
    public function update($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = \App\Core\DB::pdo();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');

        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $stmt = $pdo->prepare("UPDATE units
                SET name=:name, slug=:slug, 
                    updated_by=:updated_by, updated_at=NOW()
                WHERE id=:id");
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':slug' => $slug ?: null,
                ':updated_by' => $currentUserId,
            ]);

            $row = $this->findOne($id);
            $row['updated_by_name'] = $currentUserName;

            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật đơn vị']);
            exit;
        }
    }

    /** DELETE /admin/units/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = \App\Core\DB::pdo();

        try {
            $st = $pdo->prepare("DELETE FROM units WHERE id=?");
            $st->execute([$id]);
            echo json_encode(['ok' => true]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Lỗi máy chủ khi xoá',
                'detail' => $e->getMessage(),
                'code' => $e->getCode(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** Helper: lấy 1 bản ghi */
    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT u.id, u.name, u.slug,
                                    u.created_at, u.updated_at,
                                    u.created_by, cu.full_name AS created_by_name,
                                    u.updated_by, uu.full_name AS updated_by_name
                             FROM units u
                             LEFT JOIN users cu ON cu.id = u.created_by
                             LEFT JOIN users uu ON uu.id = u.updated_by
                             WHERE u.id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        if (class_exists('\Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        }
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 250) ?: null;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['admin_user']['id'] ?? null;
    }

    private function currentUserName(): ?string
    {
        return $_SESSION['admin_user']['full_name'] ?? null;
    }
}
