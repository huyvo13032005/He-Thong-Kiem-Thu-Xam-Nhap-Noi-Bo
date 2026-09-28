<?php
/**
 * app/models/EngagementModel.php
 * -----------------------------------------------------------------------
 * Model cho bảng `engagements`. Quản lý vòng đời:
 *   DRAFT -> PENDING_APPROVAL -> APPROVED -> IN_PROGRESS -> CLOSED
 *                              -> REJECTED -> (PM sửa lại) -> PENDING_APPROVAL
 * -----------------------------------------------------------------------
 */

class EngagementModel extends Model
{
    protected string $table = 'engagements';
    protected string $primaryKey = 'engagement_id';

    public const STATUSES = ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'CLOSED'];
    public const TEST_TYPES = ['BLACK_BOX', 'GREY_BOX', 'WHITE_BOX'];

    /** Danh sách Engagement kèm tên người tạo, có filter theo status/department, có phân trang */
    public function paginate(string $status, string $department, int $page, int $perPage): array
    {
        $where = ['1=1'];
        $params = [];

        if ($status !== '') {
            $where[] = 'e.status = :status';
            $params['status'] = $status;
        }
        if ($department !== '') {
            $where[] = 'e.department = :department';
            $params['department'] = $department;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM engagements e WHERE {$whereSql}", $params
        )['cnt'];

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, u.full_name AS creator_name,
                    (SELECT COUNT(*) FROM scope_items s WHERE s.engagement_id = e.engagement_id AND s.is_excluded = 0) AS scope_count,
                    (SELECT COUNT(*) FROM findings f WHERE f.engagement_id = e.engagement_id) AS finding_count
                FROM engagements e
                JOIN users u ON u.user_id = e.created_by
                WHERE {$whereSql}
                ORDER BY e.created_at DESC
                OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY";

        return ['data' => $this->query($sql, $params), 'total' => $total];
    }

    public function findDetailed(int $id): ?array
    {
        return $this->queryOne(
            "SELECT e.*, u.full_name AS creator_name
             FROM engagements e JOIN users u ON u.user_id = e.created_by
             WHERE e.engagement_id = :id",
            ['id' => $id]
        );
    }

    public function distinctDepartments(): array
    {
        return array_column($this->query('SELECT DISTINCT department FROM engagements ORDER BY department'), 'department');
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /** Đếm số scope item hợp lệ (không loại trừ) - dùng để chặn gửi duyệt khi Scope rỗng */
    public function countActiveScope(int $engagementId): int
    {
        return (int) $this->queryOne(
            'SELECT COUNT(*) AS cnt FROM scope_items WHERE engagement_id = :id AND is_excluded = 0',
            ['id' => $engagementId]
        )['cnt'];
    }
}
