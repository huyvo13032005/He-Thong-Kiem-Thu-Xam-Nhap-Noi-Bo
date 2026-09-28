<?php
/**
 * app/controllers/AuthController.php
 * -----------------------------------------------------------------------
 * Xử lý đăng nhập / đăng xuất.
 * Route:
 *   GET  /index.php?url=auth/login    -> hiển thị form đăng nhập
 *   POST /index.php?url=auth/doLogin  -> xử lý đăng nhập
 *   GET  /index.php?url=auth/logout   -> đăng xuất
 * -----------------------------------------------------------------------
 */

class AuthController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login(): void
    {
        // Nếu đã đăng nhập rồi thì vào thẳng Dashboard
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $this->render('auth/login', [
            'locked' => isset($_GET['locked']),
            'error'  => $_SESSION['login_error'] ?? null,
        ], layout: null);

        unset($_SESSION['login_error']);
    }

    public function doLogin(): void
    {
        Csrf::verify($this->input('csrf_token', ''));

        $username = $this->input('username', '');
        $password = $this->input('password', '');

        $user = $this->userModel->findByUsername($username);

        // So khớp mật khẩu bằng password_verify() - KHÔNG BAO GIỜ so sánh plaintext
        if (!$user || !password_verify($password, $user['password_hash'])) {
            AuditHelper::log(null, 'LOGIN_FAILED', 'USER', null, "Đăng nhập thất bại với username: {$username}");
            $_SESSION['login_error'] = 'Sai tên đăng nhập hoặc mật khẩu.';
            $this->redirect('auth/login');
        }

        if ($user['status'] !== 'ACTIVE') {
            AuditHelper::log($user['user_id'], 'LOGIN_BLOCKED', 'USER', $user['user_id'], 'Tài khoản không ở trạng thái ACTIVE');
            $_SESSION['login_error'] = 'Tài khoản đã bị khóa hoặc vô hiệu hóa. Vui lòng liên hệ Admin.';
            $this->redirect('auth/login');
        }

        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);

        // Lưu thông tin cần thiết vào session (KHÔNG lưu password_hash vào session)
        $_SESSION['user'] = [
            'user_id'    => $user['user_id'],
            'full_name'  => $user['full_name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role_code'  => $user['role_code'],
            'role_name'  => $user['role_name'],
            'department' => $user['department'],
            'status'     => $user['status'],
            'avatar_path'=> $user['avatar_path'],
        ];

        // "Remember me" - kéo dài thời gian sống của session cookie
        if ($this->input('remember')) {
            setcookie(session_name(), session_id(), [
                'expires' => time() + 30 * 24 * 3600,
                'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }

        $this->userModel->updateLastLogin($user['user_id']);
        AuditHelper::log($user['user_id'], 'LOGIN', 'USER', $user['user_id'], 'Đăng nhập thành công');

        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $userId = $_SESSION['user']['user_id'] ?? null;
        AuditHelper::log($userId, 'LOGOUT', 'USER', $userId, 'Đăng xuất');

        $_SESSION = [];
        session_destroy();
        $this->redirect('auth/login');
    }
}
