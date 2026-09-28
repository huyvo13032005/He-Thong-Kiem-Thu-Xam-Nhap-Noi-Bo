<?php
/**
 * app/helpers/AuditHelper.php
 * -----------------------------------------------------------------------
 * Ghi Audit Log. Được gọi từ bất kỳ Controller nào sau một thao tác nhạy
 * cảm (đăng nhập, tạo/sửa/xóa, phê duyệt, upload evidence...).
 *
 * Cách dùng:
 *   AuditHelper::log($userId, 'CREATE_ENGAGEMENT', 'ENGAGEMENT', $engagementId, 'Tạo Engagement X');
 * -----------------------------------------------------------------------
 */

class AuditHelper
{
    public static function log(?int $userId, string $action, ?string $objectType = null, ?int $objectId = null, string $description = ''): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_id, action, object_type, object_id, description, ip_address)
             VALUES (:user_id, :action, :object_type, :object_id, :description, :ip)'
        );
        $stmt->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'object_type' => $objectType,
            'object_id'   => $objectId,
            'description' => $description,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
}
