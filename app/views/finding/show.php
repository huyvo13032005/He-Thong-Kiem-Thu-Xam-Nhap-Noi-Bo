<?php
$severityClasses = [
    'CRITICAL' => 'bg-danger',
    'HIGH' => 'bg-danger',
    'MEDIUM' => 'bg-warning text-dark',
    'LOW' => 'bg-info text-dark',
    'INFO' => 'bg-secondary',
];

$statusClasses = [
    'DRAFT' => 'bg-secondary',
    'SUBMITTED' => 'bg-primary',
    'CONFIRMED' => 'bg-success',
    'FALSE_POSITIVE' => 'bg-dark',
    'REMEDIATION' => 'bg-warning text-dark',
    'READY_FOR_RETEST' => 'bg-info text-dark',
    'REOPENED' => 'bg-danger',
    'CLOSED' => 'bg-success',
];

$severityClass = $severityClasses[$finding['severity']] ?? 'bg-secondary';
$statusClass = $statusClasses[$finding['status']] ?? 'bg-secondary';

$latestReview = !empty($reviews) ? $reviews[0] : null;

$canReview = (
    $currentRole === 'APPROVER'
    && in_array($finding['severity'], ['CRITICAL', 'HIGH'], true)
    && $finding['status'] === 'CONFIRMED'
    && (
        !$latestReview
        || $latestReview['decision'] !== 'APPROVED'
    )
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            Finding #<?= (int) $finding['finding_id'] ?>
        </h2>

        <div class="text-muted">
            <?= htmlspecialchars($finding['title']) ?>
        </div>
    </div>

    <a
        href="<?= BASE_URL ?>/index.php?url=finding/index"
        class="btn btn-outline-secondary"
    >
        <i class="fa-solid fa-arrow-left me-1"></i>
        Quay lại
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <strong>Thông tin Finding</strong>
    </div>

    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <strong>Severity</strong>
                <div class="mt-1">
                    <span class="badge <?= $severityClass ?>">
                        <?= htmlspecialchars($finding['severity']) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-3">
                <strong>Status</strong>
                <div class="mt-1">
                    <span class="badge <?= $statusClass ?>">
                        <?= htmlspecialchars($finding['status']) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-3">
                <strong>CVE</strong>
                <div class="mt-1">
                    <?= !empty($finding['cve'])
                        ? htmlspecialchars($finding['cve'])
                        : '-' ?>
                </div>
            </div>

            <div class="col-md-3">
                <strong>CWE</strong>
                <div class="mt-1">
                    <?= !empty($finding['cwe'])
                        ? htmlspecialchars($finding['cwe'])
                        : '-' ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="mb-3">
            <strong>Description</strong>

            <div class="mt-2">
                <?= nl2br(
                    htmlspecialchars($finding['description'])
                ) ?>
            </div>
        </div>

        <div class="mb-3">
            <strong>Recommendation</strong>

            <div class="mt-2">
                <?= !empty($finding['recommendation'])
                    ? nl2br(htmlspecialchars($finding['recommendation']))
                    : '-' ?>
            </div>
        </div>

        <div class="mb-3">
            <strong>OWASP Category</strong>

            <div class="mt-1">
                <?= !empty($finding['owasp_category'])
                    ? htmlspecialchars($finding['owasp_category'])
                    : '-' ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <strong>Engagement & Scope</strong>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Engagement</strong>

                <div class="mt-1">
                    <?php if ($engagement): ?>
                        <a
                            href="<?= BASE_URL ?>/index.php?url=engagement/show/<?= (int) $engagement['engagement_id'] ?>"
                        >
                            <?= htmlspecialchars($engagement['name']) ?>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <strong>Scope Item</strong>

                <div class="mt-1">
                    <?php if ($scope): ?>
                        <?= htmlspecialchars(
                            $scope['target_value']
                            ?? $scope['target']
                            ?? ('Scope #' . $scope['scope_id'])
                        ) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (in_array($finding['severity'], ['CRITICAL', 'HIGH'], true)): ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Approver Review</strong>

        <?php if ($latestReview): ?>
            <?php if ($latestReview['decision'] === 'APPROVED'): ?>
                <span class="badge bg-success">
                    APPROVED
                </span>
            <?php else: ?>
                <span class="badge bg-danger">
                    REJECTED
                </span>
            <?php endif; ?>
        <?php else: ?>
            <span class="badge bg-secondary">
                PENDING
            </span>
        <?php endif; ?>
    </div>

    <div class="card-body">

        <?php if (!$reviews): ?>

            <div class="alert alert-info mb-3">
                Finding này chưa được Approver review.
            </div>

        <?php else: ?>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Approver</th>
                            <th>Decision</th>
                            <th>Comment</th>
                            <th>Reviewed At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $review['approver_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $review['decision'] === 'APPROVED'
                                    ): ?>
                                        <span class="badge bg-success">
                                            APPROVED
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            REJECTED
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= !empty($review['comment'])
                                        ? nl2br(
                                            htmlspecialchars(
                                                $review['comment']
                                            )
                                        )
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $review['reviewed_at']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <?php if ($canReview): ?>

            <hr>

            <h6 class="mb-3">
                Review Finding
            </h6>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= Csrf::token() ?>"
                >

                <div class="mb-3">
                    <label
                        for="review-comment"
                        class="form-label"
                    >
                        Comment
                    </label>

                    <textarea
                        id="review-comment"
                        name="comment"
                        class="form-control"
                        rows="3"
                        placeholder="Nhập nhận xét của Approver..."
                    ></textarea>
                </div>

                <button
                    type="submit"
                    formaction="<?= BASE_URL ?>/index.php?url=finding/approveReview/<?= (int) $finding['finding_id'] ?>"
                    class="btn btn-success me-2"
                >
                    <i class="fa-solid fa-check me-1"></i>
                    Approve
                </button>

                <button
                    type="submit"
                    formaction="<?= BASE_URL ?>/index.php?url=finding/rejectReview/<?= (int) $finding['finding_id'] ?>"
                    class="btn btn-danger"
                    onclick="return confirm('Bạn chắc chắn muốn Reject Finding này?');"
                >
                    <i class="fa-solid fa-xmark me-1"></i>
                    Reject
                </button>
            </form>

        <?php elseif (
            $currentRole === 'APPROVER'
            && $finding['status'] !== 'CONFIRMED'
        ): ?>

            <div class="alert alert-warning mb-0">
                Project Manager phải xác nhận Finding trước khi Approver review.
            </div>

        <?php elseif (
            $currentRole === 'APPROVER'
            && $latestReview
            && $latestReview['decision'] === 'APPROVED'
        ): ?>

            <div class="alert alert-success mb-0">
                Finding này đã được Approver chấp thuận.
            </div>

        <?php endif; ?>

    </div>
</div>

<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Thông tin hệ thống</strong>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Created At</strong>
                <div>
                    <?= htmlspecialchars($finding['created_at']) ?>
                </div>
            </div>

            <div class="col-md-6">
                <strong>Updated At</strong>
                <div>
                    <?= htmlspecialchars($finding['updated_at']) ?>
                </div>
            </div>
        </div>
    </div>
</div>