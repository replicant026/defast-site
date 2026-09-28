<?php
declare(strict_types=1);

require_once __DIR__ . '/download-config.php';

function defast_download_file_name(string $urlPath, string $version): string
{
    $base = basename($urlPath);
    if ($base !== '' && $base !== '.' && $base !== '/' && $base !== '\\') {
        return $base;
    }

    $safeVersion = preg_replace('/[^0-9A-Za-z._-]/', '', $version) ?: 'latest';
    return 'Instalador_DeFast_v' . $safeVersion . '.exe';
}

function defast_send_download_headers(string $filename, string $contentType, ?int $contentLength): void
{
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: ' . ($contentType !== '' ? $contentType : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    if (is_int($contentLength) && $contentLength > 0) {
        header('Content-Length: ' . (string) $contentLength);
    }
}

function defast_fail_download(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

$sourceUrl = trim((string) ($defast_download_source_url ?? ''));
if ($sourceUrl === '') {
    defast_fail_download(404, 'Arquivo de download nao configurado.');
}

$sourcePath = parse_url($sourceUrl, PHP_URL_PATH) ?: '';
$fileName = defast_download_file_name((string) $sourcePath, (string) ($defast_download_version ?? 'latest'));

if (!preg_match('#^https?://#i', $sourceUrl)) {
    $relative = ltrim((string) $sourcePath, '/');
    $localFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (!is_file($localFile) || !is_readable($localFile)) {
        defast_fail_download(404, 'Arquivo nao encontrado.');
    }

    $contentLength = filesize($localFile);
    defast_send_download_headers($fileName, 'application/octet-stream', is_int($contentLength) ? $contentLength : null);
    readfile($localFile);
    exit;
}

$headers = defast_get_remote_headers($sourceUrl);
$remoteType = is_array($headers) ? defast_header_value($headers, 'Content-Type') : '';
$remoteLengthRaw = is_array($headers) ? defast_header_value($headers, 'Content-Length') : '';
$remoteLength = ctype_digit($remoteLengthRaw) ? (int) $remoteLengthRaw : null;

defast_send_download_headers($fileName, $remoteType, $remoteLength);

$context = stream_context_create(array(
    'http' => array(
        'method' => 'GET',
        'timeout' => 12,
        'ignore_errors' => true,
    ),
    'ssl' => array(
        'verify_peer' => true,
        'verify_peer_name' => true,
    ),
));

$stream = @fopen($sourceUrl, 'rb', false, $context);
if (!is_resource($stream)) {
    defast_fail_download(502, 'Nao foi possivel iniciar o download no servidor de atualizacao.');
}

while (!feof($stream)) {
    echo fread($stream, 8192);
    flush();
}

fclose($stream);
exit;

