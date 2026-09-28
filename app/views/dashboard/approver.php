<div class="row g-3">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card warning">
            <i class="fa-solid fa-stamp stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['pending_engagements'] ?? 0) ?></div><div class="stat-label">Engagement chờ duyệt</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card critical">
            <i class="fa-solid fa-flag-checkered stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['pending_close'] ?? 0) ?></div><div class="stat-label">Finding chờ đóng</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card success">
            <i class="fa-solid fa-circle-check stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['approved_total'] ?? 0) ?></div><div class="stat-label">Đã Approve (tổng)</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <i class="fa-solid fa-circle-xmark stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['rejected_total'] ?? 0) ?></div><div class="stat-label">Đã Reject (tổng)</div></div>
        </div>
    </div>
</div>
<div class="card-panel mt-4 d-flex justify-content-between align-items-center">
    <p class="text-muted mb-0"><i class="fa-solid fa-circle-info me-1"></i>
    Xem và xử lý các Engagement đang chờ phê duyệt tại module Chờ phê duyệt.</p>
    <a href="<?= BASE_URL ?>/index.php?url=approval/index" class="btn btn-sm btn-outline-danger">
        Đi tới Chờ phê duyệt <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
</div>
