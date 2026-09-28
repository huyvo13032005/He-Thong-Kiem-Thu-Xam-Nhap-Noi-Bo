<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - Hệ thống Quản lý Kiểm thử Xâm nhập Nội bộ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?= BASE_URL ?>/css/app.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <div class="login-brand-title">PENTEST MGMT</div>
                    <div class="login-brand-sub">Internal Penetration Testing Management System</div>
                </div>
            </div>

            <?php if (!empty($locked)): ?>
                <div class="alert alert-warning py-2">Tài khoản của bạn đã bị khóa hoặc phiên hết hạn.</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/index.php?url=auth/doLogin">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label text-light-muted">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-light-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small text-light-muted" for="remember">Remember me</label>
                    </div>
                    <a href="#" class="small text-info" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-danger w-100">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Đăng nhập
                </button>
            </form>

            <div class="login-footnote">
                Tài khoản demo: <code>admin</code> / <code>Password123!</code>
            </div>
        </div>
    </div>

    <!-- Forgot password: chỉ UI theo yêu cầu đề bài, chưa xử lý backend thật -->
    <div class="modal fade" id="forgotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quên mật khẩu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Vui lòng liên hệ Admin hệ thống để được cấp lại mật khẩu.</p>
                    <input type="email" class="form-control" placeholder="Email đã đăng ký">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button class="btn btn-danger" disabled>Gửi yêu cầu (demo UI)</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
