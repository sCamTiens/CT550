<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\DB;

class AuthController extends Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** GET /login */
    public function showLogin(): mixed
    {
        return $this->view('auth/login', [
            'title'  => 'Đăng nhập',
            'errors' => [],
            'old'    => [],
        ]);
    }

    /** POST /login */
    public function login(Request $req): mixed
    {
        $email    = trim((string) $req->input('email'));
        $password = (string) $req->input('password');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        }
        if ($password === '') {
            $errors['password'] = 'Vui lòng nhập mật khẩu';
        }
        if ($errors) {
            http_response_code(422);
            return $this->view('auth/login', [
                'errors' => $errors,
                'old'    => ['email' => $email],
            ]);
        }

        $pdo = method_exists(DB::class, 'pdo') ? DB::pdo() : DB::getConnection();

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash, role_id
             FROM users
             WHERE email = :email AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['common'] = 'Email hoặc mật khẩu không đúng';
            http_response_code(401);
            return $this->view('auth/login', [
                'errors' => $errors,
                'old'    => ['email' => $email],
            ]);
        }

        $_SESSION['user'] = [
            'id'      => (int) $user['id'],
            'name'    => $user['full_name'],
            'email'   => $user['email'],
            'role_id' => (int) $user['role_id'],
        ];

        if (class_exists(Response::class) && method_exists(Response::class, 'redirect')) {
            return Response::redirect('/');
        }
        header('Location: /');
        exit;
    }

    /** GET /logout */
    public function logout(): mixed
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        if (class_exists(Response::class) && method_exists(Response::class, 'redirect')) {
            return Response::redirect('/login');
        }
        header('Location: /login');
        exit;
    }
}
