<?php
require __DIR__ . '/../app/core/Database.php';
try {
    $db = Database::getConnection();
    if (!$db->query('SELECT TOP 1 role_id FROM dbo.roles')->fetch()) exit(1);
    $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $html = file_get_contents('http://127.0.0.1/index.php?url=auth/login', false, $context);
    if (!is_string($html) || !str_contains($html, 'csrf_token')) exit(1);
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
