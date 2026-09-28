<?php
/**
 * app/middleware/RoleMiddleware.php
 * -----------------------------------------------------------------------
 * RBAC (Role Based Access Control) ở cấp ĐỘNG TÁC (action), không phải
 * cấp route toàn cục - vì mỗi action trong cùng 1 Controller có thể cho
 * phép các role KHÁC NHAU (vd: FindingController::index() cho nhiều role
 * xem, nhưng FindingController::confirm() chỉ Project Manager được gọi).
 *
 * CÁCH DÙNG (đặt ở DÒNG ĐẦU TIÊN của action cần bảo vệ):
 *
 *   public function confirm($id) {
 *       RoleMiddleware::require(['PROJECT_MANAGER']);
 *       ...
 *   }
 *
 * Muốn thêm 1 Role mới được phép gọi 1 action: chỉ cần thêm role_code
 * vào mảng truyền vào RoleMiddleware::require([...]) tại action đó.
 * -----------------------------------------------------------------------
 */

class RoleMiddleware
{
    public static function require(array $allowedRoles): void
    {
        $role = $_SESSION['user']['role_code'] ?? null;

        if ($role === null || !in_array($role, $allowedRoles, true)) {
            AuditHelper::log(
                $_SESSION['user']['user_id'] ?? null,
                'ACCESS_DENIED',
                null,
                null,
                'Truy cập bị từ chối: role hiện tại (' . ($role ?? 'guest') . ') không nằm trong ' . implode(',', $allowedRoles)
            );
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    /** Kiểm tra nhanh (dùng trong view để ẩn/hiện nút bấm), không log/không chặn */
    public static function can(array $allowedRoles): bool
    {
        $role = $_SESSION['user']['role_code'] ?? null;
        return $role !== null && in_array($role, $allowedRoles, true);
    }
}
