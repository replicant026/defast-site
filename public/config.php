<?php

function defast_get_csp_nonce()
{
    static $nonce = null;

    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}

function defast_script_nonce_attr()
{
    return ' nonce="' . htmlspecialchars(defast_get_csp_nonce(), ENT_QUOTES, 'UTF-8') . '"';
}

function defast_is_https_request()
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    return false;
}

function defast_is_local_host()
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return false;
    }

    $host = strtolower((string) $_SERVER['HTTP_HOST']);
    $host = preg_replace('/:\\d+$/', '', $host);
    $host = trim((string) $host, '[]');

    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return true;
    }

    return str_ends_with($host, '.test') || str_ends_with($host, '.local');
}

function defast_send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (defast_is_https_request() && !defast_is_local_host()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $nonce = defast_get_csp_nonce();
    $directives = array(
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "script-src 'self' 'nonce-" . $nonce . "' https://unpkg.com",
        "script-src-attr 'none'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data:",
        "connect-src 'self' https://*.supabase.co https://fonts.googleapis.com https://fonts.gstatic.com",
    );

    if (defast_is_https_request()) {
        $directives[] = 'upgrade-insecure-requests';
    }

    header('Content-Security-Policy: ' . implode('; ', $directives));
}

function defast_set_private_no_store_headers()
{
    if (headers_sent()) {
        return;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

defast_send_security_headers();

$defast_supabase_url = 'https://ioklwqovmyodxzfoajuj.supabase.co';
$defast_supabase_publishable_key = 'sb_publishable_fYmp-fODkKgg8M2cMXlKIA_oJbSvgQx';

require_once __DIR__ . '/download-config.php';


function defast_render_client_config()
{
    global $defast_supabase_url, $defast_supabase_publishable_key;

    $config = array(
        'SUPABASE_URL' => $defast_supabase_url,
        'SUPABASE_KEY' => $defast_supabase_publishable_key,
    );

    echo '<script id="defast-client-config" type="application/json"' . defast_script_nonce_attr() . '>';
    echo json_encode(
        $config,
        JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
    );
    echo '</script>';
}
