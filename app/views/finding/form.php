<div class="card-panel">
    <h5 class="mb-3">Sửa Finding #<?= $finding['finding_id'] ?></h5>

    <form method="post"
          action="<?= BASE_URL ?>/index.php?url=finding/update/<?= $finding['finding_id'] ?>"
          class="row g-3">

        <?= Csrf::field() ?>

        <div class="col-md-8">
            <label class="form-label">Tiêu đề</label>
            <input type="text"
                   name="title"
                   class="form-control"
                   value="<?= e($finding['title']) ?>"
                   required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Severity</label>
            <select name="severity" class="form-select" required>
                <?php foreach ($severities as $severity): ?>
                    <option value="<?= e($severity) ?>"
                        <?= $finding['severity'] === $severity ? 'selected' : '' ?>>
                        <?= e($severity) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label">Scope</label>
            <select name="scope_id" class="form-select">
                <option value="">-- Không chọn --</option>

                <?php foreach ($scopeItems as $scope): ?>
                    <?php if (!$scope['is_excluded']): ?>
                        <option value="<?= $scope['scope_id'] ?>"
                            <?= (int) ($finding['scope_id'] ?? 0) === (int) $scope['scope_id']
                                ? 'selected'
                                : '' ?>>
                            <?= e($scope['target']) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description"
                      class="form-control"
                      rows="4"
                      required><?= e($finding['description']) ?></textarea>
        </div>

        <div class="col-md-12">
            <label class="form-label">Khuyến nghị khắc phục</label>
            <textarea name="recommendation"
                      class="form-control"
                      rows="3"><?= e($finding['recommendation'] ?? '') ?></textarea>
        </div>

        <div class="col-md-4">
            <label class="form-label">CVE</label>
            <input type="text"
                   name="cve"
                   class="form-control"
                   value="<?= e($finding['cve'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">CWE</label>
            <input type="text"
                   name="cwe"
                   class="form-control"
                   value="<?= e($finding['cwe'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">OWASP Category</label>
            <input type="text"
                   name="owasp_category"
                   class="form-control"
                   value="<?= e($finding['owasp_category'] ?? '') ?>">
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/index.php?url=engagement/show/<?= $finding['engagement_id'] ?>"
               class="btn btn-outline-secondary">
                Hủy
            </a>

            <button class="btn btn-danger">
                Lưu thay đổi
            </button>
        </div>

    </form>
</div>