<?php
declare(strict_types=1);

/**
 * Shared session helpers.
 *
 * Self-contained — no dependencies on other project files.
 * Included by both public/api/index.php and public/area-cliente.php.
 */

function initSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = isSecureRequest();
    session_name('defast_session');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
    }

    ini_set('session.use_strict_mode', '1');
    session_start();

    ensureCsrfToken();
}

function ensureCsrfToken(): string
{
    $token = $_SESSION['csrf_token'] ?? null;
    if (!is_string($token) || strlen($token) < 32) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    }

    return $token;
}

function rotateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function isSecureRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    if (!shouldTrustProxyHeaders()) {
        return false;
    }

    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwardedProto !== '' && str_contains($forwardedProto, 'https')) {
        return true;
    }

    $cfVisitor = trim((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''));
    if ($cfVisitor !== '') {
        $decoded = json_decode($cfVisitor, true);
        if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
            return true;
        }
    }

    return false;
}

function shouldTrustProxyHeaders(): bool
{
    $raw = getenv('TRUST_PROXY_HEADERS');
    if (!is_string($raw) || $raw === '') {
        $raw = is_string($_ENV['TRUST_PROXY_HEADERS'] ?? null) ? (string) $_ENV['TRUST_PROXY_HEADERS'] : '0';
    }

    $value = strtolower(trim($raw));
    if (!in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return false;
    }

    $trustedProxyIpsRaw = getenv('TRUSTED_PROXY_IPS');
    if (!is_string($trustedProxyIpsRaw) || trim($trustedProxyIpsRaw) === '') {
        $trustedProxyIpsRaw = is_string($_ENV['TRUSTED_PROXY_IPS'] ?? null) ? (string) $_ENV['TRUSTED_PROXY_IPS'] : '';
    }

    $trustedProxyIpsRaw = trim($trustedProxyIpsRaw);
    if ($trustedProxyIpsRaw === '') {
        return false;
    }

    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteAddr === '') {
        return false;
    }

    $trustedProxyIps = array_filter(
        array_map('trim', explode(',', $trustedProxyIpsRaw)),
        static fn (string $ip): bool => $ip !== ''
    );

    foreach ($trustedProxyIps as $trustedProxyIp) {
        if ($trustedProxyIp === $remoteAddr) {
            return true;
        }
    }

    return false;
}
