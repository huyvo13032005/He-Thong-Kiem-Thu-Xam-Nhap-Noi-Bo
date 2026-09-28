<?php
/**
 * app/helpers/Csrf.php
 * -----------------------------------------------------------------------
 * Chống tấn công CSRF (Cross-Site Request Forgery) cho các form POST.
 *
 * Cách dùng trong view (bên trong <form>):
 *   <?= Csrf::field() ?>
 *
 * Cách dùng trong Controller (đầu mỗi action xử lý POST):
 *   Csrf::verify($_POST['csrf_token'] ?? '');
 * -----------------------------------------------------------------------
 */

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function verify(string $token): void
    {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('Phiên làm việc không hợp lệ (CSRF token sai). Vui lòng tải lại trang và thử lại.');
        }
    }
}
