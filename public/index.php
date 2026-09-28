<?php
/**
 * public/index.php
 * -----------------------------------------------------------------------
 * FRONT CONTROLLER - điểm vào DUY NHẤT của toàn bộ ứng dụng.
 * Mọi request (nhờ .htaccess rewrite hoặc gọi trực tiếp ?url=...) đều
 * chạy qua file này.
 *
 * LUỒNG XỬ LÝ (đúng kiến trúc MVC):
 *   1. Nạp cấu hình + các class lõi (core, helpers, middleware).
 *   2. Khởi động session (RBAC + đăng nhập dựa trên session).
 *   3. Đăng ký middleware toàn cục (AuthMiddleware).
 *   4. Khởi tạo Router, đọc "url" từ query string, dispatch tới đúng
 *      Controller -> Model (truy vấn DB) -> View (render HTML).
 * -----------------------------------------------------------------------
 */

// ---- 1. Cấu hình cơ bản ----
define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/database.php';
define('BASE_URL', rtrim($config['base_url'], '/'));

// Hiện lỗi khi phát triển - PHẢI tắt (0) khi deploy thật (production)
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');

// ---- 2. Session (bắt buộc để lưu thông tin đăng nhập + CSRF token) ----
session_name($config['session_name']);
session_set_cookie_params([
    'lifetime' => $config['session_lifetime'],
    'path'     => '/',
    'httponly' => true,   // JS không đọc được cookie session -> chống XSS đánh cắp session
    'samesite' => 'Lax',  // chống CSRF ở mức cookie
]);
session_start();

// ---- 3. Autoload thủ công (MVC thuần không dùng Composer autoload) ----
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/app/core/'       . $class . '.php',
        BASE_PATH . '/app/models/'     . $class . '.php',
        BASE_PATH . '/app/controllers/'. $class . '.php',
        BASE_PATH . '/app/middleware/' . $class . '.php',
        BASE_PATH . '/app/helpers/'    . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once BASE_PATH . '/app/helpers/functions.php';

// ---- 4. Router + Middleware toàn cục ----
$router = new Router();
$router->useGlobalMiddleware([AuthMiddleware::class, 'handle']);

$url = $_GET['url'] ?? '';
$router->dispatch($url);
