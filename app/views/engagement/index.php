<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa-solid fa-crosshairs me-2"></i>Engagement</h5>
    <?php if (RoleMiddleware::can(['PROJECT_MANAGER'])): ?>
        <a href="<?= BASE_URL ?>/index.php?url=engagement/create" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-plus me-1"></i>Tạo Engagement
        </a>
    <?php endif; ?>
</div>

<div class="card-panel mb-3">
    <form method="get" action="<?= BASE_URL ?>/index.php" class="row g-2">
        <input type="hidden" name="url" value="engagement/index">
        <div class="col-md-4">
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Tất cả trạng thái --</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
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

<div class="card-panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Tên Engagement</th>
                    <th>Phòng ban</th>
                    <th>Loại test</th>
                    <th>Thời gian</th>
                    <th>Scope</th>
                    <th>Findings</th>
                    <th>Người tạo</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($engagements as $eng): ?>
                <tr>
                    <td><?= e($eng['name']) ?></td>
                    <td><?= e($eng['department']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($eng['test_type']) ?></span></td>
                    <td class="text-nowrap"><?= formatDate($eng['start_date']) ?> &rarr; <?= formatDate($eng['end_date']) ?></td>
                    <td><?= (int) $eng['scope_count'] ?></td>
                    <td><?= (int) $eng['finding_count'] ?></td>
                    <td><?= e($eng['creator_name']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($eng['status']) ?>"><?= e($eng['status']) ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/index.php?url=engagement/show/<?= $eng['engagement_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($engagements)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Không có Engagement phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?url=engagement/index&page=<?= $p ?>&status=<?= urlencode($statusFilter) ?>&department=<?= urlencode($deptFilter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
