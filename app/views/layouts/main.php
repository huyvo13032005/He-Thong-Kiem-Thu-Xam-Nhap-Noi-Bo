<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Pentest Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?= BASE_URL ?>/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main-area">
        <?php require __DIR__ . '/../partials/navbar.php'; ?>
        <?php require __DIR__ . '/../partials/breadcrumb.php'; ?>

        <div class="page-content">
            <?php foreach (getFlashMessages() as $msg): ?>
                <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show py-2">
                    <?= e($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>

        <?php require __DIR__ . '/../partials/footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
