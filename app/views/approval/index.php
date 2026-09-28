<h5 class="mb-3"><i class="fa-solid fa-stamp me-2"></i>Chờ phê duyệt</h5>

<div class="card-panel mb-3">
    <form method="get" action="<?= BASE_URL ?>/index.php" class="row g-2">
        <input type="hidden" name="url" value="approval/index">
        <div class="col-md-4">
            <select name="department" class="form-select form-select-sm">
                <option value="">-- Tất cả phòng ban --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= e($d) ?>" <?= $deptFilter === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i>Lọc</button>
        </div>
    </form>
</div>

<?php if (empty($pending)): ?>
    <div class="card-panel text-center py-5">
        <i class="fa-solid fa-circle-check fa-2x text-success mb-3"></i>
        <p class="text-muted mb-0">Không có Engagement nào đang chờ phê duyệt.</p>
    </div>
<?php endif; ?>

<?php foreach ($pending as $eng): ?>
    <div class="card-panel mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="mb-1">
                    <a href="<?= BASE_URL ?>/index.php?url=engagement/show/<?= $eng['engagement_id'] ?>" class="text-decoration-none">
                        <?= e($eng['name']) ?>
                    </a>
                </h6>
                <p class="text-muted small mb-1">
                    <?= e($eng['department']) ?> &middot; <?= e($eng['test_type']) ?> &middot;
                    <?= formatDate($eng['start_date']) ?> &rarr; <?= formatDate($eng['end_date']) ?>
                </p>
                <p class="small mb-0">Người tạo: <?= e($eng['creator_name']) ?> &middot; Số Scope Item: <?= (int) $eng['scope_count'] ?></p>
            </div>
            <span class="badge <?= statusBadgeClass($eng['status']) ?>"><?= e($eng['status']) ?></span>
        </div>

        <hr>
        <form method="post" class="row g-2 align-items-start" id="approvalForm<?= $eng['engagement_id'] ?>">
            <?= Csrf::field() ?>
            <div class="col-md-8">
                <textarea name="comment" class="form-control form-control-sm" rows="2"
                          placeholder="Comment (bắt buộc nếu Reject, tùy chọn nếu Approve)"></textarea>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit"
                        formaction="<?= BASE_URL ?>/index.php?url=approval/approve/<?= $eng['engagement_id'] ?>"
                        class="btn btn-success btn-sm flex-fill"
                        onclick="return confirm('Phê duyệt Engagement này?');">
                    <i class="fa-solid fa-check me-1"></i>Approve
                </button>
                <button type="submit"
                        formaction="<?= BASE_URL ?>/index.php?url=approval/reject/<?= $eng['engagement_id'] ?>"
                        class="btn btn-danger btn-sm flex-fill"
                        onclick="return confirmReject(<?= $eng['engagement_id'] ?>);">
                    <i class="fa-solid fa-xmark me-1"></i>Reject
                </button>
            </div>
        </form>
    </div>
<?php endforeach; ?>

<script>
function confirmReject(id) {
    const form = document.getElementById('approvalForm' + id);
    const comment = form.querySelector('textarea[name="comment"]').value.trim();
    if (comment === '') {
        alert('Vui lòng nhập lý do từ chối trước khi Reject.');
        return false;
    }
    return confirm('Từ chối Engagement này?');
}
</script>
