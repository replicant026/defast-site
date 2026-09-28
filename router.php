<?php
// router.php - Roteador para o servidor embutido do PHP local
// Resolve o problema de CSS/JS não carregarem corretamente no Windows (MIME Types)

if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|svg|ico)$/', $_SERVER["REQUEST_URI"])) {
    // Definir cabeçalho correto com base no arquivo procurado
    $path = __DIR__ . '/public' . $_SERVER["REQUEST_URI"];
    
    // Fallback caso o comando seja rodado direto de dentro da pasta public
    if (!file_exists($path)) {
       $path = __DIR__ . $_SERVER["REQUEST_URI"];
    }

    if (file_exists($path)) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            case 'ico':
                header('Content-Type: image/x-icon');
                break;
            case 'woff':
                header('Content-Type: font/woff');
                break;
            case 'woff2':
                header('Content-Type: font/woff2');
                break;
            // O PHP costuma acertar imagens comuns (png, jpg, gif) sozinho
        }
        readfile($path);
        return true;
    }
}

// Se não for um arquivo estático conhecido, deixa o index.php (ou script real requisitado) lidar com a requisição
return false;
