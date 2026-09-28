<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>403 - Không có quyền truy cập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="text-center">
        <h1 class="display-1 text-danger fw-bold">403</h1>
        <p class="fs-4">Bạn không có quyền truy cập chức năng này.</p>
        <p class="text-muted">Thao tác này đã được ghi lại vào Audit Log.</p>
        <a href="<?= BASE_URL ?>/index.php?url=dashboard" class="btn btn-primary">Quay về Dashboard</a>
    </div>
</body>
</html>
