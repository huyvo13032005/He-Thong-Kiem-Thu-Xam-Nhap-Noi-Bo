<?php
/**
 * app/models/ApprovalModel.php
 * -----------------------------------------------------------------------
 * Model cho bảng `engagement_approvals` - lưu LỊCH SỬ mọi quyết định
 * phê duyệt/từ chối (append-only, không update/delete để đảm bảo tính
 * truy vết, tương tự Audit Log).
 * -----------------------------------------------------------------------
 */

class ApprovalModel extends Model
{
    protected string $table = 'engagement_approvals';
    protected string $primaryKey = 'approval_id';

    /** Danh sách Engagement đang chờ Approver này xử lý (status = PENDING_APPROVAL) */
    public function pendingEngagements(string $department = ''): array
    {
        $where = ["e.status = 'PENDING_APPROVAL'"];
        $params = [];
        if ($department !== '') {
            $where[] = 'e.department = :department';
            $params['department'] = $department;
        }
        $whereSql = implode(' AND ', $where);

        return $this->query(
            "SELECT e.*, u.full_name AS creator_name,
                (SELECT COUNT(*) FROM scope_items s WHERE s.engagement_id = e.engagement_id AND s.is_excluded = 0) AS scope_count
             FROM engagements e
             JOIN users u ON u.user_id = e.created_by
             WHERE {$whereSql}
             ORDER BY e.updated_at ASC",
            $params
        );
    }

    public function historyForEngagement(int $engagementId): array
    {
        return $this->query(
            'SELECT a.*, u.full_name AS approver_name
             FROM engagement_approvals a JOIN users u ON u.user_id = a.approver_id
             WHERE a.engagement_id = :id
             ORDER BY a.decided_at DESC',
            ['id' => $engagementId]
        );
    }

    public function recordDecision(int $engagementId, int $approverId, string $decision, string $comment): int
    {
        return $this->insert([
            'engagement_id' => $engagementId,
            'approver_id'   => $approverId,
            'decision'      => $decision,
            'comment'       => $comment,
        ]);
    }
}
