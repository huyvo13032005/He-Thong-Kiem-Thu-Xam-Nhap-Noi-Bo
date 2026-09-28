<?php
/**
 * config/database.php
 * -----------------------------------------------------------------------
 * Cấu hình kết nối Database (Microsoft SQL Server qua PDO + sqlsrv driver).
 *
 * YÊU CẦU MÔI TRƯỜNG:
 *   - PHP phải bật extension: pdo_sqlsrv (tải từ Microsoft Drivers for PHP for SQL Server)
 *   - SQL Server đang chạy (local, Docker, hoặc remote) và đã import database/schema.sql
 *
 * Cách đổi thông tin kết nối: sửa các hằng số bên dưới, KHÔNG sửa trực tiếp
 * trong app/core/Database.php.
 * -----------------------------------------------------------------------
 */

// Đọc từ biến môi trường nếu có (khuyến nghị khi deploy), nếu không dùng giá trị mặc định cho local dev.
return [
    'driver'   => 'sqlsrv',
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'port'     => getenv('DB_PORT')     ?: '1433',
    'database' => getenv('DB_DATABASE') ?: 'PentestManagementDB',
    'username' => getenv('DB_USERNAME') ?: 'pentest_app',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'UTF-8',
    // Bật TrustServerCertificate=1 khi test local với self-signed cert.
    'trust_server_certificate' => true,

    // ------- Thông tin ứng dụng dùng chung -------
    'app_name' => 'Hệ thống Quản lý Kiểm thử Xâm nhập Nội bộ',
    'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8080',
    'upload_path' => __DIR__ . '/../storage/evidence/',
    'upload_max_size' => 20 * 1024 * 1024, // 20MB
    'upload_allowed_ext' => ['png', 'jpg', 'jpeg', 'pdf', 'txt', 'pcap', 'zip'],
    'session_name' => 'PENTESTMGMT_SESSION',
    'session_lifetime' => 60 * 60 * 8, // 8 giờ
];
