<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-1"><?= e($engagement['name']) ?></h5>
        <span class="badge <?= statusBadgeClass($engagement['status']) ?>">
            <?= e($engagement['status']) ?>
        </span>
        <span class="text-muted small ms-2">
            <?= e($engagement['department']) ?>
            &middot;
            <?= e($engagement['test_type']) ?>
        </span>
    </div>

    <div class="d-flex gap-2">
        <?php if ($canManage): ?>
            <a href="<?= BASE_URL ?>/index.php?url=engagement/edit/<?= $engagement['engagement_id'] ?>"
               class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-pen me-1"></i>Sửa
            </a>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
            <form method="post"
                  action="<?= BASE_URL ?>/index.php?url=engagement/submitForApproval/<?= $engagement['engagement_id'] ?>"
                  onsubmit="return confirm('Gửi Engagement này đi phê duyệt?');">

                <?= Csrf::field() ?>

                <button class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-paper-plane me-1"></i>
                    Gửi phê duyệt
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>


<ul class="nav nav-tabs mb-3" role="tablist">

    <li class="nav-item">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#tab-overview">
            Overview
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#tab-scope">
            Scope (<?= count($scopeItems) ?>)
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#tab-assignments">
            Assignments (<?= count($assignments) ?>)
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#tab-findings">
            Findings (<?= count($findings) ?>)
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#tab-reports">
            Reports
        </button>
    </li>

    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#tab-audit">
            Audit
        </button>
    </li>

</ul>


<div class="tab-content">

    <!-- ================= OVERVIEW ================= -->

    <div class="tab-pane fade show active" id="tab-overview">

        <div class="card-panel mb-3">

            <div class="row">

                <div class="col-md-6">

                    <p class="mb-1">
                        <strong>Người tạo:</strong>
                        <?= e($engagement['creator_name']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Thời gian:</strong>
                        <?= formatDate($engagement['start_date']) ?>
                        &rarr;
                        <?= formatDate($engagement['end_date']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Ngày tạo:</strong>
                        <?= formatDateTime($engagement['created_at']) ?>
                    </p>

                </div>

                <div class="col-md-6">

                    <p class="mb-1">
                        <strong>Mô tả:</strong>
                    </p>

                    <p class="text-muted">
                        <?= nl2br(e($engagement['description'] ?? '')) ?>
                    </p>

                </div>

            </div>

        </div>


        <div class="card-panel">

            <h6 class="mb-3">
                Lịch sử phê duyệt
            </h6>

            <?php if (empty($approvalHistory)): ?>

                <p class="text-muted mb-0">
                    Chưa có quyết định phê duyệt nào.
                </p>

            <?php else: ?>

                <table class="table table-sm">

                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Approver</th>
                            <th>Quyết định</th>
                            <th>Comment</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($approvalHistory as $h): ?>

                        <tr>

                            <td class="text-nowrap">
                                <?= formatDateTime($h['decided_at']) ?>
                            </td>

                            <td>
                                <?= e($h['approver_name']) ?>
                            </td>

                            <td>
                                <span class="badge <?= statusBadgeClass($h['decision']) ?>">
                                    <?= e($h['decision']) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($h['comment']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </div>


    <!-- ================= SCOPE ================= -->

    <div class="tab-pane fade" id="tab-scope">

        <?php if ($canManage): ?>

            <div class="card-panel mb-3">

                <h6 class="mb-3">
                    Thêm Scope Item
                </h6>

                <form method="post"
                      action="<?= BASE_URL ?>/index.php?url=scope/store/<?= $engagement['engagement_id'] ?>"
                      class="row g-2">

                    <?= Csrf::field() ?>

                    <div class="col-md-3">

                        <input type="text"
                               name="target"
                               class="form-control form-control-sm"
                               placeholder="Target (vd: hr.company.local)"
                               required>

                    </div>


                    <div class="col-md-3">

                        <select name="target_type"
                                class="form-select form-select-sm"
                                required>

                            <?php foreach ($targetTypes as $t): ?>

                                <option value="<?= e($t) ?>">
                                    <?= e($t) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-3">

                        <input type="text"
                               name="description"
                               class="form-control form-control-sm"
                               placeholder="Mô tả (tùy chọn)">

                    </div>


                    <div class="col-md-2 d-flex align-items-center">

                        <div class="form-check">

                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_excluded"
                                   value="1"
                                   id="isExcluded">

                            <label class="form-check-label small"
                                   for="isExcluded">
                                Loại trừ
                            </label>

                        </div>

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-danger btn-sm w-100">
                            <i class="fa-solid fa-plus"></i>
                        </button>

                    </div>

                </form>

            </div>

        <?php elseif (
            !empty($scopeItems) &&
            $scopeItems[0]['is_locked']
        ): ?>

            <div class="alert alert-secondary py-2">

                <i class="fa-solid fa-lock me-1"></i>

                Scope đã bị khóa
                (Engagement đã được phê duyệt).

            </div>

        <?php endif; ?>


        <div class="card-panel">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Target</th>
                            <th>Loại</th>
                            <th>Mô tả</th>
                            <th>Loại trừ</th>
                            <th>Khóa</th>

                            <?php if ($canManage): ?>
                                <th></th>
                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($scopeItems as $s): ?>

                        <tr>

                            <td>
                                <?= e($s['target']) ?>
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    <?= e($s['target_type']) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($s['description']) ?>
                            </td>

                            <td>

                                <?php if ($s['is_excluded']): ?>

                                    <span class="badge bg-danger">
                                        Loại trừ
                                    </span>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($s['is_locked']): ?>

                                    <i class="fa-solid fa-lock text-secondary"></i>

                                <?php else: ?>

                                    <i class="fa-solid fa-lock-open text-success"></i>

                                <?php endif; ?>

                            </td>


                            <?php if ($canManage): ?>

                                <td class="text-end">

                                    <form method="post"
                                          action="<?= BASE_URL ?>/index.php?url=scope/destroy/<?= $s['scope_id'] ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Xóa Scope Item này?');">

                                        <?= Csrf::field() ?>

                                        <button class="btn btn-sm btn-outline-danger">

                                            <i class="fa-solid fa-trash"></i>

                                        </button>

                                    </form>

                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (empty($scopeItems)): ?>

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted py-3">

                                Chưa có Scope Item nào.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- ================= ASSIGNMENTS ================= -->

    <div class="tab-pane fade" id="tab-assignments">

        <?php if ($canAssign): ?>

            <div class="card-panel mb-3">

                <h6 class="mb-3">
                    Phân công Pentester
                </h6>

                <form method="post"
                      action="<?= BASE_URL ?>/index.php?url=assignment/store/<?= $engagement['engagement_id'] ?>"
                      class="row g-2">

                    <?= Csrf::field() ?>


                    <div class="col-md-5">

                        <select name="pentester_ids[]"
                                class="form-select form-select-sm"
                                multiple
                                size="4"
                                required>

                            <?php foreach ($pentesters as $p): ?>

                                <option value="<?= $p['user_id'] ?>">

                                    <?= e($p['full_name']) ?>
                                    (<?= e($p['email']) ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="form-text">
                            Giữ Ctrl (hoặc Cmd) để chọn nhiều Pentester.
                        </div>

                    </div>


                    <div class="col-md-3">

                        <label class="form-label small mb-1">
                            Priority
                        </label>

                        <select name="priority"
                                class="form-select form-select-sm">

                            <?php foreach ($priorities as $p): ?>

                                <option value="<?= e($p) ?>"
                                    <?= $p === 'MEDIUM' ? 'selected' : '' ?>>

                                    <?= e($p) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-3">

                        <label class="form-label small mb-1">
                            Deadline
                        </label>

                        <input type="date"
                               name="deadline"
                               class="form-control form-control-sm">

                    </div>


                    <div class="col-md-1 d-flex align-items-end">

                        <button class="btn btn-danger btn-sm w-100">

                            <i class="fa-solid fa-user-plus"></i>

                        </button>

                    </div>

                </form>

            </div>

        <?php elseif (
            !in_array(
                $engagement['status'],
                ['APPROVED', 'IN_PROGRESS', 'CLOSED'],
                true
            )
        ): ?>

            <div class="alert alert-secondary py-2">

                <i class="fa-solid fa-circle-info me-1"></i>

                Chỉ có thể phân công Pentester sau khi
                Engagement được Approved.

            </div>

        <?php endif; ?>


        <div class="card-panel">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Pentester</th>
                        <th>Priority</th>
                        <th>Deadline</th>
                        <th>Ngày giao</th>

                        <?php if (
                            RoleMiddleware::can(
                                ['PROJECT_MANAGER']
                            )
                        ): ?>

                            <th></th>

                        <?php endif; ?>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($assignments as $a): ?>

                    <tr>

                        <td>

                            <?= e($a['pentester_name']) ?>

                            <div class="text-muted small">

                                <?= e($a['pentester_email']) ?>

                            </div>

                        </td>


                        <td>

                            <span class="badge bg-secondary">

                                <?= e($a['priority']) ?>

                            </span>

                        </td>


                        <td>
                            <?= formatDate($a['deadline']) ?>
                        </td>


                        <td>
                            <?= formatDateTime($a['assigned_at']) ?>
                        </td>


                        <?php if (
                            RoleMiddleware::can(
                                ['PROJECT_MANAGER']
                            )
                        ): ?>

                            <td class="text-end">

                                <form method="post"
                                      action="<?= BASE_URL ?>/index.php?url=assignment/destroy/<?= $a['assignment_id'] ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Gỡ phân công này?');">

                                    <?= Csrf::field() ?>

                                    <button class="btn btn-sm btn-outline-danger">

                                        <i class="fa-solid fa-user-minus"></i>

                                    </button>

                                </form>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>


                <?php if (empty($assignments)): ?>

                    <tr>

                        <td colspan="5"
                            class="text-center text-muted py-3">

                            Chưa phân công Pentester nào.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ================= FINDINGS ================= -->

    <div class="tab-pane fade" id="tab-findings">

        <?php if ($canCreateFinding): ?>

            <div class="card-panel mb-3">

                <h6 class="mb-3">
                    Tạo Finding
                </h6>

                <form method="post"
                      action="<?= BASE_URL ?>/index.php?url=finding/store/<?= $engagement['engagement_id'] ?>"
                      class="row g-3">

                    <?= Csrf::field() ?>


                    <div class="col-md-8">

                        <label class="form-label">
                            Tiêu đề
                        </label>

                        <input type="text"
                               name="title"
                               class="form-control"
                               required>

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Severity
                        </label>

                        <select name="severity"
                                class="form-select"
                                required>

                            <?php foreach (
                                FindingModel::SEVERITIES as $severity
                            ): ?>

                                <option value="<?= e($severity) ?>">
                                    <?= e($severity) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-12">

                        <label class="form-label">
                            Scope Item
                        </label>

                        <select name="scope_id"
                                class="form-select">

                            <option value="">
                                -- Không chọn --
                            </option>

                            <?php foreach ($scopeItems as $scope): ?>

                                <?php if (!$scope['is_excluded']): ?>

                                    <option value="<?= $scope['scope_id'] ?>">

                                        <?= e($scope['target']) ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-12">

                        <label class="form-label">
                            Mô tả
                        </label>

                        <textarea name="description"
                                  class="form-control"
                                  rows="4"
                                  required></textarea>

                    </div>


                    <div class="col-md-12">

                        <label class="form-label">
                            Khuyến nghị khắc phục
                        </label>

                        <textarea name="recommendation"
                                  class="form-control"
                                  rows="3"></textarea>

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            CVE
                        </label>

                        <input type="text"
                               name="cve"
                               class="form-control"
                               placeholder="VD: CVE-2024-1234">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            CWE
                        </label>

                        <input type="text"
                               name="cwe"
                               class="form-control"
                               placeholder="VD: CWE-89">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            OWASP Category
                        </label>

                        <input type="text"
                               name="owasp_category"
                               class="form-control"
                               placeholder="VD: Injection">

                    </div>


                    <div class="col-12 text-end">

                        <button class="btn btn-danger">

                            <i class="fa-solid fa-bug me-1"></i>

                            Tạo Finding

                        </button>

                    </div>

                </form>

            </div>

        <?php endif; ?>


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
                            <th>Thao tác</th>

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

                                <?= formatDateTime(
                                    $finding['created_at']
                                ) ?>

                            </td>


                            <!-- ===== FINDING ACTIONS ===== -->

                            <td class="text-nowrap">

                                <?php
                                $canEditFinding =
                                    RoleMiddleware::can(
                                        ['PENTESTER']
                                    )
                                    && $finding['status'] === 'DRAFT'
                                    && (int) $finding['reported_by'] ===
                                       (int) $this->currentUser()['user_id'];
                                ?>


                                <?php if ($canEditFinding): ?>

                                    <a href="<?= BASE_URL ?>/index.php?url=finding/edit/<?= $finding['finding_id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Sửa">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <form method="post"
                                          action="<?= BASE_URL ?>/index.php?url=finding/submit/<?= $finding['finding_id'] ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Gửi Finding này cho Project Manager?');">

                                        <?= Csrf::field() ?>

                                        <button class="btn btn-sm btn-success">

                                            <i class="fa-solid fa-paper-plane me-1"></i>

                                            Submit

                                        </button>

                                    </form>


                                    <form method="post"
                                          action="<?= BASE_URL ?>/index.php?url=finding/destroy/<?= $finding['finding_id'] ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Xóa Finding này?');">

                                        <?= Csrf::field() ?>

                                        <button class="btn btn-sm btn-outline-danger"
                                                title="Xóa">

                                            <i class="fa-solid fa-trash"></i>

                                        </button>

                                    </form>


                                <?php elseif (
                                    RoleMiddleware::can(
                                        ['PROJECT_MANAGER']
                                    )
                                    && $finding['status'] === 'SUBMITTED'
                                ): ?>


                                    <form method="post"
                                          action="<?= BASE_URL ?>/index.php?url=finding/confirm/<?= $finding['finding_id'] ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Xác nhận Finding này?');">

                                        <?= Csrf::field() ?>

                                        <button class="btn btn-sm btn-success">

                                            <i class="fa-solid fa-check me-1"></i>

                                            Confirm

                                        </button>

                                    </form>


                                    <form method="post"
                                          action="<?= BASE_URL ?>/index.php?url=finding/falsePositive/<?= $finding['finding_id'] ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Đánh dấu Finding này là False Positive?');">

                                        <?= Csrf::field() ?>

                                        <button class="btn btn-sm btn-outline-danger">

                                            <i class="fa-solid fa-xmark me-1"></i>

                                            False Positive

                                        </button>

                                    </form>


                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (empty($findings)): ?>

                        <tr>

                            <td colspan="7"
                                class="text-center text-muted py-3">

                                Chưa có Finding nào.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- ================= REPORTS ================= -->

    <div class="tab-pane fade" id="tab-reports">

        <?php
        $title = 'Reports';
        $note = 'Xuất báo cáo PDF sẽ hoàn thiện ở Phase 4';

        require __DIR__ . '/../partials/coming_soon.php';
        ?>

    </div>


    <!-- ================= AUDIT ================= -->

    <div class="tab-pane fade" id="tab-audit">

        <div class="card-panel">

            <table class="table table-sm">

                <thead>

                    <tr>

                        <th>Thời gian</th>
                        <th>User</th>
                        <th>Hành động</th>
                        <th>Mô tả</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($auditTrail as $log): ?>

                    <tr>

                        <td class="text-nowrap">

                            <?= formatDateTime(
                                $log['created_at']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $log['full_name']
                                ?? 'Hệ thống'
                            ) ?>

                        </td>


                        <td>

                            <span class="badge bg-dark">

                                <?= e($log['action']) ?>

                            </span>

                        </td>


                        <td>

                            <?= e($log['description']) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <?php if (empty($auditTrail)): ?>

                    <tr>

                        <td colspan="4"
                            class="text-center text-muted py-3">

                            Chưa có log nào.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>