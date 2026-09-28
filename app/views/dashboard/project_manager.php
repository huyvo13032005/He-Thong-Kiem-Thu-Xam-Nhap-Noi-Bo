<div class="row g-3">
    <div class="col-md-4 col-lg">
        <div class="stat-card">
            <i class="fa-solid fa-crosshairs stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['running'] ?? 0) ?></div><div class="stat-label">Engagement đang chạy</div></div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="stat-card warning">
            <i class="fa-solid fa-hourglass-half stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['pending_approval'] ?? 0) ?></div><div class="stat-label">Pending Approval</div></div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="stat-card">
            <i class="fa-solid fa-user-ninja stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['pentesters_working'] ?? 0) ?></div><div class="stat-label">Pentester đang làm</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg">
        <div class="stat-card critical">
            <i class="fa-solid fa-bug stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['unconfirmed_findings'] ?? 0) ?></div><div class="stat-label">Findings chưa xác nhận</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg">
        <div class="stat-card success">
            <i class="fa-solid fa-file-lines stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['total_reports'] ?? 0) ?></div><div class="stat-label">Reports</div></div>
        </div>
    </div>
</div>

<div class="card-panel mt-4 d-flex justify-content-between align-items-center">
    <p class="text-muted mb-0"><i class="fa-solid fa-circle-info me-1"></i>
    Quản lý Engagement, Scope và phân công Pentester tại module Engagement.</p>
    <a href="<?= BASE_URL ?>/index.php?url=engagement/index" class="btn btn-sm btn-outline-danger">
        Đi tới Engagement <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
</div>
