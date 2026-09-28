<?php
/**
 * app/helpers/UploadHelper.php
 * -----------------------------------------------------------------------
 * Xử lý upload file (dùng cho module Evidence và Remediation).
 * Kiểm tra: đuôi file hợp lệ, kích thước tối đa, đổi tên file tránh trùng/
 * tránh path traversal, tính hash SHA-256 để kiểm tra toàn vẹn dữ liệu.
 *
 * LƯU Ý: đây là bước "mô phỏng" kiểm tra an toàn upload ở mức đồ án học
 * thuật (không thực hiện malware scan thật như yêu cầu XIV. MODULE EVIDENCE).
 * -----------------------------------------------------------------------
 */

class UploadHelper
{
    /**
     * @return array{success:bool, file_name?:string, file_path?:string, file_type?:string,
     *               file_size?:int, sha256?:string, error?:string}
     */
    public static function handle(array $file, string $prefix = 'evidence'): array
    {
        $config = require __DIR__ . '/../../config/database.php';

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload thất bại (mã lỗi: ' . ($file['error'] ?? 'unknown') . ')'];
        }

        if ($file['size'] > $config['upload_max_size']) {
            return ['success' => false, 'error' => 'File vượt quá dung lượng cho phép (' . ($config['upload_max_size'] / 1024 / 1024) . 'MB).'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $config['upload_allowed_ext'], true)) {
            return ['success' => false, 'error' => 'Định dạng file không được phép: .' . $ext];
        }

        // Tên file mới: tránh path traversal / trùng tên, KHÔNG dùng tên gốc do người dùng đặt
        $newName = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = rtrim($config['upload_path'], '/') . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Không thể lưu file vào thư mục uploads.'];
        }

        return [
            'success'   => true,
            'file_name' => $file['name'],       // tên gốc, chỉ để hiển thị
            'file_path' => 'evidence/' . $newName,
            'file_type' => strtoupper($ext),
            'file_size' => $file['size'],
            'sha256'    => hash_file('sha256', $destination),
        ];
    }
}
