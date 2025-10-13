<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\CustomerRepository;
use App\Controllers\Admin\AuthController;

class CustomerController extends Controller
{
    private CustomerRepository $repo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->repo = new CustomerRepository();
    }

    /** Trang quản lý khách hàng */
    public function index(): string
    {
        return $this->view('admin/customers/customer');
    }

    /** API: danh sách khách hàng */
    public function apiIndex(): void
    {
        try {
            $items = $this->repo->all();
            $this->json(['items' => $items]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Không thể tải danh sách khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: tạo khách hàng */
    public function store(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = $this->validateInput($payload, true);
        if (!empty($errors)) {
            $this->json(['error' => $errors[0]], 422);
        }

        $payload['created_by'] = $this->currentUserId();
        $payload['updated_by'] = $this->currentUserId();

        try {
            $created = $this->repo->create($payload);

            if ($created === false) {
                $this->json(['error' => 'Không thể tạo khách hàng'], 500);
            }

            if (is_string($created)) {
                $this->json(['error' => $created], 422); // báo chi tiết lỗi
            }

            $this->json($created, 201);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi tạo khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: cập nhật khách hàng */
    public function update($id): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = $this->validateInput($payload, false);
        if (!empty($errors)) {
            $this->json(['error' => $errors[0]], 422);
        }

        $payload['updated_by'] = $this->currentUserId();

        try {
            $updated = $this->repo->update($id, $payload);
            if ($updated === false) {
                $this->json(['error' => 'Không thể cập nhật khách hàng'], 404);
            }
            $this->json($updated);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi cập nhật khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /** API: xoá khách hàng */
    public function destroy($id): void
    {
        try {
            $deleted = $this->repo->delete($id);
            if (!$deleted) {
                $this->json(['error' => 'Không thể xoá khách hàng'], 404);
            }
            $this->json(['ok' => true]);
        } catch (\PDOException $e) {
            $this->json([
                'error' => 'Lỗi cơ sở dữ liệu khi xoá khách hàng',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateInput(array $data, bool $isCreate): array
    {
        $errors = [];

        $username = trim($data['username'] ?? '');
        if ($isCreate && $username === '') {
            $errors[] = 'Tài khoản không được để trống';
        }

        $fullName = trim($data['full_name'] ?? '');
        if ($fullName === '') {
            $errors[] = 'Họ tên không được để trống';
        }

        $email = trim($data['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }

        $phone = trim($data['phone'] ?? '');
        if ($phone !== '' && !preg_match('/^0\d{9,10}$/', $phone)) {
            $errors[] = 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
        }

        if ($isCreate) {
            $password = trim($data['password'] ?? '');
            if ($password !== '' && strlen($password) < 6) {
                $errors[] = 'Mật khẩu phải ít nhất 6 ký tự';
            }
        }

        return $errors;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * API: Đổi mật khẩu khách hàng
     * PUT /admin/api/customers/{id}/password
     */
    public function changePassword($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $password = trim($data['password'] ?? '');
        if ($password === '' || strlen($password) < 8) {
            return $this->json(['error' => 'Mật khẩu phải ít nhất 8 ký tự'], 422);
        }
        try {
            $ok = $this->repo->changePassword($id, $password);
            if ($ok) {
                $this->json(['ok' => true]);
            } else {
                $this->json(['error' => 'Không thể đổi mật khẩu'], 500);
            }
        } catch (\Throwable $e) {
            $this->json(['error' => 'Lỗi khi đổi mật khẩu', 'detail' => $e->getMessage()], 500);
        }
    }

}
