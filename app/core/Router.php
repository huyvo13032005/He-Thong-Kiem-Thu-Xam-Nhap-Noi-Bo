<?php
/**
 * app/core/Router.php
 * -----------------------------------------------------------------------
 * Router đơn giản cho kiến trúc MVC thuần (không framework).
 *
 * Cách hoạt động:
 *   1. public/index.php nhận MỌI request (nhờ .htaccess rewrite).
 *   2. Router đọc URL (?url=controller/action/param1/param2...)
 *   3. Router tìm class Controller tương ứng trong app/controllers/,
 *      khởi tạo và gọi đúng method (action).
 *   4. Router chạy Middleware (nếu route yêu cầu) TRƯỚC KHI gọi Controller.
 *
 * Ví dụ:
 *   /index.php?url=user/edit/5
 *   -> Controller: UserController, Method: edit(5)
 * -----------------------------------------------------------------------
 */

class Router
{
    private array $middlewares = [];

    /** Đăng ký middleware áp dụng cho MỌI request (vd: AuthMiddleware) */
    public function useGlobalMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function dispatch(string $url): void
    {
        // Chạy toàn bộ middleware toàn cục trước (vd: kiểm tra đăng nhập)
        foreach ($this->middlewares as $middleware) {
            $middleware();
        }

        $url = trim($url, '/');
        $parts = $url === '' ? ['dashboard', 'index'] : explode('/', $url);

        $controllerName = ucfirst($parts[0] ?? 'dashboard') . 'Controller';
        $action         = $parts[1] ?? 'index';
        $params         = array_slice($parts, 2);

        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->abort(404, 'Không tìm thấy trang (Controller không tồn tại).');
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->abort(404, 'Controller không hợp lệ.');
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            $this->abort(404, 'Không tìm thấy hành động (Action không tồn tại).');
        }

        $method = new ReflectionMethod($controller, $action);
        if (!$method->isPublic() || $method->isConstructor() || $method->isStatic()) {
            $this->abort(404, 'Action không hợp lệ.');
        }
        $mutations = ['store', 'update', 'destroy', 'toggleStatus',
            'submitForApproval', 'approve', 'reject', 'doLogin'];
        if (in_array($action, $mutations, true)) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                header('Allow: POST');
                $this->abort(405, 'Hành động này yêu cầu POST.');
            }
            Csrf::verify(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '');
        }

        call_user_func_array([$controller, $action], $params);
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo "<h2>Lỗi {$code}</h2><p>{$message}</p>";
        exit;
    }
}
