<?php
/**
 * app/models/ScopeModel.php
 * -----------------------------------------------------------------------
 * Model cho bảng `scope_items`. Scope chỉ được thêm/sửa/xóa khi Engagement
 * đang ở trạng thái DRAFT hoặc REJECTED (việc kiểm tra trạng thái này nằm
 * ở ScopeController, Model chỉ chịu trách nhiệm truy vấn dữ liệu).
 * -----------------------------------------------------------------------
 */

class ScopeModel extends Model
{
    protected string $table = 'scope_items';
    protected string $primaryKey = 'scope_id';

    public const TARGET_TYPES = ['WEB_APP', 'API', 'SERVER', 'DOMAIN_CONTROLLER', 'VPN_GATEWAY', 'NETWORK', 'OTHER'];

    public function findByEngagement(int $engagementId): array
    {
        return $this->query(
            'SELECT * FROM scope_items WHERE engagement_id = :id ORDER BY created_at ASC',
            ['id' => $engagementId]
        );
    }

    /** Khóa (hoặc mở khóa) toàn bộ scope item của 1 Engagement - gọi khi Approve/Reject */
    public function setLockedForEngagement(int $engagementId, bool $locked): void
    {
        $stmt = $this->db->prepare('UPDATE scope_items SET is_locked = :locked WHERE engagement_id = :id');
        $stmt->execute(['locked' => $locked ? 1 : 0, 'id' => $engagementId]);
    }
}
