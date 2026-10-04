<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Findings</h5>
        <p class="text-muted mb-0">
            Danh sách các lỗ hổng bảo mật được ghi nhận.
        </p>
    </div>
</div>

<div class="card-panel">
    <div class="table-responsive">

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Finding</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>CVE / CWE</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($findings as $finding): ?>

                <tr>
                    <td>
                        #<?= $finding['finding_id'] ?>
                    </td>

                    <td>
                        <strong>
                            <?= e($finding['title']) ?>
                        </strong>

                        <div class="text-muted small">
                            <?= e($finding['description']) ?>
                        </div>
                    </td>

                    <td>
                        <span class="badge bg-secondary">
                            <?= e($finding['severity']) ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge <?= statusBadgeClass($finding['status']) ?>">
                            <?= e($finding['status']) ?>
                        </span>
                    </td>

                    <td>
                        <?php if (!empty($finding['cve'])): ?>
                            <div>
                                <?= e($finding['cve']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($finding['cwe'])): ?>
                            <div class="text-muted small">
                                <?= e($finding['cwe']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (
                            empty($finding['cve']) &&
                            empty($finding['cwe'])
                        ): ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td class="text-nowrap">
                        <?= formatDateTime($finding['created_at']) ?>
                    </td>

                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/index.php?url=finding/show/<?= $finding['finding_id'] ?>"
                           class="btn btn-sm btn-outline-primary">

                            <i class="fa-solid fa-eye me-1"></i>
                            Chi tiết

                        </a>
                    </td>
                </tr>

            <?php endforeach; ?>

            <?php if (empty($findings)): ?>

                <tr>
                    <td colspan="7"
                        class="text-center text-muted py-4">
                        Chưa có Finding nào.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>
</div>