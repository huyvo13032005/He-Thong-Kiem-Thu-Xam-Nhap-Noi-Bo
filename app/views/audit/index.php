<h5 class="mb-3"><i class="fa-solid fa-clipboard-list me-2"></i>Audit Log</h5>

<div class="card-panel mb-3">
    <form method="get" action="<?= BASE_URL ?>/index.php" class="row g-2">
        <input type="hidden" name="url" value="audit/index">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo user, mô tả, IP..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="action" class="form-select form-select-sm">
                <option value="">-- Tất cả hành động --</option>
                <?php foreach ($distinctActions as $a): ?>
                    <option value="<?= e($a) ?>" <?= $actionFilter === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i>Lọc</button>
        </div>
    </form>
</div>

<div class="card-panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>User</th>
                    <th>Hành động</th>
                    <th>Đối tượng</th>
                    <th>Mô tả</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-nowrap"><?= formatDateTime($log['created_at']) ?></td>
                    <td><?= e($log['full_name'] ?? 'Hệ thống') ?></td>
                    <td><span class="badge bg-dark"><?= e($log['action']) ?></span></td>
                    <td><?= e($log['object_type']) ?> <?= $log['object_id'] ? '#' . $log['object_id'] : '' ?></td>
                    <td><?= e($log['description']) ?></td>
                    <td><?= e($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Không có log phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?url=audit/index&page=<?= $p ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($actionFilter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
