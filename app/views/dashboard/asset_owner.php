<div class="row g-3">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card critical">
            <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['findings_open'] ?? 0) ?></div><div class="stat-label">Findings Open</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card warning">
            <i class="fa-solid fa-rotate stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['ready_for_retest'] ?? 0) ?></div><div class="stat-label">Ready for Retest</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card success">
            <i class="fa-solid fa-circle-check stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['fixed_total'] ?? 0) ?></div><div class="stat-label">Fixed</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <i class="fa-solid fa-circle-xmark stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['failed_retest'] ?? 0) ?></div><div class="stat-label">Failed Retest</div></div>
        </div>
    </div>
</div>
<div class="card-panel mt-4">
    <p class="text-muted mb-0"><i class="fa-solid fa-circle-info me-1"></i>
    Module Remediation đầy đủ (cập nhật trạng thái, upload bằng chứng khắc phục) sẽ được bổ sung ở Phase 3.</p>
</div>
