<?php

if (!function_exists('defast_fetch_remote_json')) {
    function defast_fetch_remote_json($url)
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));

        $body = @file_get_contents($url, false, $context);
        if ($body === false || trim($body) === '') {
            return null;
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('defast_header_value')) {
    function defast_header_value(array $headers, $name)
    {
        foreach ($headers as $key => $value) {
            if (!is_string($key) || strcasecmp($key, (string) $name) !== 0) {
                continue;
            }

            if (is_array($value)) {
                $value = end($value);
            }

            return trim((string) $value);
        }

        return '';
    }
}

if (!function_exists('defast_get_remote_headers')) {
    function defast_get_remote_headers($url)
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'HEAD',
                'timeout' => 4,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));

        $headers = @get_headers($url, true, $context);
        return is_array($headers) ? $headers : null;
    }
}

if (!function_exists('defast_format_bytes')) {
    function defast_format_bytes($bytes)
    {
        if (!is_numeric($bytes) || $bytes < 0) {
            return null;
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        $precision = $value >= 10 || $unit === 0 ? 0 : 1;
        return number_format($value, $precision, ',', '.') . ' ' . $units[$unit];
    }
}

if (!function_exists('defast_get_download_size_text')) {
    function defast_get_download_size_text($downloadUrl, $platformText)
    {
        $platform = trim((string) $platformText);
        $default = 'Tamanho indisponivel' . ($platform !== '' ? ' - ' . $platform : '');

        $url = trim((string) $downloadUrl);
        if ($url === '') {
            return $default;
        }

        if (preg_match('#^https?://#i', $url)) {
            $headers = defast_get_remote_headers($url);
            if (!is_array($headers)) {
                return $default;
            }

            $contentLength = defast_header_value($headers, 'Content-Length');
            if ($contentLength === '' || !ctype_digit($contentLength)) {
                return $default;
            }

            $size = defast_format_bytes((int) $contentLength);
            if ($size === null) {
                return $default;
            }

            return 'Tamanho: ' . $size . ($platform !== '' ? ' - ' . $platform : '');
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return $default;
        }

        $relative = ltrim($path, '/');
        $localFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($localFile)) {
            return $default;
        }

        $size = defast_format_bytes(filesize($localFile));
        if ($size === null) {
            return $default;
        }

        return 'Tamanho: ' . $size . ($platform !== '' ? ' - ' . $platform : '');
    }
}

if (!function_exists('defast_build_download_release')) {
    function defast_build_download_release(array $defaults)
    {
        $manifest = defast_fetch_remote_json($defaults['manifest_url']);
        if (!is_array($manifest)) {
            return $defaults;
        }

        $resolved = $defaults;

        $version = trim((string) ($manifest['Versao'] ?? $manifest['version'] ?? ''));
        if ($version !== '') {
            $resolved['version'] = $version;
        }

        $downloadUrl = trim((string) ($manifest['LinkDownload'] ?? $manifest['downloadUrl'] ?? ''));
        if ($downloadUrl !== '') {
            $resolved['url'] = $downloadUrl;
        }

        $notes = trim((string) ($manifest['NotasVersao'] ?? $manifest['notes'] ?? ''));
        if ($notes !== '') {
            $resolved['notes'] = $notes;
        }

        $platform = trim((string) ($manifest['Plataforma'] ?? $manifest['platform'] ?? ''));
        if ($platform !== '') {
            $resolved['platform'] = $platform;
        }

        return $resolved;
    }
}

$defast_download_defaults = array(
    'url' => '/Instalador_DeFast_v1.0.1.3.exe',
    'version' => '1.0.1.3',
    'notes' => 'Compativel com Revit 2022, 2023, 2024, 2025 e 2026',
    'platform' => 'Windows 10/11',
    'manifest_url' => 'https://ioklwqovmyodxzfoajuj.supabase.co/storage/v1/object/public/updates/update.json',
);

$defast_download_release = defast_build_download_release($defast_download_defaults);

$defast_download_source_url = $defast_download_release['url'];
$defast_download_public_url = '/download';

$defast_download_url = $defast_download_public_url;
$defast_download_version = $defast_download_release['version'];
$defast_download_notes = $defast_download_release['notes'];
$defast_download_platform = $defast_download_release['platform'];
$defast_download_size = defast_get_download_size_text($defast_download_source_url, $defast_download_platform);
