<div class="breadcrumb-bar">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php?url=dashboard" class="text-decoration-none">Trang chủ</a></li>
            <?php foreach (($breadcrumbs ?? []) as $label => $link): ?>
                <?php if ($link): ?>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php?url=<?= e($link) ?>" class="text-decoration-none"><?= e($label) ?></a></li>
                <?php else: ?>
                    <li class="breadcrumb-item active"><?= e($label) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>
