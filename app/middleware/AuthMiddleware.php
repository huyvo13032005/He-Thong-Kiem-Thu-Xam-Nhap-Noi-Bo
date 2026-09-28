<?php
/**
 * app/middleware/AuthMiddleware.php
 * -----------------------------------------------------------------------
 * Middleware TOÀN CỤC - chạy trước MỌI request (được đăng ký trong
 * public/index.php qua $router->useGlobalMiddleware()).
 *
 * Nhiệm vụ: nếu người dùng CHƯA đăng nhập và đang truy cập 1 route cần
 * bảo vệ (mọi route trừ auth/login, auth/doLogin) -> redirect về trang login.
 * -----------------------------------------------------------------------
 */

class AuthMiddleware
{
    /** Các route KHÔNG cần đăng nhập */
    private static array $publicRoutes = [
        'auth/login',
        'auth/doLogin',
    ];

    public static function handle(): void
    {
        $url = trim($_GET['url'] ?? '', '/');

        if (in_array($url, self::$publicRoutes, true)) {
            return; // cho qua, không cần đăng nhập
        }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }

        // Refresh account state and role so locking/revoking a role takes effect.
        $fresh = (new UserModel())->findWithRole((int) $_SESSION['user']['user_id']);
        if (!$fresh || $fresh['status'] !== 'ACTIVE') {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . BASE_URL . '/index.php?url=auth/login&locked=1');
            exit;
        }
        unset($fresh['password_hash']);
        $_SESSION['user'] = $fresh;
    }
}
