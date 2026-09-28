<?php
/** Run inside web container. Read-only application checks except authentication audit logs. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function request(string $route, array &$cookies, ?array $form = null): array {
    $headers = ['Connection: close'];
    if ($cookies) {
        $pairs = [];
        foreach ($cookies as $name => $value) $pairs[] = $name . '=' . $value;
        $headers[] = 'Cookie: ' . implode('; ', $pairs);
    }
    if ($form !== null) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $context = stream_context_create(['http' => [
        'method' => $form === null ? 'GET' : 'POST',
        'header' => implode("\r\n", $headers),
        'content' => $form === null ? '' : http_build_query($form),
        'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 15,
    ]]);
    $body = file_get_contents('http://127.0.0.1/' . $route, false, $context);
    $responseHeaders = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $status);
    foreach ($responseHeaders as $header) {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $header, $match)) $cookies[$match[1]] = $match[2];
    }
    return [(int) ($status[1] ?? 0), $body === false ? '' : $body];
}

function expect(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

try {
    $password = getenv('DEMO_PASSWORD') ?: 'Password123!';
    foreach (['admin', 'pm.tran', 'approver.le', 'pentester.pham', 'owner.hr', 'ceo'] as $username) {
        $cookies = [];
        [$status, $html] = request('index.php?url=auth/login', $cookies);
        expect($status === 200, "{$username}: login page");
        preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $match);
        expect(isset($match[1]), "{$username}: CSRF token available");
        [$status] = request('index.php?url=auth/doLogin', $cookies, [
            'username' => $username, 'password' => $password, 'csrf_token' => $match[1],
        ]);
        expect($status === 302, "{$username}: login redirects");
        [$status, $html] = request('index.php?url=dashboard', $cookies);
        expect($status === 200 && str_contains($html, 'app-shell'), "{$username}: authenticated dashboard");
        if ($username === 'admin') {
            [$status] = request('index.php?url=user/index&search=admin', $cookies);
            expect($status === 200, 'User search with repeated search values');
            [$status] = request('index.php?url=audit/index&search=admin', $cookies);
            expect($status === 200, 'Audit search with repeated search values');
            [$status] = request('index.php?url=user/toggleStatus/999999', $cookies);
            expect($status === 405, 'GET cannot change account status');
            [$status] = request('index.php?url=user/toggleStatus/999999', $cookies, ['csrf_token' => 'invalid']);
            expect($status === 419, 'POST without valid CSRF is rejected');
        }
        [$status] = request('css/app.css', $cookies);
        expect($status === 200, "{$username}: local stylesheet");
    }
    $cookies = [];
    [$status] = request('.env', $cookies);
    expect($status !== 200, 'Environment file is outside public root');
    echo "Smoke checks completed. Engagement approval/assignment still needs the manual demo in README.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL: " . $e->getMessage() . "\n");
    exit(1);
}
