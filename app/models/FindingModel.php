<?php

class FindingModel extends Model
{
    protected string $table = 'findings';
    protected string $primaryKey = 'finding_id';

    public const SEVERITIES = [
        'CRITICAL',
        'HIGH',
        'MEDIUM',
        'LOW',
        'INFO'
    ];

    public const STATUSES = [
        'DRAFT',
        'SUBMITTED',
        'CONFIRMED',
        'FALSE_POSITIVE',
        'REMEDIATION',
        'READY_FOR_RETEST',
        'REOPENED',
        'CLOSED'
    ];

    /**
     * Lấy tất cả Finding của Engagement.
     * Chỉ dùng khi thực sự cần dữ liệu không lọc quyền.
     */
    public function findByEngagement(int $engagementId): array
    {
        return $this->query(
            'SELECT *
             FROM findings
             WHERE engagement_id = :engagement_id
             ORDER BY created_at DESC',
            ['engagement_id' => $engagementId]
        );
    }

    /**
     * Lấy Finding hiển thị trong Engagement theo role.
     *
     * DRAFT:
     * - Chỉ Pentester tạo Finding được xem.
     */
    public function findVisibleByEngagement(
        int $engagementId,
        string $role,
        int $userId
    ): array {
        if ($role === 'PENTESTER') {
            return $this->query(
                'SELECT DISTINCT f.*
                 FROM findings f
                 INNER JOIN pentester_assignments pa
                    ON pa.engagement_id = f.engagement_id
                 WHERE f.engagement_id = :engagement_id
                   AND pa.pentester_id = :user_id
                   AND (
                        f.status <> \'DRAFT\'
                        OR f.reported_by = :reporter_id
                   )
                 ORDER BY f.created_at DESC',
                [
                    'engagement_id' => $engagementId,
                    'user_id' => $userId,
                    'reporter_id' => $userId,
                ]
            );
        }

        if ($role === 'PROJECT_MANAGER') {
            return $this->query(
                "SELECT *
                 FROM findings
                 WHERE engagement_id = :engagement_id
                   AND status <> 'DRAFT'
                 ORDER BY created_at DESC",
                ['engagement_id' => $engagementId]
            );
        }

        if ($role === 'APPROVER') {
            return $this->query(
                "SELECT *
                 FROM findings
                 WHERE engagement_id = :engagement_id
                   AND severity IN ('CRITICAL', 'HIGH')
                   AND status <> 'DRAFT'
                 ORDER BY created_at DESC",
                ['engagement_id' => $engagementId]
            );
        }

        /*
         * Asset Owner / Stakeholder:
         * Không được xem DRAFT.
         */
        return $this->query(
            "SELECT *
             FROM findings
             WHERE engagement_id = :engagement_id
               AND status <> 'DRAFT'
             ORDER BY created_at DESC",
            ['engagement_id' => $engagementId]
        );
    }

    /**
     * Finding do chính Pentester tạo.
     */
    public function findByReporter(int $userId): array
    {
        return $this->query(
            'SELECT *
             FROM findings
             WHERE reported_by = :reported_by
             ORDER BY created_at DESC',
            ['reported_by' => $userId]
        );
    }

    /**
     * Danh sách Finding của Pentester.
     *
     * - Phải được assign vào Engagement.
     * - Finding không phải DRAFT: được xem.
     * - DRAFT: chỉ xem nếu chính mình tạo.
     */
    public function findByAssignedPentester(int $userId): array
    {
        return $this->query(
            "SELECT DISTINCT f.*
             FROM findings f
             INNER JOIN pentester_assignments pa
                ON pa.engagement_id = f.engagement_id
             WHERE pa.pentester_id = :user_id
               AND (
                    f.status <> 'DRAFT'
                    OR f.reported_by = :reporter_id
               )
             ORDER BY f.created_at DESC",
            [
                'user_id' => $userId,
                'reporter_id' => $userId,
            ]
        );
    }

    /**
     * PM chỉ thấy Finding đã được Pentester submit trở đi.
     */
    public function findByProjectManager(int $userId): array
    {
        return $this->query(
            "SELECT f.*
             FROM findings f
             INNER JOIN engagements e
                ON e.engagement_id = f.engagement_id
             WHERE e.created_by = :user_id
               AND f.status <> 'DRAFT'
             ORDER BY f.created_at DESC",
            ['user_id' => $userId]
        );
    }
}