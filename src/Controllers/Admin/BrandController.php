<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\BrandRepository;

use App\Controllers\Admin\AuthController;
class BrandController extends Controller
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }
    /** GET /admin/brands (view) */
    public function index()
    {
        return $this->view('admin/brands/brand');
    }

    /** GET /admin/api/brands (list JSON) */
    public function apiIndex()
    {
        $rows = BrandRepository::all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/brands (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
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
            $brand = BrandRepository::create($name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi tạo thương hiệu']);
            }
            exit;
        }
    }

    /** PUT /admin/brands/{id} (update) */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $brand = BrandRepository::update($id, $name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật thương hiệu']);
            }
            exit;
        }
    }

    /** DELETE /admin/brands/{id} */
    public function destroy($id)
    {
        // Kiểm tra ràng buộc: nếu thương hiệu đã có sản phẩm thì không cho xóa
        if (method_exists(BrandRepository::class, 'canDelete') ? !BrandRepository::canDelete($id) : $this->brandHasProducts($id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xóa, thương hiệu đang bị ràng buộc với sản phẩm.']);
            exit;
        }
        try {
            BrandRepository::delete($id);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi xóa thương hiệu']);
        }
        exit;
    }

    // Helper: fallback nếu chưa có canDelete trong BrandRepository
    private function brandHasProducts($id)
    {
        $pdo = \App\Core\DB::pdo();
        $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $count->execute([$id]);
        return $count->fetchColumn() > 0;
    }

    // ====== Helper Methods ======

    /** Convert Brand entity or array to plain array */
    private function entityToArray($brand)
    {
        if (is_array($brand)) {
            return array_map([$this, 'entityToArray'], $brand);
        }
        if (!is_object($brand)) {
            return $brand;
        }
        return get_object_vars($brand);
    }

    /** Chuyển text thành slug */
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

    /** Lấy ID user hiện tại từ session */
    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
