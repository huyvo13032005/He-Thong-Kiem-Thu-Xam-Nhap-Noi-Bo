<?php
/**
 * app/core/Controller.php
 * -----------------------------------------------------------------------
 * Base Controller - mọi Controller trong app/controllers/ nên extends class này.
 * Cung cấp các hàm dùng chung: render view, redirect, trả JSON, lấy user hiện tại.
 * -----------------------------------------------------------------------
 */

abstract class Controller
{
    /**
     * Render 1 view trong app/views/, luôn bọc trong layout chính (main.php)
     * trừ khi $layout = null (dùng cho trang login, error...).
     *
     * @param string $view  Đường dẫn view, vd: 'users/index' -> app/views/users/index.php
     * @param array  $data  Dữ liệu truyền vào view (dùng extract())
     * @param string|null $layout Tên layout trong app/views/layouts/, mặc định 'main'
     */
    protected function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("View không tồn tại: {$view}");
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        // Nội dung view được render ra buffer trước, sau đó chèn vào $content của layout
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/index.php?url=' . ltrim($path, '/'));
        exit;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Lấy thông tin user đang đăng nhập từ session (đã được AuthMiddleware xác thực) */
    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function currentRole(): ?string
    {
        return $_SESSION['user']['role_code'] ?? null;
    }

    /** Lấy giá trị từ $_POST kèm trim, tránh lỗi undefined index */
    protected function input(string $key, $default = null)
    {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}
