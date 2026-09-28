/**
 * public/js/app.js
 * JS dùng chung toàn hệ thống (kích hoạt tooltip, auto-dismiss alert...).
 * Từng module (Findings, Evidence...) có file JS riêng nếu cần logic phức tạp hơn.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Tự ẩn alert sau 5 giây
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
            alert.close();
        }, 5000);
    });
});
