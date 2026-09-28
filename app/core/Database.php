<?php
/**
 * app/core/Database.php
 * -----------------------------------------------------------------------
 * Lớp bọc PDO (Singleton) - toàn bộ Model sẽ gọi Database::getConnection()
 * để lấy về 1 đối tượng PDO dùng chung trong suốt request.
 *
 * Vì sao dùng Singleton: tránh mở nhiều kết nối DB trong cùng 1 request
 * khi nhiều Model cùng cần truy vấn.
 * -----------------------------------------------------------------------
 */

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';

            // DSN cho driver sqlsrv (Microsoft Drivers for PHP for SQL Server)
            $dsn = sprintf(
                'sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['trust_server_certificate'] ? 'true' : 'false'
            );

            try {
                self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // Không lộ thông tin kết nối thật ra ngoài trình duyệt (an toàn thông tin)
                error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
                if (PHP_SAPI === 'cli') {
                    throw new RuntimeException('Database connection failed.', 0, $e);
                }
                http_response_code(503);
                die('Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra cấu hình trong config/database.php.');
            }
        }

        return self::$connection;
    }

    /** Ngăn khởi tạo/nhân bản đối tượng (đúng chuẩn Singleton) */
    private function __construct() {}
    private function __clone() {}
}
