<?php
/**
 * app/views/partials/sidebar.php
 * Menu hiển thị theo role_code của user đang đăng nhập ($_SESSION['user']).
 * Muốn thêm mục menu mới cho 1 role: thêm 1 thẻ <li> trong khối if tương ứng.
 */
$role = $_SESSION['user']['role_code'] ?? '';
$currentUrl = trim($_GET['url'] ?? 'dashboard', '/');
function navActive(string $prefix, string $currentUrl): string {
    return str_starts_with($currentUrl, $prefix) ? 'active' : '';
}
?>
<nav class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-shield-halved"></i>
        <span>PENTEST MGMT</span>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= navActive('dashboard', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=dashboard">
                <i class="fa-solid fa-gauge-high"></i>Dashboard
            </a>
        </li>

        <?php if ($role === 'ADMIN'): ?>
            <div class="nav-section-title">Quản trị</div>
            <li class="nav-item">
                <a class="nav-link <?= navActive('user', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=user/index">
                    <i class="fa-solid fa-users"></i>Người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= navActive('audit', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=audit/index">
                    <i class="fa-solid fa-clipboard-list"></i>Audit Log
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($role, ['PROJECT_MANAGER','APPROVER','PENTESTER','ASSET_OWNER','STAKEHOLDER'], true)): ?>
            <div class="nav-section-title">Nghiệp vụ Pentest</div>
            <li class="nav-item">
                <a class="nav-link <?= navActive('engagement', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=engagement/index">
                    <i class="fa-solid fa-crosshairs"></i>Engagement
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'APPROVER'): ?>
            <li class="nav-item">
                <a class="nav-link <?= navActive('approval', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=approval/index">
                    <i class="fa-solid fa-stamp"></i>Chờ phê duyệt
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($role, ['PROJECT_MANAGER','APPROVER','PENTESTER','ASSET_OWNER'], true)): ?>
            <li class="nav-item">
                <a class="nav-link <?= navActive('finding', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=finding/index">
                    <i class="fa-solid fa-bug"></i>Findings
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($role, ['PROJECT_MANAGER','STAKEHOLDER'], true)): ?>
            <li class="nav-item">
                <a class="nav-link <?= navActive('report', $currentUrl) ?>" href="<?= BASE_URL ?>/index.php?url=report/index">
                    <i class="fa-solid fa-file-lines"></i>Reports
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
