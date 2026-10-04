<?php

/**
 * Model quản lý việc Approver review Finding CRITICAL/HIGH.
 */
class FindingReviewModel extends Model
{
    protected string $table = 'finding_reviews';
    protected string $primaryKey = 'review_id';

    public const DECISIONS = [
        'APPROVED',
        'REJECTED',
    ];

    /**
     * Lấy lịch sử review của một Finding.
     */
    public function findByFinding(int $findingId): array
    {
        return $this->query(
            'SELECT fr.*, u.full_name AS approver_name
             FROM finding_reviews fr
             INNER JOIN users u
                ON u.user_id = fr.approver_id
             WHERE fr.finding_id = :finding_id
             ORDER BY fr.reviewed_at DESC',
            ['finding_id' => $findingId]
        );
    }

    /**
     * Lấy review mới nhất của Finding.
     */
    public function findLatestByFinding(int $findingId): ?array
    {
        return $this->queryOne(
            'SELECT TOP 1 fr.*, u.full_name AS approver_name
             FROM finding_reviews fr
             INNER JOIN users u
                ON u.user_id = fr.approver_id
             WHERE fr.finding_id = :finding_id
             ORDER BY fr.reviewed_at DESC, fr.review_id DESC',
            ['finding_id' => $findingId]
        );
    }
}