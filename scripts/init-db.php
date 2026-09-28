<?php
/** CLI-only initializer. Never reruns destructive schema on existing data. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function envRequired(string $key): string {
    $value = getenv($key);
    if ($value === false || $value === '' || str_starts_with($value, 'CHANGE_ME')) {
        throw new RuntimeException("Missing or placeholder environment variable: {$key}");
    }
    return $value;
}

function runBatches(PDO $db, string $sql): void {
    foreach (preg_split('/^\s*GO\s*\r?$/mi', $sql) as $batch) {
        if (trim($batch) !== '') $db->exec($batch);
    }
}

try {
    $host = envRequired('DB_HOST');
    $port = envRequired('DB_PORT');
    $saPassword = envRequired('MSSQL_SA_PASSWORD');
    $appPassword = envRequired('DB_PASSWORD');
    $demoPassword = envRequired('DEMO_PASSWORD');
    if (strlen($appPassword) < 16) throw new RuntimeException('DB_PASSWORD must be at least 16 characters.');
    $db = new PDO("sqlsrv:Server={$host},{$port};Database=master;TrustServerCertificate=true", 'sa', $saPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
    ]);
    $exists = $db->query("SELECT DB_ID('PentestManagementDB')")->fetchColumn();
    if (!$exists) $db->exec('CREATE DATABASE [PentestManagementDB]');
    $db->exec('USE [PentestManagementDB]');
    $hasMarker = $db->query("SELECT OBJECT_ID('dbo.app_bootstrap', 'U')")->fetchColumn();
    if ($exists && !$hasMarker) {
        throw new RuntimeException('Database exists without bootstrap marker. Refusing to overwrite it; inspect or back it up first.');
    }
    if (!$hasMarker) {
        $root = dirname(__DIR__);
        $schema = file_get_contents($root . '/database/schema.sql');
        // CREATE DATABASE is handled above, outside the transaction.
        $schema = preg_replace('/\A.*?USE PentestManagementDB;\s*GO\s*/s', '', $schema, 1);
        $seed = file_get_contents($root . '/database/seed.sql');
        $seed = str_replace('__GENERATE_WITH_INIT_SCRIPT__', password_hash($demoPassword, PASSWORD_BCRYPT), $seed);
        $db->beginTransaction();
        runBatches($db, $schema);
        runBatches($db, $seed);
        runBatches($db, file_get_contents($root . '/database/views.sql'));
        $db->exec('CREATE TABLE dbo.app_bootstrap (version INT NOT NULL PRIMARY KEY, initialized_at DATETIME2 NOT NULL DEFAULT SYSDATETIME())');
        $db->exec('INSERT INTO dbo.app_bootstrap (version) VALUES (1)');
        $db->commit();
        echo "Fresh database initialized with demo users.\n";
    } else {
        echo "Existing database kept; no reseeding or password reset.\n";
    }

    // Web uses a dedicated login, never the SQL Server administrator.
    $db->exec('USE [master]');
    $passwordLiteral = "'" . str_replace("'", "''", $appPassword) . "'";
    $loginExists = $db->query("SELECT SUSER_ID('pentest_app')")->fetchColumn();
    if ($loginExists) {
        $db->exec("ALTER LOGIN [pentest_app] WITH PASSWORD = {$passwordLiteral}");
    } else {
        $db->exec("CREATE LOGIN [pentest_app] WITH PASSWORD = {$passwordLiteral}, CHECK_POLICY = ON");
    }
    $db->exec('USE [PentestManagementDB]');
    $db->exec("IF USER_ID('pentest_app') IS NULL CREATE USER [pentest_app] FOR LOGIN [pentest_app]");
    $db->exec('GRANT SELECT, INSERT, UPDATE, DELETE ON SCHEMA::dbo TO [pentest_app]');
    $db->exec('DENY UPDATE, DELETE ON OBJECT::dbo.audit_logs TO [pentest_app]');
    $db->exec('DENY INSERT, UPDATE, DELETE ON OBJECT::dbo.app_bootstrap TO [pentest_app]');
    echo "Application login ready.\n";
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    // Avoid emitting credential-bearing SQL from PDO error context.
    if ($e instanceof PDOException) {
        fwrite(STDERR, "Database initialization failed: " . $e->getMessage() . "\n");
    } else {
        fwrite(STDERR, $e->getMessage() . "\n");
    }
    exit(1);
}
