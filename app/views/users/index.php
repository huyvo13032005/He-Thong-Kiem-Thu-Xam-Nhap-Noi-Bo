<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa-solid fa-users me-2"></i>Quản lý người dùng</h5>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openCreateModal()">
        <i class="fa-solid fa-plus me-1"></i>Thêm người dùng
    </button>
</div>

<div class="card-panel mb-3">
    <form method="get" action="<?= BASE_URL ?>/index.php" class="row g-2">
        <input type="hidden" name="url" value="user/index">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo tên, username, email..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select form-select-sm">
                <option value="">-- Tất cả vai trò --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r['role_code']) ?>" <?= $roleFilter === $r['role_code'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                <option value="LOCKED" <?= $statusFilter === 'LOCKED' ? 'selected' : '' ?>>Locked</option>
                <option value="DISABLED" <?= $statusFilter === 'DISABLED' ? 'selected' : '' ?>>Disabled</option>
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
                    <th></th>
                    <th>Họ tên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Phòng ban</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><i class="fa-solid fa-circle-user fs-4 text-secondary"></i></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge bg-secondary"><?= e(roleLabel($u['role_code'])) ?></span></td>
                    <td><?= e($u['department']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($u['status']) ?>"><?= e($u['status']) ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary"
                                onclick='openEditModal(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" action="<?= BASE_URL ?>/index.php?url=user/toggleStatus/<?= $u['user_id'] ?>" class="d-inline">
                            <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-warning" title="Khóa/Mở khóa">
                                <i class="fa-solid fa-lock"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= BASE_URL ?>/index.php?url=user/destroy/<?= $u['user_id'] ?>" class="d-inline"
                              onsubmit="return confirm('Xóa người dùng này?');">
                            <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Không có dữ liệu phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?url=user/index&page=<?= $p ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal Thêm/Sửa User -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="userForm">
            <?= Csrf::field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Thêm người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="full_name" id="f_full_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="f_username" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="f_email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Mật khẩu <span id="pwHint" class="text-muted small"></span></label>
                        <input type="password" name="password" id="f_password" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label">Vai trò</label>
                            <select name="role_id" id="f_role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['role_id'] ?>"><?= e($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label">Phòng ban</label>
                            <input type="text" name="department" id="f_department" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">Lưu</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('userModalTitle').innerText = 'Thêm người dùng';
    document.getElementById('userForm').action = '<?= BASE_URL ?>/index.php?url=user/store';
    document.getElementById('userForm').reset();
    document.getElementById('f_password').required = true;
    document.getElementById('pwHint').innerText = '(bắt buộc)';
}
function openEditModal(user) {
    document.getElementById('userModalTitle').innerText = 'Sửa người dùng: ' + user.full_name;
    document.getElementById('userForm').action = '<?= BASE_URL ?>/index.php?url=user/update/' + user.user_id;
    document.getElementById('f_full_name').value = user.full_name;
    document.getElementById('f_username').value = user.username;
    document.getElementById('f_username').readOnly = true; // không cho đổi username khi sửa
    document.getElementById('f_email').value = user.email;
    document.getElementById('f_department').value = user.department || '';
    document.getElementById('f_password').required = false;
    document.getElementById('f_password').value = '';
    document.getElementById('pwHint').innerText = '(để trống nếu không đổi)';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>
