<div class="card-panel" style="max-width: 720px;">
    <h5 class="mb-3"><?= e($pageTitle) ?></h5>
    <form method="post" action="<?= BASE_URL ?>/index.php?url=<?= e($formAction) ?>">
        <?= Csrf::field() ?>

        <div class="mb-3">
            <label class="form-label">Tên Engagement <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($engagement['name'] ?? '') ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Phòng ban <span class="text-danger">*</span></label>
                <input type="text" name="department" class="form-control" required
                       placeholder="VD: HR, Finance, Sales, Infrastructure..."
                       value="<?= e($engagement['department'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Loại kiểm thử <span class="text-danger">*</span></label>
                <select name="test_type" class="form-select" required>
                    <?php foreach ($testTypes as $t): ?>
                        <option value="<?= e($t) ?>" <?= ($engagement['test_type'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" required
                       value="<?= e($engagement['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" required
                       value="<?= e($engagement['end_date'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3"><?= e($engagement['description'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-floppy-disk me-1"></i>Lưu</button>
            <a href="<?= BASE_URL ?>/index.php?url=engagement/index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
