<?php
/**
 * app/models/AssignmentModel.php
 * -----------------------------------------------------------------------
 * Model cho bảng `pentester_assignments`. Project Manager chọn nhiều
 * Pentester (multi-select) để phân công vào 1 Engagement, kèm priority
 * và deadline riêng cho từng người.
 * -----------------------------------------------------------------------
 */

class AssignmentModel extends Model
{
    protected string $table = 'pentester_assignments';
    protected string $primaryKey = 'assignment_id';

    public const PRIORITIES = ['LOW', 'MEDIUM', 'HIGH'];

    public function findByEngagement(int $engagementId): array
    {
        return $this->query(
            'SELECT a.*, u.full_name AS pentester_name, u.email AS pentester_email
             FROM pentester_assignments a JOIN users u ON u.user_id = a.pentester_id
             WHERE a.engagement_id = :id
             ORDER BY a.assigned_at DESC',
            ['id' => $engagementId]
        );
    }

    public function isAlreadyAssigned(int $engagementId, int $pentesterId): bool
    {
        return (int) $this->queryOne(
            'SELECT COUNT(*) AS cnt FROM pentester_assignments WHERE engagement_id = :eid AND pentester_id = :pid',
            ['eid' => $engagementId, 'pid' => $pentesterId]
        )['cnt'] > 0;
    }

    /** Lấy danh sách toàn bộ Pentester (role_code = PENTESTER, đang ACTIVE) để đổ vào multi-select */
    public function availablePentesters(): array
    {
        return $this->query(
            "SELECT u.user_id, u.full_name, u.email FROM users u
             JOIN roles r ON r.role_id = u.role_id
             WHERE r.role_code = 'PENTESTER' AND u.status = 'ACTIVE'
             ORDER BY u.full_name"
        );
    }

    public function findOwned(int $assignmentId, int $engagementId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM pentester_assignments WHERE assignment_id = :aid AND engagement_id = :eid',
            ['aid' => $assignmentId, 'eid' => $engagementId]
        );
    }
}
