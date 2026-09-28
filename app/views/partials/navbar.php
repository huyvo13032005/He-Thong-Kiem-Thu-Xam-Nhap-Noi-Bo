<?php $u = $_SESSION['user'] ?? []; ?>
<div class="topbar">
    <div class="fw-semibold text-secondary">
        <i class="fa-solid fa-building-shield me-1"></i> Internal Penetration Testing Management System
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="role-pill"><?= e(roleLabel($u['role_code'] ?? '')) ?></span>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none text-dark" data-bs-toggle="dropdown">
                <i class="fa-solid fa-circle-user fs-4 me-2 text-secondary"></i>
                <span class="small"><?= e($u['full_name'] ?? '') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= e($u['email'] ?? '') ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php?url=auth/logout">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</div>
