<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\UnitRepository;

use App\Controllers\Admin\AuthController;
class UnitController extends \App\Core\Controller
{
    private $unitRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->unitRepo = new UnitRepository();
    }
    /** GET /admin/units (view) */
    public function index()
    {
        return $this->view('admin/units/unit');
    }

    /** GET /admin/api/units (list JSON) */
    public function apiIndex()
    {
        header('Content-Type: application/json; charset=utf-8');
        $items = $this->unitRepo->all();
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/units (create) */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
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
            $id = $this->unitRepo->create($name, $slug, $currentUserId);
            $row = $this->unitRepo->findOne($id);
            $row->created_by_name = $currentUserName;
            $row->updated_by_name = $currentUserName;
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
            $this->unitRepo->update($id, $name, $slug, $currentUserId);
            $row = $this->unitRepo->findOne($id);
            $row->updated_by_name = $currentUserName;
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
        try {
            $this->unitRepo->delete($id);
            echo json_encode(['ok' => true]);
        } catch (\RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
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

    // findOne đã chuyển sang UnitRepository

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
